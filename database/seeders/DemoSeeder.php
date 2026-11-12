<?php

namespace Database\Seeders;

use App\Models\Cita;
use App\Models\Doctor;
use App\Models\Especialidad;
use App\Models\Estado;
use App\Models\Factura;
use App\Models\HorarioDoctor;
use App\Models\Orden;
use App\Models\Paciente;
use App\Models\RegistroMedico;
use App\Models\Rol;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Datos de demostración para desarrollo (solo se ejecuta en el entorno local).
 * Crea un usuario por rol, un doctor con su horario, pacientes, citas,
 * atenciones realizadas, órdenes y facturas.
 */
class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * La contraseña de los usuarios de demostración puede definirse en el
     * archivo .env con DEMO_CONTRASENHA.
     */
    public function run(): void
    {
        $contrasenha = env('DEMO_CONTRASENHA', 'Demo#2026');

        $medico = $this->crearPersonal($contrasenha);

        $pacientes = Paciente::factory()->count(25)->create();

        $this->crearCitasProgramadas($medico, $pacientes->take(12)->values());
        $this->crearAtencionesRealizadas($medico, $pacientes->slice(12)->values());
    }

    /**
     * Crea un usuario de demostración y devuelve el modelo.
     */
    private function crearUsuario(
        string $rol,
        string $nombres,
        string $apellidos,
        string $correo,
        string $contrasenha
    ): Usuario {
        return Usuario::updateOrCreate(
            [
                'correo' => $correo,
            ],
            [
                'id_rol' => Rol::where('rol', $rol)->value('id'),
                'id_estado' => Estado::where('estado', 'ACTIVO')->value('id'),
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'contrasenha' => $contrasenha,
                'telefono' => '+503 7000-0000',
                'debe_cambiar_contrasenha' => false,
            ]
        );
    }

    /**
     * Crea el personal de demostración y devuelve el doctor.
     */
    private function crearPersonal(string $contrasenha): Doctor
    {
        $this->crearUsuario(
            Usuario::ROL_RECEPCIONISTA,
            'Marta',
            'Recinos',
            'recepcion@healthware.com',
            $contrasenha
        );

        $this->crearUsuario(
            Usuario::ROL_CONTADOR,
            'Carlos',
            'Contreras',
            'contador@healthware.com',
            $contrasenha
        );

        $usuarioMedico = $this->crearUsuario(
            Usuario::ROL_MEDICO,
            'Laura',
            'Hernández',
            'medico@healthware.com',
            $contrasenha
        );

        $doctor = Doctor::updateOrCreate(
            [
                'usuario_id' => $usuarioMedico->getKey(),
            ],
            [
                'especialidad_id' => Especialidad::query()->orderBy('id')->value('id'),
                'dui' => '04215894-6',
                'numero_licencia' => 'JVPM-00001',
                'fecha_nacimiento' => '1985-04-12',
                'sexo' => 'F',
                'descripcion_profesional' => 'Doctora de demostración para pruebas del sistema.',
            ]
        );

        for ($dia = 1; $dia <= 5; $dia++) {
            foreach ([['08:00', '12:00'], ['13:00', '17:00']] as [$inicio, $fin]) {
                HorarioDoctor::firstOrCreate([
                    'id_doctor' => $doctor->getKey(),
                    'dia_semana' => $dia,
                    'hora_inicio' => $inicio,
                    'hora_fin' => $fin,
                ]);
            }
        }

        return $doctor;
    }

    /**
     * Citas programadas en los próximos días hábiles, dentro del horario del
     * doctor (08:00 a 12:00) y sin cruces: seis citas de 30 minutos por día.
     */
    private function crearCitasProgramadas(Doctor $medico, Collection $pacientes): void
    {
        foreach ($pacientes as $indice => $paciente) {
            $fecha = now()->startOfDay()->addWeekdays(intdiv($indice, 6) + 1);
            $inicio = $fecha->copy()->setTime(8, 0)->addMinutes(30 * ($indice % 6));

            Cita::create([
                'id_paciente' => $paciente->getKey(),
                'id_doctor' => $medico->getKey(),
                'fecha_cita' => $fecha->toDateString(),
                'hora_inicio' => $inicio->format('H:i'),
                'hora_fin' => $inicio->copy()->addMinutes(30)->format('H:i'),
                'estado' => 'programada',
                'motivo' => 'Consulta de control',
            ]);
        }
    }

    /**
     * Atenciones ya realizadas en días hábiles anteriores. Cada una tiene su
     * cita, su registro médico y su orden de servicios; la mitad ya está
     * facturada y la otra mitad espera al contador.
     */
    private function crearAtencionesRealizadas(Doctor $medico, Collection $pacientes): void
    {
        $idServicio = Servicio::query()->orderBy('id')->value('id');

        foreach ($pacientes as $indice => $paciente) {
            $fecha = now()->startOfDay()->subWeekdays(intdiv($indice, 6) + 1);
            $inicio = $fecha->copy()->setTime(8, 0)->addMinutes(30 * ($indice % 6));

            $cita = Cita::create([
                'id_paciente' => $paciente->getKey(),
                'id_doctor' => $medico->getKey(),
                'fecha_cita' => $fecha->toDateString(),
                'hora_inicio' => $inicio->format('H:i'),
                'hora_fin' => $inicio->copy()->addMinutes(30)->format('H:i'),
                'estado' => 'completada',
                'motivo' => 'Consulta general',
            ]);

            $registro = RegistroMedico::create([
                'id_paciente' => $paciente->getKey(),
                'id_doctor' => $medico->getKey(),
                'id_cita' => $cita->getKey(),
                'fecha_registro' => $inicio,
                'motivo_consulta' => 'Consulta general',
                'notas_clinicas' => 'Paciente estable, sin hallazgos relevantes.',
            ]);

            $orden = Orden::insertar([
                'id_paciente' => $paciente->getKey(),
                'id_registro_medico' => $registro->getKey(),
                'id_cita' => $cita->getKey(),
                'detalles' => [
                    [
                        'id_servicio' => $idServicio,
                        'cantidad' => 1,
                    ],
                ],
            ]);

            if ($indice % 2 === 0) {
                Factura::emitirDesdeOrden($orden->getKey());
            }
        }
    }
}
