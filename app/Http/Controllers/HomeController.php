<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home.index');
    }

    /**
     * Dashboard. Los datos de cada rol se agregan en la fase de dashboards;
     * por ahora solo el administrador recibe el resumen de usuarios por estado.
     */
    public function dashboard(): View
    {
        $usuariosPorEstado = $this->esAdministrador()
            ? Usuario::estadosDeUsuario()
            : collect();

        return view('home.dashboard', compact('usuariosPorEstado'));
    }
}
