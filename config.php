<?php
// Configuración base para conexión y correos.
// Sustituye los valores por las credenciales reales del entorno.
return [
    'db_host' => 'localhost',
    'db_name' => 'consulting',    // BD definida en sql/Consulting.sql
    'db_user' => 'root',
    'db_pass' => '',

    // Dirección que enviará las confirmaciones.
    'mail_from' => 'contacto@consultoria-ti.mx',
    'mail_from_name' => 'Consultoría Estratégica TI',
    // Opcional: copia interna para seguimiento comercial.
    'mail_bcc' => 'ventas@consultoria-ti.mx',
    // Credenciales maestras para habilitar el alta de clientes.
    'master_phrase' => 'ojos',
    'master_key' => '2812',
    'master_key_extra' => '9X-Alpha',

    // Mapea los servicios con el id_servicio real en la tabla servicios.
    // Deja en null si aún no conoces el id para evitar errores de FK;
    // el registro se guardará con id_servicio NULL.
    'service_ids' => [
        'Desarrollo Web' => 1,
        'Implementación RPA' => 2,
        'Asistencia App Móviles' => 3,
        'Control y gestión BD' => 4,
        'Fix developer' => 5,
    ],
];
