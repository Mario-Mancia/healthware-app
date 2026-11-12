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
        Schema::create('horarios_doctores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_doctor')
                ->constrained('doctores')
                ->cascadeOnDelete();

            // 1 = lunes ... 7 = domingo
            $table->unsignedTinyInteger('dia_semana');

            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_doctores');
    }
};
