<?php
// Configuración base para conexión y correos.
// Sustituye los valores por las credenciales reales del entorno.
return [
    'db_host' => 'localhost',
    'db_name' => 'consultoria',    // TODO: ajusta al nombre real de la BD
    'db_user' => 'root',
    'db_pass' => '',

    // Dirección que enviará las confirmaciones.
    'mail_from' => 'contacto@consultoria-ti.mx',
    'mail_from_name' => 'Consultoría Estratégica TI',
    // Opcional: copia interna para seguimiento comercial.
    'mail_bcc' => 'ventas@consultoria-ti.mx',

    // Mapea los servicios con el id_servicio real en la tabla servicios.
    // Deja en null si aún no conoces el id para evitar errores de FK;
    // el registro se guardará con id_servicio NULL.
    'service_ids' => [
        'Desarrollo Web' => null,
        'Implementación RPA' => null,
        'Asistencia App Móviles' => null,
        'Control y gestión BD' => null,
        'Fix developer' => null,
    ],
];
