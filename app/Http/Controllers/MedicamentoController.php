<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarMedicamentoRequest;
use App\Http\Requests\InsertarMedicamentoRequest;
use App\Models\Bitacora;
use App\Models\Medicamento;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MedicamentoController extends Controller
{
    public function index(): View
    {
        $datos = [
            'medicamentos' => Medicamento::mostrarTodos(),
        ];

        return view('medicamentos.index', $datos);
    }

    public function insertarMedicamento(
        InsertarMedicamentoRequest $request
    ): RedirectResponse {
        $medicamento = Medicamento::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'MEDICAMENTOS',
            $medicamento->getKey(),
            'Medicamento registrado: ' . $medicamento->nombre . '.'
        );

        return redirect()
            ->route('medicamentos')
            ->with('success', 'Medicamento registrado correctamente.');
    }

    public function buscarMedicamentoXId(int $id): JsonResponse
    {
        $medicamento = Medicamento::buscarMedicamentoXId($id);

        if (!$medicamento) {
            return response()->json(
                ['message' => 'Medicamento no encontrado.'],
                404
            );
        }

        return response()->json($medicamento);
    }

    public function actualizarMedicamento(
        ActualizarMedicamentoRequest $request,
        Medicamento $medicamento
    ): RedirectResponse {
        if (
            Medicamento::actualizar(
                $request->validated(),
                $medicamento->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'MEDICAMENTOS',
                $medicamento->getKey(),
                'Medicamento actualizado: ' . $medicamento->nombre . '.'
            );

            return redirect()
                ->route('medicamentos')
                ->with('success', 'Medicamento actualizado correctamente.');
        }

        return redirect()
            ->route('medicamentos')
            ->with('error', 'Medicamento no actualizado.');
    }

    public function eliminarMedicamento(
        Medicamento $medicamento
    ): RedirectResponse {
        $id = $medicamento->getKey();
        $nombre = $medicamento->nombre;

        try {
            if (Medicamento::eliminar($id)) {
                Bitacora::registrar(
                    Bitacora::ELIMINADO,
                    'MEDICAMENTOS',
                    $id,
                    'Medicamento eliminado: ' . $nombre . '.'
                );

                return redirect()
                    ->route('medicamentos')
                    ->with('success', 'Medicamento eliminado correctamente.');
            }
        } catch (QueryException $e) {
            return redirect()
                ->route('medicamentos')
                ->with('error', 'No se puede eliminar el medicamento: está en uso.');
        }

        return redirect()
            ->route('medicamentos')
            ->with('error', 'Medicamento no eliminado.');
    }
}
