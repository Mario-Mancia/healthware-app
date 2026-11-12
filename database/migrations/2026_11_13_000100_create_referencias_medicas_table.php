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
     * Una referencia es la solicitud de un médico para que otro especialista
     * (o un médico de cierta especialidad) atienda a un paciente. No crea la
     * cita: recepción la programa después.
     */
    public function up(): void
    {
        Schema::create('referencias_medicas', function (Blueprint $table) {
            $table->id();

            $table->string('numero_referencia', 20)->unique();

            $table->foreignId('id_paciente')
                ->constrained('pacientes')
                ->restrictOnDelete();

            $table->foreignId('id_doctor_origen')
                ->constrained('doctores')
                ->restrictOnDelete();

            $table->foreignId('id_doctor_destino')
                ->nullable()
                ->constrained('doctores')
                ->restrictOnDelete();

            $table->foreignId('id_especialidad_destino')
                ->nullable()
                ->constrained('especialidades')
                ->restrictOnDelete();

            $table->foreignId('id_registro_medico')
                ->nullable()
                ->constrained('registros_medicos')
                ->nullOnDelete();

            $table->string('motivo', 255);
            $table->text('resumen_clinico')->nullable();
            $table->text('estudios_solicitados')->nullable();

            $table->enum('prioridad', [
                'normal',
                'prioritaria',
                'urgente',
            ])->default('normal');

            $table->enum('estado', [
                'pendiente',
                'programada',
                'atendida',
                'rechazada',
                'cancelada',
                'vencida',
            ])->default('pendiente');

            $table->dateTime('fecha_referencia')->useCurrent();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['estado', 'prioridad']);
            $table->index('id_doctor_origen');
            $table->index('id_doctor_destino');
            $table->index('id_paciente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referencias_medicas');
    }
};
