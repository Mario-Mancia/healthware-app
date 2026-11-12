<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaExpediente;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consulta de la auditoría del expediente clínico (solo administrador).
 * La auditoría es de solo lectura: no hay operaciones para modificarla.
 */
class AuditoriaExpedienteController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'dui' => ['nullable', 'string', 'max:10'],
            'tipo_accion' => ['nullable', 'in:' . implode(',', AuditoriaExpediente::ACCIONES)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $idPaciente = null;
        $duiSinResultado = false;

        if (!empty($filtros['dui'])) {
            $paciente = Paciente::withTrashed()
                ->where('numero_identificacion', trim($filtros['dui']))
                ->first();

            $idPaciente = $paciente?->getKey();
            $duiSinResultado = $paciente === null;
        }

        $eventos = $duiSinResultado
            ? collect()
            : AuditoriaExpediente::mostrarFiltrados([
                'id_paciente' => $idPaciente,
                'tipo_accion' => $filtros['tipo_accion'] ?? null,
                'desde' => $filtros['desde'] ?? null,
                'hasta' => $filtros['hasta'] ?? null,
            ]);

        return view('auditoria.index', [
            'eventos' => $eventos,
            'filtros' => $filtros,
            'acciones' => AuditoriaExpediente::ACCIONES,
            'duiSinResultado' => $duiSinResultado,
        ]);
    }
}
