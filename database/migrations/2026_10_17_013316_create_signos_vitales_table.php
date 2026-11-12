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
        Schema::create('signos_vitales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_registro_medico')
                ->constrained('registros_medicos')
                ->cascadeOnDelete();

            $table->decimal('peso', 5, 2)->nullable();
            $table->decimal('altura', 5, 2)->nullable();

            $table->decimal('temperatura', 4, 1)->nullable();

            $table->unsignedSmallInteger('frecuencia_cardiaca')->nullable();
            $table->unsignedSmallInteger('frecuencia_respiratoria')->nullable();

            $table->decimal('saturacion_oxigeno', 5, 2)->nullable();

            $table->unsignedSmallInteger('presion_sistolica')->nullable();
            $table->unsignedSmallInteger('presion_diastolica')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signos_vitales');
    }
};
