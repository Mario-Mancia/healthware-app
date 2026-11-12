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
     * El enlace permite abrir desde la notificación la pantalla relacionada
     * (una referencia, una cita, una tarea...).
     */
    public function up(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->string('enlace', 255)->nullable()->after('mensaje');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropColumn('enlace');
        });
    }
};
