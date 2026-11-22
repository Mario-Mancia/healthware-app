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
     * Pizarrón de tareas personales:
     *  - deleted_at: la papelera. Una tarea fuera del pizarrón es una tarea en la
     *    papelera; allí permanece 30 días y luego se elimina de verdad.
     *  - motivo_papelera: por qué salió del pizarrón (manual, completada,
     *    cancelada o vencida).
     *
     * Los datos existentes se acomodan a las reglas nuevas: las tareas cerradas y
     * vencidas pasan a la papelera, las abiertas sin vencimiento reciben uno a
     * 7 días y, si un usuario tenía más de 20 en el pizarrón, las más antiguas
     * pasan a la papelera.
     */
    public function up(): void
    {
        Schema::table('tareas_personales', function (Blueprint $table) {
            $table->enum('motivo_papelera', [
                'manual',
                'completada',
                'cancelada',
                'vencida',
            ])->nullable()->after('estado');

            $table->softDeletes();

            $table->index(['id_usuario', 'deleted_at']);
            $table->index('deleted_at');
        });

        $ahora = now();

        DB::table('tareas_personales')
            ->whereNull('fecha_vencimiento')
            ->whereIn('estado', ['pendiente', 'en_progreso'])
            ->update(['fecha_vencimiento' => $ahora->copy()->addDays(7)]);

        DB::table('tareas_personales')
            ->whereNull('deleted_at')
            ->where('estado', 'completada')
            ->update(['deleted_at' => $ahora, 'motivo_papelera' => 'completada']);

        DB::table('tareas_personales')
            ->whereNull('deleted_at')
            ->where('estado', 'cancelada')
            ->update(['deleted_at' => $ahora, 'motivo_papelera' => 'cancelada']);

        DB::table('tareas_personales')
            ->whereNull('deleted_at')
            ->whereIn('estado', ['pendiente', 'en_progreso'])
            ->where('fecha_vencimiento', '<', $ahora)
            ->update(['deleted_at' => $ahora, 'motivo_papelera' => 'vencida']);

        $usuariosExcedidos = DB::table('tareas_personales')
            ->whereNull('deleted_at')
            ->select('id_usuario')
            ->groupBy('id_usuario')
            ->havingRaw('COUNT(*) > 20')
            ->pluck('id_usuario');

        foreach ($usuariosExcedidos as $idUsuario) {
            $sobrantes = DB::table('tareas_personales')
                ->whereNull('deleted_at')
                ->where('id_usuario', $idUsuario)
                ->orderByDesc('id')
                ->pluck('id')
                ->slice(20)
                ->values();

            foreach ($sobrantes->chunk(500) as $grupo) {
                DB::table('tareas_personales')
                    ->whereIn('id', $grupo->all())
                    ->update(['deleted_at' => $ahora, 'motivo_papelera' => 'manual']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tareas_personales', function (Blueprint $table) {
            $table->dropIndex(['id_usuario', 'deleted_at']);
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
            $table->dropColumn('motivo_papelera');
        });
    }
};
