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
