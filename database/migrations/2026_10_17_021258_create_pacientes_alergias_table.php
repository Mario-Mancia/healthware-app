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
        Schema::create('pacientes_alergias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')
                ->constrained('pacientes')
                ->cascadeOnDelete();

            $table->foreignId('id_alergia')
                ->constrained('alergias')
                ->restrictOnDelete();

            $table->enum('gravedad', [
                'leve',
                'moderada',
                'grave',
                'desconocida'
            ])->default('desconocida');

            $table->string('reaccion', 250)->nullable();
            $table->string('notas', 250)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes_alergias');
    }
};
