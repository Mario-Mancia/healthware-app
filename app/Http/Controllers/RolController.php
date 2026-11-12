<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarRolRequest;
use App\Http\Requests\InsertarRolRequest;
use App\Models\Bitacora;
use App\Models\Rol;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RolController extends Controller
{
    public function index(): View
    {
        $datos = [
            'roles' => Rol::mostrarTodos(),
        ];

        return view('roles.index', $datos);
    }

    public function insertarRol(
        InsertarRolRequest $request
    ): RedirectResponse {
        $rol = Rol::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'ROLES',
            $rol->getKey(),
            'Rol registrado: ' . $rol->rol . '.'
        );

        return redirect()
            ->route('roles')
            ->with('success', 'Rol registrado correctamente.');
    }

    public function buscarRolXId(int $id): JsonResponse
    {
        $rol = Rol::buscarRolXId($id);

        if (!$rol) {
            return response()->json(
                ['message' => 'Rol no encontrado.'],
                404
            );
        }

        return response()->json($rol);
    }

    public function actualizarRol(
        ActualizarRolRequest $request,
        Rol $rol
    ): RedirectResponse {
        if (
            $this->esRolBase($rol->rol) &&
            $this->normalizar($request->validated()['rol']) !== $this->normalizar($rol->rol)
        ) {
            return redirect()
                ->route('roles')
                ->with('error', 'Los roles base del sistema no se pueden renombrar.');
        }

        if (
            Rol::actualizar(
                $request->validated(),
                $rol->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'ROLES',
                $rol->getKey(),
                'Rol actualizado: ' . $rol->rol . '.'
            );

            return redirect()
                ->route('roles')
                ->with('success', 'Rol actualizado correctamente.');
        }

        return redirect()
            ->route('roles')
            ->with('error', 'Rol no actualizado.');
    }

    public function eliminarRol(
        Rol $rol
    ): RedirectResponse {
        if ($this->esRolBase($rol->rol)) {
            return redirect()
                ->route('roles')
                ->with('error', 'Los roles base del sistema no se pueden eliminar.');
        }

        $id = $rol->getKey();
        $nombre = $rol->rol;

        try {
            if (Rol::eliminar($id)) {
                Bitacora::registrar(
                    Bitacora::ELIMINADO,
                    'ROLES',
                    $id,
                    'Rol eliminado: ' . $nombre . '.'
                );

                return redirect()
                    ->route('roles')
                    ->with('success', 'Rol eliminado correctamente.');
            }
        } catch (QueryException $e) {
            return redirect()
                ->route('roles')
                ->with('error', 'No se puede eliminar el rol: está en uso.');
        }

        return redirect()
            ->route('roles')
            ->with('error', 'Rol no eliminado.');
    }

    /**
     * Roles que usa el sistema para controlar el acceso.
     */
    private function esRolBase(string $rol): bool
    {
        return in_array($this->normalizar($rol), [
            'administrador',
            'medico',
            'recepcionista',
            'contador',
        ], true);
    }

    /**
     * Minúsculas y sin tildes, para comparar nombres.
     */
    private function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii(trim($texto)));
    }
}
