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
        Schema::create('detalle_recetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_receta')
                ->constrained('recetas')
                ->cascadeOnDelete();

            $table->foreignId('id_medicamento')
                ->constrained('medicamentos')
                ->restrictOnDelete();

            $table->string('dosis', 100);
            $table->string('frecuencia', 100);
            $table->string('duracion', 100)->nullable();

            $table->string('via_administracion', 50)->nullable();

            $table->text('instrucciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_recetas');
    }
};
