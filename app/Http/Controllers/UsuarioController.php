<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarUsuarioRequest;
use App\Http\Requests\InsertarUsuarioRequest;
use App\Models\Bitacora;
use App\Models\Especialidad;
use App\Models\Estado;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    /**
     * Muestra todos los usuarios registrados.
     */
    public function index(): View
    {
        $datos = [
            'usuarios' => Usuario::mostrarTodos()->load('doctor.especialidad'),
            'roles' => Rol::mostrarTodos(),
            'estados' => Estado::mostrarTodos(),
            'especialidades' => Especialidad::mostrarTodos(),
        ];

        return view('usuarios.index', $datos);
    }

    /**
     * Inserta un nuevo usuario. Si su rol es Médico, en la misma transacción
     * se registran los datos del doctor.
     */
    public function insertarUsuario(
        InsertarUsuarioRequest $request
    ): RedirectResponse | JsonResponse {
        // La contraseña la define el administrador: el usuario debe cambiarla al entrar.
        $usuario = Usuario::insertar(
            $request->validated() + ['debe_cambiar_contrasenha' => true]
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'USUARIOS',
            $usuario->getKey(),
            'Usuario creado correctamente: '
            . trim($usuario->nombres . ' ' . $usuario->apellidos)
            . ' (' . $usuario->correo . ')'
            . ($usuario->doctor ? '. Licencia: ' . $usuario->doctor->numero_licencia : '')
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Usuario creado correctamente',
                'usuario' => $usuario,
            ], 201);
        }

        return redirect()
            ->route('usuarios')
            ->with('success', 'Usuario creado correctamente');
    }

    /**
     * Busca un usuario por su ID (incluye los datos del doctor si los tiene).
     */
    public function buscarUsuarioXId(int $id): JsonResponse
    {
        $usuario = Usuario::buscarUsuarioXId($id);

        if (!$usuario) {
            return response()->json(
                [
                    'message' => 'Usuario no encontrado.'
                ],
                404
            );
        }

        return response()->json($usuario->load('doctor'));
    }

    /**
     * Actualiza un usuario existente (y sus datos de doctor si es médico).
     */
    public function actualizarUsuario(
        ActualizarUsuarioRequest $request,
        Usuario $usuario
    ): RedirectResponse | JsonResponse {
        $datos = $request->validated();

        // Si el administrador asigna una contraseña nueva, el usuario debe cambiarla al entrar.
        if (!blank($datos['contrasenha'] ?? null)) {
            $datos['debe_cambiar_contrasenha'] = true;
        }

        $cambiaRolOEstado =
            (int) $datos['id_rol'] !== (int) $usuario->id_rol ||
            (int) $datos['id_estado'] !== (int) $usuario->id_estado;

        if ($cambiaRolOEstado) {
            if ($usuario->getKey() === $this->usuarioActual()->getKey()) {
                return $this->rechazar(
                    $request,
                    'No puede cambiar su propio rol ni su estado.'
                );
            }

            if (Usuario::esUltimoAdministrador($usuario->getKey())) {
                return $this->rechazar(
                    $request,
                    'Debe existir al menos un administrador activo.'
                );
            }
        }

        if (
            Usuario::actualizar(
                $datos,
                $usuario->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'USUARIOS',
                $usuario->getKey(),
                'Usuario actualizado correctamente: '
                . trim($usuario->nombres . ' ' . $usuario->apellidos)
                . ' (' . $usuario->correo . ')'
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Usuario actualizado correctamente',
                    'usuario' => $usuario->refresh()->load(['rol', 'estado', 'doctor']),
                ]);
            }

            return redirect()
                ->route('usuarios')
                ->with('success', 'Usuario actualizado correctamente');
        }

        return redirect()
            ->route('usuarios')
            ->with(
                'error',
                'Usuario no actualizado'
            );
    }

    /**
     * Elimina un usuario existente (eliminación lógica).
     */
    public function eliminarUsuario(
        Request $request,
        Usuario $usuario
    ): RedirectResponse | JsonResponse
    {
        if ($usuario->getKey() === $this->usuarioActual()->getKey()) {
            return $this->rechazar($request, 'No puede eliminar su propio usuario.');
        }

        if (Usuario::esUltimoAdministrador($usuario->getKey())) {
            return $this->rechazar($request, 'Debe existir al menos un administrador activo.');
        }

        $registroId = $usuario->getKey();

        $descripcion = 'Usuario eliminado correctamente: '
            . trim($usuario->nombres . ' ' . $usuario->apellidos)
            . ' (' . $usuario->correo . ')';

        if (Usuario::eliminar($registroId)) {
            Bitacora::registrar(
                Bitacora::ELIMINADO,
                'USUARIOS',
                $registroId,
                $descripcion
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Usuario eliminado correctamente',
                    'id' => $registroId,
                ]);
            }

            return redirect()
                ->route('usuarios')
                ->with('success', 'Usuario eliminado correctamente');
        }

        return redirect()
            ->route('usuarios')
            ->with(
                'error',
                'Usuario no eliminado'
            );
    }

    /**
     * Respuesta cuando una acción sobre un usuario no está permitida.
     */
    private function rechazar(Request $request, string $mensaje): RedirectResponse | JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $mensaje], 422);
        }

        return redirect()
            ->route('usuarios')
            ->with('error', $mensaje);
    }
}
