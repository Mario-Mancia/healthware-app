<?php

namespace App\Http\Controllers;

use App\Http\Requests\RevisarAccesoExcepcionalRequest;
use App\Http\Requests\SolicitarAccesoExcepcionalRequest;
use App\Models\AccesoExcepcional;
use App\Models\Bitacora;
use App\Models\Paciente;
use App\Services\AccesoExcepcionalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Acceso excepcional (break-glass) a expedientes.
 *
 * El médico sin relación asistencial (o el administrador) lo solicita con una
 * justificación; dura 4 horas, es de solo lectura y genera una alerta. El
 * administrador revisa después los accesos concedidos.
 */
class AccesoExcepcionalController extends Controller
{
    public function __construct(private readonly AccesoExcepcionalService $servicio)
    {
    }

    public function index(): View
    {
        $accesos = $this->esAdministrador()
            ? AccesoExcepcional::mostrarTodos()
            : AccesoExcepcional::mostrarDeUsuario($this->usuarioActual()->getKey());

        return view('accesos.index', [
            'accesos' => $accesos,
            'esAdministrador' => $this->esAdministrador(),
        ]);
    }

    /**
     * Solicita acceso de emergencia al expediente de un paciente.
     */
    public function solicitar(
        SolicitarAccesoExcepcionalRequest $request,
        Paciente $paciente
    ): RedirectResponse {
        $usuario = $this->usuarioActual();

        if (!$usuario->can('solicitarAccesoExcepcional', $paciente)) {
            return redirect()
                ->route('pacientes.expediente', ['paciente' => $paciente->getKey()])
                ->with('info', 'Ya puede consultar este expediente: no necesita un acceso excepcional.');
        }

        $acceso = $this->servicio->solicitar($usuario, $paciente, $request->validated('motivo'));

        Bitacora::registrar(
            Bitacora::CREADO,
            'ACCESOS_EXCEPCIONALES',
            $acceso->getKey(),
            'Acceso excepcional a un expediente. Paciente ID: ' . $paciente->getKey()
        );

        return redirect()
            ->route('pacientes.expediente', ['paciente' => $paciente->getKey()])
            ->with(
                'warning',
                'Acceso excepcional concedido por ' . AccesoExcepcional::HORAS_VIGENCIA
                . ' horas, solo lectura. Quedó registrado y se alertó a los administradores.'
            );
    }

    /**
     * El administrador registra la revisión de un acceso excepcional.
     */
    public function revisar(
        RevisarAccesoExcepcionalRequest $request,
        AccesoExcepcional $acceso
    ): RedirectResponse {
        $this->servicio->revisar($acceso, $this->usuarioActual(), $request->validated('observacion'));

        return redirect()
            ->route('accesos_excepcionales')
            ->with('success', 'La revisión del acceso excepcional quedó registrada.');
    }

    /**
     * El administrador revoca un acceso excepcional antes de que expire.
     */
    public function revocar(AccesoExcepcional $acceso): RedirectResponse
    {
        $this->servicio->revocar($acceso, $this->usuarioActual());

        return redirect()
            ->route('accesos_excepcionales')
            ->with('success', 'El acceso excepcional se revocó.');
    }
}
