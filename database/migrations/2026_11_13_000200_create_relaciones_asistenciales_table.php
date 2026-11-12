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
     * Vínculo entre un paciente y un médico que participa en su atención. No
     * hay restricción única sobre (paciente, doctor): se conservan todos los
     * episodios asistenciales. Una relación cerrada queda como historial.
     */
    public function up(): void
    {
        Schema::create('relaciones_asistenciales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_paciente')
                ->constrained('pacientes')
                ->restrictOnDelete();

            $table->foreignId('id_doctor')
                ->constrained('doctores')
                ->restrictOnDelete();

            $table->foreignId('id_cita')
                ->nullable()
                ->constrained('citas')
                ->nullOnDelete();

            $table->foreignId('id_referencia_medica')
                ->nullable()
                ->constrained('referencias_medicas')
                ->nullOnDelete();

            $table->enum('tipo_relacion', [
                'principal',
                'especializada',
                'referencia',
                'seguimiento',
            ])->default('principal');

            $table->enum('estado', [
                'activa',
                'suspendida',
                'cerrada',
                'revocada',
            ])->default('activa');

            $table->dateTime('fecha_inicio')->useCurrent();
            $table->dateTime('fecha_fin')->nullable();

            $table->string('motivo', 255);

            // Cierre explícito: fecha, motivo y quién lo realizó.
            $table->string('motivo_cierre', 255)->nullable();
            $table->foreignId('id_usuario_cierre')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['id_paciente', 'id_doctor', 'estado']);
            $table->index(['id_doctor', 'estado']);
        });

        $this->conservarAtencionesExistentes();
    }

    /**
     * Las citas vigentes y las atenciones ya registradas antes de esta
     * migración generan su relación asistencial, para que ningún médico
     * pierda el acceso a los pacientes que ya atendía.
     */
    private function conservarAtencionesExistentes(): void
    {
        $ahora = now();
        $vistos = [];

        $citas = DB::table('citas')
            ->whereNotIn('estado', ['cancelada', 'no_asistio'])
            ->orderBy('id')
            ->get(['id', 'id_paciente', 'id_doctor']);

        foreach ($citas as $cita) {
            $clave = $cita->id_paciente . '-' . $cita->id_doctor;

            if (isset($vistos[$clave])) {
                continue;
            }

            $vistos[$clave] = true;

            DB::table('relaciones_asistenciales')->insert([
                'id_paciente' => $cita->id_paciente,
                'id_doctor' => $cita->id_doctor,
                'id_cita' => $cita->id,
                'tipo_relacion' => 'principal',
                'estado' => 'activa',
                'fecha_inicio' => $ahora,
                'motivo' => 'Relación generada a partir de una cita existente.',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        $registros = DB::table('registros_medicos')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id_paciente', 'id_doctor']);

        foreach ($registros as $registro) {
            $clave = $registro->id_paciente . '-' . $registro->id_doctor;

            if (isset($vistos[$clave])) {
                continue;
            }

            $vistos[$clave] = true;

            DB::table('relaciones_asistenciales')->insert([
                'id_paciente' => $registro->id_paciente,
                'id_doctor' => $registro->id_doctor,
                'tipo_relacion' => 'seguimiento',
                'estado' => 'activa',
                'fecha_inicio' => $ahora,
                'motivo' => 'Relación generada a partir de una atención ya registrada.',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relaciones_asistenciales');
    }
};
