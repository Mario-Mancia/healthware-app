<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarAnuncioGlobalRequest;
use App\Http\Requests\InsertarAnuncioGlobalRequest;
use App\Models\AnuncioAdjunto;
use App\Models\AnuncioGlobal;
use App\Models\Bitacora;
use App\Models\Usuario;
use App\Services\RecordatorioAnunciosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Anuncios: comunicación interna desde la administración.
 *
 *  - Administrador: publica, edita y elimina (rutas protegidas además por el
 *    middleware de rol y por la autorización de cada solicitud).
 *  - Resto de usuarios: consultan los que les corresponden, confirman los
 *    dirigidos a ellos y descargan sus adjuntos. Todo anuncio fuera de su
 *    alcance responde como si no existiera.
 *  - Cada acción relevante queda en la bitácora.
 */
class AnuncioGlobalController extends Controller
{
    private const MODULO = 'ANUNCIOS_GLOBALES';

    // Consulta (todos los usuarios)

    public function index(RecordatorioAnunciosService $recordatorios): View
    {
        $this->procesarRecordatorios($recordatorios);

        $usuario = $this->usuario();

        return view('anuncios_globales.index', [
            'anuncios' => AnuncioGlobal::listarPara($usuario),
            'esAdmin' => $this->esAdministrador(),
            'idUsuario' => $usuario->id,
        ]);
    }

    public function ver(int $id, RecordatorioAnunciosService $recordatorios): View|RedirectResponse
    {
        $this->procesarRecordatorios($recordatorios);

        $usuario = $this->usuario();
        $anuncio = AnuncioGlobal::buscarVisible($id, $usuario);

        if (!$anuncio) {
            return $this->noDisponible();
        }

        return view('anuncios_globales.ver', [
            'anuncio' => $anuncio,
            'esAdmin' => $this->esAdministrador(),
            'esDestinatario' => $anuncio->esDestinatario($usuario),
        ]);
    }

    /**
     * El destinatario confirma la recepción (una sola vez, queda auditada).
     */
    public function confirmar(Request $request, int $id): RedirectResponse
    {
        $usuario = $this->usuario();

        $resultado = AnuncioGlobal::confirmarRecepcion($id, $usuario, $request->ip());

        if ($resultado === AnuncioGlobal::NO_AUTORIZADO) {
            return $this->noDisponible();
        }

        if ($resultado === AnuncioGlobal::YA_CONFIRMADO) {
            return redirect()
                ->route('anuncios_globales.ver', ['id' => $id])
                ->with('info', 'Este anuncio ya había sido confirmado.');
        }

        $anuncio = AnuncioGlobal::find($id);

        Bitacora::registrar(
            Bitacora::CONFIRMADO,
            self::MODULO,
            $id,
            $this->recortar('Confirmó la recepción del anuncio «' . $anuncio?->titulo . '».')
        );

        return redirect()
            ->route('anuncios_globales.ver', ['id' => $id])
            ->with('success', 'Recepción confirmada. La administración fue notificada.');
    }

    /**
     * Descarga de un adjunto: solo si el usuario puede ver el anuncio. Los
     * archivos están en almacenamiento privado, no hay enlace público.
     */
    public function descargar(int $id): StreamedResponse|RedirectResponse
    {
        $adjunto = AnuncioAdjunto::query()->find($id);
        $anuncio = $adjunto ? AnuncioGlobal::buscarVisible((int) $adjunto->id_anuncio, $this->usuario()) : null;

        if (!$adjunto || !$anuncio) {
            return $this->noDisponible();
        }

        if (!$adjunto->existeArchivo()) {
            return redirect()
                ->route('anuncios_globales.ver', ['id' => $anuncio->id])
                ->with('error', 'El archivo ya no está disponible en el servidor.');
        }

        Bitacora::registrar(
            Bitacora::DESCARGADO,
            self::MODULO,
            $anuncio->id,
            $this->recortar('Descargó «' . $adjunto->nombre_original . '» del anuncio «' . $anuncio->titulo . '».')
        );

        return Storage::disk('local')->download($adjunto->ruta, $adjunto->nombre_original);
    }

    // Administración

    public function crear(): View
    {
        $this->exigirAdministrador();

        return view('anuncios_globales.formulario', [
            'anuncio' => null,
            'usuarios' => $this->usuariosDestinatarios(),
        ]);
    }

    public function insertar(InsertarAnuncioGlobalRequest $request): RedirectResponse
    {
        $this->exigirAdministrador();

        try {
            $anuncio = AnuncioGlobal::publicar(
                $request->datosAnuncio(),
                $request->archivosAdjuntos(),
                $this->usuario()
            );
        } catch (\Throwable $error) {
            report($error);

            return back()
                ->withInput()
                ->with('error', 'No fue posible publicar el anuncio. Inténtelo nuevamente.');
        }

        Bitacora::registrar(
            Bitacora::CREADO,
            self::MODULO,
            $anuncio->getKey(),
            $this->recortar($this->describir('Publicó', $anuncio, count($request->archivosAdjuntos())))
        );

        return redirect()
            ->route('anuncios_globales.ver', ['id' => $anuncio->id])
            ->with('success', $anuncio->esDirigido()
                ? 'Anuncio publicado. Se notificó al destinatario.'
                : 'Anuncio publicado. Se notificó a los usuarios activos.');
    }

