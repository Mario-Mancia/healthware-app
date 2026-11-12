<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolMiddleware
{
    /**
     * Verifica que el usuario esté activo y tenga
     * uno de los roles requeridos para acceder a la ruta.
     *
     * La comparación de roles ignora mayúsculas y tildes
     * (Médico = medico = MÉDICO).
     */
    public function handle(
        Request $request,
        Closure $next,
                ...$roles
    ): Response {
        $usuario = $request->user();

        /**
         * El middleware "auth" debe ejecutarse antes que este middleware.
         * Esta comprobación funciona como medida adicional de seguridad.
         */
        if (!$usuario) {
            return $this->sinSesion($request);
        }

        $usuario->loadMissing(['rol', 'estado']);

        /**
         * Verificamos que el usuario tenga un estado válido y activo.
         */
        if (
            !$usuario->estado ||
            strtolower(trim($usuario->estado->estado)) !== 'activo'
        ) {
            auth()->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Su cuenta se encuentra inactiva, contacte al administrador.',
                ], 403);
            }

            return redirect()
                ->route('login')
                ->with(
                    'warning',
                    'Su cuenta se encuentra inactiva, contacte al administrador.'
                );
        }

        /**
         * Si no se especifican roles, cualquier usuario
         * autenticado y activo puede continuar.
         */
        if (empty($roles)) {
            return $next($request);
        }

        if ($usuario->tieneRol(...$roles)) {
            return $next($request);
        }

        /**
         * El usuario está autenticado y activo, pero no tiene
         * permisos suficientes para acceder a esta sección.
         */
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No tiene permisos para acceder a esta sección.',
            ], 403);
        }

        return redirect()
            ->route('home')
            ->with(
                'error',
                'No tiene permisos para acceder a esta sección.'
            );
    }

    /**
     * Respuesta cuando no hay un usuario autenticado.
     */
    private function sinSesion(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Debe iniciar sesión para acceder al sistema.',
            ], 401);
        }

        return redirect()
            ->route('login')
            ->with(
                'error',
                'Debe iniciar sesión para acceder al sistema.'
            );
    }
}
