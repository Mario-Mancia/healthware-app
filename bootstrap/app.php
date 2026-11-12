<?php

use App\Http\Middleware\ForzarCambioContrasenha;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\RolMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(
            [
                'rol' => RolMiddleware::class,
                'no-cache' => PreventBackHistory::class,
                'cambio-contrasenha' => ForzarCambioContrasenha::class,
            ]
        );

        $middleware->appendToGroup('web', SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function(
           AuthenticationException $e,
           Request $request
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Debe iniciar sesión para acceder al sistema.',
                ], 401);
            }

            return redirect()
                ->guest(route('login'))
                ->with('error', 'No puedes acceder a esta ruta sin iniciar sesión.');
        });

        $exceptions->render(function(
            HttpException $e,
            Request $request
        ) {
            // 419: el token CSRF venció (la sesión expiró).
            if ($e->getStatusCode() === 419) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Su sesión expiró. Inicie sesión nuevamente.',
                    ], 419);
                }

                return redirect()
                    ->route('login')
                    ->with('warning', 'Su sesión expiró. Inicie sesión nuevamente.');
            }

            // 429: demasiadas solicitudes.
            if ($e->getStatusCode() === 429) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Demasiados intentos. Intente nuevamente en unos minutos.',
                    ], 429);
                }

                return redirect()
                    ->back()
                    ->with('error', 'Demasiados intentos. Intente nuevamente en unos minutos.');
            }

            return null;
        });
    })->create();
