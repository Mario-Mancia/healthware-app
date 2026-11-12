<?php

namespace App\Http\Controllers;

use App\Http\Requests\GestionarRelacionAsistencialRequest;
use App\Models\Bitacora;
use App\Models\RelacionAsistencial;
use App\Services\AccesoAsistencialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Relaciones asistenciales: quién puede consultar el expediente de quién.
 *
 *  - El médico ve las suyas y puede cerrarlas (de forma explícita, con motivo).
 *  - El administrador ve todas y puede cerrar, revocar, suspender y reactivar.
 *    No ve información clínica: solo el vínculo.
 */
class RelacionAsistencialController extends Controller
{
    public function __construct(private readonly AccesoAsistencialService $servicio)
    {
    }

    public function index(): View
    {
        if ($this->esMedico()) {
            $doctor = $this->doctorActual();

            $relaciones = $doctor
                ? RelacionAsistencial::mostrarDeDoctor($doctor->getKey())
                : collect();
        } else {
            $relaciones = RelacionAsistencial::mostrarTodos();
        }

        return view('relaciones.index', [
            'relaciones' => $relaciones,
        ]);
    }

    /**
     * Cierre explícito: el médico dueño de la relación o el administrador.
     */
    public function cerrarRelacion(
        GestionarRelacionAsistencialRequest $request,
        RelacionAsistencial $relacion
    ): RedirectResponse {
        if ($this->esMedico() && (int) $relacion->id_doctor !== (int) $this->doctorActual()?->getKey()) {
            return redirect()
                ->route('relaciones')
                ->with('error', 'Solo puede cerrar sus propias relaciones asistenciales.');
        }

        $this->servicio->cerrar($relacion, $request->validated('motivo'), $this->usuarioActual());

        return $this->respuesta($relacion, 'cerrada', 'La relación asistencial se cerró y quedó como historial.');
    }

    public function revocarRelacion(
        GestionarRelacionAsistencialRequest $request,
        RelacionAsistencial $relacion
    ): RedirectResponse {
        $this->servicio->revocar($relacion, $request->validated('motivo'), $this->usuarioActual());

        return $this->respuesta($relacion, 'revocada', 'La relación asistencial se revocó.');
    }

    public function suspenderRelacion(
        GestionarRelacionAsistencialRequest $request,
        RelacionAsistencial $relacion
    ): RedirectResponse {
        $this->servicio->suspender($relacion, $request->validated('motivo'), $this->usuarioActual());

        return $this->respuesta($relacion, 'suspendida', 'La relación asistencial se suspendió.');
    }

    public function reactivarRelacion(
        GestionarRelacionAsistencialRequest $request,
        RelacionAsistencial $relacion
    ): RedirectResponse {
        $this->servicio->reactivar($relacion, $request->validated('motivo'), $this->usuarioActual());

        return $this->respuesta($relacion, 'reactivada', 'La relación asistencial se reactivó.');
    }

    private function respuesta(RelacionAsistencial $relacion, string $accion, string $mensaje): RedirectResponse
    {
        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'RELACIONES_ASISTENCIALES',
            $relacion->getKey(),
            'Relación asistencial ' . $accion . '. Paciente ID: ' . $relacion->id_paciente
                . ', doctor ID: ' . $relacion->id_doctor
        );

        return redirect()
            ->route('relaciones')
            ->with('success', $mensaje);
    }
}
