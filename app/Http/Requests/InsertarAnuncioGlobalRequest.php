<?php

namespace App\Http\Requests;

use App\Models\AnuncioAdjunto;
use App\Models\AnuncioGlobal;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos de un anuncio nuevo. Solo el administrador puede publicar.
 */
class InsertarAnuncioGlobalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->tieneRol(Usuario::ROL_ADMINISTRADOR);
    }

    protected function prepareForValidation(): void
    {
        $tieneEvento = $this->boolean('tiene_evento');

        $this->merge([
            'tiene_evento' => $tieneEvento,
            // Sin evento, cualquier fecha que llegue se descarta.
            'fecha_evento' => $tieneEvento ? $this->input('fecha_evento') : null,
        ]);
    }

    public function rules(): array
    {
        return array_merge(AnuncioGlobal::validaciones(), [
            'id_destinatario' => [
                'nullable',
                'integer',
                function (string $atributo, mixed $valor, \Closure $fallar) {
                    if ((int) $valor === (int) $this->user()->id) {
                        $fallar('No puede elegirse a sí mismo como destinatario.');
                    } elseif (!AnuncioGlobal::idsUsuariosActivos()->contains((int) $valor)) {
                        $fallar('El destinatario elegido no existe o no está activo.');
                    }
                },
            ],
        ], self::reglasAdjuntos());
    }

    /**
     * Reglas de los archivos adjuntos (se comparten con la edición).
     *
     * @return array<string, mixed>
     */
    public static function reglasAdjuntos(): array
    {
        return [
            'adjuntos' => ['nullable', 'array', 'max:' . AnuncioAdjunto::MAX_ARCHIVOS],
            'adjuntos.*' => [
                function (string $atributo, mixed $valor, \Closure $fallar) {
                    if ($error = AnuncioAdjunto::errorArchivo($valor)) {
                        $fallar($error);
                    }
                },
            ],
        ];
    }

    /**
     * Datos del anuncio ya validados y listos para guardar.
     *
     * @return array<string, mixed>
     */
    public function datosAnuncio(): array
    {
        $datos = $this->safe()->only([
            'titulo', 'mensaje', 'prioridad', 'lugar', 'datos_extra', 'fecha_evento', 'id_destinatario',
        ]);

        $datos['fecha_evento'] = $this->boolean('tiene_evento') ? ($datos['fecha_evento'] ?? null) : null;

        return $datos;
    }

    /**
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    public function archivosAdjuntos(): array
    {
        return array_values(array_filter((array) $this->file('adjuntos', [])));
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título del anuncio es obligatorio.',
            'titulo.max' => 'El título no debe exceder los 150 caracteres.',
            'mensaje.required' => 'El mensaje del anuncio es obligatorio.',
            'mensaje.max' => 'El mensaje no debe exceder los 5000 caracteres.',
            'prioridad.required' => 'Seleccione la prioridad.',
            'prioridad.in' => 'La prioridad debe ser baja, normal, alta o urgente.',
            'lugar.max' => 'El lugar no debe exceder los 150 caracteres.',
            'datos_extra.max' => 'Los datos adicionales no deben exceder los 2000 caracteres.',
            'fecha_evento.required_if' => 'Indique la fecha y la hora del evento.',
            'fecha_evento.date' => 'La fecha del evento no es válida.',
            'id_destinatario.integer' => 'El destinatario elegido no es válido.',
            'adjuntos.array' => 'Los adjuntos no son válidos.',
            'adjuntos.max' => 'Un anuncio admite hasta ' . AnuncioAdjunto::MAX_ARCHIVOS . ' adjuntos.',
        ];
    }
}
