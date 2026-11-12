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
        Schema::create('registros_medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')
                ->constrained('pacientes')
                ->restrictOnDelete();

            $table->foreignId('id_doctor')
                ->constrained('doctores')
                ->restrictOnDelete();

            $table->foreignId('id_cita')
                ->nullable()
                ->constrained('citas')
                ->nullOnDelete();

            $table->dateTime('fecha_registro')->useCurrent();

            $table->text('motivo_consulta')->nullable();
            $table->text('notas_clinicas')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['id_doctor', 'id_paciente']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_medicos');
    }
};
