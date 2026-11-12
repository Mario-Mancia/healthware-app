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
     * Auditoría de solo inserción de todo lo que ocurre con un expediente
     * clínico. No guarda contraseñas ni copias de expedientes: solo los datos
     * relevantes de cada acción.
     */
    public function up(): void
    {
        Schema::create('auditoria_expedientes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_paciente')
                ->constrained('pacientes')
                ->restrictOnDelete();

            // Nulo cuando la acción la realiza el sistema (por ejemplo, un comando).
            $table->foreignId('id_usuario')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->foreignId('id_doctor')
                ->nullable()
                ->constrained('doctores')
                ->nullOnDelete();

            $table->enum('tipo_accion', [
                'consulta',
                'creacion',
                'modificacion',
                'eliminacion',
                'restauracion',
                'descarga',
                'acceso_excepcional',
                'gestion_acceso',
            ]);

            $table->string('entidad', 60);
            $table->unsignedBigInteger('id_registro')->nullable();
            $table->string('motivo', 500)->nullable();
            $table->json('datos_relevantes')->nullable();
            $table->string('ip', 45)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['id_paciente', 'created_at']);
            $table->index(['id_usuario', 'created_at']);
            $table->index('tipo_accion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_expedientes');
    }
};
