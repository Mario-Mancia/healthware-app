<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas clínicas que registran qué usuario hizo cada alta. El médico
     * responsable sigue siendo id_doctor en registros_medicos; id_usuario_creacion
     * identifica al usuario autenticado que realizó la operación.
     */
    private array $tablas = [
        'registros_medicos',
        'signos_vitales',
        'registro_medico_diagnosticos',
        'recetas',
        'detalle_recetas',
        'pacientes_alergias',
        'pacientes_condiciones',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('id_usuario_creacion')
                    ->nullable()
                    ->constrained('usuarios')
                    ->nullOnDelete();
            });
        }

        // Los registros que ya existían se atribuyen al usuario de su médico.
        DB::statement(
            'UPDATE registros_medicos r
             JOIN doctores d ON d.id = r.id_doctor
             SET r.id_usuario_creacion = d.usuario_id
             WHERE r.id_usuario_creacion IS NULL'
        );

        DB::statement(
            'UPDATE signos_vitales s
             JOIN registros_medicos r ON r.id = s.id_registro_medico
             SET s.id_usuario_creacion = r.id_usuario_creacion
             WHERE s.id_usuario_creacion IS NULL'
        );

        DB::statement(
            'UPDATE registro_medico_diagnosticos x
             JOIN registros_medicos r ON r.id = x.id_registro_medico
             SET x.id_usuario_creacion = r.id_usuario_creacion
             WHERE x.id_usuario_creacion IS NULL'
        );

        DB::statement(
            'UPDATE recetas x
             JOIN registros_medicos r ON r.id = x.id_registro_medico
             SET x.id_usuario_creacion = r.id_usuario_creacion
             WHERE x.id_usuario_creacion IS NULL'
        );

        DB::statement(
            'UPDATE detalle_recetas x
             JOIN recetas r ON r.id = x.id_receta
             SET x.id_usuario_creacion = r.id_usuario_creacion
             WHERE x.id_usuario_creacion IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->tablas) as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('id_usuario_creacion');
            });
        }
    }
};
