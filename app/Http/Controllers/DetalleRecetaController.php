<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarDetalleRecetaRequest;
use App\Events\RegistroClinicoActualizado;
use App\Models\AuditoriaExpediente;
use App\Models\Bitacora;
use App\Models\DetalleReceta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DetalleRecetaController extends Controller
{
    /**
     * Agrega un medicamento a una receta y vuelve a la lista de recetas.
     */
    public function insertarDetalleReceta(
        InsertarDetalleRecetaRequest $request
    ): RedirectResponse {
        $detalleReceta = DetalleReceta::insertar(
            $request->validated()
        );

        $detalleReceta->loadMissing('receta.registroMedico');
        $idPacienteDetalle = (int) $detalleReceta->receta?->registroMedico?->id_paciente;

        AuditoriaExpediente::registrar(
            $idPacienteDetalle,
            AuditoriaExpediente::MODIFICACION,
            'detalle_receta',
            $detalleReceta->getKey(),
            null,
            ['accion' => 'medicamento_agregado', 'receta' => $detalleReceta->id_receta]
        );

        event(new RegistroClinicoActualizado(
            $idPacienteDetalle,
            (int) $this->doctorActual()?->getKey(),
            'detalle_receta'
        ));

        Bitacora::registrar(
            Bitacora::CREADO,
            'DETALLE_RECETAS',
            $detalleReceta->getKey(),
            'Se agregó un medicamento a una receta médica.'
        );

        return redirect()
            ->route('recetas')
            ->with(
                'success',
                'El medicamento se agregó a la receta correctamente.'
            );
    }

    public function buscarDetalleReceta(int $id): JsonResponse
    {
        $detalleReceta = DetalleReceta::buscarDetalleRecetaXId($id);

        if ($detalleReceta) {
            $detalleReceta->loadMissing('receta.registroMedico');
        }

        if (
            !$detalleReceta ||
            !$this->puedeConsultarExpediente($detalleReceta->receta?->registroMedico?->id_paciente)
        ) {
            return response()->json([
                'mensaje' => 'El detalle de la receta no existe.',
            ], 404);
        }

        return response()->json($detalleReceta);
    }
}
