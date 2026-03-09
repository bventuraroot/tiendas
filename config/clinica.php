<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración del Módulo de Clínica
    |--------------------------------------------------------------------------
    |
    | Configuraciones específicas para el módulo de clínica médica, incluyendo
    | información de consultas, citas, expedientes y personal médico.
    |
    */

    'habilitado' => env('MODULO_CLINICA', true),

    'informacion' => [
        'nombre_completo' => env('CLINICA_NOMBRE_COMPLETO', ''),
        'director_medico' => env('CLINICA_DIRECTOR_MEDICO', ''),
        'licencia_establecimiento' => env('CLINICA_LICENCIA_ESTABLECIMIENTO', ''),
        'horario_atencion' => env('CLINICA_HORARIO_ATENCION', 'Lunes a Viernes 8:00 AM - 5:00 PM'),
    ],

    'citas' => [
        'duracion_predeterminada' => 30, // minutos
        'anticipacion_minima' => 1, // días
        'anticipacion_maxima' => 30, // días
        'cancelacion_permitida' => true,
        'confirmacion_requerida' => true,
        'recordatorio_automatico' => true,
    ],

    'consultas' => [
        'expediente_electronico' => true,
        'recetas_digitales' => true,
        'historia_clinica' => true,
        'signos_vitales' => true,
        'diagnosticos_cie10' => true,
    ],

    'especialidades' => [
        'medicina_general' => true,
        'pediatria' => false,
        'ginecologia' => false,
        'odontologia' => false,
        'nutricion' => false,
    ],

    'reportes' => [
        'consultas_diarias' => true,
        'citas_pendientes' => true,
        'historial_pacientes' => true,
        'estadisticas_medicas' => true,
        'recetas_emitidas' => true,
    ],

    'seguridad' => [
        'acceso_expedientes' => 'restringido', // público, restringido, privado
        'auditoria_accesos' => true,
        'cifrado_expedientes' => false,
    ],

];

