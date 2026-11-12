<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\View\View;
class BitacoraController extends Controller
{
    public function index(Request $request): View
    {
        $datos = [
            'bitacoras' => Bitacora::mostrarTodos(
                $request->input('evento'),
                $request->input('fecha_desde'),
                $request->input('fecha_hasta')
            ),

            'eventos' => Bitacora::eventos(),
        ];

        return view('bitacora.index', $datos);
    }
}
