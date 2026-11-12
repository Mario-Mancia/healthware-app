<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarDoctorRequest;
use App\Models\Bitacora;
use App\Models\Doctor;
use App\Models\Especialidad;
use App\Models\HorarioDoctor;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(): View
    {
        $datos = [
            'doctores' => Doctor::mostrarTodos(),
            'especialidades' => Especialidad::mostrarTodos(),
            // Cuentas con rol Médico (la vista las muestra en el formulario de edición)
            'usuarios' => Usuario::mostrarTodos()
                ->filter(fn ($usuario) => $usuario->tieneRol(Usuario::ROL_MEDICO))
                ->values(),
        ];

        return view('doctores.index', $datos);
    }

    /**
     * Perfil profesional de un doctor con sus horarios.
     */
    public function perfil(Doctor $doctor): View
    {
        return view('doctores.perfil', [
            'doctor' => Doctor::buscarDoctorXId($doctor->getKey()),
            'horarios' => HorarioDoctor::mostrarDeDoctor($doctor->getKey()),
        ]);
    }

    public function buscarDoctorXId(int $id): JsonResponse
    {
        $doctor = Doctor::buscarDoctorXId($id);

        if (!$doctor) {
            return response()->json(
                ['message' => 'Doctor no encontrado.'],
                404
            );
        }

        return response()->json($doctor);
    }

    /**
     * Actualiza los datos profesionales de un doctor. El doctor se crea
     * junto con su usuario (ver UsuarioController).
     */
    public function actualizarDoctor(
        ActualizarDoctorRequest $request,
        Doctor $doctor
    ): RedirectResponse {
        if (
            Doctor::actualizar(
                $request->validated(),
                $doctor->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'DOCTORES',
                $doctor->getKey(),
                'Registro profesional de doctor actualizado. '
                . 'Usuario ID: ' . $doctor->usuario_id
                . ', licencia: ' . $doctor->numero_licencia
            );

            return redirect()
                ->route('doctores')
                ->with(
                    'success',
                    'Doctor actualizado correctamente.'
                );
        }

        return redirect()
            ->route('doctores')
            ->with(
                'error',
                'El doctor no pudo ser actualizado.'
            );
    }
}
