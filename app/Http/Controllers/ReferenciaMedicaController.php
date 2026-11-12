<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarReferenciaMedicaRequest;
use App\Http\Requests\MotivoReferenciaMedicaRequest;
use App\Http\Requests\ProgramarReferenciaMedicaRequest;
use App\Models\Bitacora;
use App\Models\Doctor;
use App\Models\Especialidad;
use App\Models\ReferenciaMedica;
use App\Services\ReferenciaMedicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Referencias médicas.
 *
 *  - El médico emite referencias sobre pacientes que atiende y ve las que
 *    emitió o que se le asignaron (con su resumen clínico).
 *  - Recepción gestiona la programación; ella y el administrador ven las
 *    referencias sin el contenido clínico (resumen y estudios).
 */
class ReferenciaMedicaController extends Controller
{
    public function __construct(private readonly ReferenciaMedicaService $servicio)
    {
    }

    public function index(): View
    {
        if ($this->esMedico()) {
            $doctor = $this->doctorActual();

            $referencias = $doctor
                ? ReferenciaMedica::mostrarDeDoctor($doctor->getKey())
                : collect();
        } else {
            $referencias = ReferenciaMedica::mostrarTodos();

            // Recepción y administración no necesitan el contenido clínico.
            $referencias->each(function (ReferenciaMedica $referencia) {
                $referencia->setAttribute('resumen_clinico', null);
                $referencia->setAttribute('estudios_solicitados', null);
            });
        }

        return view('referencias.index', [
            'referencias' => $referencias,
            'doctores' => Doctor::mostrarTodos(),
            'especialidades' => Especialidad::mostrarTodos(),
            'idDoctorActual' => $this->doctorActual()?->getKey(),
        ]);
    }

    /**
     * El médico emite una referencia (queda pendiente para recepción).
     */
    public function insertarReferencia(InsertarReferenciaMedicaRequest $request): RedirectResponse
    {
        $referencia = $this->servicio->emitir(
            $this->doctorActual(),
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'REFERENCIAS_MEDICAS',
            $referencia->getKey(),
            'Referencia médica emitida: ' . $referencia->numero_referencia
        );

        return redirect()
            ->route('referencias')
            ->with('success', 'La referencia ' . $referencia->numero_referencia . ' se emitió correctamente. Recepción programará la cita.');
    }

    /**
     * Recepción programa la referencia: se crea la cita vinculada y se activa
     * la relación asistencial del especialista.
     */
    public function programarReferencia(
        ProgramarReferenciaMedicaRequest $request,
        ReferenciaMedica $referencia
    ): RedirectResponse {
        $cita = $this->servicio->programar($referencia, $request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'CITAS',
            $cita->getKey(),
            'Cita programada a partir de la referencia ' . $referencia->numero_referencia
        );

        return redirect()
            ->route('referencias')
            ->with('success', 'La referencia se programó correctamente: la cita quedó registrada.');
    }

    /**
     * Rechaza una referencia pendiente: el médico de destino, recepción o el
     * administrador.
     */
    public function rechazarReferencia(
        MotivoReferenciaMedicaRequest $request,
        ReferenciaMedica $referencia
    ): RedirectResponse {
        $esDestino = $this->esMedico()
            && $referencia->id_doctor_destino !== null
            && (int) $referencia->id_doctor_destino === (int) $this->doctorActual()?->getKey();

        if ($this->esMedico() && !$esDestino) {
            return redirect()
                ->route('referencias')
                ->with('error', 'Solo el médico de destino puede rechazar esta referencia.');
        }

        $this->servicio->rechazar($referencia, $request->validated('motivo'));

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'REFERENCIAS_MEDICAS',
            $referencia->getKey(),
            'Referencia médica rechazada: ' . $referencia->numero_referencia
        );

        return redirect()
            ->route('referencias')
            ->with('success', 'La referencia se rechazó correctamente.');
    }

    /**
     * Cancela una referencia: el médico que la emitió, recepción o el
     * administrador. Si ya tenía cita, esta se cancela también.
     */
    public function cancelarReferencia(
        MotivoReferenciaMedicaRequest $request,
        ReferenciaMedica $referencia
    ): RedirectResponse {
        $esOrigen = $this->esMedico()
            && (int) $referencia->id_doctor_origen === (int) $this->doctorActual()?->getKey();

        if ($this->esMedico() && !$esOrigen) {
            return redirect()
                ->route('referencias')
                ->with('error', 'Solo el médico que emitió la referencia puede cancelarla.');
        }

        $this->servicio->cancelar($referencia, $request->validated('motivo'));

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'REFERENCIAS_MEDICAS',
            $referencia->getKey(),
            'Referencia médica cancelada: ' . $referencia->numero_referencia
        );

        return redirect()
            ->route('referencias')
            ->with('success', 'La referencia se canceló correctamente.');
    }
}
