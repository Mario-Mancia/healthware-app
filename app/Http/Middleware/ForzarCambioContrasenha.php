<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForzarCambioContrasenha
{
    /**
     * Obliga a cambiar la contraseña a los usuarios que la recibieron del
     * administrador (debe_cambiar_contrasenha). Mientras no la cambien solo
     * pueden usar las rutas de cambio de contraseña y cerrar sesión.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $usuario = $request->user();

        if (
            $usuario &&
            $usuario->debe_cambiar_contrasenha &&
            !$request->routeIs('contrasenha.*', 'logout')
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Debe cambiar su contraseña antes de continuar.',
                ], 423);
            }

            return redirect()
                ->route('contrasenha.cambiar')
                ->with(
                    'warning',
                    'Debe cambiar su contraseña para continuar.'
                );
        }

        return $next($request);
    }
}
