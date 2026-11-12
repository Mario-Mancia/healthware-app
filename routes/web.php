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