    public function editar(int $id): View|RedirectResponse
    {
        $this->exigirAdministrador();

        $anuncio = AnuncioGlobal::buscarVisible($id, $this->usuario());

        if (!$anuncio) {
            return $this->noDisponible();
        }

        if ($anuncio->estaConfirmado()) {
            return redirect()
                ->route('anuncios_globales.ver', ['id' => $id])
                ->with('warning', 'Este anuncio ya fue confirmado por su destinatario y no puede modificarse.');
        }

        return view('anuncios_globales.formulario', [
            'anuncio' => $anuncio,
            'usuarios' => collect(),
        ]);
    }

    public function actualizar(ActualizarAnuncioGlobalRequest $request, int $id): RedirectResponse
    {
        $this->exigirAdministrador();

        try {
            $resultado = AnuncioGlobal::modificar(
                $id,
                $request->datosAnuncio(),
                $request->archivosAdjuntos(),
                $request->adjuntosQuitados()
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->noDisponible();
        } catch (\DomainException $error) {
            return back()->withInput()->with('error', $error->getMessage());
        } catch (\Throwable $error) {
            report($error);

            return back()
                ->withInput()
                ->with('error', 'No fue posible actualizar el anuncio. Inténtelo nuevamente.');
        }

        $anuncio = $resultado['anuncio'];

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            self::MODULO,
            $anuncio->getKey(),
            $this->recortar($this->describir('Actualizó', $anuncio, count($request->archivosAdjuntos()))
                . ($resultado['reprogramado'] ? ' Cambió la fecha del evento.' : ''))
        );

        return redirect()
            ->route('anuncios_globales.ver', ['id' => $anuncio->id])
            ->with('success', $resultado['reprogramado'] && $anuncio->activo
                ? 'Anuncio actualizado. Se avisó del cambio de fecha del evento.'
                : 'Anuncio actualizado correctamente.');
    }

    public function eliminar(int $id): RedirectResponse
    {
        $this->exigirAdministrador();

        $anuncio = AnuncioGlobal::eliminarAnuncio($id);

        if (!$anuncio) {
            return $this->noDisponible();
        }

        Bitacora::registrar(
            Bitacora::ELIMINADO,
            self::MODULO,
            $anuncio->getKey(),
            $this->recortar('Eliminó el anuncio «' . $anuncio->titulo . '»'
                . ($anuncio->esDirigido() ? ' dirigido al usuario #' . $anuncio->id_destinatario : '')
                . ($anuncio->estaConfirmado() ? ' (ya confirmado).' : '.'))
        );

        return redirect()
            ->route('anuncios_globales')
            ->with('success', 'Anuncio eliminado correctamente.');
    }

    // Apoyo

    private function usuario(): Usuario
    {
        return request()->user();
    }

    /**
     * Segunda barrera, además del middleware de rol de las rutas.
     */
    private function exigirAdministrador(): void
    {
        abort_unless($this->esAdministrador(), 403, 'Solo el administrador puede gestionar anuncios.');
    }

    private function noDisponible(): RedirectResponse
    {
        return redirect()
            ->route('anuncios_globales')
            ->with('warning', 'El anuncio no está disponible.');
    }

    /**
     * El recordatorio no debe depender solo del planificador; si falla, la
     * pantalla igual se muestra.
     */
    private function procesarRecordatorios(RecordatorioAnunciosService $recordatorios): void
    {
        try {
            $recordatorios->procesar();
        } catch (\Throwable $error) {
            report($error);
        }
    }

    /**
     * Usuarios activos que pueden ser destinatarios (todos menos quien publica).
     */
    private function usuariosDestinatarios()
    {
        return Usuario::query()
            ->with('rol')
            ->whereIn('id', AnuncioGlobal::idsUsuariosActivos())
            ->where('id', '!=', $this->usuario()->id)
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->get();
    }

    private function describir(string $accion, AnuncioGlobal $anuncio, int $adjuntosNuevos): string
    {
        $texto = $accion . ' el anuncio «' . $anuncio->titulo . '» (prioridad ' . Str::lower($anuncio->etiquetaPrioridad()) . ')';

        if ($anuncio->esDirigido()) {
            $texto .= ', dirigido al usuario #' . $anuncio->id_destinatario;
        }

        if ($anuncio->tieneEvento()) {
            $texto .= ', evento el ' . $anuncio->textoEvento();
        }

        if ($adjuntosNuevos > 0) {
            $texto .= ', ' . $adjuntosNuevos . ($adjuntosNuevos === 1 ? ' adjunto nuevo' : ' adjuntos nuevos');
        }

        return $texto . '.';
    }

    /**
     * La columna descripcion de la bitácora admite 255 caracteres.
     */
    private function recortar(string $texto): string
    {
        return Str::limit($texto, 250, '…');
    }
}
