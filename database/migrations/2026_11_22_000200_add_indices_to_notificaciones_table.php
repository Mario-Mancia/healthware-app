<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Índices para las consultas del módulo: la bandeja de cada usuario (por
     * fecha y estado de lectura) y la purga de las notificaciones de más de
     * 30 días.
     */
    public function up(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->index(['id_usuario', 'created_at']);
            $table->index(['id_usuario', 'leido_en']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropIndex(['id_usuario', 'created_at']);
            $table->dropIndex(['id_usuario', 'leido_en']);
            $table->dropIndex(['created_at']);
        });
    }
};
