<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarFotoPerfilRequest;
use App\Http\Requests\ActualizarPerfilRequest;
use App\Http\Requests\CambiarContrasenhaRequest;
use App\Models\Bitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PerfilController extends Controller
{
    /**
     * Muestra el perfil del usuario autenticado.
     */
    public function index(): View
    {
        $usuario = $this->usuarioActual();

        $usuario->load(['rol', 'estado', 'doctor.especialidad']);

        return view('perfil.index', [
            'usuario' => $usuario,
        ]);
    }

    /**
     * Actualiza los datos personales del propio usuario.
     */
    public function actualizar(ActualizarPerfilRequest $request): RedirectResponse
    {
        $usuario = $this->usuarioActual();

        if ($usuario->update($request->validated())) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'USUARIOS',
                $usuario->getKey(),
                'El usuario actualizó los datos de su perfil.'
            );

            return redirect()
                ->route('perfil')
                ->with('success', 'Perfil actualizado correctamente.');
        }

        return redirect()
            ->route('perfil')
            ->with('error', 'No fue posible actualizar el perfil.');
    }

    /**
     * Guarda la foto de perfil en storage/app/public/media/usuarios. El
     * nombre del archivo lo genera Laravel, nunca el usuario.
     */
    public function actualizarFoto(ActualizarFotoPerfilRequest $request): RedirectResponse
    {
        $usuario = $this->usuarioActual();
        $anterior = $usuario->foto_perfil;

        $ruta = $request->file('foto')->store('media/usuarios', 'public');

        if (!$ruta) {
            return redirect()
                ->route('perfil')
                ->with('error', 'No fue posible guardar la foto.');
        }

        $usuario->update(['foto_perfil' => $ruta]);

        if ($anterior) {
            Storage::disk('public')->delete($anterior);
        }

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'USUARIOS',
            $usuario->getKey(),
            'El usuario actualizó su foto de perfil.'
        );

        return redirect()
            ->route('perfil')
            ->with('success', 'Foto de perfil actualizada correctamente.');
    }

    /**
     * Elimina la foto de perfil.
     */
    public function eliminarFoto(): RedirectResponse
    {
        $usuario = $this->usuarioActual();

        if ($usuario->foto_perfil) {
            Storage::disk('public')->delete($usuario->foto_perfil);

            $usuario->update(['foto_perfil' => null]);

            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'USUARIOS',
                $usuario->getKey(),
                'El usuario eliminó su foto de perfil.'
            );
        }

        return redirect()
            ->route('perfil')
            ->with('success', 'Foto de perfil eliminada correctamente.');
    }

    /**
     * Formulario para cambiar la contraseña (también se muestra cuando el
     * administrador obliga a cambiarla).
     */
    public function formularioContrasenha(): View
    {
        return view('perfil.contrasenha', [
            'usuario' => $this->usuarioActual(),
        ]);
    }

    /**
     * Cambia la contraseña del propio usuario y quita la obligación de cambiarla.
     */
    public function cambiarContrasenha(CambiarContrasenhaRequest $request): RedirectResponse
    {
        $usuario = $this->usuarioActual();

        $usuario->update([
            'contrasenha' => $request->validated()['contrasenha'],
            'debe_cambiar_contrasenha' => false,
        ]);

        $request->session()->regenerate();

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'USUARIOS',
            $usuario->getKey(),
            'El usuario cambió su contraseña.'
        );

        return redirect()
            ->route('home')
            ->with('success', 'Contraseña actualizada correctamente.');
    }
}
