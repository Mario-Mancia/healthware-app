<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Receta;
use App\Models\RegistroMedico;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

/**
 * Base de las solicitudes que trabajan con un paciente.
 *
 * Los pacientes se identifican por su DUI: el formulario envía "dui_paciente"
 * y aquí se convierte en "id_paciente", que es lo que validan los modelos.
 * Un médico solo puede trabajar con pacientes que atiende.
 */
abstract class SolicitudConPaciente extends FormRequest
{
    /**
     * Convierte el DUI escrito en el formulario en el id del paciente. Si el
     * DUI no existe (o el médico no atiende a ese paciente) deja id_paciente
     * vacío y la validación responde que no se encontró el paciente.
     */
    protected function prepareForValidation(): void
    {
        $dui = trim((string) $this->input('dui_paciente', ''));

        if ($dui === '') {
            return;
        }

        $paciente = Paciente::buscarPacienteXDui($dui);

        if ($paciente && !$this->puedeAccederAlPaciente($paciente->getKey())) {
            $paciente = null;
        }

        // Un id inexistente (0) hace que la validación responda "no se
        // encontró un paciente con ese DUI" en lugar de "campo obligatorio".
        $this->merge([
            'id_paciente' => $paciente?->getKey() ?? 0,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Si llega un id_paciente directo (sin DUI), un médico solo puede usarlo
     * si atiende a ese paciente.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $id = $this->input('id_paciente');

            if (
                $id !== null &&
                $id !== '' &&
                !$validator->errors()->has('id_paciente') &&
                !$this->puedeAccederAlPaciente((int) $id)
            ) {
                $validator->errors()->add(
                    'id_paciente',
                    'No tiene permiso para trabajar con este paciente.'
                );
            }
        });
    }

    /**
     * Indica si el usuario autenticado puede trabajar con un paciente.
     */
    protected function puedeAccederAlPaciente(?int $idPaciente): bool
    {
        if ($idPaciente === null) {
            return false;
        }

        $usuario = $this->user();

        if (!$usuario || !$usuario->tieneRol(Usuario::ROL_MEDICO)) {
            return true;
        }

        $doctor = Doctor::buscarPorUsuario($usuario->getKey());

        return $doctor !== null
            && Paciente::atendidoPor($idPaciente, $doctor->getKey());
    }

    /**
     * Indica si el usuario puede trabajar con el paciente de un registro
     * médico. Si el registro no existe devuelve true: la validación se
     * encarga de avisar que no existe.
     */
    protected function puedeAccederAlRegistro(?int $idRegistroMedico): bool
    {
        if (!$idRegistroMedico) {
            return true;
        }

        $registro = RegistroMedico::withTrashed()->find($idRegistroMedico);

        return $registro === null
            || $this->puedeAccederAlPaciente($registro->id_paciente);
    }

    /**
     * Indica si el usuario puede trabajar con el paciente de una receta.
     */
    protected function puedeAccederALaReceta(?int $idReceta): bool
    {
        if (!$idReceta) {
            return true;
        }

        $receta = Receta::find($idReceta);

        return $receta === null
            || $this->puedeAccederAlRegistro($receta->id_registro_medico);
    }

    /**
     * Respuesta cuando falla la autorización: mensaje en JSON o redirección
     * con el aviso de error.
     */
    protected function failedAuthorization(): void
    {
        $mensaje = 'No tiene permiso para realizar esta acción con este paciente.';

        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json(['message' => $mensaje], 403)
            );
        }

        throw new HttpResponseException(
            redirect()->back()->with('error', $mensaje)
        );
    }
}
