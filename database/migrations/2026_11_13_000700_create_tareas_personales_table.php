<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Workspace personal: cada usuario ve y modifica solo sus tareas.
     * recordatorio_enviado_en y aviso_vencimiento_enviado_en hacen que el
     * comando programado sea seguro aunque se ejecute más de una vez.
     */
    public function up(): void
    {
        Schema::create('tareas_personales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->cascadeOnDelete();

            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();

            $table->enum('prioridad', [
                'baja',
                'normal',
                'alta',
                'urgente',
            ])->default('normal');

            $table->enum('estado', [
                'pendiente',
                'en_progreso',
                'completada',
                'cancelada',
            ])->default('pendiente');

            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_vencimiento')->nullable();
            $table->dateTime('fecha_recordatorio')->nullable();
            $table->dateTime('fecha_completada')->nullable();

            $table->dateTime('recordatorio_enviado_en')->nullable();
            $table->dateTime('aviso_vencimiento_enviado_en')->nullable();

            $table->timestamps();

            $table->index(['id_usuario', 'estado']);
            $table->index('fecha_recordatorio');
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tareas_personales');
    }
};
