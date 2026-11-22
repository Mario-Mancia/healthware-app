<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anuncios con evento opcional, recordatorio, destinatario, confirmación
     * de recepción y archivos adjuntos.
     */
    public function up(): void
    {
        Schema::table('anuncios_globales', function (Blueprint $table) {
            // Datos extra del anuncio
            $table->string('lugar', 150)->nullable()->after('prioridad');
            $table->text('datos_extra')->nullable()->after('lugar');

            // Evento opcional: sin fecha, el anuncio es solo informativo
            $table->dateTime('fecha_evento')->nullable()->after('datos_extra');
            $table->dateTime('recordatorio_enviado_en')->nullable()->after('fecha_evento');

            // Destinatario opcional (solo él y el administrador ven el anuncio).
            // restrict: si el usuario desapareciera, el anuncio no puede volverse
            // público por accidente.
            $table->foreignId('id_destinatario')
                ->nullable()
                ->after('creado_por')
                ->constrained('usuarios')
                ->restrictOnDelete();

            // Confirmación de recepción (control y auditoría)
            $table->dateTime('confirmado_en')->nullable()->after('id_destinatario');
            $table->string('confirmado_ip', 45)->nullable()->after('confirmado_en');

            $table->index(['fecha_evento', 'recordatorio_enviado_en'], 'idx_anuncios_evento');
            $table->index(['id_destinatario', 'activo'], 'idx_anuncios_destinatario');
        });

        Schema::create('anuncio_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_anuncio')
                ->constrained('anuncios_globales')
                ->cascadeOnDelete();
            $table->string('nombre_original', 255);
            $table->string('ruta', 255);
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('tamanho');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncio_adjuntos');

        Schema::table('anuncios_globales', function (Blueprint $table) {
            $table->dropForeign(['id_destinatario']);
            $table->dropIndex('idx_anuncios_evento');
            $table->dropIndex('idx_anuncios_destinatario');
            $table->dropColumn([
                'lugar',
                'datos_extra',
                'fecha_evento',
                'recordatorio_enviado_en',
                'id_destinatario',
                'confirmado_en',
                'confirmado_ip',
            ]);
        });
    }
};
