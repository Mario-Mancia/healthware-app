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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);

            $table->date('fecha_nacimiento');

            $table->enum('genero', [
                'Masculino',
                'Femenino'
            ]);

            $table->foreignId('id_tipo_sanguineo')
                ->nullable()
                ->constrained('tipos_sanguineos')
                ->nullOnDelete();

            $table->string('numero_identificacion', 10)->unique();

            $table->string('telefono', 25)->nullable();
            $table->string('correo', 150)->nullable();

            $table->string('direccion')->nullable();

            $table->string('nombre_contacto_emergencia', 150)->nullable();
            $table->string('telefono_contacto_emergencia', 25)->nullable();
            $table->string('parentesco_contacto_emergencia', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
