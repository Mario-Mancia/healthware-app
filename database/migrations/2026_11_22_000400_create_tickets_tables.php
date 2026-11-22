<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reportes de soporte (tickets): el reporte, sus adjuntos y su seguimiento.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // Quien reporta y el administrador que lo atiende. restrict: los
            // usuarios no se borran; si ocurriera, el historial no se pierde.
            $table->foreignId('id_usuario')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('id_admin')->nullable()->constrained('usuarios')->restrictOnDelete();

            $table->string('titulo', 150);
            $table->string('tipo', 30);
            $table->string('prioridad', 20)->default('normal');
            $table->string('estado', 20)->default('abierto');
            $table->string('modulo_afectado', 100)->nullable();
            $table->text('descripcion');
            $table->text('resolucion')->nullable();

            $table->dateTime('tomado_en')->nullable();
            $table->dateTime('finalizado_en')->nullable();
            $table->timestamps();

            $table->index(['id_usuario', 'estado'], 'idx_tickets_usuario');
            $table->index(['id_admin', 'estado'], 'idx_tickets_admin');
            $table->index(['estado', 'id_admin'], 'idx_tickets_estado');
        });

        Schema::create('ticket_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ticket')->constrained('tickets')->cascadeOnDelete();
            $table->string('nombre_original', 255);
            $table->string('ruta', 255);
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('tamanho');
            $table->timestamps();
        });

        // Línea de tiempo: cada cambio de estado y cada nota queda registrado.
        Schema::create('ticket_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ticket')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('id_usuario')->constrained('usuarios')->restrictOnDelete();
            $table->string('evento', 30);
            $table->string('estado_nuevo', 20)->nullable();
            $table->text('comentario')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['id_ticket', 'id'], 'idx_seguimientos_ticket');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_seguimientos');
        Schema::dropIfExists('ticket_adjuntos');
        Schema::dropIfExists('tickets');
    }
};
