<?php

namespace App\Http\Requests;

use App\Models\AnuncioGlobal;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos de un anuncio que se edita. El destinatario no se puede cambiar.
 */
class ActualizarAnuncioGlobalRequest extends FormRequest
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
            'fecha_evento' => $tieneEvento ? $this->input('fecha_evento') : null,
            'activo' => $this->boolean('activo'),
        ]);
    }

    public function rules(): array
    {
        $actual = AnuncioGlobal::query()->find($this->route('id'));

        return array_merge(
            AnuncioGlobal::validaciones($actual),
            ['activo' => ['required', 'boolean']],
            InsertarAnuncioGlobalRequest::reglasAdjuntos(),
            [
                'quitar_adjuntos' => ['nullable', 'array'],
                'quitar_adjuntos.*' => ['integer'],
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function datosAnuncio(): array
    {
        $datos = $this->safe()->only([
            'titulo', 'mensaje', 'prioridad', 'lugar', 'datos_extra', 'fecha_evento', 'activo',
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

    /**
     * @return array<int, int>
     */
    public function adjuntosQuitados(): array
    {
        return array_map('intval', (array) $this->input('quitar_adjuntos', []));
    }

    public function messages(): array
    {
        return (new InsertarAnuncioGlobalRequest())->messages();
    }
}
