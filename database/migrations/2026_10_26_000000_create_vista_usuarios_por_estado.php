<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Vista con el total de usuarios por estado (la usa el dashboard).
     */
    public function up(): void
    {
        DB::statement('
            CREATE OR REPLACE VIEW vista_usuarios_por_estado AS
            SELECT
                e.estado AS nombre_estado,
                COUNT(u.id) AS total_usuarios
            FROM estados e
            LEFT JOIN usuarios u ON u.id_estado = e.id
            GROUP BY e.id, e.estado
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vista_usuarios_por_estado');
    }
};
