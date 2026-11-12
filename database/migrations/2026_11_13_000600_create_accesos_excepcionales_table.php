<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Acceso de emergencia (break-glass) a un expediente sin relación
     * asistencial: exige justificación, dura un tiempo limitado y se revisa
     * después. No sustituye a las policies: es una excepción registrada.
     */
    public function up(): void
    {
        Schema::create('accesos_excepcionales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_paciente')
                ->constrained('pacientes')
                ->restrictOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->text('motivo');

            $table->dateTime('fecha_inicio')->useCurrent();
            $table->dateTime('fecha_expiracion');

            $table->enum('estado', [
                'activo',
                'revocado',
            ])->default('activo');

            // Revisión posterior por un administrador.
            $table->foreignId('revisado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->dateTime('revisado_en')->nullable();
            $table->text('observacion_revision')->nullable();

            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['id_usuario', 'id_paciente', 'estado']);
            $table->index('fecha_expiracion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accesos_excepcionales');
    }
};
