<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración del Módulo de Farmacia
    |--------------------------------------------------------------------------
    |
    | Configuraciones específicas para el módulo de farmacia, incluyendo
    | información del regente, licencias y alertas.
    |
    */

    'habilitado' => env('MODULO_FARMACIA', true),

    'informacion' => [
        'regente_nombre' => env('FARMACIA_REGENTE_NOMBRE', ''),
        'regente_jvpm' => env('FARMACIA_REGENTE_JVPM', ''),
        'licencia_sanitaria' => env('FARMACIA_LICENCIA_SANITARIA', ''),
    ],

    'alertas' => [
        'vencimiento_dias' => env('FARMACIA_ALERTA_VENCIMIENTO_DIAS', 90),
        'stock_minimo' => env('FARMACIA_ALERTA_STOCK_MINIMO', true),
        'lotes_vencidos' => true,
        'productos_refrigerados' => true,
    ],

    'inventario' => [
        'control_lotes' => env('INVENTARIO_CONTROL_LOTES', true),
        'control_vencimiento' => env('INVENTARIO_CONTROL_VENCIMIENTO', true),
        'requiere_receta' => true,
        'control_psicofarmacos' => true,
    ],

    'categorias_especiales' => [
        'medicamentos_controlados' => true,
        'psicofarmacos' => true,
        'antibioticos' => true,
        'refrigerados' => true,
        'alto_riesgo' => true,
    ],

    'reportes' => [
        'ventas_diarias' => true,
        'medicamentos_controlados' => true,
        'proximos_vencer' => true,
        'stock_minimo' => true,
        'movimientos_inventario' => true,
    ],

];

