<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarPacienteRequest;
use App\Http\Requests\InsertarPacienteRequest;
use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\TipoSanguineo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PacienteController extends Controller
{
    /**
     * Un médico ve solo a los pacientes que atiende; la recepcionista y el
     * administrador ven a todos.
     */
    public function index(): View
    {
        if ($this->esMedico()) {
            $doctor = $this->doctorActual();

            $pacientes = $doctor
                ? Paciente::mostrarTodosDeDoctor($doctor->getKey())
                : collect();
        } else {
            $pacientes = Paciente::mostrarTodos();
        }

        $datos = [
            'pacientes' => $pacientes,
            'tiposSanguineos' => TipoSanguineo::all(),
        ];

        return view('pacientes.index', $datos);
    }

    public function insertarPaciente(
        InsertarPacienteRequest $request
    ): RedirectResponse {
        $paciente = Paciente::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'PACIENTES',
            $paciente->getKey(),
            'Paciente registrado correctamente. '
            . 'Identificación: '
            . $paciente->numero_identificacion
            . ', nombre: '
            . $paciente->nombres
            . ' '
            . $paciente->apellidos
        );

        return redirect()
            ->route('pacientes')
            ->with(
                'success',
                'Paciente registrado correctamente.'
            );
    }

    public function buscarPacienteXId(
        int $id
    ): JsonResponse {
        $paciente = Paciente::buscarPacienteXId($id);

        if (!$paciente || !$this->puedeVerPaciente($paciente->getKey())) {
            return response()->json(
                ['message' => 'Paciente no encontrado.'],
                404
            );
        }

        return response()->json($paciente);
    }

    /**
     * Busca un paciente por su DUI. Los formularios piden el DUI y, debajo,
     * muestran el nombre para confirmar. Un médico solo encuentra a quien
     * atiende; si no, la respuesta es la misma que si no existiera.
     */
    public function buscarPacienteXDui(string $dui): JsonResponse
    {
        $paciente = Paciente::buscarPacienteXDui($dui);

        if (!$paciente || !$this->puedeVerPaciente($paciente->getKey())) {
            return response()->json([
                'encontrado' => false,
                'mensaje' => 'No se encontró un paciente con ese DUI.',
            ], 404);
        }

        $respuesta = [
            'encontrado' => true,
            'id' => $paciente->getKey(),
            'numero_identificacion' => $paciente->numero_identificacion,
            'nombre_completo' => trim($paciente->nombres . ' ' . $paciente->apellidos),
        ];

        // Las citas vigentes ayudan a vincular un registro médico u orden.
        if ($this->esMedico() || $this->esAdministrador()) {
            $citas = Cita::query()
                ->where('id_paciente', $paciente->getKey())
                ->whereNotIn('estado', ['cancelada', 'no_asistio'])
                ->when(
                    $this->esMedico(),
                    fn ($consulta) => $consulta->where('id_doctor', $this->doctorActual()->getKey())
                )
                ->orderByDesc('fecha_cita')
                ->limit(10)
                ->get(['id', 'fecha_cita', 'hora_inicio', 'estado']);

            $respuesta['citas'] = $citas;
        }

        return response()->json($respuesta);
    }

    /**
     * Perfil administrativo del paciente.
     */
    public function perfil(Paciente $paciente): View|RedirectResponse
    {
        if (!$this->puedeVerPaciente($paciente->getKey())) {
            return $this->prohibidoRedirigir('pacientes');
        }

        return view('pacientes.perfil', [
            'paciente' => Paciente::buscarPacienteXId($paciente->getKey()),
        ]);
    }

    /**
     * Expediente clínico integral. Cada consulta queda registrada en la
     * bitácora y un médico solo accede al de los pacientes que atiende.
     */
    public function expediente(Paciente $paciente): View|RedirectResponse
    {
        if (!$this->puedeVerPaciente($paciente->getKey())) {
            return $this->prohibidoRedirigir(
                'pacientes',
                'No tiene permiso para consultar el expediente de este paciente.'
            );
        }

        $paciente->load([
            'tipoSanguineo',
            'alergias.alergia',
            'condiciones.condicionMedica',
            'registrosMedicos' => fn ($consulta) => $consulta->orderByDesc('fecha_registro'),
            'registrosMedicos.doctor.usuario',
            'registrosMedicos.doctor.especialidad',
            'registrosMedicos.signosVitales',
            'registrosMedicos.diagnosticos.diagnostico',
            'registrosMedicos.recetas.detalles.medicamento',
        ]);

        Bitacora::registrar(
            Bitacora::CONSULTADO,
            'EXPEDIENTES',
            $paciente->getKey(),
            'Consulta del expediente clínico. Identificación: '
            . $paciente->numero_identificacion
            . ', nombre: '
            . $paciente->nombres
            . ' '
            . $paciente->apellidos
        );

        return view('pacientes.expediente', [
            'paciente' => $paciente,
        ]);
    }

    public function actualizarPaciente(
        ActualizarPacienteRequest $request,
        Paciente $paciente
    ): RedirectResponse {
        if (
            Paciente::actualizar(
                $request->validated(),
                $paciente->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'PACIENTES',
                $paciente->getKey(),
                'Datos del paciente actualizados. '
                . 'Identificación: '
                . $paciente->numero_identificacion
                . ', nombre: '
                . $paciente->nombres
                . ' '
                . $paciente->apellidos
            );

            return redirect()
                ->route('pacientes')
                ->with(
                    'success',
                    'Paciente actualizado correctamente.'
                );
        }

        return redirect()
            ->route('pacientes')
            ->with(
                'error',
                'El paciente no pudo ser actualizado.'
            );
    }

    /**
     * Elimina un paciente. Va a la papelera: el administrador puede
     * restaurarlo con su historial clínico.
     */
    public function eliminarPaciente(
        Paciente $paciente
    ): RedirectResponse {
        $id = $paciente->getKey();
        $identificacion = $paciente->numero_identificacion;
        $nombre = $paciente->nombres . ' ' . $paciente->apellidos;

        if (Paciente::eliminar($id)) {
            Bitacora::registrar(
                Bitacora::ELIMINADO,
                'PACIENTES',
                $id,
                'Paciente enviado a la papelera. '
                . 'Identificación: '
                . $identificacion
                . ', nombre: '
                . $nombre
            );

            return redirect()
                ->route('pacientes')
                ->with(
                    'success',
                    'Paciente eliminado correctamente.'
                );
        }

        return redirect()
            ->route('pacientes')
            ->with(
                'error',
                'El paciente no pudo ser eliminado.'
            );
    }

    /**
     * Papelera de pacientes (solo administrador).
     */
    public function papelera(): View
    {
        return view('pacientes.papelera', [
            'pacientes' => Paciente::mostrarEliminados(),
        ]);
    }

    /**
     * Restaura un paciente de la papelera (solo administrador).
     */
    public function restaurarPaciente(int $id): RedirectResponse
    {
        if (Paciente::restaurar($id)) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'PACIENTES',
                $id,
                'Paciente restaurado desde la papelera.'
            );

            return redirect()
                ->route('pacientes.papelera')
                ->with('success', 'Paciente restaurado correctamente.');
        }

        return redirect()
            ->route('pacientes.papelera')
            ->with('error', 'No fue posible restaurar al paciente.');
    }
}
