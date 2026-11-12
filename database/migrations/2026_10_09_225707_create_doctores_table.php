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
        Schema::create('doctores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')
                ->constrained('usuarios');
            $table->foreignId('especialidad_id')
                ->constrained('especialidades');
            $table->string('dui', 10)
                ->unique();
            $table->string('numero_licencia', 50)
                ->unique();
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['M', 'F']);
            $table->text('descripcion_profesional')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctores');
    }
};
