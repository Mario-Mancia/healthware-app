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
        Schema::create('registro_medico_diagnosticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_registro_medico')
                ->constrained('registros_medicos')
                ->cascadeOnDelete();

            $table->foreignId('id_diagnostico')
                ->constrained('diagnosticos')
                ->restrictOnDelete();

            $table->string('notas', 250)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_medico_diagnosticos');
    }
};
