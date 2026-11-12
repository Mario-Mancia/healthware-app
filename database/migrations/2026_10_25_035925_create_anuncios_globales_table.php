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
        Schema::create('anuncios_globales', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 150);

            $table->text('mensaje');

            $table->enum('prioridad', [
                'baja',
                'normal',
                'alta',
                'urgente'
            ])->default('normal');

            $table->dateTime('inicia_en')->nullable();
            $table->dateTime('finaliza_en')->nullable();

            $table->boolean('activo')->default(true);

            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anuncios_globales');
    }
};
