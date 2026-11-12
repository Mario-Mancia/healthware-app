<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarRecetaRequest;
use App\Models\Bitacora;
use App\Models\Receta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecetaController extends Controller
{
    /**
     * Un médico ve las recetas de los pacientes que atiende; el
     * administrador ve todas.
     */
    public function index(): View
    {
        return view('recetas.index', [
            'recetas' => $this->filtrarPorPaciente(
                Receta::mostrarTodos(),
                fn ($receta) => $receta->registroMedico?->id_paciente
            ),
        ]);
    }

    public function insertarReceta(
        InsertarRecetaRequest $request
    ): RedirectResponse {
        $receta = Receta::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'RECETAS',
            $receta->getKey(),
            'Se registró una nueva receta médica.'
        );

        return redirect()
            ->route('recetas')
            ->with('success', 'La receta médica se registró correctamente.');
    }

    public function buscarReceta(int $id): JsonResponse
    {
        $receta = Receta::buscarRecetaXId($id);

        if (
            !$receta ||
            !$this->puedeVerPaciente($receta->registroMedico?->id_paciente)
        ) {
            return response()->json([
                'mensaje' => 'La receta médica no existe.',
            ], 404);
        }

        return response()->json($receta);
    }
}
