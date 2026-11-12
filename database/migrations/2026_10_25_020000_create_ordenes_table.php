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
        Schema::create('ordenes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_paciente')
                ->constrained('pacientes')
                ->restrictOnDelete();

            $table->foreignId('id_registro_medico')
                ->nullable()
                ->constrained('registros_medicos')
                ->nullOnDelete();

            $table->foreignId('id_cita')
                ->nullable()
                ->constrained('citas')
                ->nullOnDelete();

            $table->string('numero_orden', 50)->unique();
            $table->dateTime('fecha_orden')->useCurrent();

            $table->enum('estado', [
                'pendiente',
                'facturada',
                'cancelada'
            ])->default('pendiente');

            $table->string('notas')->nullable();

            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
};
