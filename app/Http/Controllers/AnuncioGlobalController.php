<?php

namespace App\Http\Controllers;

use App\Models\AnuncioGlobal;
use App\Models\Bitacora;
use App\Http\Requests\InsertarAnuncioGlobalRequest;
use App\Http\Requests\ActualizarAnuncioGlobalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnuncioGlobalController extends Controller
{
    // Mostrar vista principal
    public function index(): View
    {
        return view('anuncios_globales.index', [
            'anuncios' => AnuncioGlobal::mostrarTodos()
        ]);
    }

    // Obtener todos los anuncios en JSON
    public function mostrarTodos(): JsonResponse
    {
        return response()->json(AnuncioGlobal::mostrarTodos());
    }

    // Insertar anuncio
    public function insertar(InsertarAnuncioGlobalRequest $request): RedirectResponse
    {
        $anuncio = AnuncioGlobal::insertar($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'ANUNCIOS_GLOBALES',
            $anuncio->getKey(),
            'Se registró un nuevo anuncio global.'
        );

        return redirect()
            ->route('anuncios_globales')
            ->with('success', 'Anuncio registrado correctamente.');
    }

    // Buscar anuncio por ID
    public function buscar(int $id): JsonResponse
    {
        $anuncio = AnuncioGlobal::buscarAnuncioGlobalXId($id);

        if (!$anuncio) {
            return response()->json([
                'mensaje' => 'Anuncio no encontrado.'
            ], 404);
        }

        return response()->json($anuncio);
    }

    // Actualizar anuncio
    public function actualizar(
        ActualizarAnuncioGlobalRequest $request,
        int $id
    ): RedirectResponse {
        $anuncio = AnuncioGlobal::find($id);

        if (!$anuncio) {
            return redirect()
                ->route('anuncios_globales')
                ->with('error', 'Anuncio no encontrado.');
        }

        $anuncio->actualizar($request->validated());

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'ANUNCIOS_GLOBALES',
            $anuncio->getKey(),
            'Se actualizó un anuncio global.'
        );

        return redirect()
            ->route('anuncios_globales')
            ->with('success', 'Anuncio actualizado correctamente.');
    }
}
