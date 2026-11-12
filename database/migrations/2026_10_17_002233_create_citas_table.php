<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')
                ->constrained('pacientes')
                ->restrictOnDelete();

            $table->foreignId('id_doctor')
                ->constrained('doctores')
                ->restrictOnDelete();

            $table->date('fecha_cita');

            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->enum('estado', [
                'programada',
                'completada',
                'cancelada',
                'no_asistio',
                'reprogramada'
            ])->default('programada');

            $table->string('motivo')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['id_doctor', 'fecha_cita']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
