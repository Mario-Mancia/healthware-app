<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarTicketRequest;
use App\Http\Requests\SeguimientoTicketRequest;
use App\Models\Bitacora;
use App\Models\Ticket;
use App\Models\TicketAdjunto;
use App\Models\Usuario;
use App\Services\AlmacenArchivos;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tickets: reportes de soporte.
 *
 *  - Cualquier usuario abre reportes, consulta los suyos, agrega notas y
 *    cancela los que siguen activos.
 *  - Los administradores ven la cola de reportes abiertos, los toman y los
 *    cierran (completar o descartar) o los liberan. Un reporte tomado deja de
 *    verlo cualquier otro administrador (solo la bitácora lo refleja).
 *  - Cada acción queda en la bitácora y se notifica al emisor y al
 *    administrador responsable (ver NotificarTicket).
 */
class TicketController extends Controller
{
    private const MODULO = 'TICKETS';

    // Consulta

    public function index(Request $request): View
    {
        $usuario = $this->usuarioActual();
        $esAdmin = $this->esAdministrador();

        // Solo el administrador tiene bandeja; los demás ven sus reportes.
        $vista = $esAdmin && $request->query('vista') !== 'mis' ? 'bandeja' : 'mis';

        return view('tickets.index', [
            'tickets' => Ticket::listarPara($usuario, $vista),
            'esAdmin' => $esAdmin,
            'vista' => $vista,
            'idUsuario' => $usuario->id,
        ]);
    }

    public function crear(): View
    {
        return view('tickets.crear');
    }

    public function ver(int $id): View|RedirectResponse
    {
        $usuario = $this->usuarioActual();
        $ticket = Ticket::buscarVisible($id, $usuario);

        if (!$ticket) {
            return $this->noDisponible();
        }

        // Un administrador que abre un reporte ajeno deja constancia.
        if (!$ticket->esEmisor($usuario)) {
            Bitacora::registrar(
                Bitacora::CONSULTADO,
                self::MODULO,
                $ticket->id,
                $this->recortar('Consultó el reporte ' . $ticket->codigo() . ' «' . $ticket->titulo . '».')
            );
        }

        return view('tickets.ver', [
            'ticket' => $ticket,
            'esEmisor' => $ticket->esEmisor($usuario),
            'esResponsable' => $ticket->esResponsable($usuario),
            'esAdmin' => $this->esAdministrador(),
        ]);
    }

    /**
     * Descarga de un adjunto: solo si el usuario puede ver el reporte. Los
     * archivos están en almacenamiento privado, no hay enlace público.
     */
    public function descargar(int $id): StreamedResponse|RedirectResponse
    {
        $adjunto = TicketAdjunto::query()->find($id);
        $ticket = $adjunto ? Ticket::buscarVisible((int) $adjunto->id_ticket, $this->usuarioActual()) : null;

        if (!$adjunto || !$ticket) {
            return $this->noDisponible();
        }

        $respuesta = AlmacenArchivos::descargar($adjunto->ruta, $adjunto->nombre_original);

        if ($respuesta === null) {
            return redirect()
                ->route('tickets.ver', ['id' => $ticket->id])
                ->with('error', AlmacenArchivos::NO_DISPONIBLE);
        }

        Bitacora::registrar(
            Bitacora::DESCARGADO,
            self::MODULO,
            $ticket->id,
            $this->recortar('Descargó «' . $adjunto->nombre_original . '» del reporte ' . $ticket->codigo() . '.')
        );

        return $respuesta;
    }

    // Emisor

    public function insertar(GuardarTicketRequest $request): RedirectResponse
    {
        try {
            $ticket = Ticket::abrir($request->datosTicket(), $request->archivosAdjuntos(), $this->usuarioActual());
        } catch (\DomainException $error) {
            return back()->withInput()->with('error', $error->getMessage());
        } catch (\Throwable $error) {
            report($error);

            return back()
                ->withInput()
                ->with('error', 'No fue posible registrar el reporte. Inténtelo nuevamente.');
        }

        $adjuntos = count($request->archivosAdjuntos());

        Bitacora::registrar(
            Bitacora::CREADO,
            self::MODULO,
            $ticket->id,
            $this->recortar('Abrió el reporte ' . $ticket->codigo() . ' «' . $ticket->titulo . '» (' . Str::lower(Str::before($ticket->etiquetaTipo(), ' ('))
                . ', prioridad ' . Str::lower($ticket->etiquetaPrioridad()) . ')'
                . ($adjuntos > 0 ? ', ' . $adjuntos . ($adjuntos === 1 ? ' adjunto.' : ' adjuntos.') : '.'))
        );

        return redirect()
            ->route('tickets.ver', ['id' => $ticket->id])
            ->with('success', 'Reporte registrado. Le avisaremos por notificación de cada cambio.');
    }

