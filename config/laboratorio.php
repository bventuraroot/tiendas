<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración del Módulo de Laboratorio Clínico
    |--------------------------------------------------------------------------
    |
    | Configuraciones específicas para el módulo de laboratorio clínico,
    | incluyendo tipos de exámenes, tiempos de entrega y equipamiento.
    |
    */

    'habilitado' => env('MODULO_LABORATORIO', true),

    'informacion' => [
        'nombre' => env('LABORATORIO_NOMBRE', ''),
        'director_tecnico' => env('LABORATORIO_DIRECTOR_TECNICO', ''),
        'licencia' => env('LABORATORIO_LICENCIA', ''),
        'tiempo_resultados_dias' => env('LABORATORIO_TIEMPO_RESULTADOS_DIAS', 3),
    ],

    'examenes' => [
        'categorias' => [
            'hematologia' => true,
            'quimica_clinica' => true,
            'inmunologia' => true,
            'microbiologia' => true,
            'parasitologia' => true,
            'urinalisis' => true,
            'coprologia' => true,
            'hormonas' => false,
            'marcadores_tumorales' => false,
        ],
        'perfiles_predefinidos' => true,
        'examenes_especiales' => true,
    ],

    'muestras' => [
        'codigo_unico' => true,
        'rastreo_completo' => true,
        'tiempo_procesamiento' => true,
        'control_calidad' => true,
        'almacenamiento_muestras' => false,
    ],

    'resultados' => [
        'digitalizacion' => true,
        'valores_referencia' => true,
        'alertas_criticas' => true,
        'graficas_tendencias' => false,
        'interpretacion_automatica' => false,
    ],

    'reportes' => [
        'examenes_diarios' => true,
        'estadisticas_mensuales' => true,
        'tiempo_procesamiento' => true,
        'control_calidad' => true,
        'facturacion' => true,
    ],

    'equipamiento' => [
        'registro_equipos' => true,
        'mantenimiento_preventivo' => true,
        'calibraciones' => true,
        'control_reactivos' => true,
    ],

    'entrega' => [
        'notificacion_resultados' => true,
        'correo_electronico' => true,
        'sms' => false,
        'portal_paciente' => false,
        'impresion_automatica' => true,
    ],

];

