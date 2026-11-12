#!/usr/bin/env bash
# =============================================================================
#  fase-03c-mejoras-asistenciales.sh  ·  HealthWare (Laravel 12 + MySQL)
# -----------------------------------------------------------------------------
#  Fase intermedia de corrección y mejoras (antes de la fase 4): relaciones
#  asistenciales, referencias médicas, trazabilidad del expediente, acceso
#  excepcional, policies y workspace de tareas personales.
#
#  Crea a mano (sin php artisan make:*) todos los archivos:
#    database/migrations   8 migraciones nuevas (referencias, relaciones
#                          asistenciales, referencia en citas, autoría clínica,
#                          auditoría, accesos excepcionales, tareas, enlace de
#                          notificaciones)
#    app/Models            RelacionAsistencial, ReferenciaMedica, AuditoriaExpediente,
#                          AccesoExcepcional, TareaPersonal y el trait RegistraAutoria
#    app/Policies          PacientePolicy y RegistroMedicoPolicy (solo expediente clínico)
#    app/Services          acceso asistencial, referencias, citas, acceso excepcional
#                          y recordatorios de tareas
#    app/Events + Listeners  avisos que se envían al confirmar la transacción
#    app/Console           comandos programados (tareas:recordatorios, referencias:vencer)
#    app/Http              controladores y solicitudes nuevos; los clínicos existentes
#                          pasan a usar las policies, la autoría y la auditoría
#    routes, resources, public   rutas, vistas y scripts de los módulos nuevos
#
#  Requiere las fases 1, 2, 3A y 3B. No ejecuta PHP, Composer ni artisan: las
#  migraciones las corre usted (php artisan migrate).
#
#  Antes de sobrescribir, copia los archivos existentes que va a modificar a
#  .respaldos-fases/. Si usted editó a mano alguno de ellos, revise el respaldo.
#
#  Colócalo en la raíz del proyecto, junto a "artisan":
#
#    ./fase-03c-mejoras-asistenciales.sh --simular
#    ./fase-03c-mejoras-asistenciales.sh
#
#  Opciones:
#    --simular        Muestra qué se haría y termina sin modificar archivos.
#    --sin-respaldo   No copia lo existente a .respaldos-fases/ antes de escribir.
#    -h, --ayuda      Muestra esta ayuda.
# =============================================================================
set -Eeuo pipefail

# ----------------------------- Configuración ---------------------------------
readonly DIR_RESPALDOS=".respaldos-fases"
readonly ARCHIVO_TAREAS="TASKLIST_USUARIO.txt"

# Todos los archivos que escribe esta fase (para el plan y la verificación).
readonly -a ARCHIVOS_FASE=(
  "database/migrations/2026_11_13_000100_create_referencias_medicas_table.php"
  "database/migrations/2026_11_13_000200_create_relaciones_asistenciales_table.php"
  "database/migrations/2026_11_13_000300_add_id_referencia_medica_to_citas_table.php"
  "database/migrations/2026_11_13_000400_add_autoria_clinica_to_tablas_clinicas.php"
  "database/migrations/2026_11_13_000500_create_auditoria_expedientes_table.php"
  "database/migrations/2026_11_13_000600_create_accesos_excepcionales_table.php"
  "database/migrations/2026_11_13_000700_create_tareas_personales_table.php"
  "database/migrations/2026_11_13_000800_add_enlace_to_notificaciones_table.php"
  "app/Models/Concerns/RegistraAutoria.php"
  "app/Models/RelacionAsistencial.php"
  "app/Models/ReferenciaMedica.php"
  "app/Models/AuditoriaExpediente.php"
  "app/Models/AccesoExcepcional.php"
  "app/Models/TareaPersonal.php"
  "app/Policies/PacientePolicy.php"
  "app/Policies/RegistroMedicoPolicy.php"
  "app/Services/AccesoAsistencialService.php"
  "app/Services/ReferenciaMedicaService.php"
  "app/Services/CitaService.php"
  "app/Services/AccesoExcepcionalService.php"
  "app/Services/RecordatorioTareasService.php"
  "app/Events/ReferenciaMedicaCreada.php"
  "app/Events/CitaProgramada.php"
  "app/Events/CitaCancelada.php"
  "app/Events/ReferenciaMedicaAtendida.php"
  "app/Events/RegistroClinicoActualizado.php"
  "app/Events/AccesoExpedienteRegistrado.php"
  "app/Events/TareaPorVencer.php"
  "app/Listeners/NotificarReferenciaPendiente.php"
  "app/Listeners/NotificarCitaAsignada.php"
  "app/Listeners/NotificarCitaCancelada.php"
  "app/Listeners/NotificarReferenciaAtendida.php"
  "app/Listeners/NotificarActualizacionClinica.php"
  "app/Listeners/NotificarAlertaAuditoria.php"
  "app/Listeners/NotificarTarea.php"
  "app/Console/Commands/ProcesarRecordatoriosTareas.php"
  "app/Console/Commands/VencerReferenciasMedicas.php"
  "routes/console.php"
  "app/Http/Controllers/ReferenciaMedicaController.php"
  "app/Http/Controllers/RelacionAsistencialController.php"
  "app/Http/Controllers/AuditoriaExpedienteController.php"
  "app/Http/Controllers/AccesoExcepcionalController.php"
  "app/Http/Controllers/TareaPersonalController.php"
  "app/Http/Requests/InsertarReferenciaMedicaRequest.php"
  "app/Http/Requests/ProgramarReferenciaMedicaRequest.php"
  "app/Http/Requests/MotivoReferenciaMedicaRequest.php"
  "app/Http/Requests/GestionarRelacionAsistencialRequest.php"
  "app/Http/Requests/SolicitarAccesoExcepcionalRequest.php"
  "app/Http/Requests/RevisarAccesoExcepcionalRequest.php"
  "app/Http/Requests/GuardarTareaRequest.php"
  "app/Http/Requests/CambiarEstadoTareaRequest.php"
  "routes/web.php"
  "app/Http/Controllers/Controller.php"
  "app/Http/Controllers/PacienteController.php"
  "app/Http/Controllers/RegistroMedicoController.php"
  "app/Http/Controllers/PacienteAlergiaController.php"
  "app/Http/Controllers/PacienteCondicionController.php"
  "app/Http/Controllers/RegistroMedicoDiagnosticoController.php"
  "app/Http/Controllers/RecetaController.php"
  "app/Http/Controllers/DetalleRecetaController.php"
  "app/Http/Controllers/CitaController.php"
  "app/Http/Requests/SolicitudConPaciente.php"
  "app/Http/Requests/InsertarRegistroMedicoDiagnosticoRequest.php"
  "app/Http/Requests/InsertarRecetaRequest.php"
  "app/Http/Requests/InsertarDetalleRecetaRequest.php"
  "app/Models/RegistroMedico.php"
  "app/Models/SignoVital.php"
  "app/Models/RegistroMedicoDiagnostico.php"
  "app/Models/Receta.php"
  "app/Models/DetalleReceta.php"
  "app/Models/PacienteAlergia.php"
  "app/Models/PacienteCondicion.php"
  "app/Models/Paciente.php"
  "app/Models/Cita.php"
  "app/Models/Notificacion.php"
  "database/seeders/DemoSeeder.php"
  "resources/views/layouts/app_layout.blade.php"
  "resources/views/layouts/partials/menu_config.php"
  "resources/views/pacientes/expediente.blade.php"
  "resources/views/citas/detalle.blade.php"
  "public/js/hw/hw-accion-motivo.js"
  "resources/views/referencias/index.blade.php"
  "public/js/referencia/referencias.js"
  "resources/views/relaciones/index.blade.php"
  "resources/views/auditoria/index.blade.php"
  "resources/views/accesos/index.blade.php"
  "public/js/acceso/accesos.js"
  "resources/views/pacientes/acceso_excepcional.blade.php"
  "resources/views/tareas/index.blade.php"
  "public/js/tarea/tareas.js"
  "public/css/clinico.css"
)

DIR_BASE="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly DIR_BASE

SIMULAR=0
SIN_RESPALDO=0

# ------------------------------- Mensajes ------------------------------------
if [[ -t 1 ]]; then
  C_INFO=$'\033[1;34m'; C_OK=$'\033[1;32m'; C_AVISO=$'\033[1;33m'
  C_ERROR=$'\033[1;31m'; C_CMD=$'\033[0;36m'; C_FIN=$'\033[0m'
else
  C_INFO=''; C_OK=''; C_AVISO=''; C_ERROR=''; C_CMD=''; C_FIN=''
fi

log()  { printf '%s[INFO]%s  %s\n' "$C_INFO" "$C_FIN" "$*"; }
ok()   { printf '%s[ OK ]%s  %s\n' "$C_OK" "$C_FIN" "$*"; }
warn() { printf '%s[AVISO]%s %s\n' "$C_AVISO" "$C_FIN" "$*" >&2; }
die()  { printf '%s[ERROR]%s %s\n' "$C_ERROR" "$C_FIN" "$*" >&2; exit 1; }

trap 'printf "%s[ERROR]%s Falló la línea %s: %s\n" "$C_ERROR" "$C_FIN" "$LINENO" "$BASH_COMMAND" >&2' ERR

uso() {
  cat <<'AYUDA'
Uso: ./fase-01-datos.sh [opciones]

Fase 1 de HealthWare: escribe migraciones, seeders, factories y modelos.
No ejecuta PHP ni artisan; solo crea archivos.

Opciones:
  --simular        Muestra qué se haría y termina sin modificar archivos.
  --sin-respaldo   No copia lo existente a .respaldos-fases/ antes de escribir.
  -h, --ayuda      Muestra esta ayuda.

Colócalo en la raíz del proyecto, junto a "artisan".
AYUDA
}

# ------------------- Navegación entre directorios ----------------------------
# Se usa la pila de pushd/popd: cada "descender" tiene su "ascender", de modo
# que siempre se regresa exactamente al directorio anterior.

# Muestra la ruta actual relativa a la raíz del proyecto.
ruta_actual() {
  if [[ "$PWD" == "$DIR_BASE" ]]; then
    printf '.'
  else
    printf '%s' "${PWD#"$DIR_BASE"/}"
  fi
}

# Entra a un directorio (hacia abajo).
descender() {
  local destino="$1"
  [[ -d "$destino" ]] || die "No existe el directorio '$destino' (estoy en $(ruta_actual))"
  pushd "$destino" >/dev/null
  log "↓ Entré a: $(ruta_actual)"
}

# Regresa al directorio desde el que se llamó al último "descender".
ascender() {
  popd >/dev/null || die "La pila de directorios está vacía"
  log "↑ Regresé a: $(ruta_actual)"
}

# Vuelve a la raíz del proyecto y limpia la pila de directorios.
ir_a_base() {
  cd -- "$DIR_BASE"
  dirs -c
}

# ------------------------- Ejecución de comandos -----------------------------
# Ejecuta un comando, espera a que termine y se detiene si falla.
ejecutar() {
  printf '%s$ %s%s\n' "$C_CMD" "$*" "$C_FIN"
  if ! "$@"; then
    die "El comando falló: $*"
  fi
}

# Escribe en un archivo el contenido recibido por la entrada estándar. Si no se
# puede sobrescribir, lo elimina y lo recrea.
escribir_archivo() {
  local destino="$1"
  local temporal
  temporal="$(mktemp)"
  cat > "$temporal"
  mkdir -p -- "$(dirname -- "$destino")"

  if [[ -e "$destino" && ! -w "$destino" ]]; then
    warn "'$destino' no es editable: se elimina y se recrea."
    rm -f -- "$destino"
  fi
  if ! cat -- "$temporal" > "$destino" 2>/dev/null; then
    warn "No se pudo editar '$destino': se elimina y se recrea."
    rm -f -- "$destino"
    cat -- "$temporal" > "$destino"
  fi
  rm -f -- "$temporal"
  ok "Escrito: $(ruta_actual)/$destino"
}


uso() {
  cat <<'AYUDA'
Uso: ./fase-03c-mejoras-asistenciales.sh [opciones]

Fase intermedia de mejoras de HealthWare: relaciones asistenciales, referencias
médicas, auditoría del expediente, acceso excepcional, policies y tareas
personales. Requiere las fases 1, 2, 3A y 3B.

Opciones:
  --simular        Muestra qué se haría y termina sin modificar archivos.
  --sin-respaldo   No copia lo existente a .respaldos-fases/ antes de escribir.
  -h, --ayuda      Muestra esta ayuda.

Colócalo en la raíz del proyecto, junto a "artisan".
AYUDA
}

# --------------------------- Verificación de entorno -------------------------
verificar_entorno() {
  log "Verificando el proyecto"
  [[ -f artisan && -f composer.json ]] || die "No encuentro artisan ni composer.json: coloque el script en la raíz del proyecto Laravel."
  [[ -f routes/web.php ]] && grep -q "pacientes.expediente" routes/web.php \
    || die "No encuentro las rutas de la fase 2: ejecute primero fase-02-flujo.sh."
  [[ -f app/Models/Orden.php && -f app/Http/Controllers/PacienteController.php ]] \
    || die "Faltan los modelos o controladores de las fases 1 y 2."
  [[ -f resources/views/pacientes/expediente.blade.php && -f public/js/hw/hw-dui.js ]] \
    || die "Faltan las vistas de la fase 3B: ejecute primero fase-03b-vistas-clinicas.sh."
  ok "Proyecto con las fases 1, 2, 3A y 3B aplicadas"
}

# --------------------------------- Plan --------------------------------------
mostrar_plan() {
  local archivo nuevos=0 existentes=0
  for archivo in "${ARCHIVOS_FASE[@]}"; do
    if [[ -e "$archivo" ]]; then
      existentes=$((existentes + 1))
    else
      nuevos=$((nuevos + 1))
    fi
  done

  log "Plan de la fase 3C"
  printf '    archivos a escribir: %s (%s nuevos, %s se sobrescriben)\n' "${#ARCHIVOS_FASE[@]}" "$nuevos" "$existentes"
  printf '    migraciones: %s · archivos de app: %s · vistas: %s · css y js: %s\n' 8 65 10 5
  [[ -d public/bootstrap/css ]] || printf '    aviso: falta public/bootstrap (lo agrega usted)\n'
  printf '    no se ejecuta PHP ni artisan\n'
}

# ------------------------------- Respaldo ------------------------------------
# Copia a .respaldos-fases/fase-03c-<fecha>/ lo que la fase va a sobrescribir.
respaldar() {
  if (( SIN_RESPALDO )); then
    warn "Se omite la copia de seguridad (--sin-respaldo)."
    return 0
  fi

  local destino archivo copiados=0
  destino="$DIR_RESPALDOS/fase-03c-$(date +%Y%m%d-%H%M%S)"

  log "Copia de seguridad en $destino/ (solo de los archivos existentes que se van a sobrescribir)"
  ejecutar mkdir -p "$destino"
  chmod 700 "$DIR_RESPALDOS"

  for archivo in "${ARCHIVOS_FASE[@]}"; do
    if [[ -e "$archivo" ]]; then
      cp -a --parents -- "$archivo" "$destino/"
      copiados=$((copiados + 1))
    fi
  done

  ok "Respaldo de $copiados archivos existentes"
}

# =============================================================================
#  CONTENIDO: vistas, estilos y scripts
# =============================================================================
escribir_migraciones() {
  log "Escribiendo migraciones (8 archivos)"
  ejecutar mkdir -p database/migrations
  descender database/migrations
  escribir_archivo "2026_11_13_000100_create_referencias_medicas_table.php" <<'FIN_ARCHIVO'
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
     * Una referencia es la solicitud de un médico para que otro especialista
     * (o un médico de cierta especialidad) atienda a un paciente. No crea la
     * cita: recepción la programa después.
     */
    public function up(): void
    {
        Schema::create('referencias_medicas', function (Blueprint $table) {
            $table->id();

            $table->string('numero_referencia', 20)->unique();

            $table->foreignId('id_paciente')
                ->constrained('pacientes')
                ->restrictOnDelete();

            $table->foreignId('id_doctor_origen')
                ->constrained('doctores')
                ->restrictOnDelete();

            $table->foreignId('id_doctor_destino')
                ->nullable()
                ->constrained('doctores')
                ->restrictOnDelete();

            $table->foreignId('id_especialidad_destino')
                ->nullable()
                ->constrained('especialidades')
                ->restrictOnDelete();

            $table->foreignId('id_registro_medico')
                ->nullable()
                ->constrained('registros_medicos')
                ->nullOnDelete();

            $table->string('motivo', 255);
            $table->text('resumen_clinico')->nullable();
            $table->text('estudios_solicitados')->nullable();

            $table->enum('prioridad', [
                'normal',
                'prioritaria',
                'urgente',
            ])->default('normal');

            $table->enum('estado', [
                'pendiente',
                'programada',
                'atendida',
                'rechazada',
                'cancelada',
                'vencida',
            ])->default('pendiente');

            $table->dateTime('fecha_referencia')->useCurrent();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['estado', 'prioridad']);
            $table->index('id_doctor_origen');
            $table->index('id_doctor_destino');
            $table->index('id_paciente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referencias_medicas');
    }
};
FIN_ARCHIVO
  escribir_archivo "2026_11_13_000200_create_relaciones_asistenciales_table.php" <<'FIN_ARCHIVO'
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
FIN_ARCHIVO
  escribir_archivo "2026_11_13_000300_add_id_referencia_medica_to_citas_table.php" <<'FIN_ARCHIVO'
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
FIN_ARCHIVO
  escribir_archivo "2026_11_13_000400_add_autoria_clinica_to_tablas_clinicas.php" <<'FIN_ARCHIVO'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas clínicas que registran qué usuario hizo cada alta. El médico
     * responsable sigue siendo id_doctor en registros_medicos; id_usuario_creacion
     * identifica al usuario autenticado que realizó la operación.
     */
    private array $tablas = [
        'registros_medicos',
        'signos_vitales',
        'registro_medico_diagnosticos',
        'recetas',
        'detalle_recetas',
        'pacientes_alergias',
        'pacientes_condiciones',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('id_usuario_creacion')
                    ->nullable()
                    ->constrained('usuarios')
                    ->nullOnDelete();
            });
        }

        // Los registros que ya existían se atribuyen al usuario de su médico.
        DB::statement(
            'UPDATE registros_medicos r
             JOIN doctores d ON d.id = r.id_doctor
             SET r.id_usuario_creacion = d.usuario_id
             WHERE r.id_usuario_creacion IS NULL'
        );

        DB::statement(
            'UPDATE signos_vitales s
             JOIN registros_medicos r ON r.id = s.id_registro_medico
             SET s.id_usuario_creacion = r.id_usuario_creacion
             WHERE s.id_usuario_creacion IS NULL'
        );

        DB::statement(
            'UPDATE registro_medico_diagnosticos x
             JOIN registros_medicos r ON r.id = x.id_registro_medico
             SET x.id_usuario_creacion = r.id_usuario_creacion
             WHERE x.id_usuario_creacion IS NULL'
        );

        DB::statement(
            'UPDATE recetas x
             JOIN registros_medicos r ON r.id = x.id_registro_medico
             SET x.id_usuario_creacion = r.id_usuario_creacion
             WHERE x.id_usuario_creacion IS NULL'
        );

        DB::statement(
            'UPDATE detalle_recetas x
             JOIN recetas r ON r.id = x.id_receta
             SET x.id_usuario_creacion = r.id_usuario_creacion
             WHERE x.id_usuario_creacion IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->tablas) as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('id_usuario_creacion');
            });
        }
    }
};
FIN_ARCHIVO
  escribir_archivo "2026_11_13_000500_create_auditoria_expedientes_table.php" <<'FIN_ARCHIVO'
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
FIN_ARCHIVO
  escribir_archivo "2026_11_13_000600_create_accesos_excepcionales_table.php" <<'FIN_ARCHIVO'
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
     * Acceso de emergencia (break-glass) a un expediente sin relación
     * asistencial: exige justificación, dura un tiempo limitado y se revisa
     * después. No sustituye a las policies: es una excepción registrada.
     */
    public function up(): void
    {
        Schema::create('accesos_excepcionales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_paciente')
                ->constrained('pacientes')
                ->restrictOnDelete();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->restrictOnDelete();

            $table->text('motivo');

            $table->dateTime('fecha_inicio')->useCurrent();
            $table->dateTime('fecha_expiracion');

            $table->enum('estado', [
                'activo',
                'revocado',
            ])->default('activo');

            // Revisión posterior por un administrador.
            $table->foreignId('revisado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->dateTime('revisado_en')->nullable();
            $table->text('observacion_revision')->nullable();

            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['id_usuario', 'id_paciente', 'estado']);
            $table->index('fecha_expiracion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accesos_excepcionales');
    }
};
FIN_ARCHIVO
  escribir_archivo "2026_11_13_000700_create_tareas_personales_table.php" <<'FIN_ARCHIVO'
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
     * Workspace personal: cada usuario ve y modifica solo sus tareas.
     * recordatorio_enviado_en y aviso_vencimiento_enviado_en hacen que el
     * comando programado sea seguro aunque se ejecute más de una vez.
     */
    public function up(): void
    {
        Schema::create('tareas_personales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_usuario')
                ->constrained('usuarios')
                ->cascadeOnDelete();

            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();

            $table->enum('prioridad', [
                'baja',
                'normal',
                'alta',
                'urgente',
            ])->default('normal');

            $table->enum('estado', [
                'pendiente',
                'en_progreso',
                'completada',
                'cancelada',
            ])->default('pendiente');

            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_vencimiento')->nullable();
            $table->dateTime('fecha_recordatorio')->nullable();
            $table->dateTime('fecha_completada')->nullable();

            $table->dateTime('recordatorio_enviado_en')->nullable();
            $table->dateTime('aviso_vencimiento_enviado_en')->nullable();

            $table->timestamps();

            $table->index(['id_usuario', 'estado']);
            $table->index('fecha_recordatorio');
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tareas_personales');
    }
};
FIN_ARCHIVO
  escribir_archivo "2026_11_13_000800_add_enlace_to_notificaciones_table.php" <<'FIN_ARCHIVO'
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
FIN_ARCHIVO
  ascender
}

escribir_seeders() {
  log "Actualizando el seeder de demostración (1 archivos)"
  ejecutar mkdir -p database/seeders
  descender database/seeders
  escribir_archivo "DemoSeeder.php" <<'FIN_ARCHIVO'
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

            $cita = Cita::create([
                'id_paciente' => $paciente->getKey(),
                'id_doctor' => $medico->getKey(),
                'fecha_cita' => $fecha->toDateString(),
                'hora_inicio' => $inicio->format('H:i'),
                'hora_fin' => $inicio->copy()->addMinutes(30)->format('H:i'),
                'estado' => 'programada',
                'motivo' => 'Consulta de control',
            ]);

            app(\App\Services\AccesoAsistencialService::class)->activarPorCita($cita);
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

            app(\App\Services\AccesoAsistencialService::class)->activarPorCita($cita);

            $registro = RegistroMedico::create([
                'id_paciente' => $paciente->getKey(),
                'id_doctor' => $medico->getKey(),
                'id_cita' => $cita->getKey(),
                'fecha_registro' => $inicio,
                'motivo_consulta' => 'Consulta general',
                'notas_clinicas' => 'Paciente estable, sin hallazgos relevantes.',
                'id_usuario_creacion' => $medico->usuario_id,
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
FIN_ARCHIVO
  ascender
}

escribir_aplicacion() {
  log "Escribiendo la aplicación (modelos, policies, servicios, eventos, controladores) (65 archivos)"
  ejecutar mkdir -p app
  descender app
  ejecutar mkdir -p Models/Concerns
  descender Models/Concerns
  escribir_archivo "RegistraAutoria.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models\Concerns;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Registra qué usuario autenticado dio de alta un registro clínico.
 *
 * El modelo debe tener la columna id_usuario_creacion. Fuera de una sesión
 * (seeders, comandos) la columna queda vacía.
 */
trait RegistraAutoria
{
    public static function bootRegistraAutoria(): void
    {
        static::creating(function ($modelo) {
            if (
                $modelo->getAttribute('id_usuario_creacion') === null
                && Auth::check()
            ) {
                $modelo->setAttribute('id_usuario_creacion', Auth::id());
            }
        });
    }

    /**
     * Usuario que realizó el alta.
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_creacion');
    }
}
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p Models
  descender Models
  escribir_archivo "RelacionAsistencial.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Vínculo entre un paciente y un médico que participa en su atención.
 *
 * Mientras la relación esté activa, el médico puede consultar el expediente
 * del paciente. Cerrar una relación es explícito (fecha y motivo) y la deja
 * como historial; no hay restricción única por (paciente, doctor).
 */
class RelacionAsistencial extends Model
{
    protected $table = 'relaciones_asistenciales';

    public const TIPO_PRINCIPAL = 'principal';
    public const TIPO_ESPECIALIZADA = 'especializada';
    public const TIPO_REFERENCIA = 'referencia';
    public const TIPO_SEGUIMIENTO = 'seguimiento';

    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_SUSPENDIDA = 'suspendida';
    public const ESTADO_CERRADA = 'cerrada';
    public const ESTADO_REVOCADA = 'revocada';

    public const TIPOS = [
        self::TIPO_PRINCIPAL,
        self::TIPO_ESPECIALIZADA,
        self::TIPO_REFERENCIA,
        self::TIPO_SEGUIMIENTO,
    ];

    public const ESTADOS = [
        self::ESTADO_ACTIVA,
        self::ESTADO_SUSPENDIDA,
        self::ESTADO_CERRADA,
        self::ESTADO_REVOCADA,
    ];

    protected $fillable = [
        'id_paciente',
        'id_doctor',
        'id_cita',
        'id_referencia_medica',
        'tipo_relacion',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'motivo_cierre',
        'id_usuario_cierre',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_doctor' => 'integer',
        'id_cita' => 'integer',
        'id_referencia_medica' => 'integer',
        'id_usuario_cierre' => 'integer',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    // Relaciones

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }

    public function referenciaMedica(): BelongsTo
    {
        return $this->belongsTo(ReferenciaMedica::class, 'id_referencia_medica');
    }

    public function usuarioCierre(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_cierre');
    }

    // Alcances

    public function scopeActivas(Builder $consulta): Builder
    {
        return $consulta->where('estado', self::ESTADO_ACTIVA);
    }

    // Reglas de validación

    public static function validaciones(): array
    {
        return [
            'id_paciente' => ['required', 'integer', 'exists:pacientes,id'],
            'id_doctor' => ['required', 'integer', 'exists:doctores,id'],
            'id_cita' => ['nullable', 'integer', 'exists:citas,id'],
            'id_referencia_medica' => ['nullable', 'integer', 'exists:referencias_medicas,id'],
            'tipo_relacion' => ['required', Rule::in(self::TIPOS)],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'motivo' => ['required', 'string', 'max:255'],
        ];
    }

    public static function rules(array $datos): array
    {
        return Validator::make($datos, static::validaciones())->validate();
    }

    // Consultas

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
            'cita',
            'referenciaMedica',
            'usuarioCierre',
        ])
            ->orderByRaw("FIELD(estado, 'activa', 'suspendida', 'cerrada', 'revocada')")
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    public static function mostrarDeDoctor(int $idDoctor): Collection
    {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
            'cita',
            'referenciaMedica',
            'usuarioCierre',
        ])
            ->where('id_doctor', $idDoctor)
            ->orderByRaw("FIELD(estado, 'activa', 'suspendida', 'cerrada', 'revocada')")
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    public static function buscarRelacionXId(int $id): ?RelacionAsistencial
    {
        return static::with(['paciente', 'doctor.usuario'])->find($id);
    }
}
FIN_ARCHIVO
  escribir_archivo "ReferenciaMedica.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Solicitud de un médico para que otro especialista (o un médico de una
 * especialidad) atienda a un paciente. No crea la cita: recepción la
 * programa y ese paso la vincula con la referencia.
 */
class ReferenciaMedica extends Model
{
    protected $table = 'referencias_medicas';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PROGRAMADA = 'programada';
    public const ESTADO_ATENDIDA = 'atendida';
    public const ESTADO_RECHAZADA = 'rechazada';
    public const ESTADO_CANCELADA = 'cancelada';
    public const ESTADO_VENCIDA = 'vencida';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_PROGRAMADA,
        self::ESTADO_ATENDIDA,
        self::ESTADO_RECHAZADA,
        self::ESTADO_CANCELADA,
        self::ESTADO_VENCIDA,
    ];

    public const PRIORIDADES = ['normal', 'prioritaria', 'urgente'];

    /**
     * Días que una referencia puede esperar su programación antes de vencer.
     */
    public const DIAS_VIGENCIA = [
        'normal' => 30,
        'prioritaria' => 15,
        'urgente' => 7,
    ];

    protected $fillable = [
        'numero_referencia',
        'id_paciente',
        'id_doctor_origen',
        'id_doctor_destino',
        'id_especialidad_destino',
        'id_registro_medico',
        'motivo',
        'resumen_clinico',
        'estudios_solicitados',
        'prioridad',
        'estado',
        'fecha_referencia',
        'observaciones',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_doctor_origen' => 'integer',
        'id_doctor_destino' => 'integer',
        'id_especialidad_destino' => 'integer',
        'id_registro_medico' => 'integer',
        'fecha_referencia' => 'datetime',
    ];

    // Relaciones

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function doctorOrigen(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'id_doctor_origen');
    }

    public function doctorDestino(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'id_doctor_destino');
    }

    public function especialidadDestino(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad_destino');
    }

    public function registroMedico(): BelongsTo
    {
        return $this->belongsTo(RegistroMedico::class, 'id_registro_medico')->withTrashed();
    }

    /**
     * Cita que resultó de programar esta referencia (una sola).
     */
    public function cita(): HasOne
    {
        return $this->hasOne(Cita::class, 'id_referencia_medica');
    }

    // Reglas de validación

    public static function validaciones(): array
    {
        return [
            'id_paciente' => ['required', 'integer', 'exists:pacientes,id'],
            'id_doctor_origen' => ['required', 'integer', 'exists:doctores,id'],
            'id_doctor_destino' => ['nullable', 'integer', 'exists:doctores,id'],
            'id_especialidad_destino' => ['nullable', 'integer', 'exists:especialidades,id'],
            'id_registro_medico' => ['nullable', 'integer', 'exists:registros_medicos,id'],
            'motivo' => ['required', 'string', 'max:255'],
            'resumen_clinico' => ['nullable', 'string', 'max:5000'],
            'estudios_solicitados' => ['nullable', 'string', 'max:2000'],
            'prioridad' => ['required', Rule::in(self::PRIORIDADES)],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Valida los datos de una referencia nueva. Al menos uno de los dos
     * destinos (doctor o especialidad) debe estar definido, y el doctor
     * de destino no puede ser quien la emite.
     */
    public static function rules(array $datos): array
    {
        $validator = Validator::make($datos, static::validaciones());

        $validator->after(function ($validator) use ($datos) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $destino = $datos['id_doctor_destino'] ?? null;
            $especialidad = $datos['id_especialidad_destino'] ?? null;

            if (!$destino && !$especialidad) {
                $validator->errors()->add(
                    'id_doctor_destino',
                    'Indique el médico o la especialidad a la que se refiere al paciente.'
                );

                return;
            }

            if ($destino && (int) $destino === (int) $datos['id_doctor_origen']) {
                $validator->errors()->add(
                    'id_doctor_destino',
                    'No puede referir un paciente a usted mismo.'
                );

                return;
            }

            if ($destino && $especialidad) {
                $doctor = Doctor::find($destino);

                if ($doctor && (int) $doctor->especialidad_id !== (int) $especialidad) {
                    $validator->errors()->add(
                        'id_especialidad_destino',
                        'El médico seleccionado no pertenece a la especialidad indicada.'
                    );
                }
            }
        });

        return $validator->validate();
    }

    /**
     * Número consecutivo REF-AAAA-NNNNNN (debe llamarse dentro de una transacción).
     */
    public static function generarNumero(): string
    {
        $prefijo = 'REF-' . now()->format('Y') . '-';

        $ultimo = static::query()
            ->where('numero_referencia', 'like', $prefijo . '%')
            ->lockForUpdate()
            ->orderByDesc('numero_referencia')
            ->value('numero_referencia');

        $siguiente = $ultimo ? ((int) substr($ultimo, -6)) + 1 : 1;

        return $prefijo . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Una referencia pendiente vence si pasó el plazo de su prioridad sin
     * que recepción la programara.
     */
    public function estaVencida(): bool
    {
        if ($this->estado !== self::ESTADO_PENDIENTE) {
            return false;
        }

        $dias = self::DIAS_VIGENCIA[$this->prioridad] ?? 30;

        return $this->fecha_referencia->lt(now()->subDays($dias));
    }

    /**
     * Indica si todavía puede gestionarse (programar, rechazar o cancelar).
     */
    public function esGestionable(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE && !$this->estaVencida();
    }

    // Consultas

    private static function relacionesListado(): array
    {
        return [
            'paciente',
            'doctorOrigen.usuario',
            'doctorOrigen.especialidad',
            'doctorDestino.usuario',
            'doctorDestino.especialidad',
            'especialidadDestino',
            'cita',
        ];
    }

    public static function mostrarTodos(): Collection
    {
        return static::with(static::relacionesListado())
            ->orderByRaw("FIELD(estado, 'pendiente', 'programada', 'atendida', 'rechazada', 'cancelada', 'vencida')")
            ->orderByRaw("FIELD(prioridad, 'urgente', 'prioritaria', 'normal')")
            ->orderByDesc('fecha_referencia')
            ->get();
    }

    /**
     * Referencias que un médico emitió o que se le asignaron.
     */
    public static function mostrarDeDoctor(int $idDoctor): Collection
    {
        return static::with(static::relacionesListado())
            ->where(function ($consulta) use ($idDoctor) {
                $consulta->where('id_doctor_origen', $idDoctor)
                    ->orWhere('id_doctor_destino', $idDoctor);
            })
            ->orderByDesc('fecha_referencia')
            ->get();
    }

    public static function buscarReferenciaXId(int $id): ?ReferenciaMedica
    {
        return static::with(static::relacionesListado())->find($id);
    }
}
FIN_ARCHIVO
  escribir_archivo "AuditoriaExpediente.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Auditoría del expediente clínico: de solo inserción.
 *
 * Una vez escrito, un registro no se modifica ni se elimina desde la
 * aplicación. Guarda quién hizo qué sobre qué paciente, no copias de la
 * información clínica.
 */
class AuditoriaExpediente extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'auditoria_expedientes';

    public const CONSULTA = 'consulta';
    public const CREACION = 'creacion';
    public const MODIFICACION = 'modificacion';
    public const ELIMINACION = 'eliminacion';
    public const RESTAURACION = 'restauracion';
    public const DESCARGA = 'descarga';
    public const ACCESO_EXCEPCIONAL = 'acceso_excepcional';
    public const GESTION_ACCESO = 'gestion_acceso';

    public const ACCIONES = [
        self::CONSULTA,
        self::CREACION,
        self::MODIFICACION,
        self::ELIMINACION,
        self::RESTAURACION,
        self::DESCARGA,
        self::ACCESO_EXCEPCIONAL,
        self::GESTION_ACCESO,
    ];

    protected $fillable = [
        'id_paciente',
        'id_usuario',
        'id_doctor',
        'tipo_accion',
        'entidad',
        'id_registro',
        'motivo',
        'datos_relevantes',
        'ip',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_usuario' => 'integer',
        'id_doctor' => 'integer',
        'id_registro' => 'integer',
        'datos_relevantes' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('La auditoría del expediente es de solo inserción.');
        });

        static::deleting(function () {
            throw new LogicException('La auditoría del expediente no se puede eliminar.');
        });
    }

    // Relaciones

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }

    /**
     * Registra una acción sobre un expediente. El actor y la IP se toman de
     * la sesión; si la acción la hace el sistema (un comando) quedan vacíos.
     */
    public static function registrar(
        int $idPaciente,
        string $tipoAccion,
        string $entidad,
        ?int $idRegistro = null,
        ?string $motivo = null,
        array $datos = [],
        ?int $idDoctor = null
    ): self {
        $usuario = Auth::user();

        if ($idDoctor === null && $usuario) {
            $idDoctor = Doctor::buscarPorUsuario($usuario->getKey())?->getKey();
        }

        return static::create([
            'id_paciente' => $idPaciente,
            'id_usuario' => $usuario?->getKey(),
            'id_doctor' => $idDoctor,
            'tipo_accion' => $tipoAccion,
            'entidad' => $entidad,
            'id_registro' => $idRegistro,
            'motivo' => $motivo,
            'datos_relevantes' => $datos === [] ? null : $datos,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    /**
     * Últimos eventos, con filtros opcionales (id_paciente, id_usuario,
     * tipo_accion, desde, hasta).
     */
    public static function mostrarFiltrados(array $filtros, int $limite = 300): Collection
    {
        return static::with(['paciente', 'usuario', 'doctor.usuario'])
            ->when($filtros['id_paciente'] ?? null, fn ($q, $v) => $q->where('id_paciente', $v))
            ->when($filtros['id_usuario'] ?? null, fn ($q, $v) => $q->where('id_usuario', $v))
            ->when($filtros['tipo_accion'] ?? null, fn ($q, $v) => $q->where('tipo_accion', $v))
            ->when($filtros['desde'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filtros['hasta'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }
}
FIN_ARCHIVO
  escribir_archivo "AccesoExcepcional.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Acceso de emergencia (break-glass) a un expediente sin relación
 * asistencial. Exige justificación, dura un tiempo limitado y un
 * administrador lo revisa después.
 */
class AccesoExcepcional extends Model
{
    protected $table = 'accesos_excepcionales';

    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_REVOCADO = 'revocado';

    /** Horas de acceso que concede cada solicitud. */
    public const HORAS_VIGENCIA = 4;

    /** Longitud mínima de la justificación. */
    public const MOTIVO_MINIMO = 20;

    protected $fillable = [
        'id_paciente',
        'id_usuario',
        'motivo',
        'fecha_inicio',
        'fecha_expiracion',
        'estado',
        'revisado_por',
        'revisado_en',
        'observacion_revision',
        'ip',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_usuario' => 'integer',
        'revisado_por' => 'integer',
        'fecha_inicio' => 'datetime',
        'fecha_expiracion' => 'datetime',
        'revisado_en' => 'datetime',
    ];

    // Relaciones

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'revisado_por');
    }

    // Alcances

    public function scopeVigentes(Builder $consulta): Builder
    {
        return $consulta
            ->where('estado', self::ESTADO_ACTIVO)
            ->where('fecha_expiracion', '>', now());
    }

    public function estaVigente(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO
            && $this->fecha_expiracion->isFuture();
    }

    /**
     * Estado para mostrar: activo, expirado, revocado.
     */
    public function estadoVisible(): string
    {
        if ($this->estado === self::ESTADO_REVOCADO) {
            return 'revocado';
        }

        return $this->fecha_expiracion->isFuture() ? 'activo' : 'expirado';
    }

    /**
     * Acceso vigente de un usuario a un paciente, si lo tiene.
     */
    public static function vigenteDe(int $idUsuario, int $idPaciente): ?AccesoExcepcional
    {
        return static::query()
            ->vigentes()
            ->where('id_usuario', $idUsuario)
            ->where('id_paciente', $idPaciente)
            ->orderByDesc('fecha_expiracion')
            ->first();
    }

    // Consultas

    public static function mostrarTodos(): Collection
    {
        return static::with(['paciente', 'usuario.rol', 'revisor'])
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    public static function mostrarDeUsuario(int $idUsuario): Collection
    {
        return static::with(['paciente', 'usuario.rol', 'revisor'])
            ->where('id_usuario', $idUsuario)
            ->orderByDesc('fecha_inicio')
            ->get();
    }
}
FIN_ARCHIVO
  escribir_archivo "TareaPersonal.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Tarea del workspace personal. Es estrictamente privada: cada usuario ve y
 * modifica solo las suyas. "Vencida" no es un estado guardado: se calcula
 * comparando la fecha de vencimiento con la fecha actual.
 */
class TareaPersonal extends Model
{
    protected $table = 'tareas_personales';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_EN_PROGRESO = 'en_progreso';
    public const ESTADO_COMPLETADA = 'completada';
    public const ESTADO_CANCELADA = 'cancelada';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_PROGRESO,
        self::ESTADO_COMPLETADA,
        self::ESTADO_CANCELADA,
    ];

    /** Estados en los que la tarea sigue abierta. */
    public const ESTADOS_ABIERTOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_PROGRESO,
    ];

    public const PRIORIDADES = ['baja', 'normal', 'alta', 'urgente'];

    protected $fillable = [
        'id_usuario',
        'titulo',
        'descripcion',
        'prioridad',
        'estado',
        'fecha_inicio',
        'fecha_vencimiento',
        'fecha_recordatorio',
        'fecha_completada',
        'recordatorio_enviado_en',
        'aviso_vencimiento_enviado_en',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'fecha_inicio' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'fecha_recordatorio' => 'datetime',
        'fecha_completada' => 'datetime',
        'recordatorio_enviado_en' => 'datetime',
        'aviso_vencimiento_enviado_en' => 'datetime',
    ];

    // Relaciones

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // Alcances

    public function scopeDeUsuario(Builder $consulta, int $idUsuario): Builder
    {
        return $consulta->where('id_usuario', $idUsuario);
    }

    public function scopeAbiertas(Builder $consulta): Builder
    {
        return $consulta->whereIn('estado', self::ESTADOS_ABIERTOS);
    }

    // Estado calculado

    public function estaAbierta(): bool
    {
        return in_array($this->estado, self::ESTADOS_ABIERTOS, true);
    }

    public function estaVencida(): bool
    {
        return $this->estaAbierta()
            && $this->fecha_vencimiento !== null
            && $this->fecha_vencimiento->isPast();
    }

    // Reglas de validación

    public static function validaciones(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'prioridad' => ['required', Rule::in(self::PRIORIDADES)],
            'estado' => ['sometimes', Rule::in(self::ESTADOS)],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'fecha_recordatorio' => ['nullable', 'date'],
        ];
    }

    /**
     * Valida los datos y, cuando existen ambas fechas, que el vencimiento no
     * sea anterior al inicio ni el recordatorio posterior al vencimiento.
     */
    public static function rules(array $datos): array
    {
        $validator = Validator::make($datos, static::validaciones());

        $validator->after(function ($validator) use ($datos) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $inicio = filled($datos['fecha_inicio'] ?? null) ? Carbon::parse($datos['fecha_inicio']) : null;
            $vencimiento = filled($datos['fecha_vencimiento'] ?? null) ? Carbon::parse($datos['fecha_vencimiento']) : null;
            $recordatorio = filled($datos['fecha_recordatorio'] ?? null) ? Carbon::parse($datos['fecha_recordatorio']) : null;

            if ($inicio && $vencimiento && $vencimiento->lt($inicio)) {
                $validator->errors()->add(
                    'fecha_vencimiento',
                    'El vencimiento no puede ser anterior al inicio.'
                );
            }

            if ($recordatorio && $vencimiento && $recordatorio->gt($vencimiento)) {
                $validator->errors()->add(
                    'fecha_recordatorio',
                    'El recordatorio no puede ser posterior al vencimiento.'
                );
            }
        });

        return $validator->validate();
    }

    // Consultas

    public static function mostrarDeUsuario(int $idUsuario): Collection
    {
        return static::deUsuario($idUsuario)
            ->orderByRaw('fecha_vencimiento IS NULL')
            ->orderBy('fecha_vencimiento')
            ->orderByDesc('id')
            ->get();
    }

    // Operaciones

    public static function insertar(int $idUsuario, array $datos): TareaPersonal
    {
        $datos = static::rules($datos);

        $datos['id_usuario'] = $idUsuario;
        $datos['estado'] = $datos['estado'] ?? self::ESTADO_PENDIENTE;

        if ($datos['estado'] === self::ESTADO_COMPLETADA) {
            $datos['fecha_completada'] = now();
        }

        return static::create($datos);
    }

    /**
     * Actualiza los datos de la tarea. Reprogramar el vencimiento o el
     * recordatorio reinicia su aviso para que vuelva a notificarse.
     */
    public function actualizarDatos(array $datos): bool
    {
        $datos = static::rules($datos);

        $reprogramaRecordatorio = array_key_exists('fecha_recordatorio', $datos)
            && $this->cambioFecha($this->fecha_recordatorio, $datos['fecha_recordatorio']);

        $reprogramaVencimiento = array_key_exists('fecha_vencimiento', $datos)
            && $this->cambioFecha($this->fecha_vencimiento, $datos['fecha_vencimiento']);

        if ($reprogramaRecordatorio) {
            $datos['recordatorio_enviado_en'] = null;
        }

        if ($reprogramaVencimiento) {
            $datos['aviso_vencimiento_enviado_en'] = null;
        }

        return $this->update($datos);
    }

    /**
     * Cambia el estado: al completarla guarda la fecha; al reabrirla la borra.
     */
    public function cambiarEstado(string $estado): bool
    {
        $cambios = ['estado' => $estado];

        if ($estado === self::ESTADO_COMPLETADA) {
            $cambios['fecha_completada'] = now();
        } else {
            $cambios['fecha_completada'] = null;
        }

        return $this->update($cambios);
    }

    private function cambioFecha($actual, $nueva): bool
    {
        $anterior = $actual?->format('Y-m-d H:i');
        $siguiente = $nueva ? Carbon::parse($nueva)->format('Y-m-d H:i') : null;

        return $anterior !== $siguiente;
    }
}
FIN_ARCHIVO
  escribir_archivo "RegistroMedico.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
//Nuevo añadido para insertar al mismo tiempo, el registro médico
use Illuminate\Support\Facades\DB;

class RegistroMedico extends Model
{
    use \App\Models\Concerns\RegistraAutoria;

    use HasFactory, SoftDeletes;

    protected $table = 'registros_medicos';

    protected $fillable = [
        'id_usuario_creacion',
        'id_paciente',
        'id_doctor',
        'id_cita',
        'fecha_registro',
        'motivo_consulta',
        'notas_clinicas',
        'observaciones',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_doctor' => 'integer',
        'id_cita' => 'integer',
        'fecha_registro' => 'datetime',
    ];

    // Relaciones

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }

    public function signosVitales(): HasOne
    {
        return $this->hasOne(
            SignoVital::class,
            'id_registro_medico'
        );
    }

    public function recetas(): HasMany
    {
        return $this->hasMany(Receta::class, 'id_registro_medico');
    }

    public function diagnosticos(): HasMany
    {
        return $this->hasMany(RegistroMedicoDiagnostico::class, 'id_registro_medico');
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class, 'id_registro_medico');
    }

    // Reglas de validación

    public static function validaciones(): array
    {
        return [
            'id_paciente' => [
                'required',
                'integer',
                'exists:pacientes,id',
            ],

            'id_doctor' => [
                'required',
                'integer',
                'exists:doctores,id',
            ],

            'id_cita' => [
                'nullable',
                'integer',
                'exists:citas,id',
            ],

            'fecha_registro' => [
                'sometimes',
                'required',
                'date',
            ],

            'motivo_consulta' => [
                'nullable',
                'string',
            ],

            'notas_clinicas' => [
                'nullable',
                'string',
            ],

            'observaciones' => [
                'nullable',
                'string',
            ],
            'signos_vitales' => [
                'nullable',
                'array',
            ],

            'signos_vitales.peso' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:999.99',
            ],

            'signos_vitales.altura' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:999.99',
            ],

            'signos_vitales.temperatura' => [
                'nullable',
                'numeric',
                'between:20,50',
            ],

            'signos_vitales.frecuencia_cardiaca' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],

            'signos_vitales.frecuencia_respiratoria' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],

            'signos_vitales.saturacion_oxigeno' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],

            'signos_vitales.presion_sistolica' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],

            'signos_vitales.presion_diastolica' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],
        ];
    }

    public static function rules(array $datos): array
    {
        $validator = Validator::make(
            $datos,
            static::validaciones()
        );

        $validator->after(function ($validator) use ($datos) {

            if (
                empty($datos['id_cita']) ||
                $validator->errors()->has('id_cita') ||
                $validator->errors()->has('id_paciente') ||
                $validator->errors()->has('id_doctor')
            ) {
                return;
            }

            $cita = Cita::find($datos['id_cita']);

            if (!$cita) {
                return;
            }

            if (
                $cita->id_paciente != $datos['id_paciente'] ||
                $cita->id_doctor != $datos['id_doctor']
            ) {
                $validator->errors()->add(
                    'id_cita',
                    'La cita seleccionada no corresponde al paciente y al doctor indicados.'
                );
            }
        });

        return $validator->validate();
    }

    // Consultas

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
            'cita',
            'signosVitales',
        ])
            ->orderByDesc('fecha_registro')
            ->get();
    }

    public static function buscarRegistroMedicoXId(
        int $id
    ): ?RegistroMedico {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
            'cita',
            'signosVitales',
        ])->find($id);
    }

    // Operaciones CRUD


    public static function insertar(array $datos): RegistroMedico
    {
        return DB::transaction(function () use ($datos) {

            $datos = static::rules($datos);

            $signosVitales = $datos['signos_vitales'] ?? null;

            unset($datos['signos_vitales']);

            $registroMedico = static::create($datos);

            if (is_array($signosVitales)) {
                $hayDatos = collect($signosVitales)->contains(
                    fn ($valor) => $valor !== null && $valor !== ''
                );

                if ($hayDatos) {
                    $registroMedico->signosVitales()->create(
                        $signosVitales
                    );
                }
            }

            return $registroMedico;
        });
    }


    public static function eliminar(int $id): bool
    {
        $registroMedico = static::findOrFail($id);

        return $registroMedico->delete();
    }

    /**
     * Registros médicos eliminados (papelera).
     */
    public static function mostrarEliminados(): Collection
    {
        return static::onlyTrashed()
            ->with([
                'paciente' => fn ($consulta) => $consulta->withTrashed(),
                'doctor.usuario',
            ])
            ->orderByDesc('fecha_registro')
            ->get();
    }

    /**
     * Restaura un registro médico de la papelera.
     */
    public static function restaurar(int $id): bool
    {
        $registroMedico = static::onlyTrashed()->find($id);

        if (!$registroMedico) {
            return false;
        }

        return (bool) $registroMedico->restore();
    }
}
FIN_ARCHIVO
  escribir_archivo "SignoVital.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignoVital extends Model
{
    use \App\Models\Concerns\RegistraAutoria;

    use HasFactory;

    protected $table = 'signos_vitales';

    protected $fillable = [
        'id_usuario_creacion',
        'id_registro_medico',
        'peso',
        'altura',
        'temperatura',
        'frecuencia_cardiaca',
        'frecuencia_respiratoria',
        'saturacion_oxigeno',
        'presion_sistolica',
        'presion_diastolica',
    ];

    protected $casts = [
        'id_registro_medico' => 'integer',
        'peso' => 'decimal:2',
        'altura' => 'decimal:2',
        'temperatura' => 'decimal:1',
        'frecuencia_cardiaca' => 'integer',
        'frecuencia_respiratoria' => 'integer',
        'saturacion_oxigeno' => 'decimal:2',
        'presion_sistolica' => 'integer',
        'presion_diastolica' => 'integer',
    ];

    // Relaciones

    public function registroMedico(): BelongsTo
    {
        return $this->belongsTo(
            RegistroMedico::class,
            'id_registro_medico'
        )->withTrashed();
    }
}
FIN_ARCHIVO
  escribir_archivo "RegistroMedicoDiagnostico.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegistroMedicoDiagnostico extends Model
{
    use \App\Models\Concerns\RegistraAutoria;

    use HasFactory;

    protected $table = 'registro_medico_diagnosticos';

    protected $fillable = [
        'id_usuario_creacion',
        'id_registro_medico',
        'id_diagnostico',
        'notas',
    ];

    protected $casts = [
        'id_registro_medico' => 'integer',
        'id_diagnostico' => 'integer',
    ];

    // Relaciones

    public function registroMedico(): BelongsTo
    {
        return $this->belongsTo(
            RegistroMedico::class,
            'id_registro_medico'
        )->withTrashed();
    }

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(
            Diagnostico::class,
            'id_diagnostico'
        );
    }

    // Reglas de validación

    public static function validaciones(): array
    {
        return [
            'id_registro_medico' => [
                'required',
                'integer',
                'exists:registros_medicos,id',
            ],

            'id_diagnostico' => [
                'required',
                'integer',
                'exists:diagnosticos,id',
            ],

            'notas' => [
                'nullable',
                'string',
                'max:250',
            ],
        ];
    }

    public static function rules(array $datos): array
    {
        $validator = Validator::make(
            $datos,
            static::validaciones()
        );

        $validator->after(function ($validator) use ($datos) {
            if (
                $validator->errors()->has('id_registro_medico') ||
                $validator->errors()->has('id_diagnostico')
            ) {
                return;
            }

            $duplicado = static::query()
                ->where(
                    'id_registro_medico',
                    $datos['id_registro_medico']
                )
                ->where(
                    'id_diagnostico',
                    $datos['id_diagnostico']
                )
                ->exists();

            if ($duplicado) {
                $validator->errors()->add(
                    'id_diagnostico',
                    'Este diagnóstico ya está asociado al registro médico seleccionado.'
                );
            }
        });

        return $validator->validate();
    }

    // Consultas

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'registroMedico.paciente',
            'registroMedico.doctor.usuario',
            'diagnostico',
        ])
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarRegistroMedicoDiagnosticoXId(
        int $id
    ): ?RegistroMedicoDiagnostico {
        return static::with([
            'registroMedico.paciente',
            'registroMedico.doctor.usuario',
            'diagnostico',
        ])->find($id);
    }

    // Operaciones CRUD

    public static function insertar(
        array $datos
    ): RegistroMedicoDiagnostico {
        $datos = static::rules($datos);

        return static::create($datos);
    }

    public static function eliminar(int $id): bool
    {
        $registro = static::findOrFail($id);

        return $registro->delete();
    }
}
FIN_ARCHIVO
  escribir_archivo "Receta.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Receta extends Model
{
    use \App\Models\Concerns\RegistraAutoria;

    use HasFactory;

    protected $table = 'recetas';


    protected $fillable = [
        'id_usuario_creacion',
        'id_registro_medico',
        'fecha_prescripcion',
        'notas',
    ];

    protected $casts = [
        'id_registro_medico' => 'integer',
        'fecha_prescripcion' => 'datetime',
    ];

    public function registroMedico(): BelongsTo
    {
        return $this->belongsTo(
            RegistroMedico::class,
            'id_registro_medico'
        )->withTrashed();
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(
            DetalleReceta::class,
            'id_receta'
        );
    }

    public static function validaciones(): array
    {
        return [
            'id_registro_medico' => [
                'required',
                'integer',
                'exists:registros_medicos,id',
            ],
            'fecha_prescripcion' => [
                'sometimes',
                'date',
            ],
            'notas' => [
                'nullable',
                'string',
                'max:250',
            ],
        ];
    }

    public static function rules(array $datos): array
    {
        return Validator::make(
            $datos,
            static::validaciones()
        )->validate();
    }

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'registroMedico.paciente',
            'registroMedico.doctor',
        ])
            ->orderByDesc('fecha_prescripcion')
            ->get();
    }

    public static function buscarRecetaXId(int $id): ?Receta
    {
        return static::with([
            'registroMedico.paciente',
            'registroMedico.doctor',
            'detalles.medicamento',
        ])->find($id);
    }

    /**
     * Inserta una receta. Si llega la clave "detalles" (medicamentos), se
     * crean en la misma transacción; cada línea se valida con DetalleReceta.
     */
    public static function insertar(array $datos): Receta
    {
        $detalles = $datos['detalles'] ?? [];
        unset($datos['detalles']);

        return DB::transaction(function () use ($datos, $detalles) {
            $datos = static::rules($datos);

            $receta = static::create($datos);

            foreach ($detalles as $detalle) {
                DetalleReceta::insertar(
                    ['id_receta' => $receta->getKey()] + $detalle
                );
            }

            return $receta->load('detalles.medicamento');
        });
    }
}
FIN_ARCHIVO
  escribir_archivo "DetalleReceta.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;

class DetalleReceta extends Model
{
    use \App\Models\Concerns\RegistraAutoria;

    use HasFactory;

    protected $table = 'detalle_recetas';


    protected $fillable = [
        'id_usuario_creacion',
        'id_receta',
        'id_medicamento',
        'dosis',
        'frecuencia',
        'duracion',
        'via_administracion',
        'instrucciones',
    ];

    protected $casts = [
        'id_receta' => 'integer',
        'id_medicamento' => 'integer',
    ];

    public function receta(): BelongsTo
    {
        return $this->belongsTo(
            Receta::class,
            'id_receta'
        );
    }

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(
            Medicamento::class,
            'id_medicamento'
        );
    }

    public static function validaciones(): array
    {
        return [
            'id_receta' => [
                'required',
                'integer',
                'exists:recetas,id',
            ],
            'id_medicamento' => [
                'required',
                'integer',
                'exists:medicamentos,id',
            ],
            'dosis' => [
                'required',
                'string',
                'max:100',
            ],
            'frecuencia' => [
                'required',
                'string',
                'max:100',
            ],
            'duracion' => [
                'nullable',
                'string',
                'max:100',
            ],
            'via_administracion' => [
                'nullable',
                'string',
                'max:50',
            ],
            'instrucciones' => [
                'nullable',
                'string',
            ],
        ];
    }

    public static function rules(array $datos): array
    {
        return Validator::make(
            $datos,
            static::validaciones()
        )->validate();
    }

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'receta',
            'medicamento',
        ])
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarDetalleRecetaXId(
        int $id
    ): ?DetalleReceta {
        return static::with([
            'receta',
            'medicamento',
        ])->find($id);
    }

    public static function insertar(array $datos): DetalleReceta
    {
        $datos = static::rules($datos);

        return static::create($datos);
    }
}
FIN_ARCHIVO
  escribir_archivo "PacienteAlergia.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;


/*
 * NOTA PARA EL FUTURO: Al igual que pacientes_condiciones, vamos a integrar el añadir la alergia mediante un form
 * oculto al momento de disparar el modal de editar paciente.
 * */
class PacienteAlergia extends Model
{
    use \App\Models\Concerns\RegistraAutoria;

    use HasFactory;

    protected $table = 'pacientes_alergias';

    protected $fillable = [
        'id_usuario_creacion',
        'id_paciente',
        'id_alergia',
        'gravedad',
        'reaccion',
        'notas',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_alergia' => 'integer',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function alergia(): BelongsTo
    {
        return $this->belongsTo(Alergia::class, 'id_alergia');
    }

    public static function validaciones(): array
    {
        return [
            'id_paciente' => [
                'required',
                'integer',
                'exists:pacientes,id',
            ],
            'id_alergia' => [
                'required',
                'integer',
                'exists:alergias,id',
            ],
            'gravedad' => [
                'required',
                'in:leve,moderada,grave,desconocida',
            ],
            'reaccion' => [
                'nullable',
                'string',
                'max:250',
            ],
            'notas' => [
                'nullable',
                'string',
                'max:250',
            ],
        ];
    }

    public static function rules(array $datos): array
    {
        $validator = Validator::make(
            $datos,
            static::validaciones()
        );

        $validator->after(function ($validator) use ($datos) {
            if (
                $validator->errors()->has('id_paciente') ||
                $validator->errors()->has('id_alergia')
            ) {
                return;
            }

            $duplicado = static::where(
                'id_paciente',
                $datos['id_paciente']
            )
                ->where('id_alergia', $datos['id_alergia'])
                ->exists();

            if ($duplicado) {
                $validator->errors()->add(
                    'id_alergia',
                    'Esta alergia ya está registrada para el paciente.'
                );
            }
        });

        return $validator->validate();
    }

    public static function mostrarTodos(): Collection
    {
        return static::with(['paciente', 'alergia'])
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarPacienteAlergiaXId(
        int $id
    ): ?PacienteAlergia {
        return static::with(['paciente', 'alergia'])->find($id);
    }

    public static function insertar(array $datos): PacienteAlergia
    {
        $datos = static::rules($datos);

        return static::create($datos);
    }

    public static function eliminar(int $id): bool
    {
        return static::whereKey($id)->delete() > 0;
    }
}
FIN_ARCHIVO
  escribir_archivo "PacienteCondicion.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;

/*
 * NOTA PARA EL FUTURO: La relación entre paciente y condición médica la vamos a añadir en la vista de pacientes
 * al darle editar paciente, y que aparezca el modal, añadiremos un link para añadir una condición, el cual va a
 * disparar otro modal o parte del form que permita realizar operaciones CRUD con dicho paciente.
 * */

class PacienteCondicion extends Model
{
    use \App\Models\Concerns\RegistraAutoria;

    use HasFactory;

    protected $table = 'pacientes_condiciones';

    protected $fillable = [
        'id_usuario_creacion',
        'id_paciente',
        'id_condicion_medica',
        'fecha_diagnostico',
        'notas',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_condicion_medica' => 'integer',
        'fecha_diagnostico' => 'date',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function condicionMedica(): BelongsTo
    {
        return $this->belongsTo(CondicionMedica::class, 'id_condicion_medica');
    }

    public static function validaciones(): array
    {
        return [
            'id_paciente' => [
                'required',
                'integer',
                'exists:pacientes,id',
            ],
            'id_condicion_medica' => [
                'required',
                'integer',
                'exists:condiciones_medicas,id',
            ],
            'fecha_diagnostico' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'notas' => [
                'nullable',
                'string',
                'max:250',
            ],
        ];
    }

    public static function rules(array $datos): array
    {
        $validator = Validator::make($datos, static::validaciones());

        $validator->after(function ($validator) use ($datos) {
            if (
                $validator->errors()->has('id_paciente') ||
                $validator->errors()->has('id_condicion_medica')
            ) {
                return;
            }

            $duplicado = static::where('id_paciente', $datos['id_paciente'])
                ->where('id_condicion_medica', $datos['id_condicion_medica'])
                ->exists();

            if ($duplicado) {
                $validator->errors()->add(
                    'id_condicion_medica',
                    'Esta condición médica ya está registrada para el paciente.'
                );
            }
        });

        return $validator->validate();
    }

    public static function mostrarTodos(): Collection
    {
        return static::with(['paciente', 'condicionMedica'])
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarPacienteCondicionXId(int $id): ?PacienteCondicion
    {
        return static::with(['paciente', 'condicionMedica'])->find($id);
    }

    public static function insertar(array $datos): PacienteCondicion
    {
        $datos = static::rules($datos);

        return static::create($datos);
    }

    public static function eliminar(int $id): bool
    {
        return static::whereKey($id)->delete() > 0;
    }
}
FIN_ARCHIVO
  escribir_archivo "Paciente.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
class Paciente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pacientes';

    protected $fillable = [
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'genero',
        'id_tipo_sanguineo',
        'numero_identificacion',
        'telefono',
        'correo',
        'direccion',
        'nombre_contacto_emergencia',
        'telefono_contacto_emergencia',
        'parentesco_contacto_emergencia',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'id_tipo_sanguineo' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene el tipo sanguíneo del paciente.
     */
    public function tipoSanguineo(): BelongsTo
    {
        return $this->belongsTo(
            TipoSanguineo::class,
            'id_tipo_sanguineo'
        );
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'id_paciente');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_paciente');
    }

    public function registrosMedicos(): HasMany
    {
        return $this->hasMany(RegistroMedico::class, 'id_paciente');
    }

    public function alergias(): HasMany
    {
        return $this->hasMany(PacienteAlergia::class, 'id_paciente');
    }

    public function condiciones(): HasMany
    {
        return $this->hasMany(PacienteCondicion::class, 'id_paciente');
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class, 'id_paciente');
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Define las reglas para insertar o actualizar pacientes.
     */
    public static function validaciones($id = null): array
    {
        return [
            'nombres' => [
                'required',
                'string',
                'max:100',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],

            'fecha_nacimiento' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'genero' => [
                'required',
                Rule::in([
                    'Masculino',
                    'Femenino'
                ]),
            ],

            'id_tipo_sanguineo' => [
                'nullable',
                'integer',
                'exists:tipos_sanguineos,id',
            ],

            'numero_identificacion' => [
                'required',
                'string',
                'max:10',
                Rule::unique(
                    'pacientes',
                    'numero_identificacion'
                )->ignore($id),
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:25',
            ],

            'correo' => [
                'nullable',
                'email',
                'max:150',
            ],

            'direccion' => [
                'nullable',
                'string',
            ],

            'nombre_contacto_emergencia' => [
                'nullable',
                'string',
                'max:150',
            ],

            'telefono_contacto_emergencia' => [
                'nullable',
                'string',
                'max:25',
            ],

            'parentesco_contacto_emergencia' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }

    /**
     * Valida los datos y devuelve los datos validados.
     */
    public static function rules(
        array $datos,
              $id = null
    ): array {
        return Validator::make(
            $datos,
            static::validaciones($id)
        )->validate();
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene todos los pacientes con su tipo sanguíneo.
     */
    public static function mostrarTodos(): Collection
    {
        return static::with('tipoSanguineo')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();
    }

    /**
     * Busca un paciente por su identificador.
     */
    public static function buscarPacienteXId(
        int $id
    ): ?Paciente {
        return static::with('tipoSanguineo')
            ->find($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones CRUD
    |--------------------------------------------------------------------------
    */

    /**
     * Inserta un nuevo paciente.
     */
    public static function insertar(
        array $datos
    ): Paciente {
        $datosValidados = static::rules($datos);

        return static::create($datosValidados);
    }

    /**
     * Actualiza un paciente existente.
     */
    public static function actualizar(
        array $datos,
        int $id
    ): bool {
        $paciente = static::find($id);

        if (!$paciente) {
            return false;
        }

        $datosValidados = static::rules(
            $datos,
            $id
        );

        return $paciente->update($datosValidados);
    }

    /**
     * Elimina un paciente existente.
     */
    public static function eliminar(int $id): bool
    {
        $paciente = static::find($id);

        if (!$paciente) {
            return false;
        }

        return $paciente->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Búsqueda por DUI, atención médica y papelera
    |--------------------------------------------------------------------------
    */

    /**
     * Busca un paciente por su DUI (numero_identificacion). Es la forma de
     * identificar pacientes en los formularios: se escribe el DUI y debajo
     * aparece el nombre para confirmar.
     */
    public static function buscarPacienteXDui(string $dui): ?Paciente
    {
        return static::with('tipoSanguineo')
            ->where('numero_identificacion', trim($dui))
            ->first();
    }

    /**
     * Relaciones asistenciales del paciente (historial de quién lo atendió).
     */
    public function relacionesAsistenciales(): HasMany
    {
        return $this->hasMany(RelacionAsistencial::class, 'id_paciente');
    }

    /**
     * Indica si un médico atiende a un paciente: tiene con él una relación
     * asistencial activa (ver AccesoAsistencialService). Las relaciones se
     * crean al programar una cita o una referencia y se cierran de forma
     * explícita; una cita cancelada o terminada no las elimina.
     */
    public static function atendidoPor(int $idPaciente, int $idDoctor): bool
    {
        return RelacionAsistencial::query()
            ->activas()
            ->where('id_paciente', $idPaciente)
            ->where('id_doctor', $idDoctor)
            ->exists();
    }

    /**
     * Ids de los pacientes que atiende un médico (una sola consulta).
     */
    public static function idsAtendidosPor(int $idDoctor): array
    {
        return RelacionAsistencial::query()
            ->activas()
            ->where('id_doctor', $idDoctor)
            ->pluck('id_paciente')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Pacientes que atiende un médico (ver atendidoPor).
     */
    public static function mostrarTodosDeDoctor(int $idDoctor): Collection
    {
        return static::with('tipoSanguineo')
            ->whereIn(
                'id',
                RelacionAsistencial::query()
                    ->activas()
                    ->select('id_paciente')
                    ->where('id_doctor', $idDoctor)
            )
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();
    }

    /**
     * Pacientes eliminados (papelera).
     */
    public static function mostrarEliminados(): Collection
    {
        return static::onlyTrashed()
            ->with('tipoSanguineo')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();
    }

    /**
     * Restaura un paciente de la papelera.
     */
    public static function restaurar(int $id): bool
    {
        $paciente = static::onlyTrashed()->find($id);

        if (!$paciente) {
            return false;
        }

        return (bool) $paciente->restore();
    }
}
FIN_ARCHIVO
  escribir_archivo "Cita.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';

    protected $fillable = [
        'id_paciente',
        'id_doctor',
        'id_referencia_medica',
        'fecha_cita',
        'hora_inicio',
        'hora_fin',
        'estado',
        'motivo',
        'notas',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_doctor' => 'integer',
        'id_referencia_medica' => 'integer',
        'fecha_cita' => 'date',
    ];

    // Relaciones

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }

    /**
     * Referencia médica que originó la cita (si la hay).
     */
    public function referenciaMedica(): BelongsTo
    {
        return $this->belongsTo(ReferenciaMedica::class, 'id_referencia_medica');
    }

    // Reglas de validación

    public static function validaciones($id = null): array
    {
        return [
            'id_paciente' => [
                'required',
                'integer',
                'exists:pacientes,id',
            ],

            'id_doctor' => [
                'required',
                'integer',
                'exists:doctores,id',
            ],

            'fecha_cita' => [
                'required',
                'date',
            ],

            'hora_inicio' => [
                'required',
                'date_format:H:i',
            ],

            'hora_fin' => [
                'required',
                'date_format:H:i',
                'after:hora_inicio',
            ],

            'estado' => [
                'sometimes',
                'required',
                Rule::in([
                    'programada',
                    'completada',
                    'cancelada',
                    'no_asistio',
                    'reprogramada',
                ]),
            ],

            'motivo' => [
                'nullable',
                'string',
            ],

            'notas' => [
                'nullable',
                'string',
            ],
        ];
    }

    // Validación completa, incluyendo el horario del doctor
    // y los posibles cruces con otras citas.

    public static function rules(array $datos, $id = null): array
    {
        $validator = Validator::make(
            $datos,
            static::validaciones($id)
        );

        $validator->after(function ($validator) use ($datos, $id) {

            // Evitar ejecutar consultas si los datos necesarios
            // ya tienen errores de validación.

            if (
                $validator->errors()->has('id_doctor') ||
                $validator->errors()->has('fecha_cita') ||
                $validator->errors()->has('hora_inicio') ||
                $validator->errors()->has('hora_fin')
            ) {
                return;
            }

            $doctorId = $datos['id_doctor'];
            $fecha = $datos['fecha_cita'];
            $horaInicio = $datos['hora_inicio'];
            $horaFin = $datos['hora_fin'];

            // Obtener el día de la semana:
            // 1 = lunes, 7 = domingo.

            $diaSemana = \Carbon\Carbon::parse($fecha)
                ->dayOfWeekIso;

            // Comprobar que la cita esté dentro de un
            // horario registrado para el doctor.

            $horarioValido = HorarioDoctor::query()
                ->where('id_doctor', $doctorId)
                ->where('dia_semana', $diaSemana)
                ->where('hora_inicio', '<=', $horaInicio)
                ->where('hora_fin', '>=', $horaFin)
                ->exists();

            if (!$horarioValido) {
                $validator->errors()->add(
                    'hora_inicio',
                    'El doctor no tiene un horario registrado que cubra la fecha y las horas seleccionadas.'
                );
            }

            // Buscar citas que se crucen con el horario solicitado.
            // Las citas canceladas o en las que el paciente no
            // asistió no bloquean el horario.

            $citasSolapadas = static::query()
                ->where('id_doctor', $doctorId)
                ->whereDate('fecha_cita', $fecha)
                ->whereNotIn('estado', [
                    'cancelada',
                    'no_asistio',
                ])
                ->where('hora_inicio', '<', $horaFin)
                ->where('hora_fin', '>', $horaInicio);

            // Ignorar la cita actual al actualizarla.

            if ($id !== null) {
                $citasSolapadas->where('id', '<>', $id);
            }

            if ($citasSolapadas->exists()) {
                $validator->errors()->add(
                    'hora_inicio',
                    'El doctor ya tiene una cita programada en ese horario.'
                );
            }
        });

        return $validator->validate();
    }

    // Consultas

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
        ])
            ->orderBy('fecha_cita')
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * Citas de un doctor, en orden de fecha y hora.
     */
    public static function mostrarDeDoctor(int $idDoctor): Collection
    {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
        ])
            ->where('id_doctor', $idDoctor)
            ->orderBy('fecha_cita')
            ->orderBy('hora_inicio')
            ->get();
    }

    public static function buscarCitaXId(int $id): ?Cita
    {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
            'referenciaMedica.doctorOrigen.usuario',
            'referenciaMedica.especialidadDestino',
        ])->find($id);
    }

    // Operaciones CRUD

    public static function insertar(array $datos): Cita
    {
        $datos = static::rules($datos);

        return static::create($datos);
    }

    public static function actualizar(array $datos, int $id): bool
    {
        $cita = static::findOrFail($id);

        $datos = static::rules($datos, $id);

        return $cita->update($datos);
    }

    public static function eliminar(int $id): bool
    {
        $cita = static::findOrFail($id);

        return $cita->delete();
    }
}
FIN_ARCHIVO
  escribir_archivo "Notificacion.php" <<'FIN_ARCHIVO'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'id_usuario',
        'tipo',
        'titulo',
        'mensaje',
        'enlace',
        'leido_en'
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'leido_en' => 'datetime'
    ];

    // Relaciones

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // Validaciones

    public static function validaciones(): array
    {
        return [
            'id_usuario' => 'required|integer|exists:usuarios,id',
            'tipo' => 'required|string|max:100',
            'titulo' => 'required|string|max:150',
            'mensaje' => 'required|string',
            'enlace' => 'nullable|string|max:255',
            'leido_en' => 'nullable|date'
        ];
    }

    public static function reglasActualizacion(): array
    {
        return [
            'tipo' => 'sometimes|required|string|max:100',
            'titulo' => 'sometimes|required|string|max:150',
            'mensaje' => 'sometimes|required|string',
            'enlace' => 'sometimes|nullable|string|max:255',
            'leido_en' => 'sometimes|nullable|date'
        ];
    }

    public static function rules(array $datos, bool $actualizar = false): array
    {
        $reglas = $actualizar
            ? self::reglasActualizacion()
            : self::validaciones();

        return Validator::make($datos, $reglas)->validate();
    }

    // Avisos del sistema

    /**
     * Crea una notificación para un usuario (la usan los listeners de eventos).
     */
    public static function notificar(
        int $idUsuario,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?string $enlace = null
    ): Notificacion {
        return static::create([
            'id_usuario' => $idUsuario,
            'tipo' => $tipo,
            'titulo' => mb_substr($titulo, 0, 150),
            'mensaje' => $mensaje,
            'enlace' => $enlace,
        ]);
    }

    /**
     * Notifica a todos los usuarios de un rol. Devuelve cuántos recibieron el aviso.
     */
    public static function notificarARol(
        string $rol,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?string $enlace = null
    ): int {
        $ids = Usuario::query()
            ->whereHas('rol', fn ($consulta) => $consulta->where('rol', $rol))
            ->pluck('id');

        foreach ($ids as $idUsuario) {
            static::notificar((int) $idUsuario, $tipo, $titulo, $mensaje, $enlace);
        }

        return $ids->count();
    }

    // Consultas

    public static function mostrarTodos(): Collection
    {
        return self::with('usuario')
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarNotificacionXId(int $id): ?Notificacion
    {
        return self::with('usuario')->find($id);
    }

    // Inserción

    /**
     * Notificaciones de un usuario, de la más reciente a la más antigua.
     */
    public static function mostrarDeUsuario(int $idUsuario): Collection
    {
        return self::with('usuario')
            ->where('id_usuario', $idUsuario)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Marca como leída una notificación del usuario indicado.
     */
    public static function marcarLeida(int $id, int $idUsuario): bool
    {
        $notificacion = self::where('id_usuario', $idUsuario)->find($id);

        if (!$notificacion) {
            return false;
        }

        if ($notificacion->leido_en !== null) {
            return true;
        }

        return $notificacion->update(['leido_en' => now()]);
    }

    public static function insertar(array $datos): Notificacion
    {
        $datos = self::rules($datos);

        return self::create($datos);
    }

    // Actualización

    public function actualizar(array $datos): bool
    {
        $datos = self::rules($datos, true);

        return $this->update($datos);
    }
}
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p Policies
  descender Policies
  escribir_archivo "PacientePolicy.php" <<'FIN_ARCHIVO'
<?php

namespace App\Policies;

use App\Models\AccesoExcepcional;
use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Usuario;
use App\Services\AccesoAsistencialService;

/**
 * Autorización sobre el expediente clínico de un paciente.
 *
 * Las policies deciden si una operación está permitida; las consultas
 * Eloquent (por ejemplo Paciente::mostrarTodosDeDoctor) limitan qué registros
 * se obtienen. Son responsabilidades complementarias.
 *
 *  - Médico: expediente completo mientras tenga una relación asistencial
 *    activa con el paciente, o un acceso excepcional vigente.
 *  - Administrador: gestiona el sistema; el expediente solo con un acceso
 *    excepcional vigente (justificado, limitado y auditado).
 *  - Recepcionista y contador: nunca ven el expediente clínico.
 */
class PacientePolicy
{
    /**
     * ¿Puede consultar el expediente clínico completo?
     */
    public function viewExpediente(Usuario $usuario, Paciente $paciente): bool
    {
        if ($this->tieneRelacionActiva($usuario, $paciente)) {
            return true;
        }

        if (
            !$usuario->tieneRol(Usuario::ROL_MEDICO)
            && !$usuario->tieneRol(Usuario::ROL_ADMINISTRADOR)
        ) {
            return false;
        }

        return AccesoExcepcional::vigenteDe($usuario->getKey(), $paciente->getKey()) !== null;
    }

    /**
     * ¿Puede solicitar acceso de emergencia al expediente? Solo si es médico
     * o administrador y todavía no puede consultarlo.
     */
    public function solicitarAccesoExcepcional(Usuario $usuario, Paciente $paciente): bool
    {
        $puedeSolicitar = $usuario->tieneRol(Usuario::ROL_MEDICO)
            || $usuario->tieneRol(Usuario::ROL_ADMINISTRADOR);

        return $puedeSolicitar && !$this->viewExpediente($usuario, $paciente);
    }

    /**
     * ¿Puede escribir en el expediente (atenciones, antecedentes, recetas,
     * referencias)? Solo el médico con relación asistencial activa: un acceso
     * excepcional es de lectura.
     */
    public function registrarAtencion(Usuario $usuario, Paciente $paciente): bool
    {
        return $this->tieneRelacionActiva($usuario, $paciente);
    }

    /**
     * Agregar alergias o condiciones médicas al expediente.
     */
    public function agregarAntecedente(Usuario $usuario, Paciente $paciente): bool
    {
        return $this->registrarAtencion($usuario, $paciente);
    }

    /**
     * Referir al paciente con otro médico.
     */
    public function referir(Usuario $usuario, Paciente $paciente): bool
    {
        return $this->registrarAtencion($usuario, $paciente);
    }

    private function tieneRelacionActiva(Usuario $usuario, Paciente $paciente): bool
    {
        if (!$usuario->tieneRol(Usuario::ROL_MEDICO)) {
            return false;
        }

        $doctor = Doctor::buscarPorUsuario($usuario->getKey());

        return $doctor !== null
            && app(AccesoAsistencialService::class)
                ->tieneRelacionActiva($paciente->getKey(), $doctor->getKey());
    }
}
FIN_ARCHIVO
  escribir_archivo "RegistroMedicoPolicy.php" <<'FIN_ARCHIVO'
<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\RegistroMedico;
use App\Models\Usuario;

/**
 * Autorización sobre un registro médico concreto.
 *
 *  - Leer: quien puede consultar el expediente del paciente.
 *  - Escribir (diagnósticos, recetas): solo el médico autor del registro.
 *    Leer un registro no autoriza a modificarlo.
 *  - Eliminar y restaurar: el administrador (papelera), siempre auditado.
 */
class RegistroMedicoPolicy
{
    public function view(Usuario $usuario, RegistroMedico $registro): bool
    {
        $paciente = $registro->paciente;

        return $paciente !== null
            && $usuario->can('viewExpediente', $paciente);
    }

    public function escribir(Usuario $usuario, RegistroMedico $registro): bool
    {
        if (!$usuario->tieneRol(Usuario::ROL_MEDICO) || $registro->trashed()) {
            return false;
        }

        $doctor = Doctor::buscarPorUsuario($usuario->getKey());

        return $doctor !== null
            && (int) $registro->id_doctor === (int) $doctor->getKey();
    }

    public function delete(Usuario $usuario, RegistroMedico $registro): bool
    {
        return $usuario->tieneRol(Usuario::ROL_ADMINISTRADOR);
    }

    public function restore(Usuario $usuario, RegistroMedico $registro): bool
    {
        return $usuario->tieneRol(Usuario::ROL_ADMINISTRADOR);
    }
}
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p Services
  descender Services
  escribir_archivo "AccesoAsistencialService.php" <<'FIN_ARCHIVO'
<?php

namespace App\Services;

use App\Models\AuditoriaExpediente;
use App\Models\Cita;
use App\Models\RegistroMedico;
use App\Models\RelacionAsistencial;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de dominio de las relaciones asistenciales.
 *
 *  - Programar una cita con un médico crea (o reutiliza) su relación.
 *  - La relación sobrevive a la cita: solo se cierra de forma explícita,
 *    con fecha y motivo. Una relación cerrada queda como historial.
 *  - Si el paciente vuelve con un médico cuya relación se cerró, se abre un
 *    episodio nuevo (no se reescribe el anterior).
 *  - Una relación suspendida no se reactiva sola: la suspensión se respeta.
 */
class AccesoAsistencialService
{
    public function tieneRelacionActiva(int $idPaciente, int $idDoctor): bool
    {
        return RelacionAsistencial::query()
            ->activas()
            ->where('id_paciente', $idPaciente)
            ->where('id_doctor', $idDoctor)
            ->exists();
    }

    /**
     * Crea o reutiliza la relación que origina una cita. Las citas canceladas
     * o a las que el paciente no asistió no generan relación.
     */
    public function activarPorCita(Cita $cita): ?RelacionAsistencial
    {
        if (in_array($cita->estado, ['cancelada', 'no_asistio'], true)) {
            return null;
        }

        if ($cita->id_referencia_medica) {
            $tipo = RelacionAsistencial::TIPO_REFERENCIA;
            $motivo = 'Cita programada a partir de una referencia médica.';
        } else {
            $tipo = $this->hayPrincipalActivo($cita->id_paciente, $cita->id_doctor)
                ? RelacionAsistencial::TIPO_SEGUIMIENTO
                : RelacionAsistencial::TIPO_PRINCIPAL;
            $motivo = 'Cita médica programada.';
        }

        return $this->activar(
            $cita->id_paciente,
            $cita->id_doctor,
            $tipo,
            $cita->getKey(),
            $cita->id_referencia_medica,
            $motivo
        );
    }

    /**
     * Crea una relación activa o reutiliza la que ya existe entre el paciente
     * y el médico.
     */
    public function activar(
        int $idPaciente,
        int $idDoctor,
        string $tipo,
        ?int $idCita,
        ?int $idReferencia,
        string $motivo
    ): RelacionAsistencial {
        return DB::transaction(function () use ($idPaciente, $idDoctor, $tipo, $idCita, $idReferencia, $motivo) {
            $existente = RelacionAsistencial::query()
                ->where('id_paciente', $idPaciente)
                ->where('id_doctor', $idDoctor)
                ->whereIn('estado', [
                    RelacionAsistencial::ESTADO_ACTIVA,
                    RelacionAsistencial::ESTADO_SUSPENDIDA,
                ])
                ->lockForUpdate()
                ->first();

            if ($existente) {
                $completar = [];

                if (!$existente->id_cita && $idCita) {
                    $completar['id_cita'] = $idCita;
                }

                if (!$existente->id_referencia_medica && $idReferencia) {
                    $completar['id_referencia_medica'] = $idReferencia;
                }

                if ($completar !== []) {
                    $existente->update($completar);
                }

                return $existente;
            }

            return RelacionAsistencial::create([
                'id_paciente' => $idPaciente,
                'id_doctor' => $idDoctor,
                'id_cita' => $idCita,
                'id_referencia_medica' => $idReferencia,
                'tipo_relacion' => $tipo,
                'estado' => RelacionAsistencial::ESTADO_ACTIVA,
                'fecha_inicio' => now(),
                'motivo' => $motivo,
            ]);
        });
    }

    /**
     * Si una cita se cancela (o el paciente no asiste) y el médico nunca
     * llegó a atender al paciente, la relación que esa cita originó se
     * revoca para que no quede acceso sin atención.
     */
    public function revocarPorCitaSinAtencion(Cita $cita): void
    {
        $relaciones = RelacionAsistencial::query()
            ->activas()
            ->where('id_cita', $cita->getKey())
            ->get();

        foreach ($relaciones as $relacion) {
            $otraCita = Cita::query()
                ->where('id_paciente', $relacion->id_paciente)
                ->where('id_doctor', $relacion->id_doctor)
                ->where('id', '<>', $cita->getKey())
                ->whereNotIn('estado', ['cancelada', 'no_asistio'])
                ->exists();

            $conAtencion = RegistroMedico::query()
                ->where('id_paciente', $relacion->id_paciente)
                ->where('id_doctor', $relacion->id_doctor)
                ->exists();

            if ($otraCita || $conAtencion) {
                continue;
            }

            $this->cambiarEstado(
                $relacion,
                RelacionAsistencial::ESTADO_REVOCADA,
                'La cita se canceló antes de que el médico atendiera al paciente.',
                null
            );
        }
    }

    /**
     * Cierre explícito (médico o administrador): fecha y motivo obligatorios.
     */
    public function cerrar(RelacionAsistencial $relacion, string $motivo, ?Usuario $actor): RelacionAsistencial
    {
        return $this->cambiarEstado(
            $relacion,
            RelacionAsistencial::ESTADO_CERRADA,
            $motivo,
            $actor,
            [RelacionAsistencial::ESTADO_ACTIVA, RelacionAsistencial::ESTADO_SUSPENDIDA]
        );
    }

    /**
     * Revocación por el administrador.
     */
    public function revocar(RelacionAsistencial $relacion, string $motivo, ?Usuario $actor): RelacionAsistencial
    {
        return $this->cambiarEstado(
            $relacion,
            RelacionAsistencial::ESTADO_REVOCADA,
            $motivo,
            $actor,
            [RelacionAsistencial::ESTADO_ACTIVA, RelacionAsistencial::ESTADO_SUSPENDIDA]
        );
    }

    public function suspender(RelacionAsistencial $relacion, string $motivo, ?Usuario $actor): RelacionAsistencial
    {
        return $this->cambiarEstado(
            $relacion,
            RelacionAsistencial::ESTADO_SUSPENDIDA,
            $motivo,
            $actor,
            [RelacionAsistencial::ESTADO_ACTIVA]
        );
    }

    public function reactivar(RelacionAsistencial $relacion, string $motivo, ?Usuario $actor): RelacionAsistencial
    {
        return $this->cambiarEstado(
            $relacion,
            RelacionAsistencial::ESTADO_ACTIVA,
            $motivo,
            $actor,
            [RelacionAsistencial::ESTADO_SUSPENDIDA]
        );
    }

    private function hayPrincipalActivo(int $idPaciente, int $idDoctor): bool
    {
        return RelacionAsistencial::query()
            ->activas()
            ->where('id_paciente', $idPaciente)
            ->where('id_doctor', '<>', $idDoctor)
            ->where('tipo_relacion', RelacionAsistencial::TIPO_PRINCIPAL)
            ->exists();
    }

    /**
     * Cambia el estado de una relación y lo deja auditado.
     *
     * @param  array<int, string>|null  $desde  Estados desde los que se permite el cambio
     */
    private function cambiarEstado(
        RelacionAsistencial $relacion,
        string $nuevo,
        string $motivo,
        ?Usuario $actor,
        ?array $desde = null
    ): RelacionAsistencial {
        return DB::transaction(function () use ($relacion, $nuevo, $motivo, $actor, $desde) {
            $relacion = RelacionAsistencial::query()->whereKey($relacion->getKey())->lockForUpdate()->firstOrFail();

            if ($desde !== null && !in_array($relacion->estado, $desde, true)) {
                throw ValidationException::withMessages([
                    'relacion' => 'La relación ya no está en un estado que permita esta operación.',
                ]);
            }

            $anterior = $relacion->estado;
            $cambios = ['estado' => $nuevo];

            if (in_array($nuevo, [RelacionAsistencial::ESTADO_CERRADA, RelacionAsistencial::ESTADO_REVOCADA], true)) {
                $cambios['fecha_fin'] = now();
                $cambios['motivo_cierre'] = mb_substr($motivo, 0, 255);
                $cambios['id_usuario_cierre'] = $actor?->getKey();
            } elseif ($nuevo === RelacionAsistencial::ESTADO_ACTIVA) {
                $cambios['fecha_fin'] = null;
                $cambios['motivo_cierre'] = null;
                $cambios['id_usuario_cierre'] = null;
            } else {
                $cambios['motivo_cierre'] = mb_substr($motivo, 0, 255);
            }

            $relacion->update($cambios);

            AuditoriaExpediente::registrar(
                $relacion->id_paciente,
                AuditoriaExpediente::GESTION_ACCESO,
                'relacion_asistencial',
                $relacion->getKey(),
                mb_substr($motivo, 0, 500),
                [
                    'doctor' => $relacion->id_doctor,
                    'estado_anterior' => $anterior,
                    'estado_nuevo' => $nuevo,
                ],
                $relacion->id_doctor
            );

            return $relacion;
        });
    }
}
FIN_ARCHIVO
  escribir_archivo "ReferenciaMedicaService.php" <<'FIN_ARCHIVO'
<?php

namespace App\Services;

use App\Events\CitaCancelada;
use App\Events\CitaProgramada;
use App\Events\ReferenciaMedicaAtendida;
use App\Events\ReferenciaMedicaCreada;
use App\Models\AuditoriaExpediente;
use App\Models\Cita;
use App\Models\Doctor;
use App\Models\ReferenciaMedica;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ciclo de vida de una referencia médica.
 *
 * Emitir una referencia NO crea una cita: genera una solicitud de
 * programación para recepción. La cita se crea cuando recepción confirma el
 * horario (programar), todo dentro de una transacción y con la referencia
 * bloqueada para que dos recepcionistas no puedan programarla a la vez.
 */
class ReferenciaMedicaService
{
    public function __construct(private readonly AccesoAsistencialService $acceso)
    {
    }

    /**
     * El médico emite una referencia sobre un paciente que atiende.
     */
    public function emitir(Doctor $origen, array $datos): ReferenciaMedica
    {
        return DB::transaction(function () use ($origen, $datos) {
            $datos = ReferenciaMedica::rules($datos + [
                'id_doctor_origen' => $origen->getKey(),
            ]);

            $referencia = ReferenciaMedica::create($datos + [
                'numero_referencia' => ReferenciaMedica::generarNumero(),
                'estado' => ReferenciaMedica::ESTADO_PENDIENTE,
                'fecha_referencia' => now(),
            ]);

            AuditoriaExpediente::registrar(
                $referencia->id_paciente,
                AuditoriaExpediente::CREACION,
                'referencia_medica',
                $referencia->getKey(),
                null,
                [
                    'numero' => $referencia->numero_referencia,
                    'prioridad' => $referencia->prioridad,
                    'doctor_destino' => $referencia->id_doctor_destino,
                    'especialidad_destino' => $referencia->id_especialidad_destino,
                ],
                $origen->getKey()
            );

            event(new ReferenciaMedicaCreada($referencia));

            return $referencia;
        });
    }

    /**
     * Recepción programa la referencia: crea la cita, la vincula, actualiza
     * la referencia y activa la relación asistencial del especialista.
     *
     * @param  array{id_doctor:int, fecha_cita:string, hora_inicio:string, hora_fin:string, notas?:?string}  $datos
     */
    public function programar(ReferenciaMedica $referencia, array $datos): Cita
    {
        return DB::transaction(function () use ($referencia, $datos) {
            $referencia = ReferenciaMedica::query()
                ->whereKey($referencia->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($referencia->estado !== ReferenciaMedica::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages([
                    'referencia' => 'La referencia ya no está pendiente de programación (estado: '
                        . $referencia->estado . ').',
                ]);
            }

            if ($referencia->estaVencida()) {
                $referencia->update(['estado' => ReferenciaMedica::ESTADO_VENCIDA]);

                throw ValidationException::withMessages([
                    'referencia' => 'La referencia venció y ya no puede programarse.',
                ]);
            }

            $doctor = Doctor::query()->find($datos['id_doctor'] ?? null);

            if (!$doctor) {
                throw ValidationException::withMessages([
                    'id_doctor' => 'Seleccione el médico que atenderá la cita.',
                ]);
            }

            if ($referencia->id_doctor_destino && (int) $doctor->getKey() !== (int) $referencia->id_doctor_destino) {
                throw ValidationException::withMessages([
                    'id_doctor' => 'La referencia solicita a un médico específico: la cita debe asignarse a él.',
                ]);
            }

            if (
                $referencia->id_especialidad_destino
                && (int) $doctor->especialidad_id !== (int) $referencia->id_especialidad_destino
            ) {
                throw ValidationException::withMessages([
                    'id_doctor' => 'El médico seleccionado no pertenece a la especialidad solicitada.',
                ]);
            }

            if ((int) $doctor->getKey() === (int) $referencia->id_doctor_origen) {
                throw ValidationException::withMessages([
                    'id_doctor' => 'La cita no puede asignarse al médico que emitió la referencia.',
                ]);
            }

            // Valida horario del doctor y cruces con otras citas.
            $datosCita = Cita::rules([
                'id_paciente' => $referencia->id_paciente,
                'id_doctor' => $doctor->getKey(),
                'fecha_cita' => $datos['fecha_cita'] ?? null,
                'hora_inicio' => $datos['hora_inicio'] ?? null,
                'hora_fin' => $datos['hora_fin'] ?? null,
                'estado' => 'programada',
                'motivo' => mb_substr($referencia->motivo, 0, 255),
                'notas' => $datos['notas'] ?? null,
            ]);

            $cita = Cita::create($datosCita + [
                'id_referencia_medica' => $referencia->getKey(),
            ]);

            $referencia->update([
                'estado' => ReferenciaMedica::ESTADO_PROGRAMADA,
                'id_doctor_destino' => $referencia->id_doctor_destino ?? $doctor->getKey(),
            ]);

            $this->acceso->activarPorCita($cita);

            AuditoriaExpediente::registrar(
                $referencia->id_paciente,
                AuditoriaExpediente::GESTION_ACCESO,
                'referencia_medica',
                $referencia->getKey(),
                null,
                [
                    'numero' => $referencia->numero_referencia,
                    'accion' => 'programada',
                    'cita' => $cita->getKey(),
                    'doctor' => $doctor->getKey(),
                ],
                $doctor->getKey()
            );

            event(new CitaProgramada($cita));

            return $cita;
        });
    }

    /**
     * El médico de destino o recepción rechazan una referencia pendiente.
     */
    public function rechazar(ReferenciaMedica $referencia, string $motivo): ReferenciaMedica
    {
        return $this->cerrarPendiente($referencia, ReferenciaMedica::ESTADO_RECHAZADA, $motivo);
    }

    /**
     * Cancela una referencia pendiente o programada. Si ya tenía una cita
     * programada, esta se cancela también.
     */
    public function cancelar(ReferenciaMedica $referencia, string $motivo): ReferenciaMedica
    {
        return DB::transaction(function () use ($referencia, $motivo) {
            $referencia = ReferenciaMedica::query()->whereKey($referencia->getKey())->lockForUpdate()->firstOrFail();

            if (!in_array($referencia->estado, [
                ReferenciaMedica::ESTADO_PENDIENTE,
                ReferenciaMedica::ESTADO_PROGRAMADA,
            ], true)) {
                throw ValidationException::withMessages([
                    'referencia' => 'La referencia ya no se puede cancelar (estado: ' . $referencia->estado . ').',
                ]);
            }

            if ($referencia->estado === ReferenciaMedica::ESTADO_PROGRAMADA) {
                $cita = Cita::query()->where('id_referencia_medica', $referencia->getKey())->first();

                if ($cita && in_array($cita->estado, ['programada', 'reprogramada'], true)) {
                    $cita->update(['estado' => 'cancelada']);
                    $this->acceso->revocarPorCitaSinAtencion($cita);

                    event(new CitaCancelada($cita));
                }
            }

            return $this->guardarCierre($referencia, ReferenciaMedica::ESTADO_CANCELADA, $motivo);
        });
    }

    /**
     * Cuando una cita derivada de una referencia se completa, la referencia
     * queda atendida y se avisa al médico que la emitió.
     */
    public function marcarAtendidaPorCita(Cita $cita): void
    {
        if (!$cita->id_referencia_medica) {
            return;
        }

        $referencia = ReferenciaMedica::query()
            ->whereKey($cita->id_referencia_medica)
            ->where('estado', ReferenciaMedica::ESTADO_PROGRAMADA)
            ->first();

        if (!$referencia) {
            return;
        }

        $referencia->update(['estado' => ReferenciaMedica::ESTADO_ATENDIDA]);

        AuditoriaExpediente::registrar(
            $referencia->id_paciente,
            AuditoriaExpediente::MODIFICACION,
            'referencia_medica',
            $referencia->getKey(),
            null,
            ['numero' => $referencia->numero_referencia, 'estado_nuevo' => 'atendida'],
            $cita->id_doctor
        );

        event(new ReferenciaMedicaAtendida($referencia, $cita));
    }

    /**
     * Si la cita de una referencia se cancela (o el paciente no asiste), la
     * referencia vuelve a quedar pendiente para que recepción la reprograme.
     * La cita pierde el vínculo para que la restricción única permita crear
     * la nueva; el origen queda anotado en sus notas.
     */
    public function liberarPorCitaCancelada(Cita $cita): void
    {
        if (!$cita->id_referencia_medica) {
            return;
        }

        $referencia = ReferenciaMedica::query()
            ->whereKey($cita->id_referencia_medica)
            ->where('estado', ReferenciaMedica::ESTADO_PROGRAMADA)
            ->lockForUpdate()
            ->first();

        $numero = $referencia?->numero_referencia ?? ('#' . $cita->id_referencia_medica);
        $nota = trim(($cita->notas ? $cita->notas . "\n" : '') . 'Originada por la referencia ' . $numero . '.');

        $cita->update([
            'id_referencia_medica' => null,
            'notas' => $nota,
        ]);

        if ($referencia) {
            $referencia->update(['estado' => ReferenciaMedica::ESTADO_PENDIENTE]);
        }
    }

    /**
     * Marca como vencidas las referencias pendientes que pasaron su plazo.
     * Es seguro ejecutarlo varias veces.
     */
    public function vencerPendientes(): int
    {
        $total = 0;

        foreach (ReferenciaMedica::DIAS_VIGENCIA as $prioridad => $dias) {
            $total += ReferenciaMedica::query()
                ->where('estado', ReferenciaMedica::ESTADO_PENDIENTE)
                ->where('prioridad', $prioridad)
                ->where('fecha_referencia', '<', now()->subDays($dias))
                ->update(['estado' => ReferenciaMedica::ESTADO_VENCIDA]);
        }

        return $total;
    }

    private function cerrarPendiente(ReferenciaMedica $referencia, string $estado, string $motivo): ReferenciaMedica
    {
        return DB::transaction(function () use ($referencia, $estado, $motivo) {
            $referencia = ReferenciaMedica::query()->whereKey($referencia->getKey())->lockForUpdate()->firstOrFail();

            if ($referencia->estado !== ReferenciaMedica::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages([
                    'referencia' => 'Solo se puede rechazar una referencia pendiente (estado: '
                        . $referencia->estado . ').',
                ]);
            }

            return $this->guardarCierre($referencia, $estado, $motivo);
        });
    }

    private function guardarCierre(ReferenciaMedica $referencia, string $estado, string $motivo): ReferenciaMedica
    {
        $observaciones = trim(
            ($referencia->observaciones ? $referencia->observaciones . "\n" : '')
            . ucfirst($estado) . ': ' . $motivo
        );

        $referencia->update([
            'estado' => $estado,
            'observaciones' => $observaciones,
        ]);

        AuditoriaExpediente::registrar(
            $referencia->id_paciente,
            AuditoriaExpediente::MODIFICACION,
            'referencia_medica',
            $referencia->getKey(),
            mb_substr($motivo, 0, 500),
            ['numero' => $referencia->numero_referencia, 'estado_nuevo' => $estado]
        );

        return $referencia;
    }
}
FIN_ARCHIVO
  escribir_archivo "CitaService.php" <<'FIN_ARCHIVO'
<?php

namespace App\Services;

use App\Events\CitaCancelada;
use App\Events\CitaProgramada;
use App\Models\Cita;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Operaciones sobre citas que además afectan relaciones asistenciales y
 * referencias médicas. Todo ocurre en una transacción y los avisos se
 * envían cuando esta se confirma.
 */
class CitaService
{
    private const ESTADOS_SIN_ATENCION = ['cancelada', 'no_asistio'];

    public function __construct(
        private readonly AccesoAsistencialService $acceso,
        private readonly ReferenciaMedicaService $referencias
    ) {
    }

    /**
     * Cita ordinaria creada por recepción: activa la relación del médico.
     */
    public function crear(array $datos): Cita
    {
        return DB::transaction(function () use ($datos) {
            $cita = Cita::insertar($datos);

            $this->acceso->activarPorCita($cita);

            event(new CitaProgramada($cita));

            return $cita;
        });
    }

    public function actualizar(Cita $cita, array $datos): Cita
    {
        return DB::transaction(function () use ($cita, $datos) {
            $cita = Cita::query()->whereKey($cita->getKey())->lockForUpdate()->firstOrFail();

            $estadoAnterior = $cita->estado;
            $doctorAnterior = (int) $cita->id_doctor;

            // Una cita nacida de una referencia conserva paciente y médico.
            if ($cita->id_referencia_medica) {
                if (isset($datos['id_doctor']) && (int) $datos['id_doctor'] !== $doctorAnterior) {
                    throw ValidationException::withMessages([
                        'id_doctor' => 'La cita proviene de una referencia: el médico no puede cambiarse.',
                    ]);
                }

                if (isset($datos['id_paciente']) && (int) $datos['id_paciente'] !== (int) $cita->id_paciente) {
                    throw ValidationException::withMessages([
                        'dui_paciente' => 'La cita proviene de una referencia: el paciente no puede cambiarse.',
                    ]);
                }
            }

            Cita::actualizar($datos, $cita->getKey());
            $cita->refresh();

            $sinAtencionAntes = in_array($estadoAnterior, self::ESTADOS_SIN_ATENCION, true);
            $sinAtencionAhora = in_array($cita->estado, self::ESTADOS_SIN_ATENCION, true);

            if ($sinAtencionAhora && !$sinAtencionAntes) {
                $this->acceso->revocarPorCitaSinAtencion($cita);
                $this->referencias->liberarPorCitaCancelada($cita);

                event(new CitaCancelada($cita));

                return $cita;
            }

            if (!$sinAtencionAhora) {
                if ((int) $cita->id_doctor !== $doctorAnterior) {
                    // La relación que esta cita originó con el médico anterior se
                    // revoca si él nunca llegó a atender al paciente.
                    $this->acceso->revocarPorCitaSinAtencion($cita);
                }

                if ((int) $cita->id_doctor !== $doctorAnterior || $sinAtencionAntes) {
                    $this->acceso->activarPorCita($cita);

                    event(new CitaProgramada($cita));
                }

                if ($cita->estado === 'completada' && $estadoAnterior !== 'completada') {
                    $this->referencias->marcarAtendidaPorCita($cita);
                }
            }

            return $cita;
        });
    }

    public function eliminar(Cita $cita): bool
    {
        return DB::transaction(function () use ($cita) {
            $cita = Cita::query()->whereKey($cita->getKey())->lockForUpdate()->firstOrFail();

            $this->acceso->revocarPorCitaSinAtencion($cita);
            $this->referencias->liberarPorCitaCancelada($cita);

            return Cita::eliminar($cita->getKey());
        });
    }

    /**
     * El médico registró la atención de una cita: pasa a completada y, si
     * venía de una referencia, esta queda atendida.
     */
    public function completarPorAtencion(int $idCita): void
    {
        $cita = Cita::query()->whereKey($idCita)->lockForUpdate()->first();

        if (!$cita || !in_array($cita->estado, ['programada', 'reprogramada'], true)) {
            return;
        }

        $cita->update(['estado' => 'completada']);

        $this->referencias->marcarAtendidaPorCita($cita);
    }
}
FIN_ARCHIVO
  escribir_archivo "AccesoExcepcionalService.php" <<'FIN_ARCHIVO'
<?php

namespace App\Services;

use App\Events\AccesoExpedienteRegistrado;
use App\Models\AccesoExcepcional;
use App\Models\AuditoriaExpediente;
use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Acceso de emergencia (break-glass) a un expediente.
 *
 * No es una forma alternativa de saltarse las policies: es una excepción
 * con justificación obligatoria, vigencia limitada (4 horas), alerta de
 * auditoría y revisión posterior. Concede lectura, no escritura.
 */
class AccesoExcepcionalService
{
    public function solicitar(Usuario $usuario, Paciente $paciente, string $motivo): AccesoExcepcional
    {
        return DB::transaction(function () use ($usuario, $paciente, $motivo) {
            $vigente = AccesoExcepcional::vigenteDe($usuario->getKey(), $paciente->getKey());

            if ($vigente) {
                return $vigente;
            }

            $acceso = AccesoExcepcional::create([
                'id_paciente' => $paciente->getKey(),
                'id_usuario' => $usuario->getKey(),
                'motivo' => $motivo,
                'fecha_inicio' => now(),
                'fecha_expiracion' => now()->addHours(AccesoExcepcional::HORAS_VIGENCIA),
                'estado' => AccesoExcepcional::ESTADO_ACTIVO,
                'ip' => app()->runningInConsole() ? null : request()->ip(),
            ]);

            AuditoriaExpediente::registrar(
                $paciente->getKey(),
                AuditoriaExpediente::ACCESO_EXCEPCIONAL,
                'acceso_excepcional',
                $acceso->getKey(),
                mb_substr($motivo, 0, 500),
                ['expira' => $acceso->fecha_expiracion->toDateTimeString()],
                Doctor::buscarPorUsuario($usuario->getKey())?->getKey()
            );

            event(new AccesoExpedienteRegistrado($acceso));

            return $acceso;
        });
    }

    /**
     * El administrador revoca un acceso excepcional antes de que expire.
     */
    public function revocar(AccesoExcepcional $acceso, Usuario $actor): AccesoExcepcional
    {
        return DB::transaction(function () use ($acceso, $actor) {
            $acceso->update([
                'estado' => AccesoExcepcional::ESTADO_REVOCADO,
                'fecha_expiracion' => now(),
            ]);

            AuditoriaExpediente::registrar(
                $acceso->id_paciente,
                AuditoriaExpediente::GESTION_ACCESO,
                'acceso_excepcional',
                $acceso->getKey(),
                'Acceso excepcional revocado por ' . $actor->nombres . ' ' . $actor->apellidos . '.',
                ['usuario' => $acceso->id_usuario]
            );

            return $acceso;
        });
    }

    /**
     * Registra la revisión posterior del acceso por un administrador.
     */
    public function revisar(AccesoExcepcional $acceso, Usuario $revisor, ?string $observacion): AccesoExcepcional
    {
        return DB::transaction(function () use ($acceso, $revisor, $observacion) {
            $acceso->update([
                'revisado_por' => $revisor->getKey(),
                'revisado_en' => now(),
                'observacion_revision' => $observacion,
            ]);

            AuditoriaExpediente::registrar(
                $acceso->id_paciente,
                AuditoriaExpediente::GESTION_ACCESO,
                'acceso_excepcional',
                $acceso->getKey(),
                'Acceso excepcional revisado.',
                ['usuario' => $acceso->id_usuario]
            );

            return $acceso;
        });
    }
}
FIN_ARCHIVO
  escribir_archivo "RecordatorioTareasService.php" <<'FIN_ARCHIVO'
<?php

namespace App\Services;

use App\Events\TareaPorVencer;
use App\Models\TareaPersonal;

/**
 * Genera los recordatorios y avisos de vencimiento de las tareas personales.
 *
 * Es seguro ejecutarlo más de una vez (o desde dos procesos a la vez):
 * cada aviso se "reserva" con un UPDATE condicional sobre su marca de envío,
 * de modo que solo quien logra la reserva notifica. Las tareas completadas
 * o canceladas no generan avisos y reprogramar una tarea reinicia sus marcas.
 */
class RecordatorioTareasService
{
    /**
     * @return array{recordatorios:int, vencidas:int}
     */
    public function procesar(): array
    {
        $ahora = now();

        return [
            'recordatorios' => $this->enviar(
                'fecha_recordatorio',
                'recordatorio_enviado_en',
                'recordatorio',
                $ahora
            ),
            'vencidas' => $this->enviar(
                'fecha_vencimiento',
                'aviso_vencimiento_enviado_en',
                'vencida',
                $ahora
            ),
        ];
    }

    private function enviar(string $campoFecha, string $campoMarca, string $tipo, $ahora): int
    {
        $enviados = 0;

        $pendientes = TareaPersonal::query()
            ->abiertas()
            ->whereNotNull($campoFecha)
            ->where($campoFecha, '<=', $ahora)
            ->whereNull($campoMarca)
            ->get();

        foreach ($pendientes as $tarea) {
            $reservada = TareaPersonal::query()
                ->whereKey($tarea->getKey())
                ->whereIn('estado', TareaPersonal::ESTADOS_ABIERTOS)
                ->whereNull($campoMarca)
                ->update([$campoMarca => $ahora]);

            if ($reservada === 1) {
                event(new TareaPorVencer($tarea, $tipo));
                $enviados++;
            }
        }

        return $enviados;
    }
}
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p Events
  descender Events
  escribir_archivo "ReferenciaMedicaCreada.php" <<'FIN_ARCHIVO'
<?php

namespace App\Events;

use App\Models\ReferenciaMedica;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Un médico emitió una referencia médica.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class ReferenciaMedicaCreada implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public ReferenciaMedica $referencia)
    {
    }
}
FIN_ARCHIVO
  escribir_archivo "CitaProgramada.php" <<'FIN_ARCHIVO'
<?php

namespace App\Events;

use App\Models\Cita;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se programó una cita con un médico.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class CitaProgramada implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Cita $cita)
    {
    }
}
FIN_ARCHIVO
  escribir_archivo "CitaCancelada.php" <<'FIN_ARCHIVO'
<?php

namespace App\Events;

use App\Models\Cita;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se canceló una cita (o el paciente no asistió).
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class CitaCancelada implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Cita $cita)
    {
    }
}
FIN_ARCHIVO
  escribir_archivo "ReferenciaMedicaAtendida.php" <<'FIN_ARCHIVO'
<?php

namespace App\Events;

use App\Models\Cita;
use App\Models\ReferenciaMedica;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * El especialista completó la atención de una referencia.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class ReferenciaMedicaAtendida implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public ReferenciaMedica $referencia, public Cita $cita)
    {
    }
}
FIN_ARCHIVO
  escribir_archivo "RegistroClinicoActualizado.php" <<'FIN_ARCHIVO'
<?php

namespace App\Events;


use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Hay información clínica nueva en el expediente de un paciente. Solo
 * lleva identificadores: la notificación nunca incluye el contenido clínico.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class RegistroClinicoActualizado implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $idPaciente, public int $idDoctorAutor, public string $entidad)
    {
    }
}
FIN_ARCHIVO
  escribir_archivo "AccesoExpedienteRegistrado.php" <<'FIN_ARCHIVO'
<?php

namespace App\Events;

use App\Models\AccesoExcepcional;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se concedió un acceso excepcional (break-glass) a un expediente.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class AccesoExpedienteRegistrado implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public AccesoExcepcional $acceso)
    {
    }
}
FIN_ARCHIVO
  escribir_archivo "TareaPorVencer.php" <<'FIN_ARCHIVO'
<?php

namespace App\Events;

use App\Models\TareaPersonal;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Una tarea llegó a su recordatorio ($tipo = recordatorio) o venció ($tipo = vencida).
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class TareaPorVencer implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public TareaPersonal $tarea, public string $tipo)
    {
    }
}
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p Listeners
  descender Listeners
  escribir_archivo "NotificarReferenciaPendiente.php" <<'FIN_ARCHIVO'
<?php

namespace App\Listeners;

use App\Events\ReferenciaMedicaCreada;
use App\Models\Notificacion;
use App\Models\Usuario;

/**
 * Avisa a recepción que hay una referencia pendiente de programación.
 */
class NotificarReferenciaPendiente
{
    public function handle(ReferenciaMedicaCreada $evento): void
    {
        $referencia = $evento->referencia;

        Notificacion::notificarARol(
            Usuario::ROL_RECEPCIONISTA,
            'referencia_pendiente',
            'Referencia pendiente de programación',
            'La referencia ' . $referencia->numero_referencia
                . ' (prioridad ' . $referencia->prioridad . ') espera que se programe la cita.',
            route('referencias')
        );
    }
}
FIN_ARCHIVO
  escribir_archivo "NotificarCitaAsignada.php" <<'FIN_ARCHIVO'
<?php

namespace App\Listeners;

use App\Events\CitaProgramada;
use App\Models\Doctor;
use App\Models\Notificacion;

/**
 * Avisa al médico que se le asignó una cita.
 */
class NotificarCitaAsignada
{
    public function handle(CitaProgramada $evento): void
    {
        $cita = $evento->cita;
        $idUsuario = Doctor::query()->whereKey($cita->id_doctor)->value('usuario_id');

        if (!$idUsuario) {
            return;
        }

        Notificacion::notificar(
            (int) $idUsuario,
            'cita_asignada',
            'Nueva cita asignada',
            'Tiene una cita el ' . $cita->fecha_cita->format('d/m/Y')
                . ' a las ' . substr($cita->hora_inicio, 0, 5)
                . ($cita->id_referencia_medica ? ' (proviene de una referencia médica).' : '.'),
            route('citas.detalle', ['cita' => $cita->getKey()])
        );
    }
}
FIN_ARCHIVO
  escribir_archivo "NotificarCitaCancelada.php" <<'FIN_ARCHIVO'
<?php

namespace App\Listeners;

use App\Events\CitaCancelada;
use App\Models\Doctor;
use App\Models\Notificacion;

/**
 * Avisa al médico que una de sus citas se canceló.
 */
class NotificarCitaCancelada
{
    public function handle(CitaCancelada $evento): void
    {
        $cita = $evento->cita;
        $idUsuario = Doctor::query()->whereKey($cita->id_doctor)->value('usuario_id');

        if (!$idUsuario) {
            return;
        }

        Notificacion::notificar(
            (int) $idUsuario,
            'cita_cancelada',
            'Cita cancelada',
            'Se canceló la cita del ' . $cita->fecha_cita->format('d/m/Y')
                . ' a las ' . substr($cita->hora_inicio, 0, 5) . '.',
            route('citas')
        );
    }
}
FIN_ARCHIVO
  escribir_archivo "NotificarReferenciaAtendida.php" <<'FIN_ARCHIVO'
<?php

namespace App\Listeners;

use App\Events\ReferenciaMedicaAtendida;
use App\Models\Doctor;
use App\Models\Notificacion;

/**
 * Avisa al médico que emitió la referencia que el especialista completó la atención.
 */
class NotificarReferenciaAtendida
{
    public function handle(ReferenciaMedicaAtendida $evento): void
    {
        $referencia = $evento->referencia;
        $idUsuario = Doctor::query()->whereKey($referencia->id_doctor_origen)->value('usuario_id');

        if (!$idUsuario) {
            return;
        }

        Notificacion::notificar(
            (int) $idUsuario,
            'referencia_atendida',
            'Referencia atendida',
            'El especialista completó la atención de la referencia ' . $referencia->numero_referencia . '.',
            route('referencias')
        );
    }
}
FIN_ARCHIVO
  escribir_archivo "NotificarActualizacionClinica.php" <<'FIN_ARCHIVO'
<?php

namespace App\Listeners;

use App\Events\RegistroClinicoActualizado;
use App\Models\Doctor;
use App\Models\Notificacion;
use App\Models\RelacionAsistencial;

/**
 * Avisa a los demás médicos con relación activa que hay información clínica nueva.
 * El aviso no incluye diagnósticos ni resultados.
 */
class NotificarActualizacionClinica
{
    public function handle(RegistroClinicoActualizado $evento): void
    {
        $idsDoctores = RelacionAsistencial::query()
            ->activas()
            ->where('id_paciente', $evento->idPaciente)
            ->where('id_doctor', '<>', $evento->idDoctorAutor)
            ->pluck('id_doctor');

        $idsUsuarios = Doctor::query()->whereIn('id', $idsDoctores)->pluck('usuario_id');

        foreach ($idsUsuarios as $idUsuario) {
            Notificacion::notificar(
                (int) $idUsuario,
                'actualizacion_clinica',
                'Información clínica actualizada',
                'Hay información nueva en el expediente de un paciente que usted atiende.',
                route('pacientes.expediente', ['paciente' => $evento->idPaciente])
            );
        }
    }
}
FIN_ARCHIVO
  escribir_archivo "NotificarAlertaAuditoria.php" <<'FIN_ARCHIVO'
<?php

namespace App\Listeners;

use App\Events\AccesoExpedienteRegistrado;
use App\Models\Notificacion;
use App\Models\Usuario;

/**
 * Alerta a los administradores de un acceso excepcional a un expediente.
 */
class NotificarAlertaAuditoria
{
    public function handle(AccesoExpedienteRegistrado $evento): void
    {
        $acceso = $evento->acceso->loadMissing(['usuario', 'paciente']);

        Notificacion::notificarARol(
            Usuario::ROL_ADMINISTRADOR,
            'alerta_auditoria',
            'Acceso excepcional a un expediente',
            trim(($acceso->usuario->nombres ?? '') . ' ' . ($acceso->usuario->apellidos ?? ''))
                . ' accedió de forma excepcional al expediente de DUI '
                . ($acceso->paciente->numero_identificacion ?? '—') . '. Revise la justificación.',
            route('accesos_excepcionales')
        );
    }
}
FIN_ARCHIVO
  escribir_archivo "NotificarTarea.php" <<'FIN_ARCHIVO'
<?php

namespace App\Listeners;

use App\Events\TareaPorVencer;
use App\Models\Notificacion;

/**
 * Avisa al propietario de una tarea de su recordatorio o su vencimiento.
 */
class NotificarTarea
{
    public function handle(TareaPorVencer $evento): void
    {
        $tarea = $evento->tarea;

        $esVencida = $evento->tipo === 'vencida';

        Notificacion::notificar(
            (int) $tarea->id_usuario,
            $esVencida ? 'tarea_vencida' : 'tarea_recordatorio',
            $esVencida ? 'Tarea vencida' : 'Recordatorio de tarea',
            $esVencida
                ? 'La tarea "' . $tarea->titulo . '" venció.'
                : 'Recuerde: "' . $tarea->titulo . '"'
                    . ($tarea->fecha_vencimiento ? ' vence el ' . $tarea->fecha_vencimiento->format('d/m/Y H:i') . '.' : '.'),
            route('tareas')
        );
    }
}
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p Console/Commands
  descender Console/Commands
  escribir_archivo "ProcesarRecordatoriosTareas.php" <<'FIN_ARCHIVO'
<?php

namespace App\Console\Commands;

use App\Services\RecordatorioTareasService;
use Illuminate\Console\Command;

class ProcesarRecordatoriosTareas extends Command
{
    protected $signature = 'tareas:recordatorios';

    protected $description = 'Genera las notificaciones de recordatorio y vencimiento de las tareas personales';

    public function handle(RecordatorioTareasService $servicio): int
    {
        $resultado = $servicio->procesar();

        $this->info(
            'Recordatorios enviados: ' . $resultado['recordatorios']
            . ' · Avisos de vencimiento: ' . $resultado['vencidas']
        );

        return self::SUCCESS;
    }
}
FIN_ARCHIVO
  escribir_archivo "VencerReferenciasMedicas.php" <<'FIN_ARCHIVO'
<?php

namespace App\Console\Commands;

use App\Services\ReferenciaMedicaService;
use Illuminate\Console\Command;

class VencerReferenciasMedicas extends Command
{
    protected $signature = 'referencias:vencer';

    protected $description = 'Marca como vencidas las referencias médicas pendientes que pasaron su plazo';

    public function handle(ReferenciaMedicaService $servicio): int
    {
        $this->info('Referencias vencidas: ' . $servicio->vencerPendientes());

        return self::SUCCESS;
    }
}
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p Http/Controllers
  descender Http/Controllers
  escribir_archivo "ReferenciaMedicaController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarReferenciaMedicaRequest;
use App\Http\Requests\MotivoReferenciaMedicaRequest;
use App\Http\Requests\ProgramarReferenciaMedicaRequest;
use App\Models\Bitacora;
use App\Models\Doctor;
use App\Models\Especialidad;
use App\Models\ReferenciaMedica;
use App\Services\ReferenciaMedicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Referencias médicas.
 *
 *  - El médico emite referencias sobre pacientes que atiende y ve las que
 *    emitió o que se le asignaron (con su resumen clínico).
 *  - Recepción gestiona la programación; ella y el administrador ven las
 *    referencias sin el contenido clínico (resumen y estudios).
 */
class ReferenciaMedicaController extends Controller
{
    public function __construct(private readonly ReferenciaMedicaService $servicio)
    {
    }

    public function index(): View
    {
        if ($this->esMedico()) {
            $doctor = $this->doctorActual();

            $referencias = $doctor
                ? ReferenciaMedica::mostrarDeDoctor($doctor->getKey())
                : collect();
        } else {
            $referencias = ReferenciaMedica::mostrarTodos();

            // Recepción y administración no necesitan el contenido clínico.
            $referencias->each(function (ReferenciaMedica $referencia) {
                $referencia->setAttribute('resumen_clinico', null);
                $referencia->setAttribute('estudios_solicitados', null);
            });
        }

        return view('referencias.index', [
            'referencias' => $referencias,
            'doctores' => Doctor::mostrarTodos(),
            'especialidades' => Especialidad::mostrarTodos(),
            'idDoctorActual' => $this->doctorActual()?->getKey(),
        ]);
    }

    /**
     * El médico emite una referencia (queda pendiente para recepción).
     */
    public function insertarReferencia(InsertarReferenciaMedicaRequest $request): RedirectResponse
    {
        $referencia = $this->servicio->emitir(
            $this->doctorActual(),
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'REFERENCIAS_MEDICAS',
            $referencia->getKey(),
            'Referencia médica emitida: ' . $referencia->numero_referencia
        );

        return redirect()
            ->route('referencias')
            ->with('success', 'La referencia ' . $referencia->numero_referencia . ' se emitió correctamente. Recepción programará la cita.');
    }

    /**
     * Recepción programa la referencia: se crea la cita vinculada y se activa
     * la relación asistencial del especialista.
     */
    public function programarReferencia(
        ProgramarReferenciaMedicaRequest $request,
        ReferenciaMedica $referencia
    ): RedirectResponse {
        $cita = $this->servicio->programar($referencia, $request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'CITAS',
            $cita->getKey(),
            'Cita programada a partir de la referencia ' . $referencia->numero_referencia
        );

        return redirect()
            ->route('referencias')
            ->with('success', 'La referencia se programó correctamente: la cita quedó registrada.');
    }

    /**
     * Rechaza una referencia pendiente: el médico de destino, recepción o el
     * administrador.
     */
    public function rechazarReferencia(
        MotivoReferenciaMedicaRequest $request,
        ReferenciaMedica $referencia
    ): RedirectResponse {
        $esDestino = $this->esMedico()
            && $referencia->id_doctor_destino !== null
            && (int) $referencia->id_doctor_destino === (int) $this->doctorActual()?->getKey();

        if ($this->esMedico() && !$esDestino) {
            return redirect()
                ->route('referencias')
                ->with('error', 'Solo el médico de destino puede rechazar esta referencia.');
        }

        $this->servicio->rechazar($referencia, $request->validated('motivo'));

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'REFERENCIAS_MEDICAS',
            $referencia->getKey(),
            'Referencia médica rechazada: ' . $referencia->numero_referencia
        );

        return redirect()
            ->route('referencias')
            ->with('success', 'La referencia se rechazó correctamente.');
    }

    /**
     * Cancela una referencia: el médico que la emitió, recepción o el
     * administrador. Si ya tenía cita, esta se cancela también.
     */
    public function cancelarReferencia(
        MotivoReferenciaMedicaRequest $request,
        ReferenciaMedica $referencia
    ): RedirectResponse {
        $esOrigen = $this->esMedico()
            && (int) $referencia->id_doctor_origen === (int) $this->doctorActual()?->getKey();

        if ($this->esMedico() && !$esOrigen) {
            return redirect()
                ->route('referencias')
                ->with('error', 'Solo el médico que emitió la referencia puede cancelarla.');
        }

        $this->servicio->cancelar($referencia, $request->validated('motivo'));

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'REFERENCIAS_MEDICAS',
            $referencia->getKey(),
            'Referencia médica cancelada: ' . $referencia->numero_referencia
        );

        return redirect()
            ->route('referencias')
            ->with('success', 'La referencia se canceló correctamente.');
    }
}
FIN_ARCHIVO
  escribir_archivo "RelacionAsistencialController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\GestionarRelacionAsistencialRequest;
use App\Models\Bitacora;
use App\Models\RelacionAsistencial;
use App\Services\AccesoAsistencialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Relaciones asistenciales: quién puede consultar el expediente de quién.
 *
 *  - El médico ve las suyas y puede cerrarlas (de forma explícita, con motivo).
 *  - El administrador ve todas y puede cerrar, revocar, suspender y reactivar.
 *    No ve información clínica: solo el vínculo.
 */
class RelacionAsistencialController extends Controller
{
    public function __construct(private readonly AccesoAsistencialService $servicio)
    {
    }

    public function index(): View
    {
        if ($this->esMedico()) {
            $doctor = $this->doctorActual();

            $relaciones = $doctor
                ? RelacionAsistencial::mostrarDeDoctor($doctor->getKey())
                : collect();
        } else {
            $relaciones = RelacionAsistencial::mostrarTodos();
        }

        return view('relaciones.index', [
            'relaciones' => $relaciones,
        ]);
    }

    /**
     * Cierre explícito: el médico dueño de la relación o el administrador.
     */
    public function cerrarRelacion(
        GestionarRelacionAsistencialRequest $request,
        RelacionAsistencial $relacion
    ): RedirectResponse {
        if ($this->esMedico() && (int) $relacion->id_doctor !== (int) $this->doctorActual()?->getKey()) {
            return redirect()
                ->route('relaciones')
                ->with('error', 'Solo puede cerrar sus propias relaciones asistenciales.');
        }

        $this->servicio->cerrar($relacion, $request->validated('motivo'), $this->usuarioActual());

        return $this->respuesta($relacion, 'cerrada', 'La relación asistencial se cerró y quedó como historial.');
    }

    public function revocarRelacion(
        GestionarRelacionAsistencialRequest $request,
        RelacionAsistencial $relacion
    ): RedirectResponse {
        $this->servicio->revocar($relacion, $request->validated('motivo'), $this->usuarioActual());

        return $this->respuesta($relacion, 'revocada', 'La relación asistencial se revocó.');
    }

    public function suspenderRelacion(
        GestionarRelacionAsistencialRequest $request,
        RelacionAsistencial $relacion
    ): RedirectResponse {
        $this->servicio->suspender($relacion, $request->validated('motivo'), $this->usuarioActual());

        return $this->respuesta($relacion, 'suspendida', 'La relación asistencial se suspendió.');
    }

    public function reactivarRelacion(
        GestionarRelacionAsistencialRequest $request,
        RelacionAsistencial $relacion
    ): RedirectResponse {
        $this->servicio->reactivar($relacion, $request->validated('motivo'), $this->usuarioActual());

        return $this->respuesta($relacion, 'reactivada', 'La relación asistencial se reactivó.');
    }

    private function respuesta(RelacionAsistencial $relacion, string $accion, string $mensaje): RedirectResponse
    {
        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'RELACIONES_ASISTENCIALES',
            $relacion->getKey(),
            'Relación asistencial ' . $accion . '. Paciente ID: ' . $relacion->id_paciente
                . ', doctor ID: ' . $relacion->id_doctor
        );

        return redirect()
            ->route('relaciones')
            ->with('success', $mensaje);
    }
}
FIN_ARCHIVO
  escribir_archivo "AuditoriaExpedienteController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaExpediente;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consulta de la auditoría del expediente clínico (solo administrador).
 * La auditoría es de solo lectura: no hay operaciones para modificarla.
 */
class AuditoriaExpedienteController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'dui' => ['nullable', 'string', 'max:10'],
            'tipo_accion' => ['nullable', 'in:' . implode(',', AuditoriaExpediente::ACCIONES)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $idPaciente = null;
        $duiSinResultado = false;

        if (!empty($filtros['dui'])) {
            $paciente = Paciente::withTrashed()
                ->where('numero_identificacion', trim($filtros['dui']))
                ->first();

            $idPaciente = $paciente?->getKey();
            $duiSinResultado = $paciente === null;
        }

        $eventos = $duiSinResultado
            ? collect()
            : AuditoriaExpediente::mostrarFiltrados([
                'id_paciente' => $idPaciente,
                'tipo_accion' => $filtros['tipo_accion'] ?? null,
                'desde' => $filtros['desde'] ?? null,
                'hasta' => $filtros['hasta'] ?? null,
            ]);

        return view('auditoria.index', [
            'eventos' => $eventos,
            'filtros' => $filtros,
            'acciones' => AuditoriaExpediente::ACCIONES,
            'duiSinResultado' => $duiSinResultado,
        ]);
    }
}
FIN_ARCHIVO
  escribir_archivo "AccesoExcepcionalController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\RevisarAccesoExcepcionalRequest;
use App\Http\Requests\SolicitarAccesoExcepcionalRequest;
use App\Models\AccesoExcepcional;
use App\Models\Bitacora;
use App\Models\Paciente;
use App\Services\AccesoExcepcionalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Acceso excepcional (break-glass) a expedientes.
 *
 * El médico sin relación asistencial (o el administrador) lo solicita con una
 * justificación; dura 4 horas, es de solo lectura y genera una alerta. El
 * administrador revisa después los accesos concedidos.
 */
class AccesoExcepcionalController extends Controller
{
    public function __construct(private readonly AccesoExcepcionalService $servicio)
    {
    }

    public function index(): View
    {
        $accesos = $this->esAdministrador()
            ? AccesoExcepcional::mostrarTodos()
            : AccesoExcepcional::mostrarDeUsuario($this->usuarioActual()->getKey());

        return view('accesos.index', [
            'accesos' => $accesos,
            'esAdministrador' => $this->esAdministrador(),
        ]);
    }

    /**
     * Solicita acceso de emergencia al expediente de un paciente.
     */
    public function solicitar(
        SolicitarAccesoExcepcionalRequest $request,
        Paciente $paciente
    ): RedirectResponse {
        $usuario = $this->usuarioActual();

        if (!$usuario->can('solicitarAccesoExcepcional', $paciente)) {
            return redirect()
                ->route('pacientes.expediente', ['paciente' => $paciente->getKey()])
                ->with('info', 'Ya puede consultar este expediente: no necesita un acceso excepcional.');
        }

        $acceso = $this->servicio->solicitar($usuario, $paciente, $request->validated('motivo'));

        Bitacora::registrar(
            Bitacora::CREADO,
            'ACCESOS_EXCEPCIONALES',
            $acceso->getKey(),
            'Acceso excepcional a un expediente. Paciente ID: ' . $paciente->getKey()
        );

        return redirect()
            ->route('pacientes.expediente', ['paciente' => $paciente->getKey()])
            ->with(
                'warning',
                'Acceso excepcional concedido por ' . AccesoExcepcional::HORAS_VIGENCIA
                . ' horas, solo lectura. Quedó registrado y se alertó a los administradores.'
            );
    }

    /**
     * El administrador registra la revisión de un acceso excepcional.
     */
    public function revisar(
        RevisarAccesoExcepcionalRequest $request,
        AccesoExcepcional $acceso
    ): RedirectResponse {
        $this->servicio->revisar($acceso, $this->usuarioActual(), $request->validated('observacion'));

        return redirect()
            ->route('accesos_excepcionales')
            ->with('success', 'La revisión del acceso excepcional quedó registrada.');
    }

    /**
     * El administrador revoca un acceso excepcional antes de que expire.
     */
    public function revocar(AccesoExcepcional $acceso): RedirectResponse
    {
        $this->servicio->revocar($acceso, $this->usuarioActual());

        return redirect()
            ->route('accesos_excepcionales')
            ->with('success', 'El acceso excepcional se revocó.');
    }
}
FIN_ARCHIVO
  escribir_archivo "TareaPersonalController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambiarEstadoTareaRequest;
use App\Http\Requests\GuardarTareaRequest;
use App\Models\TareaPersonal;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Workspace personal: cada usuario ve y modifica únicamente sus tareas.
 */
class TareaPersonalController extends Controller
{
    public function index(): View
    {
        $tareas = TareaPersonal::mostrarDeUsuario($this->usuarioActual()->getKey());

        return view('tareas.index', [
            'vencidas' => $tareas->filter(fn (TareaPersonal $t) => $t->estaVencida())->values(),
            'abiertas' => $tareas->filter(fn (TareaPersonal $t) => $t->estaAbierta() && !$t->estaVencida())->values(),
            'completadas' => $tareas->where('estado', TareaPersonal::ESTADO_COMPLETADA)->values(),
            'canceladas' => $tareas->where('estado', TareaPersonal::ESTADO_CANCELADA)->values(),
        ]);
    }

    public function insertarTarea(GuardarTareaRequest $request): RedirectResponse
    {
        TareaPersonal::insertar($this->usuarioActual()->getKey(), $request->validated());

        return redirect()
            ->route('tareas')
            ->with('success', 'La tarea se creó correctamente.');
    }

    public function actualizarTarea(GuardarTareaRequest $request, TareaPersonal $tarea): RedirectResponse
    {
        $this->autorizarPropietario($tarea);

        $datos = $request->validated();
        $estado = $datos['estado'] ?? null;
        unset($datos['estado']);

        $tarea->actualizarDatos($datos);

        if ($estado !== null && $estado !== $tarea->estado) {
            $tarea->cambiarEstado($estado);
        }

        return redirect()
            ->route('tareas')
            ->with('success', 'La tarea se actualizó correctamente.');
    }

    public function cambiarEstadoTarea(CambiarEstadoTareaRequest $request, TareaPersonal $tarea): RedirectResponse
    {
        $this->autorizarPropietario($tarea);

        $tarea->cambiarEstado($request->validated('estado'));

        return redirect()
            ->route('tareas')
            ->with('success', 'El estado de la tarea se actualizó.');
    }

    public function eliminarTarea(TareaPersonal $tarea): RedirectResponse
    {
        $this->autorizarPropietario($tarea);

        $tarea->delete();

        return redirect()
            ->route('tareas')
            ->with('success', 'La tarea se eliminó correctamente.');
    }

    /**
     * Las tareas son privadas: nadie accede a las de otro usuario.
     */
    private function autorizarPropietario(TareaPersonal $tarea): void
    {
        abort_unless(
            (int) $tarea->id_usuario === (int) $this->usuarioActual()->getKey(),
            403,
            'No puede acceder a las tareas de otro usuario.'
        );
    }
}
FIN_ARCHIVO
  escribir_archivo "Controller.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Models\AccesoExcepcional;
use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * Doctor del usuario autenticado (solo si es médico).
     */
    private ?Doctor $doctor = null;

    private bool $doctorCargado = false;

    /**
     * Usuario autenticado.
     */
    protected function usuarioActual(): ?Usuario
    {
        return Auth::user();
    }

    protected function esMedico(): bool
    {
        return (bool) $this->usuarioActual()?->tieneRol(Usuario::ROL_MEDICO);
    }

    protected function esAdministrador(): bool
    {
        return (bool) $this->usuarioActual()?->tieneRol(Usuario::ROL_ADMINISTRADOR);
    }

    /**
     * Registro de doctor del usuario autenticado, o null si no es médico.
     */
    protected function doctorActual(): ?Doctor
    {
        if (!$this->doctorCargado) {
            $this->doctorCargado = true;

            if ($this->esMedico()) {
                $this->doctor = Doctor::buscarPorUsuario($this->usuarioActual()->getKey());
            }
        }

        return $this->doctor;
    }

    /**
     * Ids de los pacientes que el usuario puede consultar: null significa
     * todos; para un médico son únicamente los que atiende.
     */
    protected function idsPacientesPermitidos(): ?array
    {
        if (!$this->esMedico()) {
            return null;
        }

        $doctor = $this->doctorActual();

        return $doctor ? Paciente::idsAtendidosPor($doctor->getKey()) : [];
    }

    /**
     * Indica si el usuario puede consultar a un paciente.
     */
    protected function puedeVerPaciente(?int $idPaciente): bool
    {
        if ($idPaciente === null) {
            return false;
        }

        $permitidos = $this->idsPacientesPermitidos();

        return $permitidos === null || in_array($idPaciente, $permitidos, true);
    }

    /**
     * Deja solo los elementos de una colección cuyo paciente puede ver el
     * usuario. $idPaciente recibe cada elemento y devuelve el id del paciente.
     */
    protected function filtrarPorPaciente(Collection $coleccion, callable $idPaciente): Collection
    {
        $permitidos = $this->idsPacientesPermitidos();

        if ($permitidos === null) {
            return $coleccion->values();
        }

        return $coleccion
            ->filter(fn ($elemento) => in_array((int) $idPaciente($elemento), $permitidos, true))
            ->values();
    }

    /**
     * ¿Puede el usuario consultar el expediente clínico de un paciente?
     * Lo decide PacientePolicy::viewExpediente: relación asistencial activa
     * o acceso excepcional vigente. Un administrador sin acceso excepcional
     * no puede consultar información clínica.
     */
    protected function puedeConsultarExpediente(?int $idPaciente): bool
    {
        if ($idPaciente === null) {
            return false;
        }

        $usuario = $this->usuarioActual();
        $paciente = Paciente::withTrashed()->find($idPaciente);

        return $usuario !== null
            && $paciente !== null
            && $usuario->can('viewExpediente', $paciente);
    }

    /**
     * ¿Puede el usuario modificar o quitar un antecedente (alergia o
     * condición)? Requiere relación asistencial activa y haberlo registrado
     * él mismo (o que no tenga autor registrado por ser anterior).
     */
    protected function puedeModificarAntecedente(?int $idPaciente, ?int $idUsuarioCreacion): bool
    {
        $usuario = $this->usuarioActual();
        $paciente = $idPaciente ? Paciente::withTrashed()->find($idPaciente) : null;

        if (!$usuario || !$paciente || !$usuario->can('agregarAntecedente', $paciente)) {
            return false;
        }

        return $idUsuarioCreacion === null
            || (int) $idUsuarioCreacion === (int) $usuario->getKey();
    }

    /**
     * En los listados, un administrador no ve el contenido clínico de los
     * pacientes para los que no tiene un acceso excepcional vigente: esos
     * atributos se muestran vacíos (no se guardan cambios).
     *
     * @param  array<int, string>  $atributos
     */
    protected function ocultarClinicoSinAcceso(Collection $coleccion, callable $idPaciente, array $atributos): Collection
    {
        if (!$this->esAdministrador()) {
            return $coleccion;
        }

        $conAcceso = AccesoExcepcional::query()
            ->vigentes()
            ->where('id_usuario', $this->usuarioActual()->getKey())
            ->pluck('id_paciente')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $coleccion->each(function ($elemento) use ($idPaciente, $atributos, $conAcceso) {
            if (in_array((int) $idPaciente($elemento), $conAcceso, true)) {
                return;
            }

            foreach ($atributos as $atributo) {
                $elemento->setAttribute($atributo, null);
            }
        });
    }

    /**
     * Respuesta JSON cuando no se puede consultar un paciente.
     */
    protected function prohibidoJson(string $mensaje = 'No tiene permiso para consultar este paciente.'): JsonResponse
    {
        return response()->json(['message' => $mensaje], 403);
    }

    /**
     * Redirección con error cuando no se puede consultar un paciente.
     */
    protected function prohibidoRedirigir(
        string $ruta,
        string $mensaje = 'No tiene permiso para consultar este paciente.'
    ): RedirectResponse {
        return redirect()
            ->route($ruta)
            ->with('error', $mensaje);
    }
}
FIN_ARCHIVO
  escribir_archivo "PacienteController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarPacienteRequest;
use App\Http\Requests\InsertarPacienteRequest;
use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\AccesoExcepcional;
use App\Models\Alergia;
use App\Models\AuditoriaExpediente;
use App\Models\CondicionMedica;
use App\Models\Diagnostico;
use App\Models\Paciente;
use App\Models\RegistroMedico;
use App\Models\TipoSanguineo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PacienteController extends Controller
{
    /**
     * Un médico ve solo a los pacientes que atiende; la recepcionista y el
     * administrador ven a todos.
     */
    public function index(): View
    {
        if ($this->esMedico()) {
            $doctor = $this->doctorActual();

            $pacientes = $doctor
                ? Paciente::mostrarTodosDeDoctor($doctor->getKey())
                : collect();
        } else {
            $pacientes = Paciente::mostrarTodos();
        }

        $datos = [
            'pacientes' => $pacientes,
            'tiposSanguineos' => TipoSanguineo::all(),
        ];

        return view('pacientes.index', $datos);
    }

    public function insertarPaciente(
        InsertarPacienteRequest $request
    ): RedirectResponse {
        $paciente = Paciente::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'PACIENTES',
            $paciente->getKey(),
            'Paciente registrado correctamente. '
            . 'Identificación: '
            . $paciente->numero_identificacion
            . ', nombre: '
            . $paciente->nombres
            . ' '
            . $paciente->apellidos
        );

        return redirect()
            ->route('pacientes')
            ->with(
                'success',
                'Paciente registrado correctamente.'
            );
    }

    public function buscarPacienteXId(
        int $id
    ): JsonResponse {
        $paciente = Paciente::buscarPacienteXId($id);

        if (!$paciente || !$this->puedeVerPaciente($paciente->getKey())) {
            return response()->json(
                ['message' => 'Paciente no encontrado.'],
                404
            );
        }

        return response()->json($paciente);
    }

    /**
     * Busca un paciente por su DUI. Los formularios piden el DUI y, debajo,
     * muestran el nombre para confirmar. Un médico solo encuentra a quien
     * atiende; si no, la respuesta es la misma que si no existiera.
     */
    public function buscarPacienteXDui(string $dui): JsonResponse
    {
        $paciente = Paciente::buscarPacienteXDui($dui);

        if (!$paciente || !$this->puedeVerPaciente($paciente->getKey())) {
            return response()->json([
                'encontrado' => false,
                'mensaje' => 'No se encontró un paciente con ese DUI.',
            ], 404);
        }

        $respuesta = [
            'encontrado' => true,
            'id' => $paciente->getKey(),
            'numero_identificacion' => $paciente->numero_identificacion,
            'nombre_completo' => trim($paciente->nombres . ' ' . $paciente->apellidos),
        ];

        // Las citas vigentes ayudan a vincular un registro médico u orden.
        $respuesta['citas'] = Cita::query()
            ->where('id_paciente', $paciente->getKey())
            ->whereNotIn('estado', ['cancelada', 'no_asistio'])
            ->when(
                $this->esMedico(),
                fn ($consulta) => $consulta->where('id_doctor', $this->doctorActual()->getKey())
            )
            ->orderByDesc('fecha_cita')
            ->limit(10)
            ->get(['id', 'fecha_cita', 'hora_inicio', 'estado']);

        // Los registros médicos (para recetas y órdenes) solo los ve el personal clínico.
        if ($this->esMedico() || $this->esAdministrador()) {
            $respuesta['registros'] = RegistroMedico::query()
                ->where('id_paciente', $paciente->getKey())
                ->when(
                    $this->esMedico(),
                    fn ($consulta) => $consulta->where('id_doctor', $this->doctorActual()->getKey())
                )
                ->orderByDesc('fecha_registro')
                ->limit(10)
                ->get(['id', 'fecha_registro', 'motivo_consulta']);
        }

        return response()->json($respuesta);
    }

    /**
     * Perfil administrativo del paciente.
     */
    public function perfil(Paciente $paciente): View|RedirectResponse
    {
        if (!$this->puedeVerPaciente($paciente->getKey())) {
            return $this->prohibidoRedirigir('pacientes');
        }

        // Citas recientes (un médico solo ve las suyas).
        $citas = $paciente->citas()
            ->with('doctor.usuario')
            ->when(
                $this->esMedico(),
                fn ($consulta) => $consulta->where('id_doctor', $this->doctorActual()->getKey())
            )
            ->orderByDesc('fecha_cita')
            ->limit(8)
            ->get();

        return view('pacientes.perfil', [
            'paciente' => Paciente::buscarPacienteXId($paciente->getKey()),
            'citas' => $citas,
        ]);
    }

    /**
     * Expediente clínico integral. Cada consulta queda registrada en la
     * bitácora y un médico solo accede al de los pacientes que atiende.
     */
    public function expediente(Paciente $paciente): View|RedirectResponse
    {
        $usuario = $this->usuarioActual();

        // Sin relación asistencial activa ni acceso excepcional vigente, un
        // médico o administrador puede solicitar acceso de emergencia.
        if (!$usuario->can('viewExpediente', $paciente)) {
            if ($usuario->can('solicitarAccesoExcepcional', $paciente)) {
                return view('pacientes.acceso_excepcional', [
                    'paciente' => $paciente,
                    'horas' => AccesoExcepcional::HORAS_VIGENCIA,
                    'minimo' => AccesoExcepcional::MOTIVO_MINIMO,
                ]);
            }

            return $this->prohibidoRedirigir(
                'pacientes',
                'No tiene permiso para consultar el expediente de este paciente.'
            );
        }

        $paciente->load([
            'tipoSanguineo',
            'alergias.alergia',
            'alergias.creadoPor',
            'condiciones.condicionMedica',
            'condiciones.creadoPor',
            'registrosMedicos' => fn ($consulta) => $consulta->orderByDesc('fecha_registro'),
            'registrosMedicos.doctor.usuario',
            'registrosMedicos.doctor.especialidad',
            'registrosMedicos.signosVitales',
            'registrosMedicos.diagnosticos.diagnostico',
            'registrosMedicos.recetas.detalles.medicamento',
        ]);

        // La consulta del expediente se audita en auditoria_expedientes.
        AuditoriaExpediente::registrar(
            $paciente->getKey(),
            AuditoriaExpediente::CONSULTA,
            'expediente',
            $paciente->getKey()
        );

        return view('pacientes.expediente', [
            'paciente' => $paciente,
            'alergias' => Alergia::mostrarTodos(),
            'condicionesMedicas' => CondicionMedica::mostrarTodos(),
            'diagnosticos' => Diagnostico::mostrarTodos(),
            'puedeRegistrar' => $usuario->can('registrarAtencion', $paciente),
            'idDoctorActual' => $this->doctorActual()?->getKey(),
            'accesoExcepcional' => AccesoExcepcional::vigenteDe($usuario->getKey(), $paciente->getKey()),
        ]);
    }

    public function actualizarPaciente(
        ActualizarPacienteRequest $request,
        Paciente $paciente
    ): RedirectResponse {
        if (
            Paciente::actualizar(
                $request->validated(),
                $paciente->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'PACIENTES',
                $paciente->getKey(),
                'Datos del paciente actualizados. '
                . 'Identificación: '
                . $paciente->numero_identificacion
                . ', nombre: '
                . $paciente->nombres
                . ' '
                . $paciente->apellidos
            );

            return redirect()
                ->route('pacientes')
                ->with(
                    'success',
                    'Paciente actualizado correctamente.'
                );
        }

        return redirect()
            ->route('pacientes')
            ->with(
                'error',
                'El paciente no pudo ser actualizado.'
            );
    }

    /**
     * Elimina un paciente. Va a la papelera: el administrador puede
     * restaurarlo con su historial clínico.
     */
    public function eliminarPaciente(
        Paciente $paciente
    ): RedirectResponse {
        $id = $paciente->getKey();
        $identificacion = $paciente->numero_identificacion;
        $nombre = $paciente->nombres . ' ' . $paciente->apellidos;

        if (Paciente::eliminar($id)) {
            Bitacora::registrar(
                Bitacora::ELIMINADO,
                'PACIENTES',
                $id,
                'Paciente enviado a la papelera. '
                . 'Identificación: '
                . $identificacion
                . ', nombre: '
                . $nombre
            );

            return redirect()
                ->route('pacientes')
                ->with(
                    'success',
                    'Paciente eliminado correctamente.'
                );
        }

        return redirect()
            ->route('pacientes')
            ->with(
                'error',
                'El paciente no pudo ser eliminado.'
            );
    }

    /**
     * Papelera de pacientes (solo administrador).
     */
    public function papelera(): View
    {
        return view('pacientes.papelera', [
            'pacientes' => Paciente::mostrarEliminados(),
        ]);
    }

    /**
     * Restaura un paciente de la papelera (solo administrador).
     */
    public function restaurarPaciente(int $id): RedirectResponse
    {
        if (Paciente::restaurar($id)) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'PACIENTES',
                $id,
                'Paciente restaurado desde la papelera.'
            );

            return redirect()
                ->route('pacientes.papelera')
                ->with('success', 'Paciente restaurado correctamente.');
        }

        return redirect()
            ->route('pacientes.papelera')
            ->with('error', 'No fue posible restaurar al paciente.');
    }
}
FIN_ARCHIVO
  escribir_archivo "RegistroMedicoController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarRegistroMedicoRequest;
use App\Events\RegistroClinicoActualizado;
use App\Models\AuditoriaExpediente;
use App\Models\Bitacora;
use App\Models\RegistroMedico;
use App\Services\CitaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistroMedicoController extends Controller
{
    /**
     * Un médico ve los registros de los pacientes que atiende; el
     * administrador ve todos. Los pacientes se indican por DUI.
     */
    public function index(): View
    {
        return view('registros_medicos.index', [
            'registrosMedicos' => $this->ocultarClinicoSinAcceso(
                $this->filtrarPorPaciente(
                    RegistroMedico::mostrarTodos(),
                    fn ($registro) => $registro->id_paciente
                ),
                fn ($registro) => $registro->id_paciente,
                ['motivo_consulta', 'notas_clinicas', 'observaciones']
            ),
        ]);
    }

    /**
     * Registra una atención. El registro queda firmado por el médico que
     * inició sesión y, si se vincula una cita programada, esta pasa a completada.
     */
    public function insertarRegistroMedico(
        InsertarRegistroMedicoRequest $request
    ): RedirectResponse {
        // Todo en una transacción: el registro, la cita completada (y la
        // referencia atendida, si la cita venía de una), la auditoría y el
        // aviso a los demás médicos autorizados, que se envía al confirmar.
        $registroMedico = DB::transaction(function () use ($request) {
            $registro = RegistroMedico::insertar($request->validated());

            if ($registro->id_cita) {
                app(CitaService::class)->completarPorAtencion($registro->id_cita);
            }

            AuditoriaExpediente::registrar(
                $registro->id_paciente,
                AuditoriaExpediente::CREACION,
                'registro_medico',
                $registro->getKey(),
                null,
                ['cita' => $registro->id_cita],
                $registro->id_doctor
            );

            event(new RegistroClinicoActualizado(
                $registro->id_paciente,
                $registro->id_doctor,
                'registro_medico'
            ));

            return $registro;
        });

        Bitacora::registrar(
            Bitacora::CREADO,
            'REGISTROS_MEDICOS',
            $registroMedico->getKey(),
            'Registro médico creado correctamente. '
            . 'Paciente ID: ' . $registroMedico->id_paciente
            . ', doctor ID: ' . $registroMedico->id_doctor
            . ', cita ID: '
            . ($registroMedico->id_cita ?? 'Sin cita asociada')
        );

        return redirect()
            ->route('registros_medicos')
            ->with(
                'success',
                'Registro médico creado correctamente.'
            );
    }

    public function buscarRegistroMedicoXId(
        int $id
    ): JsonResponse {
        $registroMedico =
            RegistroMedico::buscarRegistroMedicoXId($id);

        if (
            !$registroMedico ||
            !$this->puedeConsultarExpediente($registroMedico->id_paciente)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el registro médico solicitado.',
            ], 404);
        }

        AuditoriaExpediente::registrar(
            $registroMedico->id_paciente,
            AuditoriaExpediente::CONSULTA,
            'registro_medico',
            $registroMedico->getKey(),
            null,
            [],
            $registroMedico->id_doctor
        );

        // Se devuelve el modelo tal cual (lo que esperan los scripts de la vista).
        return response()->json($registroMedico);
    }

    /**
     * Elimina un registro médico (solo administrador). Va a la papelera.
     */
    public function eliminarRegistroMedico(
        RegistroMedico $registroMedico
    ): RedirectResponse {
        $id = $registroMedico->getKey();
        $idPaciente = $registroMedico->id_paciente;
        $idDoctor = $registroMedico->id_doctor;

        if (RegistroMedico::eliminar($id)) {
            AuditoriaExpediente::registrar(
                $idPaciente,
                AuditoriaExpediente::ELIMINACION,
                'registro_medico',
                $id,
                null,
                [],
                $idDoctor
            );

            Bitacora::registrar(
                Bitacora::ELIMINADO,
                'REGISTROS_MEDICOS',
                $id,
                'Registro médico enviado a la papelera. '
                . 'Paciente ID: ' . $idPaciente
                . ', doctor ID: ' . $idDoctor
            );

            return redirect()
                ->route('registros_medicos')
                ->with(
                    'success',
                    'Registro médico eliminado correctamente.'
                );
        }

        return redirect()
            ->route('registros_medicos')
            ->with(
                'error',
                'No fue posible eliminar el registro médico.'
            );
    }

    /**
     * Papelera de registros médicos (solo administrador).
     */
    public function papelera(): View
    {
        return view('registros_medicos.papelera', [
            'registrosMedicos' => RegistroMedico::mostrarEliminados(),
        ]);
    }

    /**
     * Restaura un registro médico de la papelera (solo administrador).
     */
    public function restaurarRegistroMedico(int $id): RedirectResponse
    {
        $idPaciente = RegistroMedico::withTrashed()->whereKey($id)->value('id_paciente');

        if (RegistroMedico::restaurar($id)) {
            if ($idPaciente) {
                AuditoriaExpediente::registrar(
                    (int) $idPaciente,
                    AuditoriaExpediente::RESTAURACION,
                    'registro_medico',
                    $id
                );
            }

            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'REGISTROS_MEDICOS',
                $id,
                'Registro médico restaurado desde la papelera.'
            );

            return redirect()
                ->route('registros_medicos.papelera')
                ->with('success', 'Registro médico restaurado correctamente.');
        }

        return redirect()
            ->route('registros_medicos.papelera')
            ->with('error', 'No fue posible restaurar el registro médico.');
    }
}
FIN_ARCHIVO
  escribir_archivo "PacienteAlergiaController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarPacienteAlergiaRequest;
use App\Events\RegistroClinicoActualizado;
use App\Models\AuditoriaExpediente;
use App\Models\Bitacora;
use App\Models\PacienteAlergia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PacienteAlergiaController extends Controller
{
    /**
     * Registra una alergia de un paciente (indicado por DUI) y vuelve a su
     * expediente.
     */
    public function insertarPacienteAlergia(
        InsertarPacienteAlergiaRequest $request
    ): RedirectResponse {
        $pacienteAlergia = PacienteAlergia::insertar(
            $request->validated()
        );

        AuditoriaExpediente::registrar(
            $pacienteAlergia->id_paciente,
            AuditoriaExpediente::CREACION,
            'alergia_paciente',
            $pacienteAlergia->getKey()
        );

        event(new RegistroClinicoActualizado(
            $pacienteAlergia->id_paciente,
            (int) $this->doctorActual()?->getKey(),
            'alergia_paciente'
        ));

        Bitacora::registrar(
            Bitacora::CREADO,
            'PACIENTES_ALERGIAS',
            $pacienteAlergia->getKey(),
            'Se registró una alergia para un paciente.'
        );

        return redirect()
            ->route('pacientes.expediente', ['paciente' => $pacienteAlergia->id_paciente])
            ->with('success', 'La alergia se registró correctamente.');
    }

    public function buscarPacienteAlergia(int $id): JsonResponse
    {
        $pacienteAlergia =
            PacienteAlergia::buscarPacienteAlergiaXId($id);

        if (
            !$pacienteAlergia ||
            !$this->puedeConsultarExpediente($pacienteAlergia->id_paciente)
        ) {
            return response()->json([
                'mensaje' => 'El registro de alergia no existe.',
            ], 404);
        }

        return response()->json($pacienteAlergia);
    }

    public function eliminarPacienteAlergia(
        PacienteAlergia $pacienteAlergia
    ): RedirectResponse {
        $id = $pacienteAlergia->getKey();
        $idPaciente = $pacienteAlergia->id_paciente;

        if (!$this->puedeModificarAntecedente($idPaciente, $pacienteAlergia->id_usuario_creacion)) {
            return $this->prohibidoRedirigir(
                'pacientes',
                'Solo el médico que lo registró (con relación asistencial activa) puede quitar este antecedente.'
            );
        }

        PacienteAlergia::eliminar($id);

        AuditoriaExpediente::registrar(
            $idPaciente,
            AuditoriaExpediente::ELIMINACION,
            'alergia_paciente',
            $id
        );

        Bitacora::registrar(
            Bitacora::ELIMINADO,
            'PACIENTES_ALERGIAS',
            $id,
            'Se eliminó una alergia registrada para un paciente.'
        );

        return redirect()
            ->route('pacientes.expediente', ['paciente' => $idPaciente])
            ->with('success', 'La alergia se eliminó correctamente.');
    }
}
FIN_ARCHIVO
  escribir_archivo "PacienteCondicionController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarPacienteCondicionRequest;
use App\Events\RegistroClinicoActualizado;
use App\Models\AuditoriaExpediente;
use App\Models\Bitacora;
use App\Models\PacienteCondicion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PacienteCondicionController extends Controller
{
    /**
     * Registra una condición médica de un paciente (indicado por DUI) y
     * vuelve a su expediente.
     */
    public function insertarPacienteCondicion(
        InsertarPacienteCondicionRequest $request
    ): RedirectResponse {
        $pacienteCondicion = PacienteCondicion::insertar(
            $request->validated()
        );

        AuditoriaExpediente::registrar(
            $pacienteCondicion->id_paciente,
            AuditoriaExpediente::CREACION,
            'condicion_paciente',
            $pacienteCondicion->getKey()
        );

        event(new RegistroClinicoActualizado(
            $pacienteCondicion->id_paciente,
            (int) $this->doctorActual()?->getKey(),
            'condicion_paciente'
        ));

        Bitacora::registrar(
            Bitacora::CREADO,
            'PACIENTES_CONDICIONES',
            $pacienteCondicion->getKey(),
            'Se registró una condición médica para un paciente.'
        );

        return redirect()
            ->route('pacientes.expediente', ['paciente' => $pacienteCondicion->id_paciente])
            ->with('success', 'La condición médica se registró correctamente.');
    }

    public function buscarPacienteCondicion(int $id): JsonResponse
    {
        $pacienteCondicion =
            PacienteCondicion::buscarPacienteCondicionXId($id);

        if (
            !$pacienteCondicion ||
            !$this->puedeConsultarExpediente($pacienteCondicion->id_paciente)
        ) {
            return response()->json([
                'mensaje' => 'El registro de condición médica no existe.',
            ], 404);
        }

        return response()->json($pacienteCondicion);
    }

    public function eliminarPacienteCondicion(
        PacienteCondicion $pacienteCondicion
    ): RedirectResponse {
        $id = $pacienteCondicion->getKey();
        $idPaciente = $pacienteCondicion->id_paciente;

        if (!$this->puedeModificarAntecedente($idPaciente, $pacienteCondicion->id_usuario_creacion)) {
            return $this->prohibidoRedirigir(
                'pacientes',
                'Solo el médico que lo registró (con relación asistencial activa) puede quitar este antecedente.'
            );
        }

        PacienteCondicion::eliminar($id);

        AuditoriaExpediente::registrar(
            $idPaciente,
            AuditoriaExpediente::ELIMINACION,
            'condicion_paciente',
            $id
        );

        Bitacora::registrar(
            Bitacora::ELIMINADO,
            'PACIENTES_CONDICIONES',
            $id,
            'Se eliminó una condición médica registrada para un paciente.'
        );

        return redirect()
            ->route('pacientes.expediente', ['paciente' => $idPaciente])
            ->with('success', 'La condición médica se eliminó correctamente.');
    }
}
FIN_ARCHIVO
  escribir_archivo "RegistroMedicoDiagnosticoController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarRegistroMedicoDiagnosticoRequest;
use App\Events\RegistroClinicoActualizado;
use App\Models\AuditoriaExpediente;
use App\Models\Bitacora;
use App\Models\RegistroMedico;
use App\Models\RegistroMedicoDiagnostico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class RegistroMedicoDiagnosticoController extends Controller
{
    /**
     * Asocia un diagnóstico a un registro médico y vuelve al expediente del paciente.
     */
    public function insertarRegistroMedicoDiagnostico(
        InsertarRegistroMedicoDiagnosticoRequest $request
    ): RedirectResponse {
        $registro = RegistroMedicoDiagnostico::insertar(
            $request->validated()
        );

        $idPacienteRegistro = $this->idPacienteDelRegistro($registro->id_registro_medico);

        AuditoriaExpediente::registrar(
            (int) $idPacienteRegistro,
            AuditoriaExpediente::MODIFICACION,
            'diagnostico_registro',
            $registro->getKey(),
            null,
            ['accion' => 'agregado', 'registro_medico' => $registro->id_registro_medico, 'diagnostico' => $registro->id_diagnostico]
        );

        event(new RegistroClinicoActualizado(
            (int) $idPacienteRegistro,
            (int) $this->doctorActual()?->getKey(),
            'diagnostico_registro'
        ));

        Bitacora::registrar(
            Bitacora::CREADO,
            'REG_MED_DIA',
            $registro->getKey(),
            'Diagnóstico asociado al registro médico. '
            . 'Registro médico ID: ' . $registro->id_registro_medico
            . ', diagnóstico ID: ' . $registro->id_diagnostico
        );

        return $this->volverAlExpediente(
            $registro->id_registro_medico,
            'success',
            'Diagnóstico asociado al registro médico correctamente.'
        );
    }

    public function buscarRegistroMedicoDiagnosticoXId(
        int $id
    ): JsonResponse {
        $registro = RegistroMedicoDiagnostico::
        buscarRegistroMedicoDiagnosticoXId($id);

        if (
            !$registro ||
            !$this->puedeConsultarExpediente($this->idPacienteDelRegistro($registro->id_registro_medico))
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la asociación solicitada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'registro' => $registro,
        ]);
    }

    public function eliminarRegistroMedicoDiagnostico(
        RegistroMedicoDiagnostico $registroMedicoDiagnostico
    ): RedirectResponse {
        $id = $registroMedicoDiagnostico->getKey();
        $idRegistroMedico =
            $registroMedicoDiagnostico->id_registro_medico;
        $idDiagnostico =
            $registroMedicoDiagnostico->id_diagnostico;

        $registroDelDiagnostico = RegistroMedico::find($idRegistroMedico);

        if (!$registroDelDiagnostico || !$this->usuarioActual()?->can('escribir', $registroDelDiagnostico)) {
            return $this->prohibidoRedirigir(
                'registros_medicos',
                'Solo el médico autor del registro puede modificar sus diagnósticos.'
            );
        }

        if (RegistroMedicoDiagnostico::eliminar($id)) {
            AuditoriaExpediente::registrar(
                (int) $registroDelDiagnostico->id_paciente,
                AuditoriaExpediente::MODIFICACION,
                'diagnostico_registro',
                $id,
                null,
                ['accion' => 'quitado', 'registro_medico' => $idRegistroMedico, 'diagnostico' => $idDiagnostico]
            );

            Bitacora::registrar(
                Bitacora::ELIMINADO,
                'REG_MED_DIA',
                $id,
                'Asociación de diagnóstico eliminada. '
                . 'Registro médico ID: ' . $idRegistroMedico
                . ', diagnóstico ID: ' . $idDiagnostico
            );

            return $this->volverAlExpediente(
                $idRegistroMedico,
                'success',
                'Asociación eliminada correctamente.'
            );
        }

        return $this->volverAlExpediente(
            $idRegistroMedico,
            'error',
            'No fue posible eliminar la asociación.'
        );
    }

    /**
     * Id del paciente al que pertenece un registro médico.
     */
    private function idPacienteDelRegistro(?int $idRegistroMedico): ?int
    {
        $registro = RegistroMedico::withTrashed()->find($idRegistroMedico);

        return $registro?->id_paciente;
    }

    /**
     * Regresa al expediente del paciente del registro médico indicado.
     */
    private function volverAlExpediente(
        ?int $idRegistroMedico,
        string $tipo,
        string $mensaje
    ): RedirectResponse {
        $idPaciente = $this->idPacienteDelRegistro($idRegistroMedico);

        $redireccion = $idPaciente
            ? redirect()->route('pacientes.expediente', ['paciente' => $idPaciente])
            : redirect()->route('registros_medicos');

        return $redireccion->with($tipo, $mensaje);
    }
}
FIN_ARCHIVO
  escribir_archivo "RecetaController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarRecetaRequest;
use App\Events\RegistroClinicoActualizado;
use App\Models\AuditoriaExpediente;
use App\Models\Bitacora;
use App\Models\Medicamento;
use App\Models\Receta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecetaController extends Controller
{
    /**
     * Un médico ve las recetas de los pacientes que atiende; el
     * administrador ve todas.
     */
    public function index(): View
    {
        return view('recetas.index', [
            'recetas' => $this->ocultarClinicoSinAcceso(
                $this->filtrarPorPaciente(
                    Receta::mostrarTodos(),
                    fn ($receta) => $receta->registroMedico?->id_paciente
                ),
                fn ($receta) => $receta->registroMedico?->id_paciente,
                ['notas']
            ),
            'medicamentos' => Medicamento::mostrarTodos(),
        ]);
    }

    public function insertarReceta(
        InsertarRecetaRequest $request
    ): RedirectResponse {
        $receta = Receta::insertar(
            $request->validated()
        );

        $idPacienteReceta = (int) $receta->registroMedico?->id_paciente;

        AuditoriaExpediente::registrar(
            $idPacienteReceta,
            AuditoriaExpediente::CREACION,
            'receta',
            $receta->getKey()
        );

        event(new RegistroClinicoActualizado(
            $idPacienteReceta,
            (int) $this->doctorActual()?->getKey(),
            'receta'
        ));

        Bitacora::registrar(
            Bitacora::CREADO,
            'RECETAS',
            $receta->getKey(),
            'Se registró una nueva receta médica.'
        );

        return redirect()
            ->route('recetas')
            ->with('success', 'La receta médica se registró correctamente.');
    }

    public function buscarReceta(int $id): JsonResponse
    {
        $receta = Receta::buscarRecetaXId($id);

        if (
            !$receta ||
            !$this->puedeConsultarExpediente($receta->registroMedico?->id_paciente)
        ) {
            return response()->json([
                'mensaje' => 'La receta médica no existe.',
            ], 404);
        }

        AuditoriaExpediente::registrar(
            (int) $receta->registroMedico?->id_paciente,
            AuditoriaExpediente::CONSULTA,
            'receta',
            $receta->getKey()
        );

        return response()->json($receta);
    }
}
FIN_ARCHIVO
  escribir_archivo "DetalleRecetaController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarDetalleRecetaRequest;
use App\Events\RegistroClinicoActualizado;
use App\Models\AuditoriaExpediente;
use App\Models\Bitacora;
use App\Models\DetalleReceta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DetalleRecetaController extends Controller
{
    /**
     * Agrega un medicamento a una receta y vuelve a la lista de recetas.
     */
    public function insertarDetalleReceta(
        InsertarDetalleRecetaRequest $request
    ): RedirectResponse {
        $detalleReceta = DetalleReceta::insertar(
            $request->validated()
        );

        $detalleReceta->loadMissing('receta.registroMedico');
        $idPacienteDetalle = (int) $detalleReceta->receta?->registroMedico?->id_paciente;

        AuditoriaExpediente::registrar(
            $idPacienteDetalle,
            AuditoriaExpediente::MODIFICACION,
            'detalle_receta',
            $detalleReceta->getKey(),
            null,
            ['accion' => 'medicamento_agregado', 'receta' => $detalleReceta->id_receta]
        );

        event(new RegistroClinicoActualizado(
            $idPacienteDetalle,
            (int) $this->doctorActual()?->getKey(),
            'detalle_receta'
        ));

        Bitacora::registrar(
            Bitacora::CREADO,
            'DETALLE_RECETAS',
            $detalleReceta->getKey(),
            'Se agregó un medicamento a una receta médica.'
        );

        return redirect()
            ->route('recetas')
            ->with(
                'success',
                'El medicamento se agregó a la receta correctamente.'
            );
    }

    public function buscarDetalleReceta(int $id): JsonResponse
    {
        $detalleReceta = DetalleReceta::buscarDetalleRecetaXId($id);

        if ($detalleReceta) {
            $detalleReceta->loadMissing('receta.registroMedico');
        }

        if (
            !$detalleReceta ||
            !$this->puedeConsultarExpediente($detalleReceta->receta?->registroMedico?->id_paciente)
        ) {
            return response()->json([
                'mensaje' => 'El detalle de la receta no existe.',
            ], 404);
        }

        return response()->json($detalleReceta);
    }
}
FIN_ARCHIVO
  escribir_archivo "CitaController.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarCitaRequest;
use App\Http\Requests\InsertarCitaRequest;
use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\Doctor;
use App\Services\CitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CitaController extends Controller
{
    /**
     * Un médico ve solo sus citas; la recepcionista y el administrador ven
     * todas. Los pacientes se indican por DUI en los formularios.
     */
    public function index(): View
    {
        if ($this->esMedico()) {
            $doctor = $this->doctorActual();

            $citas = $doctor
                ? Cita::mostrarDeDoctor($doctor->getKey())
                : collect();
        } else {
            $citas = Cita::mostrarTodos();
        }

        return view('citas.index', [
            'citas' => $citas,
            'doctores' => Doctor::mostrarTodos(),
        ]);
    }

    /**
     * Detalle de una cita.
     */
    public function detalle(Cita $cita): View|RedirectResponse
    {
        if (!$this->puedeVerCita($cita)) {
            return redirect()
                ->route('citas')
                ->with('error', 'No tiene permiso para consultar esta cita.');
        }

        return view('citas.detalle', [
            'cita' => Cita::buscarCitaXId($cita->getKey()),
        ]);
    }

    public function insertarCita(
        InsertarCitaRequest $request
    ): RedirectResponse {
        $cita = app(CitaService::class)->crear($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'CITAS',
            $cita->getKey(),
            'Cita registrada correctamente. '
            . 'Paciente ID: ' . $cita->id_paciente
            . ', doctor ID: ' . $cita->id_doctor
            . ', fecha: ' . $cita->fecha_cita->format('Y-m-d')
            . ', hora: ' . $cita->hora_inicio
        );

        return redirect()
            ->route('citas')
            ->with('success', 'Cita registrada correctamente.');
    }

    public function buscarCitaXId(int $id): JsonResponse
    {
        $cita = Cita::buscarCitaXId($id);

        if (!$cita || !$this->puedeVerCita($cita)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la cita solicitada.',
            ], 404);
        }

        // Se devuelve el modelo tal cual (lo que esperan los scripts de la vista),
        // más el DUI del paciente para el formulario de edición.
        $cita->setAttribute('dui_paciente', $cita->paciente?->numero_identificacion);

        return response()->json($cita);
    }

    public function actualizarCita(
        ActualizarCitaRequest $request,
        Cita $cita
    ): RedirectResponse {
        // El servicio actualiza la cita y sincroniza la relación asistencial
        // y la referencia médica asociadas, todo en una transacción.
        $citaActualizada = app(CitaService::class)->actualizar(
            $cita,
            $request->validated()
        );

        $actualizada = $citaActualizada !== null;

        if ($actualizada) {
            $cita = $citaActualizada;

            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'CITAS',
                $cita->getKey(),
                'Cita actualizada correctamente. '
                . 'Paciente ID: ' . $cita->id_paciente
                . ', doctor ID: ' . $cita->id_doctor
                . ', fecha: ' . $cita->fecha_cita->format('Y-m-d')
                . ', hora: ' . $cita->hora_inicio
            );

            return redirect()
                ->route('citas')
                ->with('success', 'Cita actualizada correctamente.');
        }

        return redirect()
            ->route('citas')
            ->with('error', 'No fue posible actualizar la cita.');
    }

    public function eliminarCita(Cita $cita): RedirectResponse
    {
        $id = $cita->getKey();
        $fecha = $cita->fecha_cita->format('Y-m-d');
        $hora = $cita->hora_inicio;

        if (app(CitaService::class)->eliminar($cita)) {
            Bitacora::registrar(
                Bitacora::ELIMINADO,
                'CITAS',
                $id,
                'Cita eliminada. Fecha: ' . $fecha
                . ', hora: ' . $hora
            );

            return redirect()
                ->route('citas')
                ->with('success', 'Cita eliminada correctamente.');
        }

        return redirect()
            ->route('citas')
            ->with('error', 'No fue posible eliminar la cita.');
    }

    /**
     * Un médico solo consulta sus citas; los demás roles consultan todas.
     */
    private function puedeVerCita(Cita $cita): bool
    {
        if (!$this->esMedico()) {
            return true;
        }

        $doctor = $this->doctorActual();

        return $doctor !== null
            && (int) $cita->id_doctor === (int) $doctor->getKey();
    }
}
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p Http/Requests
  descender Http/Requests
  escribir_archivo "InsertarReferenciaMedicaRequest.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use App\Models\ReferenciaMedica;
use App\Models\RegistroMedico;
use Illuminate\Validation\Validator;

/**
 * Nueva referencia médica. El paciente se indica por DUI (y debe ser alguien
 * a quien el médico atiende); el médico que refiere sale de la sesión.
 */
class InsertarReferenciaMedicaRequest extends SolicitudConPaciente
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'id_doctor_origen' => Doctor::buscarPorUsuario($this->user()->getKey())?->getKey(),
        ]);
    }

    public function rules(): array
    {
        return ReferenciaMedica::validaciones();
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (!$this->input('id_doctor_destino') && !$this->input('id_especialidad_destino')) {
                $validator->errors()->add(
                    'id_doctor_destino',
                    'Indique el médico o la especialidad a la que se refiere al paciente.'
                );
            }

            $idRegistro = $this->input('id_registro_medico');

            if ($idRegistro) {
                $registro = RegistroMedico::find($idRegistro);

                if (!$registro || (int) $registro->id_paciente !== (int) $this->input('id_paciente')) {
                    $validator->errors()->add(
                        'id_registro_medico',
                        'El registro médico no corresponde al paciente indicado.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'id_paciente.required' => 'Ingrese el DUI del paciente.',
            'id_paciente.exists' => 'No se encontró un paciente con ese DUI entre los que usted atiende.',
            'id_doctor_origen.required' => 'Su usuario no tiene un perfil de médico registrado.',
            'id_doctor_destino.exists' => 'El médico de destino no existe.',
            'id_especialidad_destino.exists' => 'La especialidad de destino no existe.',
            'id_registro_medico.exists' => 'El registro médico seleccionado no existe.',
            'motivo.required' => 'Indique el motivo de la referencia.',
            'motivo.max' => 'El motivo no debe exceder los 255 caracteres.',
            'resumen_clinico.max' => 'El resumen clínico es demasiado extenso.',
            'estudios_solicitados.max' => 'Los estudios solicitados son demasiado extensos.',
            'prioridad.required' => 'Seleccione la prioridad.',
            'prioridad.in' => 'La prioridad debe ser normal, prioritaria o urgente.',
        ];
    }
}
FIN_ARCHIVO
  escribir_archivo "ProgramarReferenciaMedicaRequest.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos con los que recepción programa una referencia (la cita se valida
 * después contra el horario del médico y los cruces con otras citas).
 */
class ProgramarReferenciaMedicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_doctor' => ['required', 'integer', 'exists:doctores,id'],
            'fecha_cita' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_doctor.required' => 'Seleccione el médico que atenderá la cita.',
            'id_doctor.exists' => 'El médico seleccionado no existe.',
            'fecha_cita.required' => 'Seleccione la fecha de la cita.',
            'fecha_cita.date' => 'La fecha de la cita no es válida.',
            'fecha_cita.after_or_equal' => 'La fecha de la cita no puede ser anterior a hoy.',
            'hora_inicio.required' => 'Indique la hora de inicio.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
            'hora_fin.required' => 'Indique la hora de finalización.',
            'hora_fin.date_format' => 'La hora de finalización debe tener el formato HH:MM.',
            'hora_fin.after' => 'La hora de finalización debe ser posterior a la de inicio.',
            'notas.max' => 'Las notas no deben exceder los 500 caracteres.',
        ];
    }
}
FIN_ARCHIVO
  escribir_archivo "MotivoReferenciaMedicaRequest.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Motivo para rechazar o cancelar una referencia médica.
 */
class MotivoReferenciaMedicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Indique el motivo.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
            'motivo.max' => 'El motivo no debe exceder los 500 caracteres.',
        ];
    }
}
FIN_ARCHIVO
  escribir_archivo "GestionarRelacionAsistencialRequest.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Motivo para cerrar, revocar, suspender o reactivar una relación asistencial.
 */
class GestionarRelacionAsistencialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Indique el motivo.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
            'motivo.max' => 'El motivo no debe exceder los 255 caracteres.',
        ];
    }
}
FIN_ARCHIVO
  escribir_archivo "SolicitarAccesoExcepcionalRequest.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Justificación de un acceso excepcional (break-glass) a un expediente.
 */
class SolicitarAccesoExcepcionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:20', 'max:500'],
            'confirmo' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'La justificación es obligatoria.',
            'motivo.min' => 'Describa la emergencia con al menos 20 caracteres.',
            'motivo.max' => 'La justificación no debe exceder los 500 caracteres.',
            'confirmo.accepted' => 'Debe confirmar que se trata de una emergencia real.',
        ];
    }
}
FIN_ARCHIVO
  escribir_archivo "RevisarAccesoExcepcionalRequest.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Observación de la revisión posterior de un acceso excepcional.
 */
class RevisarAccesoExcepcionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'observacion.max' => 'La observación no debe exceder los 1000 caracteres.',
        ];
    }
}
FIN_ARCHIVO
  escribir_archivo "GuardarTareaRequest.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos de una tarea personal (alta y edición).
 */
class GuardarTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'prioridad' => ['required', 'in:baja,normal,alta,urgente'],
            'estado' => ['sometimes', 'in:pendiente,en_progreso,completada,cancelada'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'fecha_recordatorio' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Escriba el título de la tarea.',
            'titulo.max' => 'El título no debe exceder los 150 caracteres.',
            'descripcion.max' => 'La descripción no debe exceder los 2000 caracteres.',
            'prioridad.required' => 'Seleccione la prioridad.',
            'prioridad.in' => 'La prioridad debe ser baja, normal, alta o urgente.',
            'estado.in' => 'El estado de la tarea no es válido.',
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'fecha_vencimiento.date' => 'La fecha de vencimiento no es válida.',
            'fecha_recordatorio.date' => 'La fecha del recordatorio no es válida.',
        ];
    }
}
FIN_ARCHIVO
  escribir_archivo "CambiarEstadoTareaRequest.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Nuevo estado de una tarea personal.
 */
class CambiarEstadoTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:pendiente,en_progreso,completada,cancelada'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Indique el estado.',
            'estado.in' => 'El estado de la tarea no es válido.',
        ];
    }
}
FIN_ARCHIVO
  escribir_archivo "SolicitudConPaciente.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Receta;
use App\Models\RegistroMedico;
use App\Models\Usuario;
use Illuminate\Contracts\Validation\Validator as ContratoValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * Base de las solicitudes que trabajan con un paciente.
 *
 * Los pacientes se identifican por su DUI: el formulario envía "dui_paciente"
 * y aquí se convierte en "id_paciente", que es lo que validan los modelos.
 * Un médico solo puede trabajar con pacientes que atiende.
 */
abstract class SolicitudConPaciente extends FormRequest
{
    /**
     * Convierte el DUI escrito en el formulario en el id del paciente. Si el
     * DUI no existe (o el médico no atiende a ese paciente) deja id_paciente
     * vacío y la validación responde que no se encontró el paciente.
     */
    protected function prepareForValidation(): void
    {
        $dui = trim((string) $this->input('dui_paciente', ''));

        if ($dui === '') {
            return;
        }

        $paciente = Paciente::buscarPacienteXDui($dui);

        if ($paciente && !$this->puedeAccederAlPaciente($paciente->getKey())) {
            $paciente = null;
        }

        // Un id inexistente (0) hace que la validación responda "no se
        // encontró un paciente con ese DUI" en lugar de "campo obligatorio".
        $this->merge([
            'id_paciente' => $paciente?->getKey() ?? 0,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Si llega un id_paciente directo (sin DUI), un médico solo puede usarlo
     * si atiende a ese paciente.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $id = $this->input('id_paciente');

            if (
                $id !== null &&
                $id !== '' &&
                !$validator->errors()->has('id_paciente') &&
                !$this->puedeAccederAlPaciente((int) $id)
            ) {
                $validator->errors()->add(
                    'id_paciente',
                    'No tiene permiso para trabajar con este paciente.'
                );
            }
        });
    }

    /**
     * Indica si el usuario autenticado puede trabajar con un paciente.
     */
    protected function puedeAccederAlPaciente(?int $idPaciente): bool
    {
        if ($idPaciente === null) {
            return false;
        }

        $usuario = $this->user();

        if (!$usuario || !$usuario->tieneRol(Usuario::ROL_MEDICO)) {
            return true;
        }

        $doctor = Doctor::buscarPorUsuario($usuario->getKey());

        return $doctor !== null
            && Paciente::atendidoPor($idPaciente, $doctor->getKey());
    }

    /**
     * Indica si el usuario puede trabajar con el paciente de un registro
     * médico. Si el registro no existe devuelve true: la validación se
     * encarga de avisar que no existe.
     */
    protected function puedeAccederAlRegistro(?int $idRegistroMedico): bool
    {
        if (!$idRegistroMedico) {
            return true;
        }

        $registro = RegistroMedico::withTrashed()->find($idRegistroMedico);

        return $registro === null
            || $this->puedeAccederAlPaciente($registro->id_paciente);
    }

    /**
     * Indica si el usuario es el médico autor de un registro médico: leer un
     * registro no autoriza a escribir en él (diagnósticos, recetas). Si el
     * registro no existe devuelve true: la validación avisa que no existe.
     */
    protected function esAutorDelRegistro(?int $idRegistroMedico): bool
    {
        if (!$idRegistroMedico) {
            return true;
        }

        $registro = RegistroMedico::withTrashed()->find($idRegistroMedico);

        return $registro === null
            || ($this->user()?->can('escribir', $registro) ?? false);
    }

    /**
     * Indica si el usuario es el médico autor del registro de una receta.
     */
    protected function esAutorDeLaReceta(?int $idReceta): bool
    {
        if (!$idReceta) {
            return true;
        }

        $receta = Receta::find($idReceta);

        return $receta === null
            || $this->esAutorDelRegistro($receta->id_registro_medico);
    }

    /**
     * Indica si el usuario puede trabajar con el paciente de una receta.
     */
    protected function puedeAccederALaReceta(?int $idReceta): bool
    {
        if (!$idReceta) {
            return true;
        }

        $receta = Receta::find($idReceta);

        return $receta === null
            || $this->puedeAccederAlRegistro($receta->id_registro_medico);
    }

    /**
     * Cuando el formulario usa el DUI, los errores del paciente se muestran
     * bajo ese campo (dui_paciente) en lugar de id_paciente.
     */
    protected function failedValidation(ContratoValidator $validator): void
    {
        $mensajes = $validator->errors()->messages();

        if ($this->has('dui_paciente') && isset($mensajes['id_paciente'])) {
            $mensajes['dui_paciente'] = array_merge(
                $mensajes['dui_paciente'] ?? [],
                $mensajes['id_paciente']
            );

            unset($mensajes['id_paciente']);
        }

        throw (ValidationException::withMessages($mensajes))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }

    /**
     * Respuesta cuando falla la autorización: mensaje en JSON o redirección
     * con el aviso de error.
     */
    protected function failedAuthorization(): void
    {
        $mensaje = 'No tiene permiso para realizar esta acción con este paciente.';

        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json(['message' => $mensaje], 403)
            );
        }

        throw new HttpResponseException(
            redirect()->back()->with('error', $mensaje)
        );
    }
}
FIN_ARCHIVO
  escribir_archivo "InsertarRegistroMedicoDiagnosticoRequest.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Requests;

use App\Models\RegistroMedicoDiagnostico;

class InsertarRegistroMedicoDiagnosticoRequest extends SolicitudConPaciente
{
    public function authorize(): bool
    {
        return $this->esAutorDelRegistro((int) $this->input('id_registro_medico'));
    }

    public function rules(): array
    {
        return RegistroMedicoDiagnostico::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_registro_medico.required' => 'Debe seleccionar un registro médico.',
            'id_registro_medico.integer' => 'El registro médico seleccionado no es válido.',
            'id_registro_medico.exists' => 'El registro médico seleccionado no existe.',

            'id_diagnostico.required' => 'Debe seleccionar un diagnóstico.',
            'id_diagnostico.integer' => 'El diagnóstico seleccionado no es válido.',
            'id_diagnostico.exists' => 'El diagnóstico seleccionado no existe.',

            'notas.string' => 'Las notas deben contener texto válido.',
            'notas.max' => 'Las notas no pueden superar los 250 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id_registro_medico' => 'registro médico',
            'id_diagnostico' => 'diagnóstico',
            'notas' => 'notas',
        ];
    }
}
FIN_ARCHIVO
  escribir_archivo "InsertarRecetaRequest.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Requests;

use App\Models\DetalleReceta;
use App\Models\Receta;

class InsertarRecetaRequest extends SolicitudConPaciente
{
    public function authorize(): bool
    {
        return $this->esAutorDelRegistro((int) $this->input('id_registro_medico'));
    }

    public function rules(): array
    {
        $reglas = Receta::validaciones();

        $reglas['detalles'] = [
            'required',
            'array',
            'min:1',
        ];

        foreach (DetalleReceta::validaciones() as $campo => $reglasCampo) {
            if ($campo === 'id_receta') {
                continue;
            }

            $reglas['detalles.*.' . $campo] = $reglasCampo;
        }

        return $reglas;
    }

    protected function mensajesReceta(): array
    {
        return [
            'id_registro_medico.required' => 'Debe seleccionar un registro médico.',
            'id_registro_medico.integer' => 'El registro médico seleccionado no es válido.',
            'id_registro_medico.exists' => 'El registro médico seleccionado no existe.',

            'fecha_prescripcion.date' => 'La fecha de prescripción no es válida.',

            'notas.string' => 'Las notas deben ser texto.',
            'notas.max' => 'Las notas no deben superar los 250 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id_registro_medico' => 'registro médico',
            'fecha_prescripcion' => 'fecha de prescripción',
            'notas' => 'notas',
        ];
    }

    /**
     * Mensajes de validación (receta y medicamentos).
     */
    public function messages(): array
    {
        return array_merge($this->mensajesReceta(), [
            'detalles.required' => 'Agregue al menos un medicamento a la receta.',
            'detalles.array' => 'Los medicamentos de la receta no son válidos.',
            'detalles.min' => 'Agregue al menos un medicamento a la receta.',
            'detalles.*.id_medicamento.required' => 'Seleccione el medicamento.',
            'detalles.*.id_medicamento.integer' => 'El medicamento seleccionado no es válido.',
            'detalles.*.id_medicamento.exists' => 'El medicamento seleccionado no existe.',
            'detalles.*.dosis.required' => 'Indique la dosis.',
            'detalles.*.dosis.string' => 'La dosis debe ser texto.',
            'detalles.*.dosis.max' => 'La dosis no debe exceder los 100 caracteres.',
            'detalles.*.frecuencia.required' => 'Indique la frecuencia.',
            'detalles.*.frecuencia.string' => 'La frecuencia debe ser texto.',
            'detalles.*.frecuencia.max' => 'La frecuencia no debe exceder los 100 caracteres.',
            'detalles.*.duracion.string' => 'La duración debe ser texto.',
            'detalles.*.duracion.max' => 'La duración no debe exceder los 100 caracteres.',
            'detalles.*.via_administracion.string' => 'La vía de administración debe ser texto.',
            'detalles.*.via_administracion.max' => 'La vía de administración no debe exceder los 50 caracteres.',
            'detalles.*.instrucciones.string' => 'Las indicaciones deben ser texto.',
        ]);
    }
}
FIN_ARCHIVO
  escribir_archivo "InsertarDetalleRecetaRequest.php" <<'FIN_ARCHIVO'
<?php

namespace App\Http\Requests;

use App\Models\DetalleReceta;

class InsertarDetalleRecetaRequest extends SolicitudConPaciente
{
    public function authorize(): bool
    {
        return $this->esAutorDeLaReceta((int) $this->input('id_receta'));
    }

    public function rules(): array
    {
        return DetalleReceta::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_receta.required' => 'Debe seleccionar una receta.',
            'id_receta.integer' => 'La receta seleccionada no es válida.',
            'id_receta.exists' => 'La receta seleccionada no existe.',

            'id_medicamento.required' => 'Debe seleccionar un medicamento.',
            'id_medicamento.integer' => 'El medicamento seleccionado no es válido.',
            'id_medicamento.exists' => 'El medicamento seleccionado no existe.',

            'dosis.required' => 'Debe especificar la dosis del medicamento.',
            'dosis.string' => 'La dosis debe ser texto.',
            'dosis.max' => 'La dosis no debe superar los 100 caracteres.',

            'frecuencia.required' => 'Debe especificar la frecuencia de administración.',
            'frecuencia.string' => 'La frecuencia debe ser texto.',
            'frecuencia.max' => 'La frecuencia no debe superar los 100 caracteres.',

            'duracion.string' => 'La duración debe ser texto.',
            'duracion.max' => 'La duración no debe superar los 100 caracteres.',

            'via_administracion.string' => 'La vía de administración debe ser texto.',
            'via_administracion.max' => 'La vía de administración no debe superar los 50 caracteres.',

            'instrucciones.string' => 'Las instrucciones deben ser texto.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id_receta' => 'receta',
            'id_medicamento' => 'medicamento',
            'dosis' => 'dosis',
            'frecuencia' => 'frecuencia',
            'duracion' => 'duración',
            'via_administracion' => 'vía de administración',
            'instrucciones' => 'instrucciones',
        ];
    }
}
FIN_ARCHIVO
  ascender
  ascender
}

escribir_rutas() {
  log "Escribiendo rutas (2 archivos)"
  ejecutar mkdir -p routes
  descender routes
  escribir_archivo "console.php" <<'FIN_ARCHIVO'
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
| En desarrollo se ejecutan con:  php artisan schedule:work
| En producción basta una entrada de cron que ejecute cada minuto:
|   php artisan schedule:run
*/

// Recordatorios y avisos de vencimiento de las tareas personales.
Schedule::command('tareas:recordatorios')
    ->everyMinute()
    ->withoutOverlapping();

// Referencias pendientes que pasaron su plazo.
Schedule::command('referencias:vencer')
    ->dailyAt('01:00')
    ->withoutOverlapping();
FIN_ARCHIVO
  escribir_archivo "web.php" <<'FIN_ARCHIVO'
<?php

use App\Http\Controllers\AlergiaController;
use App\Http\Controllers\AnuncioGlobalController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\CondicionMedicaController;
use App\Http\Controllers\DetalleRecetaController;
use App\Http\Controllers\DiagnosticoController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HorarioDoctorController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MedicamentoController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\PacienteAlergiaController;
use App\Http\Controllers\PacienteCondicionController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RecetaController;
use App\Http\Controllers\RegistroMedicoController;
use App\Http\Controllers\RegistroMedicoDiagnosticoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\TipoSanguineoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AccesoExcepcionalController;
use App\Http\Controllers\AuditoriaExpedienteController;
use App\Http\Controllers\ReferenciaMedicaController;
use App\Http\Controllers\RelacionAsistencialController;
use App\Http\Controllers\TareaPersonalController;
use Illuminate\Support\Facades\Route;

// RUTAS DE AUTENTICACIÓN
Route::get('login', [LoginController::class, 'index'])->name('login');
Route::post('login', [LoginController::class, 'login'])->middleware('throttle:20,1');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// RUTAS PROTEGIDAS
Route::middleware(['auth', 'rol', 'no-cache'])->group(function () {

    // CAMBIO DE CONTRASEÑA
    // Se puede usar aunque el usuario esté obligado a cambiarla.
    Route::get('cambiar-contrasenha', [PerfilController::class, 'formularioContrasenha'])->name('contrasenha.cambiar');
    Route::put('cambiar-contrasenha', [PerfilController::class, 'cambiarContrasenha'])->name('contrasenha.actualizar');

    // Todo lo demás exige haber cambiado la contraseña temporal.
    Route::middleware('cambio-contrasenha')->group(function () {

        // ACCESO GENERAL: TODOS LOS ROLES
        // Home
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('home/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

        // Perfil propio
        Route::get('perfil', [PerfilController::class, 'index'])->name('perfil');
        Route::put('perfil', [PerfilController::class, 'actualizar'])->name('perfil.actualizar');
        Route::post('perfil/foto', [PerfilController::class, 'actualizarFoto'])->name('perfil.foto');
        Route::delete('perfil/foto', [PerfilController::class, 'eliminarFoto'])->name('perfil.foto.eliminar');

        // Notificaciones: cada usuario ve las suyas
        Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones');
        Route::get('/notificaciones/todos', [NotificacionController::class, 'mostrarTodos'])->name('notificaciones.todos');
        Route::get('/notificaciones/buscar/{id}', [NotificacionController::class, 'buscar'])->name('notificaciones.buscar');
        Route::put('/notificaciones/{id}/leer', [NotificacionController::class, 'leer'])->name('notificaciones.leer');

        // Anuncios globales: consulta
        Route::get('/anuncios-globales', [AnuncioGlobalController::class, 'index'])->name('anuncios_globales');
        Route::get('/anuncios-globales/todos', [AnuncioGlobalController::class, 'mostrarTodos'])->name('anuncios_globales.todos');
        Route::get('/anuncios-globales/buscar/{id}', [AnuncioGlobalController::class, 'buscar'])->name('anuncios_globales.buscar');

        // ADMINISTRADOR
        Route::middleware('rol:Administrador')->group(function () {

            // Bitácora
            Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora');

            // Roles
            Route::get('roles', [RolController::class, 'index'])->name('roles');
            Route::post('roles', [RolController::class, 'insertarRol'])->name('roles.insertar');
            Route::get('roles/{id}/buscar', [RolController::class, 'buscarRolXId'])->name('roles.buscar');
            Route::put('roles/{rol}', [RolController::class, 'actualizarRol'])->name('roles.actualizar');
            Route::delete('roles/{rol}', [RolController::class, 'eliminarRol'])->name('roles.eliminar');

            // Estados
            Route::get('estados', [EstadoController::class, 'index'])->name('estados');
            Route::post('estados', [EstadoController::class, 'insertarEstado'])->name('estados.insertar');
            Route::get('estados/{id}/buscar', [EstadoController::class, 'buscarEstadoXId'])->name('estados.buscar');
            Route::put('estados/{estado}', [EstadoController::class, 'actualizarEstado'])->name('estados.actualizar');
            Route::delete('estados/{estado}', [EstadoController::class, 'eliminarEstado'])->name('estados.eliminar');

            // Usuarios (si el rol es Médico, se registran también los datos del doctor)
            Route::get('usuarios', [UsuarioController::class, 'index'])->name('usuarios');
            Route::post('usuarios', [UsuarioController::class, 'insertarUsuario'])->name('usuarios.insertar');
            Route::get('usuarios/{id}/buscar', [UsuarioController::class, 'buscarUsuarioXId'])->name('usuarios.buscar');
            Route::put('usuarios/{usuario}', [UsuarioController::class, 'actualizarUsuario'])->name('usuarios.actualizar');
            Route::delete('usuarios/{usuario}', [UsuarioController::class, 'eliminarUsuario'])->name('usuarios.eliminar');

            // Administración de catálogos
            // Tipos sanguíneos
            Route::post('tipos-sanguineos', [TipoSanguineoController::class, 'insertarTipoSanguineo'])->name('tipos-sanguineos.insertar');
            Route::put('tipos-sanguineos/{tipoSanguineo}', [TipoSanguineoController::class, 'actualizarTipoSanguineo'])->name('tipos-sanguineos.actualizar');
            Route::delete('tipos-sanguineos/{tipoSanguineo}', [TipoSanguineoController::class, 'eliminarTipoSanguineo'])->name('tipos-sanguineos.eliminar');

            // Especialidades
            Route::post('especialidades', [EspecialidadController::class, 'insertarEspecialidad'])->name('especialidades.insertar');
            Route::put('especialidades/{especialidad}', [EspecialidadController::class, 'actualizarEspecialidad'])->name('especialidades.actualizar');
            Route::delete('especialidades/{especialidad}', [EspecialidadController::class, 'eliminarEspecialidad'])->name('especialidades.eliminar');

            // Diagnósticos
            Route::post('diagnosticos', [DiagnosticoController::class, 'insertarDiagnostico'])->name('diagnosticos.insertar');
            Route::put('diagnosticos/{diagnostico}', [DiagnosticoController::class, 'actualizarDiagnostico'])->name('diagnosticos.actualizar');
            Route::delete('diagnosticos/{diagnostico}', [DiagnosticoController::class, 'eliminarDiagnostico'])->name('diagnosticos.eliminar');

            // Condiciones médicas
            Route::post('condiciones-medicas', [CondicionMedicaController::class, 'insertarCondicionMedica'])->name('condiciones-medicas.insertar');
            Route::put('condiciones-medicas/{condicionMedica}', [CondicionMedicaController::class, 'actualizarCondicionMedica'])->name('condiciones-medicas.actualizar');
            Route::delete('condiciones-medicas/{condicionMedica}', [CondicionMedicaController::class, 'eliminarCondicionMedica'])->name('condiciones-medicas.eliminar');

            // Alergias
            Route::post('alergias', [AlergiaController::class, 'insertarAlergia'])->name('alergias.insertar');
            Route::put('alergias/{alergia}', [AlergiaController::class, 'actualizarAlergia'])->name('alergias.actualizar');
            Route::delete('alergias/{alergia}', [AlergiaController::class, 'eliminarAlergia'])->name('alergias.eliminar');

            // Medicamentos
            Route::post('medicamentos', [MedicamentoController::class, 'insertarMedicamento'])->name('medicamentos.insertar');
            Route::put('medicamentos/{medicamento}', [MedicamentoController::class, 'actualizarMedicamento'])->name('medicamentos.actualizar');
            Route::delete('medicamentos/{medicamento}', [MedicamentoController::class, 'eliminarMedicamento'])->name('medicamentos.eliminar');

            // Servicios
            Route::post('servicios', [ServicioController::class, 'insertarServicio'])->name('servicios.insertar');
            Route::put('servicios/{servicio}', [ServicioController::class, 'actualizarServicio'])->name('servicios.actualizar');
            Route::delete('servicios/{servicio}', [ServicioController::class, 'eliminarServicio'])->name('servicios.eliminar');

            // Doctores: los datos profesionales se crean junto con el usuario
            Route::put('doctores/{doctor}', [DoctorController::class, 'actualizarDoctor'])->whereNumber('doctor')->name('doctores.actualizar');

            // Anuncios globales: administración
            Route::post('/anuncios-globales/insertar', [AnuncioGlobalController::class, 'insertar'])->name('anuncios_globales.insertar');
            Route::put('/anuncios-globales/actualizar/{id}', [AnuncioGlobalController::class, 'actualizar'])->name('anuncios_globales.actualizar');

            // Notificaciones: administración
            Route::post('/notificaciones/insertar', [NotificacionController::class, 'insertar'])->name('notificaciones.insertar');
            Route::put('/notificaciones/actualizar/{id}', [NotificacionController::class, 'actualizar'])->name('notificaciones.actualizar');

            // Papelera de datos clínicos
            Route::get('pacientes/papelera', [PacienteController::class, 'papelera'])->name('pacientes.papelera');
            Route::put('pacientes/{id}/restaurar', [PacienteController::class, 'restaurarPaciente'])->whereNumber('id')->name('pacientes.restaurar');
            Route::get('registros-medicos/papelera', [RegistroMedicoController::class, 'papelera'])->name('registros_medicos.papelera');
            Route::put('registros-medicos/{id}/restaurar', [RegistroMedicoController::class, 'restaurarRegistroMedico'])->whereNumber('id')->name('registros_medicos.restaurar');
            Route::delete('registros-medicos/{registroMedico}', [RegistroMedicoController::class, 'eliminarRegistroMedico'])->name('registros_medicos.eliminar');

            // Facturación: anulación (el administrador supervisa, no emite ni cobra)
            Route::put('/facturas/{factura}/anular', [FacturaController::class, 'anularFactura'])->whereNumber('factura')->name('facturas.anular');

        });

        // ADMINISTRADOR Y MÉDICO
        Route::middleware('rol:Administrador,Médico')->group(function () {

            // Diagnósticos: consulta
            Route::get('diagnosticos', [DiagnosticoController::class, 'index'])->name('diagnosticos');
            Route::get('diagnosticos/{id}/buscar', [DiagnosticoController::class, 'buscarDiagnosticoXId'])->name('diagnosticos.buscar');

            // Condiciones médicas: consulta
            Route::get('condiciones-medicas', [CondicionMedicaController::class, 'index'])->name('condiciones-medicas');
            Route::get('condiciones-medicas/{id}/buscar', [CondicionMedicaController::class, 'buscarCondicionMedicaXId'])->name('condiciones-medicas.buscar');

            // Alergias: consulta
            Route::get('alergias', [AlergiaController::class, 'index'])->name('alergias');
            Route::get('alergias/{id}/buscar', [AlergiaController::class, 'buscarAlergiaXId'])->name('alergias.buscar');

            // Medicamentos: consulta
            Route::get('medicamentos', [MedicamentoController::class, 'index'])->name('medicamentos');
            Route::get('medicamentos/{id}/buscar', [MedicamentoController::class, 'buscarMedicamentoXId'])->name('medicamentos.buscar');

            // Expediente clínico del paciente (un médico solo ve a quien atiende)
            Route::get('pacientes/{paciente}/expediente', [PacienteController::class, 'expediente'])->whereNumber('paciente')->name('pacientes.expediente');

            // Registros médicos: consulta
            Route::get('registros-medicos', [RegistroMedicoController::class, 'index'])->name('registros_medicos');
            Route::get('registros-medicos/{id}/buscar', [RegistroMedicoController::class, 'buscarRegistroMedicoXId'])->name('registros_medicos.buscar');

            // Diagnósticos asociados a registros médicos
            Route::get('registro-medico-diagnosticos/{id}/buscar', [RegistroMedicoDiagnosticoController::class, 'buscarRegistroMedicoDiagnosticoXId'])->name('registro_medico_diagnosticos.buscar');
            Route::delete('registro-medico-diagnosticos/{registroMedicoDiagnostico}', [RegistroMedicoDiagnosticoController::class, 'eliminarRegistroMedicoDiagnostico'])->name('registro_medico_diagnosticos.eliminar');

            // Condiciones de pacientes
            Route::get('/pacientes-condiciones/buscar/{id}', [PacienteCondicionController::class, 'buscarPacienteCondicion'])->name('pacientes_condiciones.buscar');
            Route::delete('/pacientes-condiciones/eliminar/{pacienteCondicion}', [PacienteCondicionController::class, 'eliminarPacienteCondicion'])->name('pacientes_condiciones.eliminar');

            // Alergias de pacientes
            Route::get('/pacientes-alergias/buscar/{id}', [PacienteAlergiaController::class, 'buscarPacienteAlergia'])->name('pacientes_alergias.buscar');
            Route::delete('/pacientes-alergias/eliminar/{pacienteAlergia}', [PacienteAlergiaController::class, 'eliminarPacienteAlergia'])->name('pacientes_alergias.eliminar');

            // Recetas
            Route::get('/recetas', [RecetaController::class, 'index'])->name('recetas');
            Route::get('/recetas/buscar/{id}', [RecetaController::class, 'buscarReceta'])->name('recetas.buscar');

            // Detalle de recetas
            Route::get('/detalle-recetas/buscar/{id}', [DetalleRecetaController::class, 'buscarDetalleReceta'])->name('detalle_recetas.buscar');

            // Horarios: gestión (un médico solo gestiona los suyos)
            Route::post('horarios', [HorarioDoctorController::class, 'insertarHorario'])->name('horarios.insertar');
            Route::put('horarios/{horario}', [HorarioDoctorController::class, 'actualizarHorario'])->name('horarios.actualizar');
            Route::delete('horarios/{horario}', [HorarioDoctorController::class, 'eliminarHorario'])->name('horarios.eliminar');

        });

        // MÉDICO: REGISTRO DE LA ATENCIÓN CLÍNICA
        Route::middleware('rol:Médico')->group(function () {

            Route::post('registros-medicos', [RegistroMedicoController::class, 'insertarRegistroMedico'])->name('registros_medicos.insertar');
            Route::post('registro-medico-diagnosticos', [RegistroMedicoDiagnosticoController::class, 'insertarRegistroMedicoDiagnostico'])->name('registro_medico_diagnosticos.insertar');
            Route::post('/pacientes-condiciones/insertar', [PacienteCondicionController::class, 'insertarPacienteCondicion'])->name('pacientes_condiciones.insertar');
            Route::post('/pacientes-alergias/insertar', [PacienteAlergiaController::class, 'insertarPacienteAlergia'])->name('pacientes_alergias.insertar');
            Route::post('/recetas/insertar', [RecetaController::class, 'insertarReceta'])->name('recetas.insertar');
            Route::post('/detalle-recetas/insertar', [DetalleRecetaController::class, 'insertarDetalleReceta'])->name('detalle_recetas.insertar');

        });

        // ADMINISTRADOR Y RECEPCIONISTA
        Route::middleware('rol:Administrador,Recepcionista')->group(function () {

            // Gestión de pacientes
            Route::post('pacientes', [PacienteController::class, 'insertarPaciente'])->name('pacientes.insertar');
            Route::put('pacientes/{paciente}', [PacienteController::class, 'actualizarPaciente'])->whereNumber('paciente')->name('pacientes.actualizar');
            Route::delete('pacientes/{paciente}', [PacienteController::class, 'eliminarPaciente'])->whereNumber('paciente')->name('pacientes.eliminar');

            // Gestión de citas
            Route::post('citas', [CitaController::class, 'insertarCita'])->name('citas.insertar');
            Route::put('citas/{cita}', [CitaController::class, 'actualizarCita'])->whereNumber('cita')->name('citas.actualizar');
            Route::delete('citas/{cita}', [CitaController::class, 'eliminarCita'])->whereNumber('cita')->name('citas.eliminar');

        });

        // ADMINISTRADOR, MÉDICO Y RECEPCIONISTA
        Route::middleware('rol:Administrador,Médico,Recepcionista')->group(function () {

            // Tipos sanguíneos: consulta
            Route::get('tipos-sanguineos', [TipoSanguineoController::class, 'index'])->name('tipos-sanguineos');
            Route::get('tipos-sanguineos/{id}/buscar', [TipoSanguineoController::class, 'buscarTipoSanguineoXId'])->name('tipos-sanguineos.buscar');

            // Especialidades: consulta
            Route::get('especialidades', [EspecialidadController::class, 'index'])->name('especialidades');
            Route::get('especialidades/{id}/buscar', [EspecialidadController::class, 'buscarEspecialidadXId'])->name('especialidades.buscar');

            // Doctores: consulta
            Route::get('doctores', [DoctorController::class, 'index'])->name('doctores');
            Route::get('doctores/{id}/buscar', [DoctorController::class, 'buscarDoctorXId'])->whereNumber('id')->name('doctores.buscar');
            Route::get('doctores/{doctor}', [DoctorController::class, 'perfil'])->whereNumber('doctor')->name('doctores.perfil');

            // Horarios de doctores: consulta
            Route::get('horarios', [HorarioDoctorController::class, 'index'])->name('horarios');
            Route::get('horarios/{id}/buscar', [HorarioDoctorController::class, 'buscarHorarioXId'])->name('horarios.buscar');

            // Pacientes: consulta (un médico solo ve a quien atiende). Se identifican por DUI.
            Route::get('pacientes', [PacienteController::class, 'index'])->name('pacientes');
            Route::get('pacientes/dui/{dui}', [PacienteController::class, 'buscarPacienteXDui'])->middleware('throttle:60,1')->name('pacientes.dui');
            Route::get('pacientes/{id}/buscar', [PacienteController::class, 'buscarPacienteXId'])->whereNumber('id')->name('pacientes.buscar');
            Route::get('pacientes/{paciente}', [PacienteController::class, 'perfil'])->whereNumber('paciente')->name('pacientes.perfil');

            // Citas: consulta (un médico solo ve las suyas)
            Route::get('citas', [CitaController::class, 'index'])->name('citas');
            Route::get('citas/{id}/buscar', [CitaController::class, 'buscarCitaXId'])->whereNumber('id')->name('citas.buscar');
            Route::get('citas/{cita}', [CitaController::class, 'detalle'])->whereNumber('cita')->name('citas.detalle');

        });

        // ÓRDENES DE SERVICIOS
        // Consulta: cada quien ve las que creó; administrador y contador ven todas.
        Route::middleware('rol:Administrador,Médico,Recepcionista,Contador')->group(function () {

            // Servicios: consulta (catálogo con precios)
            Route::get('servicios', [ServicioController::class, 'index'])->name('servicios');
            Route::get('servicios/{id}/buscar', [ServicioController::class, 'buscarServicioXId'])->name('servicios.buscar');

            Route::get('ordenes', [OrdenController::class, 'index'])->name('ordenes');
            Route::get('ordenes/{id}/buscar', [OrdenController::class, 'buscarOrdenXId'])->whereNumber('id')->name('ordenes.buscar');

        });

        // Crea la orden el médico (al terminar la atención) o la recepcionista.
        Route::middleware('rol:Médico,Recepcionista')->group(function () {

            Route::post('ordenes', [OrdenController::class, 'insertarOrden'])->name('ordenes.insertar');

        });

        Route::middleware('rol:Administrador,Médico,Recepcionista')->group(function () {

            Route::put('ordenes/{orden}/cancelar', [OrdenController::class, 'cancelarOrden'])->whereNumber('orden')->name('ordenes.cancelar');

        });

        // FACTURACIÓN: CONTADOR (emite y cobra) Y ADMINISTRADOR (supervisa)
        Route::middleware('rol:Administrador,Contador')->group(function () {

            Route::get('/facturas', [FacturaController::class, 'index'])->name('facturas');
            Route::get('/facturas/todos', [FacturaController::class, 'mostrarTodos'])->name('facturas.todos');
            Route::get('/facturas/buscar/{id}', [FacturaController::class, 'buscarFactura'])->whereNumber('id')->name('facturas.buscar');
            Route::get('/facturas/{id}', [FacturaController::class, 'ver'])->whereNumber('id')->name('facturas.ver');

            Route::get('/pagos', [PagoController::class, 'index'])->name('pagos');
            Route::get('/pagos/buscar/{id}', [PagoController::class, 'buscarPago'])->whereNumber('id')->name('pagos.buscar');

        });

        Route::middleware('rol:Contador')->group(function () {

            Route::post('/facturas', [FacturaController::class, 'emitirFactura'])->name('facturas.emitir');
            Route::post('/pagos', [PagoController::class, 'insertarPago'])->name('pagos.insertar');

        });


        // =====================================================================
        // REFERENCIAS MÉDICAS, RELACIONES ASISTENCIALES, ACCESO EXCEPCIONAL,
        // AUDITORÍA DEL EXPEDIENTE Y TAREAS PERSONALES
        // =====================================================================

        // Referencias: consulta y gestión (cada rol ve lo suyo)
        Route::middleware('rol:Administrador,Médico,Recepcionista')->group(function () {

            Route::get('referencias', [ReferenciaMedicaController::class, 'index'])->name('referencias');
            Route::put('referencias/{referencia}/rechazar', [ReferenciaMedicaController::class, 'rechazarReferencia'])->whereNumber('referencia')->name('referencias.rechazar');
            Route::put('referencias/{referencia}/cancelar', [ReferenciaMedicaController::class, 'cancelarReferencia'])->whereNumber('referencia')->name('referencias.cancelar');

        });

        // Referencias: el médico las emite
        Route::middleware('rol:Médico')->group(function () {

            Route::post('referencias', [ReferenciaMedicaController::class, 'insertarReferencia'])->name('referencias.insertar');

        });

        // Referencias: recepción (y administrador) las programa
        Route::middleware('rol:Administrador,Recepcionista')->group(function () {

            Route::put('referencias/{referencia}/programar', [ReferenciaMedicaController::class, 'programarReferencia'])->whereNumber('referencia')->name('referencias.programar');

        });

        // Relaciones asistenciales y accesos excepcionales: médico y administrador
        Route::middleware('rol:Administrador,Médico')->group(function () {

            Route::get('relaciones-asistenciales', [RelacionAsistencialController::class, 'index'])->name('relaciones');
            Route::put('relaciones-asistenciales/{relacion}/cerrar', [RelacionAsistencialController::class, 'cerrarRelacion'])->whereNumber('relacion')->name('relaciones.cerrar');

            Route::get('accesos-excepcionales', [AccesoExcepcionalController::class, 'index'])->name('accesos_excepcionales');
            Route::post('pacientes/{paciente}/acceso-excepcional', [AccesoExcepcionalController::class, 'solicitar'])->whereNumber('paciente')->name('pacientes.acceso_excepcional');

        });

        // Gestión de accesos y auditoría: solo administrador
        Route::middleware('rol:Administrador')->group(function () {

            Route::put('relaciones-asistenciales/{relacion}/revocar', [RelacionAsistencialController::class, 'revocarRelacion'])->whereNumber('relacion')->name('relaciones.revocar');
            Route::put('relaciones-asistenciales/{relacion}/suspender', [RelacionAsistencialController::class, 'suspenderRelacion'])->whereNumber('relacion')->name('relaciones.suspender');
            Route::put('relaciones-asistenciales/{relacion}/reactivar', [RelacionAsistencialController::class, 'reactivarRelacion'])->whereNumber('relacion')->name('relaciones.reactivar');

            Route::put('accesos-excepcionales/{acceso}/revisar', [AccesoExcepcionalController::class, 'revisar'])->whereNumber('acceso')->name('accesos_excepcionales.revisar');
            Route::put('accesos-excepcionales/{acceso}/revocar', [AccesoExcepcionalController::class, 'revocar'])->whereNumber('acceso')->name('accesos_excepcionales.revocar');

            Route::get('auditoria-expedientes', [AuditoriaExpedienteController::class, 'index'])->name('auditoria_expedientes');

        });

        // Tareas personales: todos los usuarios, cada uno con las suyas
        Route::middleware('rol:Administrador,Médico,Recepcionista,Contador')->group(function () {

            Route::get('tareas', [TareaPersonalController::class, 'index'])->name('tareas');
            Route::post('tareas', [TareaPersonalController::class, 'insertarTarea'])->name('tareas.insertar');
            Route::put('tareas/{tarea}', [TareaPersonalController::class, 'actualizarTarea'])->whereNumber('tarea')->name('tareas.actualizar');
            Route::put('tareas/{tarea}/estado', [TareaPersonalController::class, 'cambiarEstadoTarea'])->whereNumber('tarea')->name('tareas.estado');
            Route::delete('tareas/{tarea}', [TareaPersonalController::class, 'eliminarTarea'])->whereNumber('tarea')->name('tareas.eliminar');

        });

    });

});
FIN_ARCHIVO
  ascender
}

escribir_vistas() {
  log "Escribiendo vistas y componentes (10 archivos)"
  ejecutar mkdir -p resources/views
  descender resources/views
  ejecutar mkdir -p layouts
  descender layouts
  escribir_archivo "app_layout.blade.php" <<'FIN_ARCHIVO'
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('titulo', 'HealthWare')</title>

    {{-- Tema y estado del menú lateral, antes de pintar (evita parpadeos) --}}
    <script src="{{ asset('js/global-scripts.js') }}"></script>
    <script>
        (function () {
            try {
                if (window.matchMedia('(min-width: 992px)').matches && localStorage.getItem('hw-nav-collapsed') === '1') {
                    document.documentElement.classList.add('hw-nav-collapsed');
                }
            } catch (e) { /* almacenamiento no disponible */ }
        })();
    </script>

    {{-- Framework y sistema de diseño --}}
    <link rel="stylesheet" href="{{ asset('bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}">

    {{-- Material Symbols --}}
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

    {{-- Estilos de librerías y particulares de cada vista --}}
    @stack('estilos')

    {{-- Componentes de vista: se cargan al final para prevalecer sobre las librerías --}}
    <link rel="stylesheet" href="{{ asset('css/hw-components.css') }}">
</head>

<body class="app-body">

@php
    /*
    |--------------------------------------------------------------------------
    | Usuario autenticado
    |--------------------------------------------------------------------------
    */
    $usuario = Auth::user();

    $nombreUsuario = trim(($usuario->nombres ?? '') . ' ' . ($usuario->apellidos ?? ''));
    $nombreUsuario = $nombreUsuario ?: 'Usuario';

    $iniciales = mb_strtoupper(
        mb_substr($usuario->nombres ?? 'U', 0, 1) .
        mb_substr($usuario->apellidos ?? '', 0, 1)
    );

    $rolUsuario = mb_strtolower(trim($usuario->rol->rol ?? ''));

    // Foto de perfil (null si el usuario no tiene una)
    $fotoUsuario = $usuario?->fotoUrl();

    /*
    |--------------------------------------------------------------------------
    | Menú (única fuente: layouts/partials/menu_config.php)
    |--------------------------------------------------------------------------
    */
    $configuracionMenu = require resource_path('views/layouts/partials/menu_config.php');
    $menuModulos = $configuracionMenu['modulos'];

    $puedeVer = function (array $enlace) use ($rolUsuario) {
        return \Illuminate\Support\Facades\Route::has($enlace['ruta'])
            && in_array($rolUsuario, $enlace['roles'], true);
    };

    $accesosRapidos = array_values(array_filter($configuracionMenu['accesos_rapidos'], $puedeVer));

    /*
    |--------------------------------------------------------------------------
    | Notificaciones del usuario (no rompe la página si el modelo no existe)
    |--------------------------------------------------------------------------
    */
    $notifPendientes = 0;
    $notifRecientes = collect();

    $claseNotificacion = collect(['\\App\\Models\\Notificacion', '\\App\\Models\\Notificaciones'])
        ->first(fn ($clase) => class_exists($clase));

    if ($claseNotificacion && $usuario) {
        try {
            $consultaNotificaciones = $claseNotificacion::query()->where('id_usuario', $usuario->id);
            $notifPendientes = (clone $consultaNotificaciones)->whereNull('leido_en')->count();
            $notifRecientes = (clone $consultaNotificaciones)->orderByDesc('created_at')->limit(5)->get();
        } catch (\Throwable $e) {
            $notifPendientes = 0;
            $notifRecientes = collect();
        }
    }

    $iconoNotificacion = function ($notificacion) {
        $tipo = mb_strtolower((string) ($notificacion->tipo ?? ''));
        return match (true) {
            str_contains($tipo, 'referencia') => 'swap_horiz',
            str_contains($tipo, 'tarea') => 'task_alt',
            str_contains($tipo, 'clinica') => 'clinical_notes',
            str_contains($tipo, 'cita') => 'event',
            str_contains($tipo, 'factur') || str_contains($tipo, 'pago') => 'receipt_long',
            str_contains($tipo, 'receta') => 'medication',
            str_contains($tipo, 'anuncio') => 'campaign',
            str_contains($tipo, 'alerta') || str_contains($tipo, 'urgente') => 'warning',
            default => 'notifications',
        };
    };
@endphp

{{-- =====================================================
     BARRA SUPERIOR: menú · marca centrada · acciones
====================================================== --}}
<header class="app-topbar">

    {{-- Izquierda: botón del menú lateral + menús extra --}}
    <div class="app-topbar-start">

        <button
            type="button"
            class="btn btn-icon app-menu-toggle"
            id="sidebarToggle"
            aria-controls="appSidebar"
            aria-expanded="true"
            aria-label="Mostrar u ocultar el menú de navegación">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <nav class="app-topnav" aria-label="Accesos de la barra superior">

            <a href="{{ route('home') }}"
               class="app-topnav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                <span class="material-symbols-outlined">home</span>
                Inicio
            </a>

            @if(\Illuminate\Support\Facades\Route::has('dashboard'))
                <a href="{{ route('dashboard') }}"
                   class="app-topnav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">dashboard</span>
                    Dashboard
                </a>
            @endif

            @if(count($accesosRapidos))
                <div class="dropdown">
                    <button type="button"
                            class="app-topnav-link dropdown-toggle"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <span class="material-symbols-outlined">bolt</span>
                        Accesos rápidos
                        <span class="material-symbols-outlined">expand_more</span>
                    </button>

                    <ul class="dropdown-menu">
                        @foreach($accesosRapidos as $acceso)
                            <li>
                                <a class="dropdown-item" href="{{ route($acceso['ruta']) }}">
                                    <span class="material-symbols-outlined">{{ $acceso['icono'] }}</span>
                                    {{ $acceso['etiqueta'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

        </nav>
    </div>

    {{-- Centro: nombre del sistema --}}
    <div class="app-topbar-center">
        <a href="{{ route('home') }}" class="app-brand" aria-label="HealthWare, ir al inicio">
            <span class="material-symbols-outlined app-brand-icon">health_and_safety</span>
            <span class="app-brand-name">HealthWare</span>
        </a>
    </div>

    {{-- Derecha: notificaciones · tema · usuario --}}
    <div class="app-topbar-end">

        @if(\Illuminate\Support\Facades\Route::has('notificaciones'))
            <div class="dropdown app-notif">
                <button type="button"
                        class="btn btn-icon app-notif-button"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside"
                        aria-expanded="false"
                        aria-label="Notificaciones{{ $notifPendientes ? ': ' . $notifPendientes . ' sin leer' : '' }}"
                        title="Notificaciones">
                    <span class="material-symbols-outlined">notifications</span>
                    @if($notifPendientes > 0)
                        <span class="app-notif-badge">{{ $notifPendientes > 99 ? '99+' : $notifPendientes }}</span>
                    @endif
                </button>

                <div class="dropdown-menu dropdown-menu-end app-notif-menu">

                    <div class="app-notif-header">
                        <h2>Notificaciones</h2>
                        <span>{{ $notifPendientes }} sin leer</span>
                    </div>

                    <div class="app-notif-list">
                        @forelse($notifRecientes as $notificacion)
                            @php $sinLeer = empty($notificacion->leido_en); @endphp
                            <a href="{{ $notificacion->enlace ?: route('notificaciones') }}"
                               class="app-notif-item {{ $sinLeer ? 'is-unread' : '' }}">
                                <span class="app-notif-icon">
                                    <span class="material-symbols-outlined">{{ $iconoNotificacion($notificacion) }}</span>
                                </span>
                                <span class="app-notif-body">
                                    <span class="app-notif-title">{{ $notificacion->titulo ?? 'Notificación' }}</span>
                                    <span class="app-notif-text">{{ $notificacion->mensaje ?? '' }}</span>
                                    <span class="app-notif-time">
                                        {{ $notificacion->created_at ? \Illuminate\Support\Carbon::parse($notificacion->created_at)->locale('es')->diffForHumans() : '' }}
                                    </span>
                                </span>
                                @if($sinLeer)
                                    <span class="app-notif-dot" aria-hidden="true"></span>
                                @endif
                            </a>
                        @empty
                            <div class="app-notif-empty">
                                <span class="material-symbols-outlined">notifications_off</span>
                                No tienes notificaciones recientes.
                            </div>
                        @endforelse
                    </div>

                    <a href="{{ route('notificaciones') }}" class="app-notif-footer">Ver todas las notificaciones</a>
                </div>
            </div>
        @endif

        {{-- Cambio de tema --}}
        <button
            type="button"
            class="btn btn-icon app-theme-button"
            id="themeToggleBtn"
            aria-label="Cambiar tema"
            aria-pressed="false"
            title="Cambiar tema">
            <span class="material-symbols-outlined" id="themeIcon">dark_mode</span>
        </button>

        {{-- Menú de usuario --}}
        <div class="dropdown app-user-dropdown">

            <button
                type="button"
                class="app-user-trigger"
                data-bs-toggle="dropdown"
                data-bs-auto-close="true"
                aria-expanded="false"
                aria-label="Abrir menú de usuario">

                <span class="app-user-avatar">@if($fotoUsuario)<img src="{{ $fotoUsuario }}" alt="">@else{{ $iniciales ?: 'U' }}@endif</span>

                <span class="app-user-summary d-none d-md-flex">
                    <span class="app-user-name">{{ $nombreUsuario }}</span>
                    <span class="app-user-role">{{ $usuario->rol->rol ?? 'Usuario' }}</span>
                </span>

                <span class="material-symbols-outlined app-user-chevron d-none d-md-inline">expand_more</span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end app-user-menu">

                <li>
                    <div class="app-user-menu-header">
                        <span class="app-user-avatar app-user-avatar-large">@if($fotoUsuario)<img src="{{ $fotoUsuario }}" alt="">@else{{ $iniciales ?: 'U' }}@endif</span>
                        <div class="app-user-menu-identity">
                            <span class="app-user-menu-name">{{ $nombreUsuario }}</span>
                            <span class="app-user-menu-email">{{ $usuario->correo ?? '' }}</span>
                        </div>
                    </div>
                </li>

                <li>
                    <div class="app-user-role-badge">
                        <span class="material-symbols-outlined">verified_user</span>
                        {{ $usuario->rol->rol ?? 'Usuario' }}
                    </div>
                </li>

                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('perfil') }}">
                        <span class="material-symbols-outlined">manage_accounts</span>
                        Mi perfil
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('contrasenha.cambiar') }}">
                        <span class="material-symbols-outlined">lock_reset</span>
                        Cambiar contraseña
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>

                <li>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item app-logout-button">
                            <span class="material-symbols-outlined">logout</span>
                            Cerrar sesión
                        </button>
                    </form>
                </li>

            </ul>
        </div>

    </div>

</header>


{{-- =====================================================
     ESPACIO DE TRABAJO
====================================================== --}}
<div class="app-workspace">

    {{-- MENÚ LATERAL (se oculta y despliega con el botón de la barra superior) --}}
    <aside class="app-sidebar" id="appSidebar" aria-label="Navegación principal">

        <div class="app-sidebar-body">

            <div class="sidebar-heading">
                <span class="sidebar-section-label">Menú principal</span>
            </div>

            <nav class="app-navigation">

                <a href="{{ route('home') }}"
                   class="sidebar-link {{ request()->routeIs('home') ? 'active' : '' }}">
                    <span class="material-symbols-outlined sidebar-icon">home</span>
                    <span>Inicio</span>
                </a>

                @if(\Illuminate\Support\Facades\Route::has('dashboard'))
                    <a href="{{ route('dashboard') }}"
                       class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="material-symbols-outlined sidebar-icon">dashboard</span>
                        <span>Dashboard</span>
                    </a>
                @endif

                @foreach ($menuModulos as $menuModulo)

                    @php
                        // Solo enlaces con ruta existente y permitidos para el rol
                        $menuEnlaces = array_values(array_filter($menuModulo['enlaces'], $puedeVer));

                        $menuActivo = false;

                        foreach ($menuEnlaces as $menuEnlace) {
                            $patrones = [$menuEnlace['ruta'], $menuEnlace['ruta'] . '.*'];

                            foreach ($menuEnlace['rutas_activas'] ?? [] as $rutaActiva) {
                                $patrones[] = $rutaActiva;
                                $patrones[] = $rutaActiva . '.*';
                            }

                            if (request()->routeIs(...$patrones)) {
                                $menuActivo = true;
                                break;
                            }
                        }
                    @endphp

                    @continue($menuEnlaces === [])

                    <div class="sidebar-module">

                        <button
                            type="button"
                            class="sidebar-link sidebar-group-toggle {{ $menuActivo ? 'has-active' : 'collapsed' }}"
                            data-bs-toggle="collapse"
                            data-bs-target="#modulo-{{ $menuModulo['id'] }}"
                            aria-expanded="{{ $menuActivo ? 'true' : 'false' }}"
                            aria-controls="modulo-{{ $menuModulo['id'] }}">

                            <span class="material-symbols-outlined sidebar-icon">{{ $menuModulo['icono'] }}</span>
                            <span class="sidebar-link-label">{{ $menuModulo['titulo'] }}</span>
                            <span class="material-symbols-outlined sidebar-chevron">expand_more</span>
                        </button>

                        <div class="collapse sidebar-submenu {{ $menuActivo ? 'show' : '' }}"
                             id="modulo-{{ $menuModulo['id'] }}">

                            @foreach ($menuEnlaces as $menuEnlace)

                                @php
                                    $patronesEnlace = [$menuEnlace['ruta'], $menuEnlace['ruta'] . '.*'];

                                    foreach ($menuEnlace['rutas_activas'] ?? [] as $rutaActiva) {
                                        $patronesEnlace[] = $rutaActiva;
                                        $patronesEnlace[] = $rutaActiva . '.*';
                                    }

                                    $enlaceActivo = request()->routeIs(...$patronesEnlace);
                                @endphp

                                <a href="{{ route($menuEnlace['ruta']) }}"
                                   class="sidebar-link sidebar-sublink {{ $enlaceActivo ? 'active' : '' }}">
                                    <span class="material-symbols-outlined sidebar-icon">{{ $menuEnlace['icono'] }}</span>
                                    <span>{{ $menuEnlace['etiqueta'] }}</span>
                                </a>

                            @endforeach

                        </div>

                    </div>

                @endforeach

            </nav>

            <footer class="app-sidebar-footer">
                <span class="app-sidebar-footer-brand">HealthWare</span>
                <span class="app-sidebar-footer-version">Sistema de Gestión Clínica · v1.0.0</span>
                <span class="app-sidebar-copyright">&copy; {{ date('Y') }} HealthWare</span>
            </footer>

        </div>

    </aside>

    <div class="app-sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    {{-- PANTALLA INTERNA --}}
    <main class="app-screen">
        <div class="app-screen-content">
            @yield('contenido')
        </div>
    </main>

</div>


{{-- COMPONENTES REUTILIZABLES --}}
@include('components.toast')


{{-- SCRIPTS --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/toast.js') }}"></script>

@stack('scripts')

{{-- Comportamientos compartidos: se cargan después de los scripts de cada módulo --}}
<script src="{{ asset('js/hw/hw-shell.js') }}"></script>
<script src="{{ asset('js/hw/hw-ui.js') }}"></script>

</body>
</html>
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p layouts/partials
  descender layouts/partials
  escribir_archivo "menu_config.php" <<'FIN_ARCHIVO'
<?php

/*
|--------------------------------------------------------------------------
| HealthWare · Configuración única del menú de navegación
|--------------------------------------------------------------------------
| La usan el menú lateral, los accesos rápidos de la barra superior y la
| pantalla de inicio. Cada enlace declara los roles que pueden verlo
| (en minúsculas y con tilde: administrador, médico, recepcionista, contador).
|
| Para agregar un módulo: añade un grupo en $menuModulos y, si quieres un
| acceso directo en la barra superior, agrégalo a $accesosRapidos.
*/

$rolesTodos = [
    'administrador',
    'médico',
    'recepcionista',
];

$rolesTodosConContador = [
    'administrador',
    'médico',
    'recepcionista',
    'contador',
];

$rolesAdminMedico = [
    'administrador',
    'médico',
];

$rolesAdminContador = [
    'administrador',
    'contador',
];

$soloAdmin = [
    'administrador',
];

/*
|--------------------------------------------------------------------------
| Configuración de módulos
|--------------------------------------------------------------------------
*/
$menuModulos = [
    [
        'id' => 'pacientes',
        'titulo' => 'Pacientes',
        'icono' => 'personal_injury',
        'enlaces' => [
            [
                'ruta' => 'pacientes',
                'etiqueta' => 'Gestión de pacientes',
                'icono' => 'groups',
                'roles' => $rolesTodos,
            ],
            [
                'ruta' => 'pacientes.papelera',
                'etiqueta' => 'Papelera de pacientes',
                'icono' => 'delete_sweep',
                'roles' => $soloAdmin,
            ],
        ],
    ],
    [
        'id' => 'atencion',
        'titulo' => 'Atención médica',
        'icono' => 'stethoscope',
        'enlaces' => [
            [
                'ruta' => 'citas',
                'etiqueta' => 'Citas médicas',
                'icono' => 'calendar_month',
                'roles' => $rolesTodos,
            ],
            [
                'ruta' => 'registros_medicos',
                'etiqueta' => 'Expedientes clínicos',
                'icono' => 'clinical_notes',
                'roles' => $rolesAdminMedico,
                'rutas_activas' => [
                    'registro_medico_diagnosticos',
                    'pacientes_condiciones',
                    'pacientes_alergias',
                    'pacientes.expediente',
                ],
            ],
            [
                'ruta' => 'recetas',
                'etiqueta' => 'Recetas médicas',
                'icono' => 'prescriptions',
                'roles' => $rolesAdminMedico,
                'rutas_activas' => [
                    'detalle_recetas',
                ],
            ],
            [
                'ruta' => 'referencias',
                'etiqueta' => 'Referencias médicas',
                'icono' => 'swap_horiz',
                'roles' => $rolesTodos,
            ],
            [
                'ruta' => 'relaciones',
                'etiqueta' => 'Relaciones asistenciales',
                'icono' => 'diversity_3',
                'roles' => $rolesAdminMedico,
            ],
            [
                'ruta' => 'accesos_excepcionales',
                'etiqueta' => 'Accesos excepcionales',
                'icono' => 'emergency',
                'roles' => $rolesAdminMedico,
            ],
            [
                'ruta' => 'registros_medicos.papelera',
                'etiqueta' => 'Papelera de expedientes',
                'icono' => 'delete_sweep',
                'roles' => $soloAdmin,
            ],
        ],
    ],
    [
        'id' => 'personal',
        'titulo' => 'Personal médico',
        'icono' => 'badge',
        'enlaces' => [
            [
                'ruta' => 'doctores',
                'etiqueta' => 'Doctores',
                'icono' => 'medical_services',
                'roles' => $rolesTodos,
            ],
            [
                'ruta' => 'horarios',
                'etiqueta' => 'Horarios de doctores',
                'icono' => 'schedule',
                'roles' => $rolesTodos,
            ],
            [
                'ruta' => 'especialidades',
                'etiqueta' => 'Especialidades',
                'icono' => 'category',
                'roles' => $rolesTodos,
            ],
        ],
    ],
    [
        'id' => 'facturacion',
        'titulo' => 'Facturación',
        'icono' => 'payments',
        'enlaces' => [
            [
                'ruta' => 'ordenes',
                'etiqueta' => 'Órdenes de servicio',
                'icono' => 'assignment',
                'roles' => $rolesTodosConContador,
            ],
            [
                'ruta' => 'facturas',
                'etiqueta' => 'Facturas',
                'icono' => 'receipt_long',
                'roles' => $rolesAdminContador,
            ],
            [
                'ruta' => 'pagos',
                'etiqueta' => 'Pagos',
                'icono' => 'credit_card',
                'roles' => $rolesAdminContador,
            ],
            [
                'ruta' => 'servicios',
                'etiqueta' => 'Servicios y precios',
                'icono' => 'sell',
                'roles' => $rolesTodosConContador,
            ],
        ],
    ],
    [
        'id' => 'workspace',
        'titulo' => 'Mi espacio',
        'icono' => 'task_alt',
        'enlaces' => [
            [
                'ruta' => 'tareas',
                'etiqueta' => 'Mis tareas',
                'icono' => 'checklist',
                'roles' => $rolesTodosConContador,
            ],
        ],
    ],
    [
        'id' => 'comunicacion',
        'titulo' => 'Comunicación',
        'icono' => 'forum',
        'enlaces' => [
            [
                'ruta' => 'anuncios_globales',
                'etiqueta' => 'Anuncios globales',
                'icono' => 'campaign',
                'roles' => $rolesTodosConContador,
            ],
            [
                'ruta' => 'notificaciones',
                'etiqueta' => 'Notificaciones',
                'icono' => 'notifications',
                'roles' => $rolesTodosConContador,
            ],
        ],
    ],
    [
        'id' => 'catalogos',
        'titulo' => 'Catálogos clínicos',
        'icono' => 'folder_open',
        'enlaces' => [
            [
                'ruta' => 'tipos-sanguineos',
                'etiqueta' => 'Tipos sanguíneos',
                'icono' => 'bloodtype',
                'roles' => $rolesTodos,
            ],
            [
                'ruta' => 'diagnosticos',
                'etiqueta' => 'Diagnósticos',
                'icono' => 'diagnosis',
                'roles' => $rolesAdminMedico,
            ],
            [
                'ruta' => 'condiciones-medicas',
                'etiqueta' => 'Condiciones médicas',
                'icono' => 'medical_information',
                'roles' => $rolesAdminMedico,
            ],
            [
                'ruta' => 'alergias',
                'etiqueta' => 'Alergias',
                'icono' => 'warning',
                'roles' => $rolesAdminMedico,
            ],
            [
                'ruta' => 'medicamentos',
                'etiqueta' => 'Medicamentos',
                'icono' => 'medication',
                'roles' => $rolesAdminMedico,
            ],
        ],
    ],
    [
        'id' => 'administracion',
        'titulo' => 'Administración',
        'icono' => 'admin_panel_settings',
        'enlaces' => [
            [
                'ruta' => 'usuarios',
                'etiqueta' => 'Gestión de usuarios',
                'icono' => 'group',
                'roles' => $soloAdmin,
            ],
            [
                'ruta' => 'roles',
                'etiqueta' => 'Roles',
                'icono' => 'verified_user',
                'roles' => $soloAdmin,
            ],
            [
                'ruta' => 'estados',
                'etiqueta' => 'Estados',
                'icono' => 'toggle_on',
                'roles' => $soloAdmin,
            ],
            [
                'ruta' => 'bitacora',
                'etiqueta' => 'Bitácora del sistema',
                'icono' => 'history',
                'roles' => $soloAdmin,
            ],
            [
                'ruta' => 'auditoria_expedientes',
                'etiqueta' => 'Auditoría de expedientes',
                'icono' => 'policy',
                'roles' => $soloAdmin,
            ],
        ],
    ],
];

/*
|--------------------------------------------------------------------------
| Accesos rápidos de la barra superior (menú extra a la izquierda)
|--------------------------------------------------------------------------
*/
$accesosRapidos = [
    ['ruta' => 'pacientes', 'etiqueta' => 'Pacientes', 'icono' => 'groups', 'roles' => $rolesTodos],
    ['ruta' => 'citas', 'etiqueta' => 'Citas médicas', 'icono' => 'calendar_month', 'roles' => $rolesTodos],
    ['ruta' => 'doctores', 'etiqueta' => 'Doctores', 'icono' => 'medical_services', 'roles' => $rolesTodos],
    ['ruta' => 'registros_medicos', 'etiqueta' => 'Expedientes clínicos', 'icono' => 'clinical_notes', 'roles' => $rolesAdminMedico],
    ['ruta' => 'ordenes', 'etiqueta' => 'Órdenes de servicio', 'icono' => 'assignment', 'roles' => $rolesTodosConContador],
    ['ruta' => 'facturas', 'etiqueta' => 'Facturas', 'icono' => 'receipt_long', 'roles' => $rolesAdminContador],
];

return [
    'modulos' => $menuModulos,
    'accesos_rapidos' => $accesosRapidos,
];
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p pacientes
  descender pacientes
  escribir_archivo "expediente.blade.php" <<'FIN_ARCHIVO'
@extends('layouts.app_layout')

@section('titulo', 'Expediente clínico')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $nombrePaciente = trim(($paciente->nombres ?? '') . ' ' . ($paciente->apellidos ?? ''));
        $edad = $paciente->fecha_nacimiento ? \Illuminate\Support\Carbon::parse($paciente->fecha_nacimiento)->age : null;
        $fmtFecha = fn ($valor, $formato = 'd/m/Y') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $alergiasGraves = $paciente->alergias->where('gravedad', 'grave');
        $claseGravedad = ['grave' => 'error', 'moderada' => 'warning', 'leve' => 'info', 'desconocida' => 'neutral'];
        $dui = $paciente->numero_identificacion;
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">folder_shared</span>
                </div>
                <div>
                    <h1 class="hw-title">Expediente de {{ $nombrePaciente }}</h1>
                    <p class="hw-subtitle">DUI {{ $dui }}{{ $edad !== null ? ' · ' . $edad . ' años' : '' }} · {{ $paciente->genero }}</p>
                </div>
            </div>
            <div class="hw-document-actions">
                <a href="{{ route('pacientes') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Volver a pacientes
                </a>
                @if($puedeRegistrar)
                    <a href="{{ route('registros_medicos', ['dui' => $dui]) }}" class="btn btn-primary">
                        <span class="material-symbols-outlined">clinical_notes</span>
                        Registrar atención
                    </a>
                    <a href="{{ route('recetas', ['dui' => $dui]) }}" class="btn btn-tonal">
                        <span class="material-symbols-outlined">prescriptions</span>
                        Nueva receta
                    </a>
                    <a href="{{ route('ordenes', ['dui' => $dui]) }}" class="btn btn-tonal">
                        <span class="material-symbols-outlined">assignment</span>
                        Nueva orden
                    </a>
                    <a href="{{ route('referencias', ['dui' => $dui]) }}" class="btn btn-tonal">
                        <span class="material-symbols-outlined">swap_horiz</span>
                        Referir paciente
                    </a>
                @endif
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                    <span class="material-symbols-outlined">print</span>
                    Imprimir
                </button>
            </div>
        </header>

        @if($alergiasGraves->isNotEmpty())
            <div class="exp-alerta" role="alert">
                <span class="material-symbols-outlined">warning</span>
                <div>
                    <strong>Alergias graves:</strong>
                    {{ $alergiasGraves->map(fn ($a) => $a->alergia->alergia ?? '—')->implode(', ') }}
                </div>
            </div>
        @endif

        @if(!empty($accesoExcepcional))
            <div class="exp-alerta" role="status">
                <span class="material-symbols-outlined">emergency</span>
                <div>
                    <strong>Acceso excepcional (solo lectura).</strong>
                    Vigente hasta {{ $accesoExcepcional->fecha_expiracion->format('d/m/Y H:i') }}. Cada consulta queda auditada.
                </div>
            </div>
        @endif

        {{-- RESUMEN --}}
        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Resumen del paciente</h2>
                    <p class="hw-surface-description">Datos básicos y contacto de emergencia.</p>
                </div>
            </div>
            <dl class="exp-resumen">
                <div class="exp-dato"><dt>Nacimiento</dt><dd>{{ $fmtFecha($paciente->fecha_nacimiento) }}</dd></div>
                <div class="exp-dato"><dt>Tipo sanguíneo</dt><dd>{{ $paciente->tipoSanguineo?->tipo_sanguineo ?? 'Sin registrar' }}</dd></div>
                <div class="exp-dato"><dt>Teléfono</dt><dd>{{ $paciente->telefono ?: '—' }}</dd></div>
                <div class="exp-dato"><dt>Contacto de emergencia</dt><dd>{{ $paciente->nombre_contacto_emergencia ?: '—' }}{{ $paciente->parentesco_contacto_emergencia ? ' (' . $paciente->parentesco_contacto_emergencia . ')' : '' }}</dd></div>
                <div class="exp-dato"><dt>Teléfono del contacto</dt><dd>{{ $paciente->telefono_contacto_emergencia ?: '—' }}</dd></div>
            </dl>
        </section>

        {{-- ALERGIAS --}}
        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Alergias</h2>
                    <p class="hw-surface-description">{{ $paciente->alergias->count() }} registradas.</p>
                </div>
                @if($puedeRegistrar)
                    <button type="button" class="btn btn-tonal" data-bs-toggle="modal" data-bs-target="#modalNuevaAlergia">
                        <span class="material-symbols-outlined">add</span>
                        Agregar alergia
                    </button>
                @endif
            </div>
            <div class="exp-seccion-cuerpo">
                @forelse($paciente->alergias as $registro)
                    <div class="exp-registro" style="margin: 0 0 0.6rem;">
                        <div class="exp-registro-cabecera">
                            <h3>{{ $registro->alergia->alergia ?? '—' }}</h3>
                            <span class="hw-status hw-status-{{ $claseGravedad[$registro->gravedad] ?? 'neutral' }}">
                                <span class="hw-status-dot"></span>{{ ucfirst($registro->gravedad) }}
                            </span>
                        </div>
                        <p class="mb-1 hw-muted">Registrada por {{ $registro->creadoPor ? trim($registro->creadoPor->nombres . ' ' . $registro->creadoPor->apellidos) : 'autor no registrado' }}</p>
                        @if($registro->reaccion)<p class="mb-1"><strong>Reacción:</strong> {{ $registro->reaccion }}</p>@endif
                        @if($registro->notas)<p class="mb-1 hw-muted">{{ $registro->notas }}</p>@endif
                        @if($puedeRegistrar && (is_null($registro->id_usuario_creacion) || (int) $registro->id_usuario_creacion === (int) auth()->id()))
                        <form method="POST" action="{{ route('pacientes_alergias.eliminar', ['pacienteAlergia' => $registro->id]) }}"
                              data-confirmar="¿Eliminar esta alergia del expediente?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-text btn-sm">Quitar</button>
                        </form>
                        @endif
                    </div>
                @empty
                    <p class="hw-muted mb-0">No hay alergias registradas.</p>
                @endforelse
            </div>
        </section>

        {{-- CONDICIONES MÉDICAS --}}
        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Condiciones médicas</h2>
                    <p class="hw-surface-description">{{ $paciente->condiciones->count() }} registradas.</p>
                </div>
                @if($puedeRegistrar)
                    <button type="button" class="btn btn-tonal" data-bs-toggle="modal" data-bs-target="#modalNuevaCondicion">
                        <span class="material-symbols-outlined">add</span>
                        Agregar condición
                    </button>
                @endif
            </div>
            <div class="exp-seccion-cuerpo">
                @forelse($paciente->condiciones as $registro)
                    <div class="exp-registro" style="margin: 0 0 0.6rem;">
                        <div class="exp-registro-cabecera">
                            <h3>{{ $registro->condicionMedica->condicion_medica ?? '—' }}</h3>
                            <span class="hw-muted">{{ $registro->fecha_diagnostico ? 'Diagnosticada el ' . $fmtFecha($registro->fecha_diagnostico) : 'Sin fecha de diagnóstico' }}</span>
                        </div>
                        <p class="mb-1 hw-muted">Registrada por {{ $registro->creadoPor ? trim($registro->creadoPor->nombres . ' ' . $registro->creadoPor->apellidos) : 'autor no registrado' }}</p>
                        @if($registro->notas)<p class="mb-1 hw-muted">{{ $registro->notas }}</p>@endif
                        @if($puedeRegistrar && (is_null($registro->id_usuario_creacion) || (int) $registro->id_usuario_creacion === (int) auth()->id()))
                        <form method="POST" action="{{ route('pacientes_condiciones.eliminar', ['pacienteCondicion' => $registro->id]) }}"
                              data-confirmar="¿Eliminar esta condición del expediente?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-text btn-sm">Quitar</button>
                        </form>
                        @endif
                    </div>
                @empty
                    <p class="hw-muted mb-0">No hay condiciones médicas registradas.</p>
                @endforelse
            </div>
        </section>

        {{-- HISTORIAL CLÍNICO --}}
        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Historial clínico</h2>
                    <p class="hw-surface-description">{{ $paciente->registrosMedicos->count() }} atenciones, de la más reciente a la más antigua.</p>
                </div>
            </div>

            @forelse($paciente->registrosMedicos as $registro)
                @php
                    $signos = $registro->signosVitales;
                    $esAutorRegistro = $puedeRegistrar && (int) $registro->id_doctor === (int) ($idDoctorActual ?? 0);
                    $doctorRegistro = trim(($registro->doctor?->usuario?->nombres ?? '') . ' ' . ($registro->doctor?->usuario?->apellidos ?? ''));
                @endphp
                <article class="exp-registro">
                    <div class="exp-registro-cabecera">
                        <h3>{{ $fmtFecha($registro->fecha_registro, 'd/m/Y H:i') }} · {{ $registro->motivo_consulta ?: 'Consulta' }}</h3>
                        <span class="hw-muted">{{ $doctorRegistro ?: 'Doctor' }}{{ $registro->doctor?->especialidad?->especialidad ? ' · ' . $registro->doctor->especialidad->especialidad : '' }}</span>
                    </div>

                    @if($signos)
                        <div class="exp-signos">
                            @if($signos->peso !== null)<span class="exp-signo">Peso <strong>{{ $signos->peso }}</strong></span>@endif
                            @if($signos->altura !== null)<span class="exp-signo">Altura <strong>{{ $signos->altura }}</strong></span>@endif
                            @if($signos->temperatura !== null)<span class="exp-signo">Temp. <strong>{{ $signos->temperatura }}</strong></span>@endif
                            @if($signos->presion_sistolica !== null)<span class="exp-signo">Presión <strong>{{ $signos->presion_sistolica }}/{{ $signos->presion_diastolica }}</strong></span>@endif
                            @if($signos->frecuencia_cardiaca !== null)<span class="exp-signo">FC <strong>{{ $signos->frecuencia_cardiaca }}</strong></span>@endif
                            @if($signos->frecuencia_respiratoria !== null)<span class="exp-signo">FR <strong>{{ $signos->frecuencia_respiratoria }}</strong></span>@endif
                            @if($signos->saturacion_oxigeno !== null)<span class="exp-signo">SatO₂ <strong>{{ $signos->saturacion_oxigeno }}</strong></span>@endif
                        </div>
                    @endif

                    @if($registro->notas_clinicas)
                        <div class="exp-bloque"><h4>Notas clínicas</h4><p class="mb-0">{{ $registro->notas_clinicas }}</p></div>
                    @endif
                    @if($registro->observaciones)
                        <div class="exp-bloque"><h4>Observaciones</h4><p class="mb-0">{{ $registro->observaciones }}</p></div>
                    @endif

                    <div class="exp-bloque">
                        <h4>Diagnósticos</h4>
                        <div class="exp-chips">
                            @forelse($registro->diagnosticos as $asociado)
                                <span class="hw-chip" title="{{ $asociado->notas }}">{{ $asociado->diagnostico->diagnostico ?? '—' }}</span>
                                @if($esAutorRegistro)
                                    <form method="POST" action="{{ route('registro_medico_diagnosticos.eliminar', ['registroMedicoDiagnostico' => $asociado->id]) }}"
                                          data-confirmar="¿Quitar este diagnóstico del registro?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-text btn-sm" aria-label="Quitar diagnóstico">Quitar</button>
                                    </form>
                                @endif
                            @empty
                                <span class="hw-muted">Sin diagnósticos asociados.</span>
                            @endforelse
                            @if($esAutorRegistro)
                                <button type="button" class="btn btn-tonal btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#modalNuevoDiagnostico"
                                        data-registro-id="{{ $registro->id }}"
                                        data-registro-texto="{{ $fmtFecha($registro->fecha_registro, 'd/m/Y H:i') }}">
                                    <span class="material-symbols-outlined">add</span>
                                    Diagnóstico
                                </button>
                            @endif
                        </div>
                    </div>

                    @if($registro->recetas->isNotEmpty())
                        <div class="exp-bloque">
                            <h4>Recetas</h4>
                            @foreach($registro->recetas as $receta)
                                <p class="mb-1 hw-muted">Receta del {{ $fmtFecha($receta->fecha_prescripcion) }}{{ $receta->notas ? ' · ' . $receta->notas : '' }}</p>
                                <table class="table hw-subtable">
                                    <thead><tr><th>Medicamento</th><th>Dosis</th><th>Frecuencia</th><th>Duración</th></tr></thead>
                                    <tbody>
                                        @foreach($receta->detalles as $detalle)
                                            <tr>
                                                <td>{{ $detalle->medicamento->nombre ?? '—' }}</td>
                                                <td>{{ $detalle->dosis }}</td>
                                                <td>{{ $detalle->frecuencia }}</td>
                                                <td>{{ $detalle->duracion ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endforeach
                        </div>
                    @endif
                </article>
            @empty
                <div class="hw-empty">
                    <span class="material-symbols-outlined">clinical_notes</span>
                    <h3>Sin atenciones registradas</h3>
                    <p>Cuando se registre una atención aparecerá aquí.</p>
                </div>
            @endforelse
        </section>
    </div>

    @if($puedeRegistrar)
    <div class="modal fade hw-modal" id="modalNuevaAlergia" tabindex="-1" aria-labelledby="tmodalNuevaAlergia" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">warning</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevaAlergia">Registrar alergia</h2>
                            <p class="hw-modal-subtitle">Se agrega al expediente del paciente.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('pacientes_alergias.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalNuevaAlergia">
                        <input type="hidden" name="dui_paciente" value="{{ $paciente->numero_identificacion }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="id_alergia" id="exp_id_alergia" class="form-select" required>
                                        <option value="">Seleccione</option>
                                        @foreach($alergias as $alergia)
                                            <option value="{{ $alergia->id }}" @selected((string) old('id_alergia') === (string) $alergia->id)>{{ $alergia->alergia }}</option>
                                        @endforeach
                                    </select>
                                    <label for="exp_id_alergia">Alergia</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="gravedad" id="exp_gravedad" class="form-select" required>
                                        @foreach(['desconocida' => 'Desconocida', 'leve' => 'Leve', 'moderada' => 'Moderada', 'grave' => 'Grave'] as $valor => $nombre)
                                            <option value="{{ $valor }}" @selected(old('gravedad', 'desconocida') === $valor)>{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    <label for="exp_gravedad">Gravedad</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="reaccion" id="exp_reaccion" class="form-control" placeholder="Reacción" maxlength="250" value="{{ old('reaccion') }}">
                                    <label for="exp_reaccion">Reacción (opcional)</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="notas" id="exp_notas_alergia" class="form-control" placeholder="Notas" maxlength="250" value="{{ old('notas') }}">
                                    <label for="exp_notas_alergia">Notas (opcional)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Guardar alergia
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    <div class="modal fade hw-modal" id="modalNuevaCondicion" tabindex="-1" aria-labelledby="tmodalNuevaCondicion" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">medical_information</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevaCondicion">Registrar condición médica</h2>
                            <p class="hw-modal-subtitle">Se agrega al expediente del paciente.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('pacientes_condiciones.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalNuevaCondicion">
                        <input type="hidden" name="dui_paciente" value="{{ $paciente->numero_identificacion }}">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="form-floating">
                                    <select name="id_condicion_medica" id="exp_id_condicion" class="form-select" required>
                                        <option value="">Seleccione</option>
                                        @foreach($condicionesMedicas as $condicion)
                                            <option value="{{ $condicion->id }}" @selected((string) old('id_condicion_medica') === (string) $condicion->id)>{{ $condicion->condicion_medica }}</option>
                                        @endforeach
                                    </select>
                                    <label for="exp_id_condicion">Condición médica</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="date" name="fecha_diagnostico" id="exp_fecha_diagnostico" class="form-control" placeholder="Fecha" value="{{ old('fecha_diagnostico') }}">
                                    <label for="exp_fecha_diagnostico">Diagnosticada el</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="notas" id="exp_notas_condicion" class="form-control" placeholder="Notas" maxlength="250" value="{{ old('notas') }}">
                                    <label for="exp_notas_condicion">Notas (opcional)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Guardar condición
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    <div class="modal fade hw-modal" id="modalNuevoDiagnostico" tabindex="-1" aria-labelledby="tmodalNuevoDiagnostico" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">diagnosis</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevoDiagnostico">Asociar diagnóstico</h2>
                            <p class="hw-modal-subtitle">Se vincula al registro médico seleccionado.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('registro_medico_diagnosticos.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalNuevoDiagnostico">
                        <input type="hidden" name="id_registro_medico" id="exp_diag_registro" value="{{ old('id_registro_medico') }}">
                        <p class="hw-field-hint mb-3">Registro médico <strong id="exp_diag_registro_texto">—</strong></p>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <select name="id_diagnostico" id="exp_id_diagnostico" class="form-select" required>
                                        <option value="">Seleccione</option>
                                        @foreach($diagnosticos as $diagnostico)
                                            <option value="{{ $diagnostico->id }}" @selected((string) old('id_diagnostico') === (string) $diagnostico->id)>{{ $diagnostico->diagnostico }}</option>
                                        @endforeach
                                    </select>
                                    <label for="exp_id_diagnostico">Diagnóstico</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="notas" id="exp_notas_diagnostico" class="form-control" placeholder="Notas" maxlength="250" value="{{ old('notas') }}">
                                    <label for="exp_notas_diagnostico">Notas (opcional)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Guardar diagnóstico
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    @include('components.reabrir_modal')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            /* Confirmación antes de quitar alergias, condiciones y diagnósticos */
            document.querySelectorAll('form[data-confirmar]').forEach(function (formulario) {
                formulario.addEventListener('submit', function (evento) {
                    if (!window.confirm(formulario.dataset.confirmar)) evento.preventDefault();
                });
            });

            /* El modal de diagnóstico recibe el registro médico elegido */
            var modalDiagnostico = document.getElementById('modalNuevoDiagnostico');
            if (modalDiagnostico) {
                modalDiagnostico.addEventListener('show.bs.modal', function (evento) {
                    var boton = evento.relatedTarget;
                    if (!boton || !boton.dataset.registroId) return;
                    document.getElementById('exp_diag_registro').value = boton.dataset.registroId;
                    document.getElementById('exp_diag_registro_texto').textContent = boton.dataset.registroTexto;
                });
            }
        });
    </script>
@endpush
FIN_ARCHIVO
  escribir_archivo "acceso_excepcional.blade.php" <<'FIN_ARCHIVO'
@extends('layouts.app_layout')

@section('titulo', 'Acceso al expediente')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">lock</span>
                </div>
                <div>
                    <h1 class="hw-title">Expediente protegido</h1>
                    <p class="hw-subtitle">Usted no tiene una relación asistencial activa con este paciente.</p>
                </div>
            </div>
            <a href="{{ route('pacientes') }}" class="btn btn-tonal">
                <span class="material-symbols-outlined">arrow_back</span>
                Volver a pacientes
            </a>
        </header>

        <section class="hw-surface" style="max-width: 46rem;">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">{{ trim($paciente->nombres . ' ' . $paciente->apellidos) }}</h2>
                    <p class="hw-surface-description">DUI {{ $paciente->numero_identificacion }}</p>
                </div>
            </div>

            <div style="padding: 0 1.25rem 1.25rem;">
                <div class="exp-alerta mb-3" role="alert">
                    <span class="material-symbols-outlined">emergency</span>
                    <div>
                        <strong>Acceso excepcional (emergencia).</strong>
                        Solo úselo si existe una emergencia real. Obtendrá {{ $horas }} horas de acceso de <strong>solo lectura</strong>;
                        la solicitud, la justificación y cada consulta quedan auditadas y se alerta a los administradores.
                    </div>
                </div>

                <form method="POST" action="{{ route('pacientes.acceso_excepcional', ['paciente' => $paciente->id]) }}" novalidate>
                    @csrf
                    <div class="form-floating mb-3">
                        <textarea name="motivo" id="acceso_motivo" class="form-control" placeholder="Justificación"
                                  maxlength="500" style="min-height: 8rem;" required>{{ old('motivo') }}</textarea>
                        <label for="acceso_motivo">Justificación (mínimo {{ $minimo }} caracteres)</label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="confirmo" value="1" id="acceso_confirmo" @checked(old('confirmo')) required>
                        <label class="form-check-label" for="acceso_confirmo">
                            Confirmo que se trata de una emergencia real y entiendo que el acceso será revisado.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined">lock_open</span>
                        Solicitar acceso excepcional
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p citas
  descender citas
  escribir_archivo "detalle.blade.php" <<'FIN_ARCHIVO'
@extends('layouts.app_layout')

@section('titulo', 'Detalle de la cita')

@section('contenido')
    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $esMedico = $rolActual === 'medico';
        $puedeExpediente = in_array($rolActual, ['administrador', 'medico']);
        $nombrePaciente = trim(($cita->paciente?->nombres ?? '') . ' ' . ($cita->paciente?->apellidos ?? ''));
        $nombreDoctor = trim(($cita->doctor?->usuario?->nombres ?? '') . ' ' . ($cita->doctor?->usuario?->apellidos ?? ''));
        $fmtFecha = fn ($valor, $formato = 'd/m/Y') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $claseCita = ['programada' => 'info', 'completada' => 'success', 'cancelada' => 'error', 'no_asistio' => 'warning', 'reprogramada' => 'warning'];
        $dui = $cita->paciente?->numero_identificacion;
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">event</span>
                </div>
                <div>
                    <h1 class="hw-title">Cita del {{ $fmtFecha($cita->fecha_cita) }}</h1>
                    <p class="hw-subtitle">{{ substr($cita->hora_inicio, 0, 5) }} a {{ substr($cita->hora_fin, 0, 5) }} con {{ $nombreDoctor ?: "doctor sin asignar" }}</p>
                </div>
            </div>
            <div class="hw-document-actions">
                <a href="{{ route('citas') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Volver a citas
                </a>
                @if($puedeExpediente && $cita->paciente)
                    <a href="{{ route('pacientes.expediente', ['paciente' => $cita->id_paciente]) }}" class="btn btn-tonal">
                        <span class="material-symbols-outlined">folder_shared</span>
                        Expediente
                    </a>
                @endif
                @if($esMedico && $dui)
                    <a href="{{ route('registros_medicos', ['dui' => $dui]) }}" class="btn btn-primary">
                        <span class="material-symbols-outlined">clinical_notes</span>
                        Registrar atención
                    </a>
                @endif
                @if(in_array($rolActual, ['medico', 'recepcionista']) && $dui)
                    <a href="{{ route('ordenes', ['dui' => $dui]) }}" class="btn btn-tonal">
                        <span class="material-symbols-outlined">assignment</span>
                        Nueva orden
                    </a>
                @endif
            </div>
        </header>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Información de la cita</h2>
                    <p class="hw-surface-description">Paciente, doctor y motivo de la consulta.</p>
                </div>
                <span class="hw-status hw-status-{{ $claseCita[$cita->estado] ?? 'neutral' }}">
                    <span class="hw-status-dot"></span>{{ ucfirst(str_replace('_', ' ', $cita->estado)) }}
                </span>
            </div>
            <dl class="hw-detail-grid" style="padding: 0 1.25rem 1.25rem;">
                <div class="hw-detail-item"><dt>Paciente</dt><dd>{{ $nombrePaciente ?: '—' }}</dd></div>
                <div class="hw-detail-item"><dt>DUI</dt><dd>{{ $dui ?: '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Doctor</dt><dd>{{ $nombreDoctor ?: '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Especialidad</dt><dd>{{ $cita->doctor?->especialidad?->especialidad ?? '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Fecha</dt><dd>{{ $fmtFecha($cita->fecha_cita) }}</dd></div>
                <div class="hw-detail-item"><dt>Horario</dt><dd>{{ substr($cita->hora_inicio, 0, 5) }} - {{ substr($cita->hora_fin, 0, 5) }}</dd></div>
                <div class="hw-detail-item is-wide"><dt>Motivo de la consulta</dt><dd>{{ $cita->motivo ?: '—' }}</dd></div>
                <div class="hw-detail-item is-wide"><dt>Notas</dt><dd>{{ $cita->notas ?: '—' }}</dd></div>
            </dl>
        </section>

        @if($cita->referenciaMedica)
            @php
                $referenciaCita = $cita->referenciaMedica;
                $esMedicoDestino = auth()->id() === ($cita->doctor?->usuario_id);
            @endphp
            <section class="hw-surface">
                <div class="hw-surface-header">
                    <div>
                        <h2 class="hw-surface-title">Origen: referencia médica {{ $referenciaCita->numero_referencia }}</h2>
                        <p class="hw-surface-description">Esta cita se programó a partir de una referencia.</p>
                    </div>
                    <a href="{{ route('referencias') }}" class="btn btn-tonal">Ver referencias</a>
                </div>
                <dl class="hw-detail-grid" style="padding: 0 1.25rem 1.25rem;">
                    <div class="hw-detail-item"><dt>Referido por</dt><dd>{{ trim(($referenciaCita->doctorOrigen?->usuario?->nombres ?? '') . ' ' . ($referenciaCita->doctorOrigen?->usuario?->apellidos ?? '')) ?: '—' }}</dd></div>
                    <div class="hw-detail-item"><dt>Prioridad</dt><dd>{{ ucfirst($referenciaCita->prioridad) }}</dd></div>
                    <div class="hw-detail-item is-wide"><dt>Motivo</dt><dd>{{ $referenciaCita->motivo }}</dd></div>
                    @if($esMedicoDestino)
                        <div class="hw-detail-item is-wide"><dt>Resumen clínico</dt><dd>{{ $referenciaCita->resumen_clinico ?: '—' }}</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Estudios solicitados</dt><dd>{{ $referenciaCita->estudios_solicitados ?: '—' }}</dd></div>
                    @endif
                </dl>
            </section>
        @endif
    </div>
@endsection
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p referencias
  descender referencias
  escribir_archivo "index.blade.php" <<'FIN_ARCHIVO'
@extends('layouts.app_layout')

@section('titulo', 'Referencias médicas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $fmt = fn ($valor, $formato = 'd/m/Y H:i') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $esMedico = $rolActual === 'medico';
        $puedeProgramar = in_array($rolActual, ['recepcionista', 'administrador']);
        $puedeGestionar = in_array($rolActual, ['recepcionista', 'administrador']);
        $lista = $referencias ?? collect();
        $claseEstado = ['pendiente' => 'warning', 'programada' => 'info', 'atendida' => 'success', 'rechazada' => 'error', 'cancelada' => 'neutral', 'vencida' => 'error'];
        $clasePrioridad = ['normal' => 'neutral', 'prioritaria' => 'warning', 'urgente' => 'error'];
        $nombreDoctor = fn ($doctor) => $doctor ? trim(($doctor->usuario?->nombres ?? '') . ' ' . ($doctor->usuario?->apellidos ?? '')) : '';
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">swap_horiz</span>
                </div>
                <div>
                    <h1 class="hw-title">Referencias médicas</h1>
                    <p class="hw-subtitle">Un médico solicita la atención de otro especialista; recepción programa la cita.</p>
                </div>
            </div>
            @if($esMedico)
                <button type="button" class="btn btn-primary hw-create-button" data-bs-toggle="modal" data-bs-target="#modalNuevaReferencia">
                    <span class="material-symbols-outlined">add</span>
                    Nueva referencia
                </button>
            @endif
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">swap_horiz</span>
                <div><strong>{{ $lista->count() }}</strong><span>Referencias</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">pending_actions</span>
                <div><strong>{{ $lista->where('estado', 'pendiente')->count() }}</strong><span>Pendientes</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">event_available</span>
                <div><strong>{{ $lista->where('estado', 'programada')->count() }}</strong><span>Programadas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">task_alt</span>
                <div><strong>{{ $lista->where('estado', 'atendida')->count() }}</strong><span>Atendidas</span></div>
            </div>
        </div>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Referencias</h2>
                    <p class="hw-surface-description">
                        {{ $esMedico ? 'Las que usted emitió y las que se le asignaron.' : 'Las urgentes y prioritarias aparecen primero. El contenido clínico lo ven solo los médicos involucrados.' }}
                    </p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">swap_horiz</span>
                    <h3>Aún no hay referencias</h3>
                    <p>Cuando un médico refiera a un paciente aparecerá aquí.</p>
                </div>
            @else
                <div class="hw-toolbar">
                    <div class="hw-search">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" class="form-control" data-hw-buscar placeholder="Buscar por referencia, paciente o DUI…" aria-label="Buscar en la tabla" autocomplete="off">
                        <button type="button" class="btn btn-icon hw-search-clear" data-hw-buscar-limpiar aria-label="Limpiar búsqueda">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <select class="form-select" data-hw-filtro-columna="3" aria-label="Filtrar por prioridad">
                        <option value="">Prioridad: todas</option>
                    </select>
                    <select class="form-select" data-hw-filtro-columna="5" aria-label="Filtrar por estado">
                        <option value="">Estado: todos</option>
                    </select>
                    <span class="hw-toolbar-count" data-hw-contador></span>
                    <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
                </div>

                <div class="table-responsive hw-table-container">
                    <table id="tabla-referencias" class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Referencia</th>
                                <th scope="col">Paciente</th>
                                <th scope="col">Origen → destino</th>
                                <th scope="col">Prioridad</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lista as $item)
                                @php
                                    $vencida = $item->estaVencida();
                                    $estadoMostrado = $vencida ? 'vencida' : $item->estado;
                                    $esDestino = $esMedico && $item->id_doctor_destino && (int) $item->id_doctor_destino === (int) $idDoctorActual;
                                    $esOrigen = $esMedico && (int) $item->id_doctor_origen === (int) $idDoctorActual;
                                    $nombrePaciente = trim(($item->paciente?->nombres ?? '') . ' ' . ($item->paciente?->apellidos ?? ''));
                                    $destinoTexto = $item->doctorDestino
                                        ? $nombreDoctor($item->doctorDestino) . ' (' . ($item->doctorDestino->especialidad?->especialidad ?? 'sin especialidad') . ')'
                                        : ($item->especialidadDestino?->especialidad ?? '—') . ' (por asignar)';
                                @endphp
                                <tr>
                                    <td class="hw-mono">{{ $item->numero_referencia }}</td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombrePaciente ?: '—' }}</span>
                                            <span class="hw-identity-caption">DUI {{ $item->paciente?->numero_identificacion ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombreDoctor($item->doctorOrigen) ?: '—' }}</span>
                                            <span class="hw-identity-caption">→ {{ $destinoTexto }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $clasePrioridad[$item->prioridad] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst($item->prioridad) }}
                                        </span>
                                    </td>
                                    <td>{{ $fmt($item->fecha_referencia, 'd/m/Y') }}</td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $claseEstado[$estadoMostrado] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst($estadoMostrado) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="hw-actions">
                                            <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalVerReferencia"
                                                    title="Consultar referencia" aria-label="Consultar referencia"
                                                    data-numero="{{ $item->numero_referencia }}"
                                                    data-estado="{{ ucfirst($estadoMostrado) }}"
                                                    data-paciente="{{ $nombrePaciente }} (DUI {{ $item->paciente?->numero_identificacion ?? '—' }})"
                                                    data-prioridad="{{ ucfirst($item->prioridad) }}"
                                                    data-origen="{{ $nombreDoctor($item->doctorOrigen) }}"
                                                    data-destino="{{ $destinoTexto }}"
                                                    data-fecha="{{ $fmt($item->fecha_referencia) }}"
                                                    data-cita="{{ $item->cita ? $fmt($item->cita->fecha_cita, 'd/m/Y') . ' ' . substr($item->cita->hora_inicio, 0, 5) : 'Sin cita' }}"
                                                    data-motivo="{{ $item->motivo }}"
                                                    data-resumen="{{ $item->resumen_clinico }}"
                                                    data-estudios="{{ $item->estudios_solicitados }}"
                                                    data-observaciones="{{ $item->observaciones }}"
                                                    data-clinico="{{ ($esOrigen || $esDestino) ? '1' : '0' }}">
                                                <span class="material-symbols-outlined">visibility</span>
                                            </button>

                                            @if($item->cita)
                                                <a href="{{ route('citas.detalle', ['cita' => $item->cita->id]) }}" class="hw-action-button" title="Ver la cita" aria-label="Ver la cita">
                                                    <span class="material-symbols-outlined">event</span>
                                                </a>
                                            @endif

                                            @if($puedeProgramar && $item->estado === 'pendiente' && !$vencida)
                                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalProgramar"
                                                        title="Programar la cita" aria-label="Programar la cita"
                                                        data-url="{{ route('referencias.programar', ['referencia' => $item->id]) }}"
                                                        data-numero="{{ $item->numero_referencia }}"
                                                        data-paciente="{{ $nombrePaciente }} (DUI {{ $item->paciente?->numero_identificacion ?? '—' }})"
                                                        data-destino="{{ $destinoTexto }}"
                                                        data-doctor-destino="{{ $item->id_doctor_destino }}"
                                                        data-doctor-origen="{{ $item->id_doctor_origen }}"
                                                        data-especialidad-destino="{{ $item->id_especialidad_destino }}">
                                                    <span class="material-symbols-outlined">event_available</span>
                                                </button>
                                            @endif

                                            @if($item->estado === 'pendiente' && ($puedeGestionar || $esDestino))
                                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalMotivo"
                                                        title="Rechazar la referencia" aria-label="Rechazar la referencia"
                                                        data-url="{{ route('referencias.rechazar', ['referencia' => $item->id]) }}"
                                                        data-titulo="Rechazar referencia {{ $item->numero_referencia }}"
                                                        data-descripcion="El destino no puede aceptar la referencia. El médico que la emitió verá el motivo."
                                                        data-boton="Rechazar">
                                                    <span class="material-symbols-outlined">thumb_down</span>
                                                </button>
                                            @endif

                                            @if(in_array($item->estado, ['pendiente', 'programada'], true) && ($puedeGestionar || $esOrigen))
                                                <button type="button" class="hw-action-button hw-action-delete" data-bs-toggle="modal" data-bs-target="#modalMotivo"
                                                        title="Cancelar la referencia" aria-label="Cancelar la referencia"
                                                        data-url="{{ route('referencias.cancelar', ['referencia' => $item->id]) }}"
                                                        data-titulo="Cancelar referencia {{ $item->numero_referencia }}"
                                                        data-descripcion="{{ $item->estado === 'programada' ? 'La cita programada también se cancelará.' : 'La referencia dejará de gestionarse.' }}"
                                                        data-boton="Cancelar referencia">
                                                    <span class="material-symbols-outlined">cancel</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="hw-no-results">
                    <span class="material-symbols-outlined">search_off</span>
                    No se encontraron coincidencias con la búsqueda.
                </div>
            @endif
        </section>
    </div>

    {{-- MODAL: CONSULTA --}}
    <div class="modal fade hw-modal" id="modalVerReferencia" tabindex="-1" aria-labelledby="tmodalVerReferencia" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalVerReferencia">Detalle de la referencia</h2>
                            <p class="hw-modal-subtitle">Motivo, destino y estado de la referencia.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                    <div class="modal-body hw-modal-body">
                        <dl class="hw-detail-grid">
                            <div class="hw-detail-item"><dt>Referencia</dt><dd data-ver="numero">—</dd></div>
                            <div class="hw-detail-item"><dt>Estado</dt><dd data-ver="estado">—</dd></div>
                            <div class="hw-detail-item"><dt>Paciente</dt><dd data-ver="paciente">—</dd></div>
                            <div class="hw-detail-item"><dt>Prioridad</dt><dd data-ver="prioridad">—</dd></div>
                            <div class="hw-detail-item"><dt>Referido por</dt><dd data-ver="origen">—</dd></div>
                            <div class="hw-detail-item"><dt>Destino</dt><dd data-ver="destino">—</dd></div>
                            <div class="hw-detail-item"><dt>Fecha</dt><dd data-ver="fecha">—</dd></div>
                            <div class="hw-detail-item"><dt>Cita</dt><dd data-ver="cita">—</dd></div>
                            <div class="hw-detail-item is-wide"><dt>Motivo</dt><dd data-ver="motivo">—</dd></div>
                            <div class="hw-detail-item is-wide" data-ver-clinico><dt>Resumen clínico</dt><dd data-ver="resumen" style="white-space: pre-line;">—</dd></div>
                            <div class="hw-detail-item is-wide" data-ver-clinico><dt>Estudios solicitados</dt><dd data-ver="estudios" style="white-space: pre-line;">—</dd></div>
                            <div class="hw-detail-item is-wide"><dt>Observaciones</dt><dd data-ver="observaciones" style="white-space: pre-line;">—</dd></div>
                        </dl>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cerrar</button>
                    </div>
            </div>
        </div>
    </div>

    {{-- MODAL: NUEVA REFERENCIA --}}
    @if($esMedico)
    <div class="modal fade hw-modal" id="modalNuevaReferencia" tabindex="-1" aria-labelledby="tmodalNuevaReferencia" aria-hidden="true" data-abrir-con-dui>
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">swap_horiz</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevaReferencia">Nueva referencia médica</h2>
                            <p class="hw-modal-subtitle">Solo puede referir a pacientes que usted atiende.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('referencias.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalNuevaReferencia">
                        <div class="row g-3">
                            <div class="col-md-6">
                                @include('components.campo_dui', ['id' => 'referencia_dui_paciente'])
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="id_registro_medico" id="referencia_registro" class="form-select"
                                            data-dui-registros data-valor-inicial="{{ old('id_registro_medico') }}">
                                        <option value="">Sin registro médico asociado</option>
                                    </select>
                                    <label for="referencia_registro">Registro médico de origen (opcional)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="id_doctor_destino" id="referencia_doctor" class="form-select">
                                        <option value="">Cualquier médico de la especialidad</option>
                                        @foreach($doctores as $doctor)
                                            @continue((int) $doctor->id === (int) $idDoctorActual)
                                            <option value="{{ $doctor->id }}" @selected((string) old('id_doctor_destino') === (string) $doctor->id)>
                                                {{ trim(($doctor->usuario?->nombres ?? '') . ' ' . ($doctor->usuario?->apellidos ?? '')) }} · {{ $doctor->especialidad?->especialidad ?? 'Sin especialidad' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="referencia_doctor">Médico solicitado</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="id_especialidad_destino" id="referencia_especialidad" class="form-select">
                                        <option value="">Sin especialidad específica</option>
                                        @foreach($especialidades as $especialidad)
                                            <option value="{{ $especialidad->id }}" @selected((string) old('id_especialidad_destino') === (string) $especialidad->id)>{{ $especialidad->especialidad }}</option>
                                        @endforeach
                                    </select>
                                    <label for="referencia_especialidad">Especialidad solicitada</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="hw-field-hint mb-0">Indique un médico, una especialidad o ambos. Si solo indica la especialidad, recepción asigna al médico disponible.</p>
                            </div>
                            <div class="col-md-8">
                                <div class="form-floating">
                                    <input type="text" name="motivo" id="referencia_motivo" class="form-control" placeholder="Motivo" maxlength="255" value="{{ old('motivo') }}" required>
                                    <label for="referencia_motivo">Motivo de la referencia</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select name="prioridad" id="referencia_prioridad" class="form-select" required>
                                        @foreach(['normal' => 'Normal', 'prioritaria' => 'Prioritaria', 'urgente' => 'Urgente'] as $valor => $nombre)
                                            <option value="{{ $valor }}" @selected(old('prioridad', 'normal') === $valor)>{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    <label for="referencia_prioridad">Prioridad</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="resumen_clinico" id="referencia_resumen" class="form-control" placeholder="Resumen clínico" style="min-height: 7rem;">{{ old('resumen_clinico') }}</textarea>
                                    <label for="referencia_resumen">Resumen clínico para el especialista</label>
                                </div>
                                <div class="hw-field-hint">Solo lo verán usted y el médico de destino.</div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="estudios_solicitados" id="referencia_estudios" class="form-control" placeholder="Estudios" style="min-height: 5rem;">{{ old('estudios_solicitados') }}</textarea>
                                    <label for="referencia_estudios">Estudios o procedimientos solicitados</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="observaciones" id="referencia_observaciones" class="form-control" placeholder="Observaciones" style="min-height: 4rem;">{{ old('observaciones') }}</textarea>
                                    <label for="referencia_observaciones">Observaciones (opcional)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">send</span>
                            Emitir referencia
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL: PROGRAMAR --}}
    @if($puedeProgramar)
    <div class="modal fade hw-modal" id="modalProgramar" tabindex="-1" aria-labelledby="tmodalProgramar" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">event_available</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalProgramar">Programar la cita</h2>
                            <p class="hw-modal-subtitle">Recepción confirma el horario con el médico.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="#" data-programar-formulario novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalProgramar">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <div class="hw-document-party mb-3">
                            <h3>Referencia</h3>
                            <p><strong data-programar-numero>—</strong> · <span data-programar-paciente>—</span></p>
                            <p data-programar-destino>—</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <select name="id_doctor" id="programar_doctor" class="form-select" required>
                                        <option value="">Seleccione el médico</option>
                                        @foreach($doctores as $doctor)
                                            <option value="{{ $doctor->id }}"
                                                    data-especialidad="{{ $doctor->especialidad_id }}"
                                                    @selected((string) old('id_doctor') === (string) $doctor->id)>
                                                {{ trim(($doctor->usuario?->nombres ?? '') . ' ' . ($doctor->usuario?->apellidos ?? '')) }} · {{ $doctor->especialidad?->especialidad ?? 'Sin especialidad' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="programar_doctor">Médico que atenderá</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="date" name="fecha_cita" id="programar_fecha" class="form-control" placeholder="Fecha" min="{{ now()->toDateString() }}" value="{{ old('_modal') === 'modalProgramar' ? old('fecha_cita') : '' }}" required>
                                    <label for="programar_fecha">Fecha</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="time" name="hora_inicio" id="programar_inicio" class="form-control" placeholder="Inicio" value="{{ old('_modal') === 'modalProgramar' ? old('hora_inicio') : '' }}" required>
                                    <label for="programar_inicio">Hora de inicio</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="time" name="hora_fin" id="programar_fin" class="form-control" placeholder="Fin" value="{{ old('_modal') === 'modalProgramar' ? old('hora_fin') : '' }}" required>
                                    <label for="programar_fin">Hora de fin</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="notas" id="programar_notas" class="form-control" placeholder="Notas" maxlength="500" value="{{ old('_modal') === 'modalProgramar' ? old('notas') : '' }}">
                                    <label for="programar_notas">Notas de la cita (opcional)</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="hw-field-hint mb-0">El sistema valida el horario registrado del médico y que no tenga otra cita en ese momento. La cita queda vinculada a la referencia y el médico obtiene acceso asistencial al expediente.</p>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">event_available</span>
                            Programar cita
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL: RECHAZAR / CANCELAR --}}
    <div class="modal fade hw-modal" id="modalMotivo" tabindex="-1" aria-labelledby="tmodalMotivo" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">edit_note</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalMotivo">Confirmar acción</h2>
                            <p class="hw-modal-subtitle">Indique el motivo: quedará registrado.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="#" data-motivo-formulario novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalMotivo">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <p class="mb-3" data-motivo-descripcion></p>
                        <div class="form-floating">
                            <textarea name="motivo" id="accion_motivo" class="form-control" placeholder="Motivo" maxlength="500" required>{{ old('_modal') === 'modalMotivo' ? old('motivo') : '' }}</textarea>
                            <label for="accion_motivo">Motivo</label>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" data-motivo-boton>
                            <span class="material-symbols-outlined">check</span>
                            <span data-motivo-boton-texto>Confirmar</span>
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/hw/hw-dui.js') }}"></script>
    <script src="{{ asset('js/hw/hw-accion-motivo.js') }}"></script>
    <script src="{{ asset('js/referencia/referencias.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p relaciones
  descender relaciones
  escribir_archivo "index.blade.php" <<'FIN_ARCHIVO'
@extends('layouts.app_layout')

@section('titulo', 'Relaciones asistenciales')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $fmt = fn ($valor, $formato = 'd/m/Y H:i') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $esAdmin = $rolActual === 'administrador';
        $lista = $relaciones ?? collect();
        $claseEstado = ['activa' => 'success', 'suspendida' => 'warning', 'cerrada' => 'neutral', 'revocada' => 'error'];
        $nombreTipo = ['principal' => 'Principal', 'especializada' => 'Especializada', 'referencia' => 'Por referencia', 'seguimiento' => 'Seguimiento'];
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">diversity_3</span>
                </div>
                <div>
                    <h1 class="hw-title">Relaciones asistenciales</h1>
                    <p class="hw-subtitle">Quién puede consultar el expediente de cada paciente. Se crean al programar una cita o una referencia.</p>
                </div>
            </div>
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">diversity_3</span>
                <div><strong>{{ $lista->count() }}</strong><span>Relaciones</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">verified_user</span>
                <div><strong>{{ $lista->where('estado', 'activa')->count() }}</strong><span>Activas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">pause_circle</span>
                <div><strong>{{ $lista->where('estado', 'suspendida')->count() }}</strong><span>Suspendidas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">history</span>
                <div><strong>{{ $lista->whereIn('estado', ['cerrada', 'revocada'])->count() }}</strong><span>En historial</span></div>
            </div>
        </div>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">{{ $esAdmin ? 'Todas las relaciones' : 'Mis relaciones' }}</h2>
                    <p class="hw-surface-description">
                        Una relación activa permite consultar el expediente del paciente. Al cerrarla (con motivo) se conserva como historial y se pierde el acceso ordinario.
                    </p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">diversity_3</span>
                    <h3>Aún no hay relaciones</h3>
                    <p>Se crean cuando recepción programa una cita con un médico.</p>
                </div>
            @else
                <div class="hw-toolbar">
                    <div class="hw-search">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" class="form-control" data-hw-buscar placeholder="Buscar por paciente, DUI o médico…" aria-label="Buscar en la tabla" autocomplete="off">
                        <button type="button" class="btn btn-icon hw-search-clear" data-hw-buscar-limpiar aria-label="Limpiar búsqueda">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <select class="form-select" data-hw-filtro-columna="2" aria-label="Filtrar por tipo">
                        <option value="">Tipo: todos</option>
                    </select>
                    <select class="form-select" data-hw-filtro-columna="3" aria-label="Filtrar por estado">
                        <option value="">Estado: todos</option>
                    </select>
                    <span class="hw-toolbar-count" data-hw-contador></span>
                    <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
                </div>

                <div class="table-responsive hw-table-container">
                    <table id="tabla-relaciones" class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Paciente</th>
                                <th scope="col">Médico</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Inicio</th>
                                <th scope="col">Fin y motivo</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lista as $item)
                                @php
                                    $nombrePaciente = trim(($item->paciente?->nombres ?? '') . ' ' . ($item->paciente?->apellidos ?? ''));
                                    $nombreMedico = trim(($item->doctor?->usuario?->nombres ?? '') . ' ' . ($item->doctor?->usuario?->apellidos ?? ''));
                                    $activa = $item->estado === 'activa';
                                    $suspendida = $item->estado === 'suspendida';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombrePaciente ?: '—' }}</span>
                                            <span class="hw-identity-caption">DUI {{ $item->paciente?->numero_identificacion ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombreMedico ?: '—' }}</span>
                                            <span class="hw-identity-caption">{{ $item->doctor?->especialidad?->especialidad ?? '' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $nombreTipo[$item->tipo_relacion] ?? $item->tipo_relacion }}</td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $claseEstado[$item->estado] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst($item->estado) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $fmt($item->fecha_inicio, 'd/m/Y') }}
                                        <span class="hw-muted d-block">{{ \Illuminate\Support\Str::limit($item->motivo, 48) }}</span>
                                    </td>
                                    <td>
                                        @if($item->fecha_fin)
                                            {{ $fmt($item->fecha_fin, 'd/m/Y') }}
                                            <span class="hw-muted d-block">{{ \Illuminate\Support\Str::limit((string) $item->motivo_cierre, 48) }}</span>
                                        @else
                                            <span class="hw-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="hw-actions">
                                            @if($activa || $suspendida)
                                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalMotivo"
                                                        title="Cerrar la relación" aria-label="Cerrar la relación"
                                                        data-url="{{ route('relaciones.cerrar', ['relacion' => $item->id]) }}"
                                                        data-titulo="Cerrar la relación asistencial"
                                                        data-descripcion="El médico dejará de tener acceso ordinario al expediente. La relación se conserva como historial."
                                                        data-boton="Cerrar relación">
                                                    <span class="material-symbols-outlined">lock</span>
                                                </button>
                                            @endif
                                            @if($esAdmin && $activa)
                                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalMotivo"
                                                        title="Suspender" aria-label="Suspender"
                                                        data-url="{{ route('relaciones.suspender', ['relacion' => $item->id]) }}"
                                                        data-titulo="Suspender la relación asistencial"
                                                        data-descripcion="Se suspende el acceso mientras se aclara una situación. Puede reactivarse."
                                                        data-boton="Suspender">
                                                    <span class="material-symbols-outlined">pause_circle</span>
                                                </button>
                                            @endif
                                            @if($esAdmin && $suspendida)
                                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalMotivo"
                                                        title="Reactivar" aria-label="Reactivar"
                                                        data-url="{{ route('relaciones.reactivar', ['relacion' => $item->id]) }}"
                                                        data-titulo="Reactivar la relación asistencial"
                                                        data-descripcion="El médico recupera el acceso ordinario al expediente."
                                                        data-boton="Reactivar">
                                                    <span class="material-symbols-outlined">play_circle</span>
                                                </button>
                                            @endif
                                            @if($esAdmin && ($activa || $suspendida))
                                                <button type="button" class="hw-action-button hw-action-delete" data-bs-toggle="modal" data-bs-target="#modalMotivo"
                                                        title="Revocar" aria-label="Revocar"
                                                        data-url="{{ route('relaciones.revocar', ['relacion' => $item->id]) }}"
                                                        data-titulo="Revocar la relación asistencial"
                                                        data-descripcion="Se retira el acceso por una causa administrativa. Queda en el historial y en la auditoría."
                                                        data-boton="Revocar">
                                                    <span class="material-symbols-outlined">block</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="hw-no-results">
                    <span class="material-symbols-outlined">search_off</span>
                    No se encontraron coincidencias con la búsqueda.
                </div>
            @endif
        </section>
    </div>

    <div class="modal fade hw-modal" id="modalMotivo" tabindex="-1" aria-labelledby="tmodalMotivo" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">edit_note</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalMotivo">Confirmar acción</h2>
                            <p class="hw-modal-subtitle">Indique el motivo: quedará registrado.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="#" data-motivo-formulario novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalMotivo">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <p class="mb-3" data-motivo-descripcion></p>
                        <div class="form-floating">
                            <textarea name="motivo" id="accion_motivo" class="form-control" placeholder="Motivo" maxlength="500" required>{{ old('_modal') === 'modalMotivo' ? old('motivo') : '' }}</textarea>
                            <label for="accion_motivo">Motivo</label>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" data-motivo-boton>
                            <span class="material-symbols-outlined">check</span>
                            <span data-motivo-boton-texto>Confirmar</span>
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/hw/hw-accion-motivo.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p auditoria
  descender auditoria
  escribir_archivo "index.blade.php" <<'FIN_ARCHIVO'
@extends('layouts.app_layout')

@section('titulo', 'Auditoría de expedientes')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $fmt = fn ($valor, $formato = 'd/m/Y H:i') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $lista = $eventos ?? collect();
        $claseAccion = [
            'consulta' => 'info', 'creacion' => 'success', 'modificacion' => 'warning', 'eliminacion' => 'error',
            'restauracion' => 'success', 'descarga' => 'info', 'acceso_excepcional' => 'error', 'gestion_acceso' => 'neutral',
        ];
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">policy</span>
                </div>
                <div>
                    <h1 class="hw-title">Auditoría de expedientes</h1>
                    <p class="hw-subtitle">Quién consultó o modificó un expediente clínico. Es de solo lectura: ningún evento se puede editar ni borrar.</p>
                </div>
            </div>
        </header>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Filtros</h2>
                    <p class="hw-surface-description">Se muestran los 300 eventos más recientes que coincidan.</p>
                </div>
            </div>
            <form method="GET" action="{{ route('auditoria_expedientes') }}" class="hw-server-filters" style="padding: 0 1.25rem 1.25rem;">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" name="dui" id="aud_dui" class="form-control" placeholder="DUI" maxlength="10" value="{{ $filtros['dui'] ?? '' }}">
                            <label for="aud_dui">DUI del paciente</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select name="tipo_accion" id="aud_accion" class="form-select">
                                <option value="">Todas</option>
                                @foreach($acciones as $accion)
                                    <option value="{{ $accion }}" @selected(($filtros['tipo_accion'] ?? '') === $accion)>{{ ucfirst(str_replace('_', ' ', $accion)) }}</option>
                                @endforeach
                            </select>
                            <label for="aud_accion">Acción</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="date" name="desde" id="aud_desde" class="form-control" placeholder="Desde" value="{{ $filtros['desde'] ?? '' }}">
                            <label for="aud_desde">Desde</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="date" name="hasta" id="aud_hasta" class="form-control" placeholder="Hasta" value="{{ $filtros['hasta'] ?? '' }}">
                            <label for="aud_hasta">Hasta</label>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">search</span>
                            Buscar
                        </button>
                        <a href="{{ route('auditoria_expedientes') }}" class="btn btn-tonal">Limpiar</a>
                    </div>
                </div>
            </form>
        </section>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Eventos</h2>
                    <p class="hw-surface-description">
                        @if($duiSinResultado) No existe un paciente con ese DUI. @else De los más recientes a los más antiguos. @endif
                    </p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">policy</span>
                    <h3>Sin eventos</h3>
                    <p>No hay eventos de auditoría con esos filtros.</p>
                </div>
            @else
                <div class="table-responsive hw-table-container">
                    <table class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Paciente</th>
                                <th>Acción</th>
                                <th>Entidad</th>
                                <th>Detalle</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lista as $evento)
                                <tr>
                                    <td>{{ $fmt($evento->created_at) }}</td>
                                    <td>{{ $evento->usuario ? trim($evento->usuario->nombres . ' ' . $evento->usuario->apellidos) : 'Sistema' }}</td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ trim(($evento->paciente?->nombres ?? '') . ' ' . ($evento->paciente?->apellidos ?? '')) ?: '—' }}</span>
                                            <span class="hw-identity-caption">DUI {{ $evento->paciente?->numero_identificacion ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $claseAccion[$evento->tipo_accion] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst(str_replace('_', ' ', $evento->tipo_accion)) }}
                                        </span>
                                    </td>
                                    <td>{{ str_replace('_', ' ', $evento->entidad) }}{{ $evento->id_registro ? ' #' . $evento->id_registro : '' }}</td>
                                    <td>
                                        @if($evento->motivo)<span class="d-block">{{ \Illuminate\Support\Str::limit($evento->motivo, 90) }}</span>@endif
                                        @if($evento->datos_relevantes)
                                            <span class="hw-muted d-block">{{ collect($evento->datos_relevantes)->map(fn ($v, $k) => $k . ': ' . (is_scalar($v) ? $v : json_encode($v)))->implode(' · ') }}</span>
                                        @endif
                                    </td>
                                    <td class="hw-mono">{{ $evento->ip ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p accesos
  descender accesos
  escribir_archivo "index.blade.php" <<'FIN_ARCHIVO'
@extends('layouts.app_layout')

@section('titulo', 'Accesos excepcionales')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $fmt = fn ($valor, $formato = 'd/m/Y H:i') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $lista = $accesos ?? collect();
        $claseEstado = ['activo' => 'warning', 'expirado' => 'neutral', 'revocado' => 'error'];
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">emergency</span>
                </div>
                <div>
                    <h1 class="hw-title">Accesos excepcionales</h1>
                    <p class="hw-subtitle">Acceso de emergencia a un expediente sin relación asistencial: justificado, de 4 horas y de solo lectura.</p>
                </div>
            </div>
        </header>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">{{ $esAdministrador ? 'Accesos concedidos' : 'Mis accesos excepcionales' }}</h2>
                    <p class="hw-surface-description">
                        {{ $esAdministrador ? 'Revise la justificación de cada acceso. Cada solicitud genera una alerta y queda en la auditoría.' : 'Para solicitar uno, abra el expediente de un paciente que no atiende y explique la emergencia.' }}
                    </p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">emergency</span>
                    <h3>Sin accesos excepcionales</h3>
                    <p>Aquí aparecerán los accesos de emergencia que se soliciten.</p>
                </div>
            @else
                <div class="table-responsive hw-table-container">
                    <table class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Paciente</th>
                                <th>Justificación</th>
                                <th>Estado</th>
                                <th>Revisión</th>
                                @if($esAdministrador)<th class="text-center">Acciones</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lista as $item)
                                @php
                                    $nombreUsuario = trim(($item->usuario?->nombres ?? '') . ' ' . ($item->usuario?->apellidos ?? ''));
                                    $nombrePaciente = trim(($item->paciente?->nombres ?? '') . ' ' . ($item->paciente?->apellidos ?? ''));
                                    $estado = $item->estadoVisible();
                                @endphp
                                <tr>
                                    <td>
                                        {{ $fmt($item->fecha_inicio) }}
                                        <span class="hw-muted d-block">hasta {{ $fmt($item->fecha_expiracion) }}</span>
                                    </td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombreUsuario ?: '—' }}</span>
                                            <span class="hw-identity-caption">{{ $item->usuario?->rol?->rol ?? '' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombrePaciente ?: '—' }}</span>
                                            <span class="hw-identity-caption">DUI {{ $item->paciente?->numero_identificacion ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td title="{{ $item->motivo }}">{{ \Illuminate\Support\Str::limit($item->motivo, 80) }}</td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $claseEstado[$estado] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst($estado) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($item->revisado_en)
                                            {{ $fmt($item->revisado_en, 'd/m/Y') }}
                                            <span class="hw-muted d-block">{{ trim(($item->revisor?->nombres ?? '') . ' ' . ($item->revisor?->apellidos ?? '')) }}</span>
                                        @else
                                            <span class="hw-muted">Pendiente</span>
                                        @endif
                                    </td>
                                    @if($esAdministrador)
                                        <td>
                                            <div class="hw-actions">
                                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalRevisarAcceso"
                                                        title="Registrar la revisión" aria-label="Registrar la revisión"
                                                        data-url="{{ route('accesos_excepcionales.revisar', ['acceso' => $item->id]) }}"
                                                        data-usuario="{{ $nombreUsuario }}"
                                                        data-paciente="{{ $nombrePaciente }} (DUI {{ $item->paciente?->numero_identificacion ?? '—' }})"
                                                        data-motivo="{{ $item->motivo }}"
                                                        data-observacion="{{ $item->observacion_revision }}">
                                                    <span class="material-symbols-outlined">fact_check</span>
                                                </button>
                                                @if($estado === 'activo')
                                                    <form method="POST" action="{{ route('accesos_excepcionales.revocar', ['acceso' => $item->id]) }}" class="d-inline"
                                                          data-confirmar="¿Revocar este acceso excepcional ahora?">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="hw-action-button hw-action-delete" title="Revocar el acceso" aria-label="Revocar el acceso">
                                                            <span class="material-symbols-outlined">block</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    @if($esAdministrador)
    <div class="modal fade hw-modal" id="modalRevisarAcceso" tabindex="-1" aria-labelledby="tmodalRevisarAcceso" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">fact_check</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalRevisarAcceso">Revisar el acceso excepcional</h2>
                            <p class="hw-modal-subtitle">Deje constancia de la revisión posterior.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="#" data-revisar-formulario novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalRevisarAcceso">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <div class="hw-document-party mb-3">
                            <h3>Acceso excepcional</h3>
                            <p><strong data-revisar-usuario>—</strong></p>
                            <p data-revisar-paciente>—</p>
                            <p data-revisar-motivo style="white-space: pre-line;">—</p>
                        </div>
                        <div class="form-floating">
                            <textarea name="observacion" id="revisar_observacion" class="form-control" placeholder="Observación" maxlength="1000" style="min-height: 6rem;">{{ old('_modal') === 'modalRevisarAcceso' ? old('observacion') : '' }}</textarea>
                            <label for="revisar_observacion">Observación de la revisión (opcional)</label>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">fact_check</span>
                            Registrar revisión
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/acceso/accesos.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p tareas
  descender tareas
  escribir_archivo "index.blade.php" <<'FIN_ARCHIVO'
@extends('layouts.app_layout')

@section('titulo', 'Mis tareas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $fmt = fn ($valor) => $valor ? $valor->format('d/m/Y H:i') : null;
        $clasePrioridad = ['baja' => 'neutral', 'normal' => 'info', 'alta' => 'warning', 'urgente' => 'error'];
        $nombreEstado = ['pendiente' => 'Pendiente', 'en_progreso' => 'En progreso', 'completada' => 'Completada', 'cancelada' => 'Cancelada'];
        $viejoNueva = fn ($campo) => old('_modal') === 'modalNuevaTarea' ? old($campo) : '';
        $viejoEditar = fn ($campo) => old('_modal') === 'modalEditarTarea' ? old($campo) : '';
        $secciones = [
            ['titulo' => 'Vencidas', 'icono' => 'alarm', 'lista' => $vencidas, 'clase' => 'is-vencida', 'vacio' => 'No tiene tareas vencidas.'],
            ['titulo' => 'Pendientes y en progreso', 'icono' => 'pending_actions', 'lista' => $abiertas, 'clase' => '', 'vacio' => 'No tiene tareas abiertas. Cree una con "Nueva tarea".'],
            ['titulo' => 'Completadas', 'icono' => 'task_alt', 'lista' => $completadas, 'clase' => 'is-completada', 'vacio' => 'Aún no ha completado tareas.'],
            ['titulo' => 'Canceladas', 'icono' => 'cancel', 'lista' => $canceladas, 'clase' => 'is-cancelada', 'vacio' => 'No tiene tareas canceladas.'],
        ];
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">checklist</span>
                </div>
                <div>
                    <h1 class="hw-title">Mis tareas</h1>
                    <p class="hw-subtitle">Su espacio de trabajo personal: solo usted ve y modifica estas tareas.</p>
                </div>
            </div>
            <button type="button" class="btn btn-primary hw-create-button" data-bs-toggle="modal" data-bs-target="#modalNuevaTarea">
                <span class="material-symbols-outlined">add</span>
                Nueva tarea
            </button>
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">pending_actions</span>
                <div><strong>{{ $abiertas->count() }}</strong><span>Abiertas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">alarm</span>
                <div><strong>{{ $vencidas->count() }}</strong><span>Vencidas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">task_alt</span>
                <div><strong>{{ $completadas->count() }}</strong><span>Completadas</span></div>
            </div>
        </div>

        @foreach($secciones as $seccion)
            <section class="hw-surface">
                <div class="hw-surface-header">
                    <div>
                        <h2 class="hw-surface-title">{{ $seccion['titulo'] }}</h2>
                        <p class="hw-surface-description">{{ $seccion['lista']->count() }} tareas.</p>
                    </div>
                </div>

                <div class="exp-seccion-cuerpo">
                    @forelse($seccion['lista'] as $tarea)
                        <article class="tarea-card {{ $seccion['clase'] }}">
                            <div class="tarea-cabecera">
                                <h3>{{ $tarea->titulo }}</h3>
                                <div class="tarea-etiquetas">
                                    <span class="hw-status hw-status-{{ $clasePrioridad[$tarea->prioridad] ?? 'neutral' }}">
                                        <span class="hw-status-dot"></span>{{ ucfirst($tarea->prioridad) }}
                                    </span>
                                    <span class="hw-chip">{{ $nombreEstado[$tarea->estado] ?? $tarea->estado }}</span>
                                </div>
                            </div>

                            @if($tarea->descripcion)
                                <p class="tarea-descripcion">{{ $tarea->descripcion }}</p>
                            @endif

                            <div class="tarea-fechas">
                                @if($tarea->fecha_vencimiento)<span><span class="material-symbols-outlined">event</span> Vence: {{ $fmt($tarea->fecha_vencimiento) }}</span>@endif
                                @if($tarea->fecha_recordatorio)<span><span class="material-symbols-outlined">notifications</span> Recordatorio: {{ $fmt($tarea->fecha_recordatorio) }}</span>@endif
                                @if($tarea->fecha_completada)<span><span class="material-symbols-outlined">check_circle</span> Completada: {{ $fmt($tarea->fecha_completada) }}</span>@endif
                            </div>

                            <div class="tarea-acciones">
                                @if($tarea->estaAbierta())
                                    @if($tarea->estado === 'pendiente')
                                        <form method="POST" action="{{ route('tareas.estado', ['tarea' => $tarea->id]) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="estado" value="en_progreso">
                                            <button type="submit" class="btn btn-tonal btn-sm">Iniciar</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('tareas.estado', ['tarea' => $tarea->id]) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="estado" value="completada">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <span class="material-symbols-outlined">check</span>
                                            Completar
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('tareas.estado', ['tarea' => $tarea->id]) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="estado" value="pendiente">
                                        <button type="submit" class="btn btn-tonal btn-sm">Reabrir</button>
                                    </form>
                                @endif

                                <button type="button" class="btn btn-tonal btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditarTarea"
                                        data-url="{{ route('tareas.actualizar', ['tarea' => $tarea->id]) }}"
                                        data-tarea="{{ json_encode([
                                            'titulo' => $tarea->titulo,
                                            'descripcion' => $tarea->descripcion,
                                            'prioridad' => $tarea->prioridad,
                                            'estado' => $tarea->estado,
                                            'fecha_inicio' => $tarea->fecha_inicio?->format('Y-m-d\TH:i'),
                                            'fecha_vencimiento' => $tarea->fecha_vencimiento?->format('Y-m-d\TH:i'),
                                            'fecha_recordatorio' => $tarea->fecha_recordatorio?->format('Y-m-d\TH:i'),
                                        ]) }}">
                                    <span class="material-symbols-outlined">edit</span>
                                    Editar
                                </button>

                                <form method="POST" action="{{ route('tareas.eliminar', ['tarea' => $tarea->id]) }}"
                                      data-confirmar="¿Eliminar la tarea &quot;{{ $tarea->titulo }}&quot;?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-text btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="hw-muted mb-0">{{ $seccion['vacio'] }}</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <div class="modal fade hw-modal" id="modalNuevaTarea" tabindex="-1" aria-labelledby="tmodalNuevaTarea" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">add_task</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevaTarea">Nueva tarea</h2>
                            <p class="hw-modal-subtitle">Una tarea personal con prioridad, vencimiento y recordatorio.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('tareas.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalNuevaTarea">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="titulo" id="tarea_nueva_titulo" class="form-control" placeholder="Título" maxlength="150" value="{{ $viejoNueva('titulo') }}" required>
                                    <label for="tarea_nueva_titulo">Título</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="descripcion" id="tarea_nueva_descripcion" class="form-control" placeholder="Descripción" maxlength="2000" style="min-height: 6rem;">{{ $viejoNueva('descripcion') }}</textarea>
                                    <label for="tarea_nueva_descripcion">Descripción (opcional)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="prioridad" id="tarea_nueva_prioridad" class="form-select" required>
                                        @foreach(['baja' => 'Baja', 'normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'] as $valor => $nombre)
                                            <option value="{{ $valor }}" @selected(($viejoNueva('prioridad') ?: 'normal') === $valor)>{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    <label for="tarea_nueva_prioridad">Prioridad</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_inicio" id="tarea_nueva_inicio" class="form-control" placeholder="Inicio" value="{{ $viejoNueva('fecha_inicio') }}">
                                    <label for="tarea_nueva_inicio">Inicio</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_vencimiento" id="tarea_nueva_vencimiento" class="form-control" placeholder="Vencimiento" value="{{ $viejoNueva('fecha_vencimiento') }}">
                                    <label for="tarea_nueva_vencimiento">Vencimiento</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_recordatorio" id="tarea_nueva_recordatorio" class="form-control" placeholder="Recordatorio" value="{{ $viejoNueva('fecha_recordatorio') }}">
                                    <label for="tarea_nueva_recordatorio">Recordatorio</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="hw-field-hint mb-0">Recibirá una notificación en la fecha del recordatorio y otra si la tarea vence sin completarse.</p>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">add_task</span>
                            Crear tarea
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    <div class="modal fade hw-modal" id="modalEditarTarea" tabindex="-1" aria-labelledby="tmodalEditarTarea" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">edit</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalEditarTarea">Editar tarea</h2>
                            <p class="hw-modal-subtitle">Si cambia el vencimiento o el recordatorio, se vuelve a avisar.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="#" data-tarea-formulario novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalEditarTarea">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="titulo" id="tarea_editar_titulo" class="form-control" placeholder="Título" maxlength="150" value="{{ $viejoEditar('titulo') }}" required>
                                    <label for="tarea_editar_titulo">Título</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="descripcion" id="tarea_editar_descripcion" class="form-control" placeholder="Descripción" maxlength="2000" style="min-height: 6rem;">{{ $viejoEditar('descripcion') }}</textarea>
                                    <label for="tarea_editar_descripcion">Descripción (opcional)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="prioridad" id="tarea_editar_prioridad" class="form-select" required>
                                        @foreach(['baja' => 'Baja', 'normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'] as $valor => $nombre)
                                            <option value="{{ $valor }}" @selected(($viejoEditar('prioridad') ?: 'normal') === $valor)>{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    <label for="tarea_editar_prioridad">Prioridad</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="estado" id="tarea_editar_estado" class="form-select">
                                        @foreach(['pendiente' => 'Pendiente', 'en_progreso' => 'En progreso', 'completada' => 'Completada', 'cancelada' => 'Cancelada'] as $valor => $nombre)
                                            <option value="{{ $valor }}" @selected($viejoEditar('estado') === $valor)>{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    <label for="tarea_editar_estado">Estado</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_inicio" id="tarea_editar_inicio" class="form-control" placeholder="Inicio" value="{{ $viejoEditar('fecha_inicio') }}">
                                    <label for="tarea_editar_inicio">Inicio</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_vencimiento" id="tarea_editar_vencimiento" class="form-control" placeholder="Vencimiento" value="{{ $viejoEditar('fecha_vencimiento') }}">
                                    <label for="tarea_editar_vencimiento">Vencimiento</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_recordatorio" id="tarea_editar_recordatorio" class="form-control" placeholder="Recordatorio" value="{{ $viejoEditar('fecha_recordatorio') }}">
                                    <label for="tarea_editar_recordatorio">Recordatorio</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="hw-field-hint mb-0">Recibirá una notificación en la fecha del recordatorio y otra si la tarea vence sin completarse.</p>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Guardar cambios
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/tarea/tareas.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
FIN_ARCHIVO
  ascender
  ascender
}

escribir_estilos() {
  log "Escribiendo estilos (1 archivos)"
  ejecutar mkdir -p public/css
  descender public/css
  escribir_archivo "clinico.css" <<'FIN_ARCHIVO'
/* ============================================================
   HEALTHWARE - ESTILOS CLÍNICOS Y DE FACTURACIÓN
   Archivo: public/css/clinico.css
   Se usa en: pacientes (perfil, expediente, papelera), citas (detalle),
   registros médicos, recetas, órdenes, facturación y pagos.
   ============================================================ */

/* ----- Campo de DUI con confirmación del nombre ----- */
.hw-dui-resultado {
    display: none;
    align-items: center;
    gap: 0.4rem;
    min-height: 1.4rem;
    margin-top: 0.35rem;
    padding-left: 0.25rem;
    font-size: 0.82rem;
}

.hw-dui-resultado[data-estado] { display: flex; }
.hw-dui-resultado .material-symbols-outlined { font-size: 1.1rem; }

.hw-dui-resultado[data-estado="encontrado"] { color: var(--md-sys-color-primary); font-weight: 600; }
.hw-dui-resultado[data-estado="buscando"] { color: var(--md-sys-color-on-surface-variant); }
.hw-dui-resultado[data-estado="no-encontrado"],
.hw-dui-resultado[data-estado="formato"] { color: var(--md-sys-color-error); }

/* ----- Líneas dinámicas (servicios de una orden, medicamentos de una receta) ----- */
.hw-lineas-lista { display: flex; flex-direction: column; gap: 0.75rem; }

.hw-linea {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 0.7fr) minmax(0, 1fr) auto;
    align-items: start;
    gap: 0.6rem;
    padding: 0.75rem;
    background-color: var(--md-sys-color-surface-container);
    border: 1px solid var(--md-sys-color-outline-variant);
    border-radius: var(--md-sys-shape-lg);
}

.hw-linea-receta {
    grid-template-columns: repeat(6, minmax(0, 1fr));
}

.hw-linea-receta > :first-child { grid-column: span 2; }
.hw-linea-receta > .hw-linea-instrucciones { grid-column: span 5; }

.hw-linea-subtotal {
    align-self: center;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
    text-align: right;
}

@media (max-width: 767.98px) {
    .hw-linea,
    .hw-linea-receta { grid-template-columns: 1fr 1fr; }
    .hw-linea > :first-child,
    .hw-linea-receta > :first-child,
    .hw-linea-receta > .hw-linea-instrucciones { grid-column: 1 / -1; }
}

.hw-orden-total {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 0.75rem;
    padding: 0.85rem 1rem;
    color: var(--md-sys-color-on-primary-container);
    background-color: var(--md-sys-color-primary-container);
    border-radius: var(--md-sys-shape-md);
    font-weight: 700;
}

/* ----- Expediente clínico ----- */
.exp-alerta {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.9rem 1.1rem;
    color: var(--md-sys-color-on-error-container);
    background-color: var(--md-sys-color-error-container);
    border-radius: var(--md-sys-shape-lg);
    font-size: 0.9rem;
}

.exp-alerta .material-symbols-outlined { font-variation-settings: 'FILL' 1; }

.exp-resumen {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 0.75rem;
    padding: 0 1.25rem 1.25rem;
}

.exp-dato {
    padding: 0.75rem 0.9rem;
    background-color: var(--md-sys-color-surface-container);
    border-radius: var(--md-sys-shape-md);
}

.exp-dato dt {
    margin: 0;
    color: var(--md-sys-color-on-surface-variant);
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.exp-dato dd { margin: 0.15rem 0 0; font-size: 0.92rem; }

.exp-registro {
    margin: 0 1.25rem 1rem;
    padding: 1rem 1.15rem;
    border: 1px solid var(--md-sys-color-outline-variant);
    border-radius: var(--md-sys-shape-lg);
}

.exp-registro-cabecera {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.6rem;
}

.exp-registro-cabecera h3 { margin: 0; font-size: 1rem; font-weight: 600; }

.exp-signos {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin: 0.6rem 0;
}

.exp-signo {
    padding: 0.25rem 0.6rem;
    color: var(--md-sys-color-on-surface-variant);
    background-color: var(--md-sys-color-surface-container);
    border-radius: var(--md-sys-shape-md);
    font-size: 0.78rem;
}

.exp-signo strong { color: var(--md-sys-color-on-surface); }

.exp-bloque { margin-top: 0.75rem; }
.exp-bloque h4 {
    margin: 0 0 0.35rem;
    color: var(--md-sys-color-on-surface-variant);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

.exp-chips { display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; }
.exp-chips form { display: inline; }

.exp-seccion-cuerpo { padding: 0 1.25rem 1.25rem; }

/* ----- Facturación y pagos ----- */
.fac-saldo {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 0.4rem;
    font-weight: 700;
}

.fac-saldo.is-pendiente { color: var(--md-sys-color-error); }
.fac-saldo.is-pagado { color: var(--md-sys-color-primary); }

.fac-anulada {
    margin-top: 1rem;
    padding: 0.85rem 1rem;
    color: var(--md-sys-color-on-error-container);
    background-color: var(--md-sys-color-error-container);
    border-radius: var(--md-sys-shape-md);
    font-size: 0.9rem;
}

.fac-metodos { display: flex; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 1rem; }

.fac-metodo {
    flex: 1 1 10rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1rem;
    cursor: pointer;
    border: 1px solid var(--md-sys-color-outline-variant);
    border-radius: var(--md-sys-shape-lg);
}

.fac-metodo:has(input:checked) {
    border-color: var(--md-sys-color-primary);
    background-color: var(--md-sys-color-primary-container);
}

.fac-tarjetas-prueba {
    margin-top: 1rem;
    padding: 0.75rem 1rem;
    border: 1px dashed var(--md-sys-color-outline);
    border-radius: var(--md-sys-shape-md);
    font-size: 0.8rem;
}

.fac-tarjetas-prueba summary { cursor: pointer; font-weight: 600; }
.fac-tarjetas-prueba table { margin-top: 0.5rem; }

@media print {
    .hw-dui-resultado,
    .fac-tarjetas-prueba { display: none !important; }
}

/* ----- Mis tareas (workspace personal) ----- */
.tarea-card {
    margin-bottom: 0.75rem;
    padding: 0.9rem 1.1rem;
    border: 1px solid var(--md-sys-color-outline-variant);
    border-left: 4px solid var(--md-sys-color-primary);
    border-radius: var(--md-sys-shape-lg);
}

.tarea-card.is-vencida { border-left-color: var(--md-sys-color-error); }
.tarea-card.is-completada { border-left-color: var(--md-sys-color-outline); opacity: 0.8; }
.tarea-card.is-cancelada { border-left-color: var(--md-sys-color-outline-variant); opacity: 0.65; }
.tarea-card.is-completada .tarea-cabecera h3 { text-decoration: line-through; }

.tarea-cabecera {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.tarea-cabecera h3 { margin: 0; font-size: 1rem; font-weight: 600; }
.tarea-etiquetas { display: flex; align-items: center; gap: 0.4rem; }
.tarea-descripcion { margin: 0.4rem 0 0; color: var(--md-sys-color-on-surface-variant); font-size: 0.88rem; white-space: pre-line; }

.tarea-fechas {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem 1.1rem;
    margin-top: 0.5rem;
    color: var(--md-sys-color-on-surface-variant);
    font-size: 0.8rem;
}

.tarea-fechas span { display: inline-flex; align-items: center; gap: 0.25rem; }
.tarea-fechas .material-symbols-outlined { font-size: 1rem; }

.tarea-acciones { display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem; margin-top: 0.7rem; }
.tarea-acciones form { display: inline; }
FIN_ARCHIVO
  ascender
}

escribir_scripts() {
  log "Escribiendo scripts (4 archivos)"
  ejecutar mkdir -p public/js
  descender public/js
  ejecutar mkdir -p hw
  descender hw
  escribir_archivo "hw-accion-motivo.js" <<'FIN_ARCHIVO'
/* ============================================================
   HEALTHWARE - ACCIONES CON MOTIVO
   Archivo: public/js/hw/hw-accion-motivo.js

   Un botón con data-bs-target="#modalMotivo" abre un formulario que pide el
   motivo y lo envía por PUT a su data-url. Atributos del botón:
     data-url          ruta de la acción
     data-titulo       título del modal
     data-descripcion  texto explicativo
     data-boton        texto del botón de confirmación
   Si el servidor devuelve errores, el modal se reabre (hw reabrir_modal) y la
   ruta se recupera del campo oculto _url.
   ============================================================ */
(() => {
    'use strict';

    const modal = document.getElementById('modalMotivo');
    if (!modal) return;

    const formulario = modal.querySelector('[data-motivo-formulario]');
    const campoUrl = formulario.querySelector('[name="_url"]');

    modal.addEventListener('show.bs.modal', (evento) => {
        const boton = evento.relatedTarget;

        if (boton && boton.dataset.url) {
            formulario.action = boton.dataset.url;
            campoUrl.value = boton.dataset.url;
            formulario.querySelector('[name="motivo"]').value = '';

            modal.querySelector('.hw-modal-title').textContent = boton.dataset.titulo || 'Confirmar acción';
            modal.querySelector('[data-motivo-descripcion]').textContent = boton.dataset.descripcion || '';
            modal.querySelector('[data-motivo-boton-texto]').textContent = boton.dataset.boton || 'Confirmar';
        } else if (campoUrl.value) {
            // Reapertura tras un error de validación.
            formulario.action = campoUrl.value;
        }
    });
})();
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p referencia
  descender referencia
  escribir_archivo "referencias.js" <<'FIN_ARCHIVO'
/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Referencias médicas
   Archivo: public/js/referencia/referencias.js
   Blade: resources/views/referencias/index.blade.php

   - Rellena el modal de consulta con los datos de la fila.
   - En el modal de programación muestra solo los médicos que pueden atender
     la referencia (el solicitado, o los de la especialidad pedida) y deja
     fuera al médico que la emitió.
   ============================================================ */
(() => {
    'use strict';

    /* ----- Consulta ----- */
    const modalVer = document.getElementById('modalVerReferencia');
    if (modalVer) {
        modalVer.addEventListener('show.bs.modal', (evento) => {
            const boton = evento.relatedTarget;
            if (!boton) return;

            const datos = boton.dataset;
            const mapa = {
                numero: datos.numero,
                estado: datos.estado,
                paciente: datos.paciente,
                prioridad: datos.prioridad,
                origen: datos.origen,
                destino: datos.destino,
                fecha: datos.fecha,
                cita: datos.cita,
                motivo: datos.motivo,
                resumen: datos.resumen,
                estudios: datos.estudios,
                observaciones: datos.observaciones,
            };

            Object.entries(mapa).forEach(([clave, valor]) => {
                const celda = modalVer.querySelector(`[data-ver="${clave}"]`);
                if (celda) celda.textContent = valor && valor.trim() !== '' ? valor : '—';
            });

            // El contenido clínico solo se muestra a los médicos involucrados.
            modalVer.querySelectorAll('[data-ver-clinico]').forEach((bloque) => {
                bloque.hidden = datos.clinico !== '1';
            });
        });
    }

    /* ----- Programación ----- */
    const modalProgramar = document.getElementById('modalProgramar');
    if (modalProgramar) {
        const formulario = modalProgramar.querySelector('[data-programar-formulario]');
        const campoUrl = formulario.querySelector('[name="_url"]');
        const select = formulario.querySelector('[name="id_doctor"]');

        modalProgramar.addEventListener('show.bs.modal', (evento) => {
            const boton = evento.relatedTarget;

            if (!boton || !boton.dataset.url) {
                // Reapertura tras un error de validación.
                if (campoUrl.value) formulario.action = campoUrl.value;
                return;
            }

            const datos = boton.dataset;
            formulario.action = datos.url;
            campoUrl.value = datos.url;

            modalProgramar.querySelector('[data-programar-numero]').textContent = datos.numero;
            modalProgramar.querySelector('[data-programar-paciente]').textContent = datos.paciente;
            modalProgramar.querySelector('[data-programar-destino]').textContent = `Destino: ${datos.destino}`;

            Array.from(select.options).forEach((opcion) => {
                if (!opcion.value) return;

                let visible = opcion.value !== datos.doctorOrigen;

                if (visible && datos.doctorDestino) {
                    visible = opcion.value === datos.doctorDestino;
                } else if (visible && datos.especialidadDestino) {
                    visible = opcion.dataset.especialidad === datos.especialidadDestino;
                }

                opcion.hidden = !visible;
                opcion.disabled = !visible;
            });

            select.value = datos.doctorDestino || '';
        });
    }
})();
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p acceso
  descender acceso
  escribir_archivo "accesos.js" <<'FIN_ARCHIVO'
/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Accesos excepcionales
   Archivo: public/js/acceso/accesos.js
   Blade: resources/views/accesos/index.blade.php
   ============================================================ */
(() => {
    'use strict';

    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) evento.preventDefault();
        });
    });

    const modal = document.getElementById('modalRevisarAcceso');
    if (!modal) return;

    const formulario = modal.querySelector('[data-revisar-formulario]');
    const campoUrl = formulario.querySelector('[name="_url"]');

    modal.addEventListener('show.bs.modal', (evento) => {
        const boton = evento.relatedTarget;

        if (!boton || !boton.dataset.url) {
            if (campoUrl.value) formulario.action = campoUrl.value;
            return;
        }

        formulario.action = boton.dataset.url;
        campoUrl.value = boton.dataset.url;
        formulario.querySelector('[name="observacion"]').value = boton.dataset.observacion || '';

        modal.querySelector('[data-revisar-usuario]').textContent = boton.dataset.usuario;
        modal.querySelector('[data-revisar-paciente]').textContent = boton.dataset.paciente;
        modal.querySelector('[data-revisar-motivo]').textContent = boton.dataset.motivo;
    });
})();
FIN_ARCHIVO
  ascender
  ejecutar mkdir -p tarea
  descender tarea
  escribir_archivo "tareas.js" <<'FIN_ARCHIVO'
/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Mis tareas
   Archivo: public/js/tarea/tareas.js
   Blade: resources/views/tareas/index.blade.php
   ============================================================ */
(() => {
    'use strict';

    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) evento.preventDefault();
        });
    });

    /* El modal de edición se rellena con los datos de la tarea elegida. */
    const modal = document.getElementById('modalEditarTarea');
    if (!modal) return;

    const formulario = modal.querySelector('[data-tarea-formulario]');
    const campoUrl = formulario.querySelector('[name="_url"]');

    modal.addEventListener('show.bs.modal', (evento) => {
        const boton = evento.relatedTarget;

        if (!boton || !boton.dataset.url) {
            // Reapertura tras un error de validación: los valores vienen de old().
            if (campoUrl.value) formulario.action = campoUrl.value;
            return;
        }

        let tarea = {};
        try {
            tarea = JSON.parse(boton.dataset.tarea || '{}');
        } catch (error) {
            tarea = {};
        }

        formulario.action = boton.dataset.url;
        campoUrl.value = boton.dataset.url;

        ['titulo', 'descripcion', 'prioridad', 'estado', 'fecha_inicio', 'fecha_vencimiento', 'fecha_recordatorio'].forEach((campo) => {
            const control = formulario.querySelector(`[name="${campo}"]`);
            if (control) control.value = tarea[campo] ?? '';
        });
    });
})();
FIN_ARCHIVO
  ascender
  ascender
}

# --------------------------- Verificación (sin PHP) --------------------------
# Revisa que cada archivo exista y no esté vacío, y que todo CSS y JS invocado
# desde las vistas con asset() exista en public (salvo public/bootstrap).
verificar_archivos() {
  log "Verificando los archivos escritos (sin ejecutar PHP)"
  local archivo recurso problemas=0
  for archivo in "${ARCHIVOS_FASE[@]}"; do
    if [[ ! -s "$archivo" ]]; then
      warn "Falta o está vacío: $archivo"
      problemas=$((problemas + 1))
    fi
  done

  while IFS= read -r recurso; do
    if [[ ! -f "public/$recurso" ]]; then
      warn "Una vista invoca public/$recurso y no existe."
      problemas=$((problemas + 1))
    fi
  done < <(grep -rhoE "asset\('[^']+'\)" resources/views \
             | sed -E "s/asset\('([^']+)'\)/\1/" | grep -v '^bootstrap/' | sort -u)

  if grep -rl 'medicamentos\.css' resources public/css public/js >/dev/null 2>&1; then
    warn "Quedan referencias a 'medicamentos.css'."
    problemas=$((problemas + 1))
  fi

  local ruta
  for ruta in referencias.programar relaciones.cerrar pacientes.acceso_excepcional accesos_excepcionales auditoria_expedientes tareas.insertar; do
    if ! grep -q "name('${ruta}')" routes/web.php; then
      warn "Falta la ruta '${ruta}' en routes/web.php."
      problemas=$((problemas + 1))
    fi
  done

  if (( problemas == 0 )); then
    ok "${#ARCHIVOS_FASE[@]} archivos correctos"
  else
    warn "Se encontraron $problemas problemas; revise los avisos anteriores."
  fi
}

# ------------------------------ Lista de tareas ------------------------------
# Escribe (o reemplaza) la sección de una fase en TASKLIST_USUARIO.txt.
escribir_seccion_tareas() {
  local fase="$1"
  local contenido sin_seccion nuevo
  contenido="$(mktemp)"
  sin_seccion="$(mktemp)"
  nuevo="$(mktemp)"
  cat > "$contenido"

  if [[ -f "$ARCHIVO_TAREAS" ]]; then
    awk -v ini="# [${fase}:inicio]" -v fin="# [${fase}:fin]" '
      $0 == ini { saltar = 1 }
      !saltar   { print }
      $0 == fin { saltar = 0 }
    ' "$ARCHIVO_TAREAS" > "$sin_seccion"
  else
    printf 'HEALTHWARE · TAREAS DEL USUARIO POR FASE\n\n' > "$sin_seccion"
  fi

  {
    cat -- "$sin_seccion"
    printf '# [%s:inicio]\n' "$fase"
    cat -- "$contenido"
    printf '# [%s:fin]\n\n' "$fase"
  } > "$nuevo"

  if [[ -f "$ARCHIVO_TAREAS" ]] && cmp -s -- "$nuevo" "$ARCHIVO_TAREAS"; then
    log "Sin cambios: $ARCHIVO_TAREAS"
  else
    escribir_archivo "$ARCHIVO_TAREAS" < "$nuevo"
  fi
  rm -f -- "$contenido" "$sin_seccion" "$nuevo"
}

escribir_tareas_fase_3c() {
  log "Escribiendo la sección de la fase 3C en $ARCHIVO_TAREAS"
  escribir_seccion_tareas "fase-03c" <<'FIN_TAREAS'
FASE 3C · MEJORAS ASISTENCIALES (intermedia, antes de la fase 4)

Comandos que debe ejecutar usted (en el equipo donde tenga PHP):
  [ ] 1. php artisan migrate
         (8 migraciones nuevas. Generan solas las relaciones asistenciales de las
          citas y atenciones que ya existían y atribuyen los registros clínicos
          antiguos al usuario de su médico. Alternativa con datos de demostración:
          php artisan migrate:fresh --seed)
  [ ] 2. php artisan optimize:clear
  [ ] 3. php artisan route:list --name=referencias   (y relaciones, accesos, tareas)
  [ ] 4. php artisan event:list                       (deben verse los 7 eventos con su listener)
  [ ] 5. php artisan schedule:list                    (tareas:recordatorios y referencias:vencer)
  [ ] 6. En desarrollo, dejar corriendo:  php artisan schedule:work
         (prueba manual de una sola pasada:  php artisan tareas:recordatorios)

Pruebas sugeridas (con los usuarios de la fase 1; use varios médicos si crea más):
  [ ] A. Recepcionista programa una cita para el médico demo: aparece la relación
         asistencial (menú Atención > Relaciones asistenciales) y el médico recibe
         una notificación de cita asignada.
  [ ] B. Médico A refiere un paciente a la especialidad X (o a un médico B): la
         referencia queda "pendiente" y recepción recibe una notificación.
  [ ] C. Recepción programa la referencia: se crea UNA cita vinculada, la referencia
         pasa a "programada", el médico B obtiene relación asistencial y una
         notificación. Intentar programarla otra vez falla.
  [ ] D. El médico B abre el expediente, registra una atención con esa cita: la cita
         pasa a "completada", la referencia a "atendida" y el médico A recibe un
         aviso. El médico A vuelve a abrir el expediente y ve lo registrado por B.
  [ ] E. El médico B cierra su relación (con motivo): pierde el acceso ordinario.
  [ ] F. Un médico sin relación abre un expediente ajeno: se le pide justificación
         (acceso excepcional, 4 h, solo lectura); los administradores reciben una
         alerta; Auditoría de expedientes lo muestra; el administrador lo revisa.
  [ ] G. El administrador intenta abrir un expediente: también debe solicitar acceso
         excepcional. Sin él, los registros y recetas se muestran sin texto clínico.
  [ ] H. Mis tareas: crear una con recordatorio dentro de 2 minutos; con
         schedule:work llega UNA sola notificación; completarla evita avisos
         futuros; reprogramarla vuelve a habilitar el aviso; otro usuario no
         puede abrirla (403).
  [ ] I. Cancelar la cita de una referencia: la referencia vuelve a "pendiente"
         (puede reprogramarse) y el médico recibe un aviso de cancelación.

Decisiones que se asumieron (dígame si quiere cambiar alguna):
  - Relación: una cita ordinaria crea la del médico ("principal" si el paciente no
    tiene otra principal activa; "seguimiento" si ya la tiene). Una cita de
    referencia crea una "referencia". Se activa al programar la cita (en este
    sistema programar = confirmar). Si hay una relación activa o suspendida con el
    mismo médico se reutiliza; si estaba cerrada, se abre un episodio nuevo.
  - El cierre es explícito (médico o administrador, con motivo). Solo se revoca
    sola cuando la cita se cancela antes de que el médico atienda al paciente.
  - Escribir (registros, diagnósticos, recetas, antecedentes, referencias) exige
    relación activa; el acceso excepcional es solo lectura. Los diagnósticos y las
    recetas de un registro solo los modifica el médico autor de ese registro.
  - Administrador: gestiona relaciones, referencias y accesos, pero el expediente
    clínico solo con acceso excepcional justificado. Recepción y contador nunca.
  - Referencias: recepción y administrador no ven el resumen clínico ni los estudios.
    Vencen a los 30 días (normal), 15 (prioritaria) y 7 (urgente) si siguen pendientes.
  - Si la cita de una referencia se cancela (o el paciente no asiste), la referencia
    vuelve a "pendiente" y la cita pierde el vínculo (la restricción única exige un
    solo vínculo por referencia).
  - Una atención registrada con cita completa la cita (y la referencia asociada).
  - La consulta del expediente se audita en auditoria_expedientes y ya no en la
    bitácora general; la bitácora conserva autenticación y administración.
  - Se agregó una quinta tabla: accesos_excepcionales (el acceso de emergencia
    necesita vigencia y revisión posterior).
  - Las policies cubren solo el expediente clínico (PacientePolicy,
    RegistroMedicoPolicy); referencias, tareas y el resto siguen con middleware de
    rol y consultas Eloquent (las tareas son privadas por consulta y por 403).

Pendiente o fuera de esta fase:
  - Edición de un registro médico ya guardado (hoy solo se crean y eliminan;
    la auditoría ya tiene el tipo "modificacion").
  - El tipo de auditoría "descarga" queda reservado para las exportaciones.
  - Pruebas automatizadas: según su regla, no se generan.
FIN_TAREAS
}

mostrar_resumen() {
  printf '\n'
  ok "Fase 3C terminada"
  cat <<RESUMEN

  Se escribieron ${#ARCHIVOS_FASE[@]} archivos. Falta ejecutar: php artisan migrate
  No se ejecutó PHP.

  Respaldo previo en: ${DIR_RESPALDOS}/
  Tareas pendientes para usted: ${ARCHIVO_TAREAS}

RESUMEN
}

analizar_argumentos() {
  local arg
  for arg in "$@"; do
    case "$arg" in
      --simular)      SIMULAR=1 ;;
      --sin-respaldo) SIN_RESPALDO=1 ;;
      -h|--ayuda)     uso; exit 0 ;;
      *)              die "Opción desconocida: $arg (usa --ayuda)" ;;
    esac
  done
}

# ---------------------------------- Principal --------------------------------
main() {
  analizar_argumentos "$@"
  ir_a_base
  log "Raíz del proyecto: $DIR_BASE"

  verificar_entorno
  mostrar_plan

  if (( SIMULAR )); then
    warn "Modo --simular: no se modificó ningún archivo."
    exit 0
  fi

  respaldar
  escribir_migraciones
  escribir_seeders
  escribir_aplicacion
  escribir_rutas
  escribir_vistas
  escribir_estilos
  escribir_scripts
  verificar_archivos
  escribir_tareas_fase_3c
  mostrar_resumen
}

main "$@"
