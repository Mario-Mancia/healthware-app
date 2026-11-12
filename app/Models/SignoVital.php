<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignoVital extends Model
{
    use \App\Models\Concerns\RegistraAutoria;

    use HasFactory;

    protected $table = 'signos_vitales';

    protected $fillable = [
        'id_usuario_creacion',
        'id_registro_medico',
        'peso',
        'altura',
        'temperatura',
        'frecuencia_cardiaca',
        'frecuencia_respiratoria',
        'saturacion_oxigeno',
        'presion_sistolica',
        'presion_diastolica',
    ];

    protected $casts = [
        'id_registro_medico' => 'integer',
        'peso' => 'decimal:2',
        'altura' => 'decimal:2',
        'temperatura' => 'decimal:1',
        'frecuencia_cardiaca' => 'integer',
        'frecuencia_respiratoria' => 'integer',
        'saturacion_oxigeno' => 'decimal:2',
        'presion_sistolica' => 'integer',
        'presion_diastolica' => 'integer',
    ];

    // Relaciones

    public function registroMedico(): BelongsTo
    {
        return $this->belongsTo(
            RegistroMedico::class,
            'id_registro_medico'
        )->withTrashed();
    }
}