    public function cancelar(SeguimientoTicketRequest $request, int $id): RedirectResponse
    {
        $motivo = $request->validated('texto');

        return $this->ejecutar(
            $id,
            fn () => Ticket::cancelar($id, $this->usuarioActual(), $motivo),
            Bitacora::CANCELADO,
            fn (Ticket $t) => 'Canceló el reporte ' . $t->codigo() . ' «' . $t->titulo . '»' . ($motivo ? '. Motivo: ' . $motivo : '.'),
            'Reporte cancelado.'
        );
    }

    public function comentar(SeguimientoTicketRequest $request, int $id): RedirectResponse
    {
        $texto = $request->validated('texto');

        return $this->ejecutar(
            $id,
            fn () => Ticket::comentar($id, $this->usuarioActual(), $texto),
            Bitacora::COMENTADO,
            fn (Ticket $t) => 'Agregó una nota al reporte ' . $t->codigo() . '.',
            'Nota agregada. Se avisó a la otra parte.'
        );
    }

    // Administración

    public function tomar(int $id): RedirectResponse
    {
        $this->exigirAdministrador();

        return $this->ejecutar(
            $id,
            fn () => Ticket::tomar($id, $this->usuarioActual()),
            Bitacora::TOMADO,
            fn (Ticket $t) => 'Tomó el reporte ' . $t->codigo() . ' «' . $t->titulo . '» emitido por el usuario #' . $t->id_usuario . '.',
            'Reporte tomado. Se notificó al emisor.'
        );
    }

    public function liberar(int $id): RedirectResponse
    {
        $this->exigirAdministrador();

        return $this->ejecutar(
            $id,
            fn () => Ticket::liberar($id, $this->usuarioActual()),
            Bitacora::LIBERADO,
            fn (Ticket $t) => 'Liberó el reporte ' . $t->codigo() . ' «' . $t->titulo . '»; volvió a la cola.',
            'Reporte liberado: volvió a la cola.'
        );
    }

    public function completar(SeguimientoTicketRequest $request, int $id): RedirectResponse
    {
        $this->exigirAdministrador();
        $resolucion = $request->validated('texto');

        return $this->ejecutar(
            $id,
            fn () => Ticket::completar($id, $this->usuarioActual(), $resolucion),
            Bitacora::COMPLETADO,
            fn (Ticket $t) => 'Completó el reporte ' . $t->codigo() . ' «' . $t->titulo . '». Resolución: ' . $resolucion,
            'Reporte completado. Se notificó al emisor.'
        );
    }

    public function descartar(SeguimientoTicketRequest $request, int $id): RedirectResponse
    {
        $this->exigirAdministrador();
        $motivo = $request->validated('texto');

        return $this->ejecutar(
            $id,
            fn () => Ticket::descartar($id, $this->usuarioActual(), $motivo),
            Bitacora::DESCARTADO,
            fn (Ticket $t) => 'Descartó el reporte ' . $t->codigo() . ' «' . $t->titulo . '». Motivo: ' . $motivo,
            'Reporte descartado. Se notificó al emisor.'
        );
    }

    // Apoyo

    /**
     * Ejecuta una acción del modelo, la registra en la bitácora y vuelve al
     * reporte. Si el usuario ya no puede ver el reporte (otro administrador lo
     * tomó, por ejemplo), responde como si no existiera.
     */
    private function ejecutar(int $id, \Closure $accion, string $eventoBitacora, \Closure $describir, string $exito): RedirectResponse
    {
        try {
            $ticket = $accion();
        } catch (ModelNotFoundException) {
            return $this->noDisponible();
        } catch (\DomainException $error) {
            return redirect()->route('tickets.ver', ['id' => $id])->with('error', $error->getMessage());
        } catch (\Throwable $error) {
            report($error);

            return redirect()
                ->route('tickets.ver', ['id' => $id])
                ->with('error', 'No fue posible completar la acción. Inténtelo nuevamente.');
        }

        Bitacora::registrar($eventoBitacora, self::MODULO, $ticket->id, $this->recortar($describir($ticket)));

        return redirect()->route('tickets.ver', ['id' => $id])->with('success', $exito);
    }

    /**
     * Segunda barrera, además del middleware de rol de las rutas.
     */
    private function exigirAdministrador(): void
    {
        abort_unless($this->esAdministrador(), 403, 'Solo un administrador puede realizar esta acción.');
    }

    private function noDisponible(): RedirectResponse
    {
        return redirect()
            ->route('tickets')
            ->with('warning', 'El reporte no está disponible.');
    }

    /**
     * La columna descripcion de la bitácora admite 255 caracteres.
     */
    private function recortar(string $texto): string
    {
        return Str::limit($texto, 250, '…');
    }
}
