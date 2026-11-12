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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_rol')
                ->constrained('roles')
                ->restrictOnDelete();

            $table->foreignId('id_estado')
                ->constrained('estados')
                ->restrictOnDelete();

            $table->string('nombres', 100);
            $table->string('apellidos', 100);

            $table->string('correo', 150)
                ->unique();

            $table->string('contrasenha', 250);

            $table->string('telefono', 25)
                ->nullable();

            $table->string('foto_perfil', 255)
                ->nullable();

            $table->boolean('debe_cambiar_contrasenha')
                ->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
