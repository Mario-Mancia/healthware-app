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
     * Una referencia puede originar una sola cita: la restricción única evita
     * que dos programaciones simultáneas creen citas duplicadas.
     */
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->foreignId('id_referencia_medica')
                ->nullable()
                ->after('id_doctor')
                ->unique()
                ->constrained('referencias_medicas')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_referencia_medica');
        });
    }
};
