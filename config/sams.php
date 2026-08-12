<?php

return [

    'allow_registration' => env('SAMS_ALLOW_REGISTRATION', false),

    /** Si true: sin partículas/burbujas (más rápido); el gradiente de fondo sigue visible. */
    'lightweight_ui' => env('SAMS_LIGHTWEIGHT_UI', true),

    /** Segundos entre sincronizaciones de códigos reutilizables al abrir inventario (0 = cada vez). */
    'codigos_sync_ttl' => (int) env('SAMS_CODIGOS_SYNC_TTL', 600),

    'default_logos' => [
        'principal' => 'images/logo principal .png',
        'secundario' => 'images/LOGO-INSTITUTO-PREVENTION-WORLD.png',
    ],

    'modulos' => [
        'users',
        'roles',
        'cargos',
        'grupos',
        'fabricantes',
        'equipos',
        'equipos_baja',
        'hoja_vida',
        'inspeccion',
        'exportar',
        'material_didactico',
        'auditoria',
        'asignar',
        'prestamos_temporales',
        'empresa',
        'sede',
        'bodega',
    ],

    'modulo_niveles' => ['none', 'view', 'edit'],

    'default_modulos' => [
        'users' => 'none',
        'roles' => 'none',
        'cargos' => 'none',
        'grupos' => 'none',
        'fabricantes' => 'none',
        'equipos' => 'none',
        'equipos_baja' => 'none',
        'hoja_vida' => 'none',
        'inspeccion' => 'none',
        'exportar' => 'none',
        'material_didactico' => 'none',
        'auditoria' => 'none',
        'asignar' => 'none',
        'prestamos_temporales' => 'none',
        'empresa' => 'none',
        'sede' => 'none',
        'bodega' => 'none',
    ],
];
