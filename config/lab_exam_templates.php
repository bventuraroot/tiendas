<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plantillas de Exámenes de Laboratorio
    |--------------------------------------------------------------------------
    |
    | Define los formatos específicos para cada tipo de examen basado en
    | los documentos .doc proporcionados.
    |
    */

    'acido_valproico' => [
        'nombre' => 'Ácido Valpróico',
        'categoria' => 'DROGAS TERAPEUTICAS',
        'unidad_medida' => 'ug/mL',
        'valores_referencia' => [
            'terapeutico' => [
                'label' => 'Terapéutico',
                'rango' => '50 a 100 ug/mL',
                'min' => 50,
                'max' => 100
            ],
            'toxico' => [
                'label' => 'Tóxico',
                'rango' => 'Mayor de 100 ug/mL',
                'min' => 100,
                'max' => null
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'depuracion_creatinina' => [
        'nombre' => 'Depuración de Creatinina 24 Horas',
        'categoria' => 'DEPURACION DE CREATININA',
        'unidad_medida' => null, // Cada parámetro tendrá su propia unidad en el PDF
        'valores_referencia' => [
            'creatinina_suero' => [
                'label' => 'Creatinina en Suero',
                'unidad' => 'mg/dL',
                'rango' => implode("\n", [
                    'Hombres: 0.70 a 1.2 mg/dL',
                    'Mujeres: 0.50 a 0.90 mg/dL',
                    'Neonatos Prematuros: 0.29 a 1.04 mg/dL',
                    'Neonatos a término: 0.24 a 0.85 mg/dL',
                    '2 a 12 meses: 0.17 a 0.42 mg/dL',
                    '1 a 2 años: 0.24 a 0.41 mg/dL',
                    '3 a 4 años: 0.31 a 0.47 mg/dL',
                ]),
            ],
            'creatinina_orina' => [
                'label' => 'Creatinina en Orina',
                'unidad' => 'mg/dL',
                'rango' => implode("\n", [
                    'Mujeres: 28 a 217 mg/dL',
                    'Hombres: 39 a 259 mg/dL',
                ]),
            ],
            'depuracion' => [
                'label' => 'Depuración de Creatinina',
                'unidad' => 'mL/Minuto',
                'rango' => implode("\n", [
                    '1 a 20 años: 40 a 96 mL/Minuto',
                    '21 a 30 años: 50 a 150 mL/Minuto',
                    '31 a 40 años: 25 a 170 mL/Minuto',
                    '41 a 50 años: 45 a 132 mL/Minuto',
                    '51 a 60 años: 35 a 122 mL/Minuto',
                    '61 a 70 años: 30 a 110 mL/Minuto',
                    '71 a 80 años: 35 a 95 mL/Minuto',
                    '81 a 90 años: 18 a 76 mL/Minuto',
                    '91 a 100 años: 15 a 50 mL/Minuto',
                ]),
            ],
            'volumen_orina' => [
                'label' => 'Volumen de Orina',
                'unidad' => 'mL/24 Horas',
                'rango' => '800 a 2,000 mL/24 Horas',
            ],
        ],
        'campos_adicionales' => [
            'resultado_creatinina_suero' => [
                'tipo' => 'number',
                'label' => 'Creatinina en Suero',
                'placeholder' => 'Ingrese la creatinina en suero',
                'required' => true,
                'step' => '0.01',
            ],
            'resultado_creatinina_orina' => [
                'tipo' => 'number',
                'label' => 'Creatinina en Orina',
                'placeholder' => 'Ingrese la creatinina en orina',
                'required' => true,
                'step' => '0.01',
            ],
            'resultado_depuracion' => [
                'tipo' => 'number',
                'label' => 'Depuración de Creatinina',
                'placeholder' => 'Ingrese la depuración de creatinina',
                'required' => true,
                'step' => '0.01',
            ],
            'resultado_volumen_orina' => [
                'tipo' => 'number',
                'label' => 'Volumen de Orina',
                'placeholder' => 'Ingrese el volumen de orina en 24 horas',
                'required' => true,
                'step' => '0.01',
            ],
        ],
    ],

    'coagulacion' => [
        'nombre' => 'Coagulación',
        'categoria' => 'COAGULACION',
        'unidad_medida' => 'minutos',
        'valores_referencia' => [
            'tiempo_sangramiento' => [
                'label' => 'Tiempo de Sangramiento',
                'unidad' => 'minutos',
                'rango' => '1 a 4 Minutos',
            ],
            'tiempo_coagulacion' => [
                'label' => 'Tiempo de Coagulación',
                'unidad' => 'minutos',
                'rango' => '5 a 10 Minutos',
            ],
        ],
        'campos_adicionales' => [
            'tiempo_sangramiento' => [
                'tipo' => 'number',
                'label' => 'Tiempo de Sangramiento',
                'placeholder' => 'Ej: 3',
                'required' => true,
                'step' => '0.1',
            ],
            'tiempo_coagulacion' => [
                'tipo' => 'number',
                'label' => 'Tiempo de Coagulación',
                'placeholder' => 'Ej: 7',
                'required' => true,
                'step' => '0.1',
            ],
        ],
    ],

    'albumina' => [
        'nombre' => 'Albúmina',
        'categoria' => 'PRUEBAS RENALES',
        'unidad_medida' => 'g/dL',
        'valores_referencia' => [
            'adultos' => [
                'label' => 'Adultos',
                'rango' => '3.5 a 5.2 g/dL',
                'min' => 3.5,
                'max' => 5.2
            ],
            'neonatos_0_4_dias' => [
                'label' => 'Neonatos 0 a 4 días',
                'rango' => '2.8 a 4.4 g/dL',
                'min' => 2.8,
                'max' => 4.4
            ],
            'ninos_4_dias_14_anos' => [
                'label' => 'Niños 4 días a 14 años',
                'rango' => '3.8 a 5.4 g/dL',
                'min' => 3.8,
                'max' => 5.4
            ],
            'ninos_14_18_anos' => [
                'label' => 'Niños 14 años a 18 años',
                'rango' => '3.2 a 4.5 g/dL',
                'min' => 3.2,
                'max' => 4.5
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'amilasa_lipasa' => [
        'nombre' => 'Amilasa Lipasa',
        'categoria' => 'ENZIMAS',
        'unidad_medida' => 'U/L',
        'valores_referencia' => [
            'amilasa' => [
                'label' => 'Amilasa',
                'rango' => '28 a 100 U/L',
                'unidad' => 'U/L',
                'min' => 28,
                'max' => 100
            ],
            'lipasa' => [
                'label' => 'Lipasa',
                'rango' => implode("\n", [
                    'Niños menores de 12 años hasta: 34 U/L',
                    'Adultos: 13 a 60 U/L',
                ]),
                'unidad' => 'U/L',
            ]
        ],
        'campos_adicionales' => [
            'resultado_amilasa' => [
                'tipo' => 'number',
                'label' => 'Amilasa',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_lipasa' => [
                'tipo' => 'number',
                'label' => 'Lipasa',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'ana_tamizaje' => [
        'nombre' => 'Ac. Antinucleares Látex (ANA tamizaje)',
        'categoria' => 'SEROLOGÍA',
        'unidad_medida' => null, // Examen cualitativo, no tiene unidad
        'valores_referencia' => [
            'normal' => [
                'label' => 'Normal',
                'rango' => 'Negativo',
                'valor' => 'negativo'
            ],
            'anormal' => [
                'label' => 'Anormal',
                'rango' => 'Positivo',
                'valor' => 'positivo'
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'select',
                'label' => 'Resultado',
                'placeholder' => 'Seleccione el resultado',
                'required' => true,
                'opciones' => [
                    'negativo' => 'Negativo',
                    'positivo' => 'Positivo'
                ]
            ]
        ]
    ],

    'antigenos_febriles' => [
        'nombre' => 'ANTIGENOS FEBRILES',
        'categoria' => 'ANTIGENOS FEBRILES',
        'unidad_medida' => null,
        'valores_referencia' => [
            'salmonella_paratyphi_ah' => [
                'label' => 'SALMONELLA PARATYPHI AH',
                'rango' => '',
                'unidad' => '',
            ],
            'salmonella_paratyphi_bh' => [
                'label' => 'SALMONELLA PARATYPHI BH',
                'rango' => '',
                'unidad' => '',
            ],
            'salmonella_typhi_h' => [
                'label' => 'SALMONELLA TYPHI H',
                'rango' => '',
                'unidad' => '',
            ],
            'salmonella_typhi_o' => [
                'label' => 'SALMONELLA TYPHI O',
                'rango' => '',
                'unidad' => '',
            ],
            'brucella_abortus' => [
                'label' => 'BRUCELLA ABORTUS',
                'rango' => '',
                'unidad' => '',
            ],
            'proteus_ox19' => [
                'label' => 'PROTEUS OX19',
                'rango' => '',
                'unidad' => '',
            ]
        ],
        'campos_adicionales' => [
            'resultado_salmonella_paratyphi_ah' => [
                'tipo' => 'text',
                'label' => 'SALMONELLA PARATYPHI AH',
                'placeholder' => 'Ingrese el resultado (Ej: POSITIVO 1:20, NEGATIVO)',
                'required' => true,
            ],
            'resultado_salmonella_paratyphi_bh' => [
                'tipo' => 'text',
                'label' => 'SALMONELLA PARATYPHI BH',
                'placeholder' => 'Ingrese el resultado (Ej: POSITIVO 1:40, NEGATIVO)',
                'required' => true,
            ],
            'resultado_salmonella_typhi_h' => [
                'tipo' => 'text',
                'label' => 'SALMONELLA TYPHI H',
                'placeholder' => 'Ingrese el resultado (Ej: POSITIVO 1:40, NEGATIVO)',
                'required' => true,
            ],
            'resultado_salmonella_typhi_o' => [
                'tipo' => 'text',
                'label' => 'SALMONELLA TYPHI O',
                'placeholder' => 'Ingrese el resultado (Ej: POSITIVO, NEGATIVO)',
                'required' => true,
            ],
            'resultado_brucella_abortus' => [
                'tipo' => 'text',
                'label' => 'BRUCELLA ABORTUS',
                'placeholder' => 'Ingrese el resultado (Ej: POSITIVO 1:40, NEGATIVO)',
                'required' => true,
            ],
            'resultado_proteus_ox19' => [
                'tipo' => 'text',
                'label' => 'PROTEUS OX19',
                'placeholder' => 'Ingrese el resultado (Ej: POSITIVO, NEGATIVO)',
                'required' => true,
            ]
        ]
    ],

    'antimicrosomales' => [
        'nombre' => 'Ac. Antimicrosomales (ATM)(Anti TPO)',
        'categoria' => 'PRUEBAS ESPECIALIZADAS',
        'unidad_medida' => 'UI/mL',
        'valores_referencia' => [
            'adultos' => [
                'label' => 'Adultos',
                'rango' => 'Hasta 34 UI/mL',
                'min' => null,
                'max' => 34
            ],
            'neonatos' => [
                'label' => 'Neonatos',
                'rango' => 'Hasta 117 UI/mL',
                'min' => null,
                'max' => 117
            ],
            '6_dias_3_meses' => [
                'label' => '6 Días a 3 Meses',
                'rango' => 'Hasta 47 UI/mL',
                'min' => null,
                'max' => 47
            ],
            '4_12_meses' => [
                'label' => '4 a 12 Meses',
                'rango' => 'Hasta 32 UI/mL',
                'min' => null,
                'max' => 32
            ],
            '1_11_anos' => [
                'label' => '1 a 11 Años',
                'rango' => 'Hasta 18 UI/mL',
                'min' => null,
                'max' => 18
            ],
            '12_20_anos' => [
                'label' => '12 a 20 Años',
                'rango' => 'Hasta 26 UI/mL',
                'min' => null,
                'max' => 26
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'antitiroglobulinicos' => [
        'nombre' => 'Ac. AntitiroGlobulinicos (ATT)',
        'categoria' => 'PRUEBAS ESPECIALIZADAS',
        'unidad_medida' => 'UI/mL',
        'valores_referencia' => [
            'adultos' => [
                'label' => 'Adultos',
                'rango' => 'Hasta 115 UI/mL',
                'min' => null,
                'max' => 115
            ],
            'neonatos' => [
                'label' => 'Neonatos',
                'rango' => 'Hasta 134 UI/mL',
                'min' => null,
                'max' => 134
            ],
            '6_dias_3_meses' => [
                'label' => '6 Días a 3 Meses',
                'rango' => 'Hasta 146 UI/mL',
                'min' => null,
                'max' => 146
            ],
            '4_12_meses' => [
                'label' => '4 a 12 Meses',
                'rango' => 'Hasta 130 UI/mL',
                'min' => null,
                'max' => 130
            ],
            '1_11_anos' => [
                'label' => '1 a 11 Años',
                'rango' => 'Hasta 38 UI/mL',
                'min' => null,
                'max' => 38
            ],
            '12_20_anos' => [
                'label' => '12 a 20 Años',
                'rango' => 'Hasta 64 UI/mL',
                'min' => null,
                'max' => 64
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'aso' => [
        'nombre' => 'Anti-estreptolisina O (ASO)',
        'categoria' => 'SEROLOGIA',
        'unidad_medida' => 'UI/L',
        'valores_referencia' => [
            'adultos' => [
                'label' => 'Adultos',
                'rango' => 'Hasta 200 UI/L',
                'min' => null,
                'max' => 200
            ],
            'ninos' => [
                'label' => 'Niños',
                'rango' => 'Hasta 150 UI/L',
                'min' => null,
                'max' => 150
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'factor_reumatoide' => [
        'nombre' => 'Factor Reumatoideo (FR)',
        'categoria' => 'SEROLOGIA',
        'unidad_medida' => 'UI/mL',
        'valores_referencia' => [
            'general' => [
                'label' => 'General',
                'rango' => 'Menor o Igual a 12 UI/mL',
                'min' => null,
                'max' => 12,
            ],
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01',
            ],
        ],
    ],

    'baciloscopia' => [
        'nombre' => 'Baciloscopía (BK esputo)',
        'categoria' => 'MICROSCOPIA',
        'unidad_medida' => null, // Examen cualitativo
        'valores_referencia' => [
            'normal' => [
                'label' => 'Normal',
                'rango' => 'No se Observa BAAR',
                'valor' => 'negativo'
            ],
            'positivo' => [
                'label' => 'Positivo',
                'rango' => 'Se Observa BAAR',
                'valor' => 'positivo'
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'select',
                'label' => 'Resultado',
                'placeholder' => 'Seleccione el resultado',
                'required' => true,
                'opciones' => [
                    'no_se_observa_baar' => 'No se Observa BAAR',
                    'se_observa_baar' => 'Se Observa BAAR'
                ]
            ]
        ]
    ],

    'bilirrubina' => [
        'nombre' => 'Bilirrubina',
        'categoria' => 'BIOQUIMICA CLINICA',
        'unidad_medida' => 'mg/dL',
        'valores_referencia' => [
            'total' => [
                'label' => 'Bilirrubina Total',
                'rango' => 'Hasta 1 mg/dL',
                'unidad' => 'mg/dL',
                'min' => null,
                'max' => 1
            ],
            'directa' => [
                'label' => 'Bilirrubina Directa',
                'rango' => 'Hasta 0.3 mg/dL',
                'unidad' => 'mg/dL',
                'min' => null,
                'max' => 0.3
            ],
            'indirecta' => [
                'label' => 'Bilirrubina Indirecta',
                'rango' => 'Hasta 0.8 mg/dL',
                'unidad' => 'mg/dL',
                'min' => null,
                'max' => 0.8
            ]
        ],
        'campos_adicionales' => [
            'resultado_total' => [
                'tipo' => 'number',
                'label' => 'Bilirrubina Total',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_directa' => [
                'tipo' => 'number',
                'label' => 'Bilirrubina Directa',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_indirecta' => [
                'tipo' => 'number',
                'label' => 'Bilirrubina Indirecta',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'curva_tolerancia_glucosa' => [
        'nombre' => 'Curva de Tolerancia a la Glucosa',
        'categoria' => 'BIOQUIMICA CLINICA',
        'unidad_medida' => 'mg/dL',
        'valores_referencia' => [
            'ayunas' => [
                'label' => 'Glucosa en Ayunas',
                'rango' => '',
                'unidad' => 'mg/dL'
            ],
            '1h' => [
                'label' => 'Glucosa 1 Hora después de la toma',
                'rango' => '',
                'unidad' => 'mg/dL'
            ],
            '2h' => [
                'label' => 'Glucosa 2 Hora después de la toma',
                'rango' => '',
                'unidad' => 'mg/dL'
            ],
            '3h' => [
                'label' => 'Glucosa 3 Hora después de la toma',
                'rango' => '',
                'unidad' => 'mg/dL'
            ],
            'nota_dextrosa' => 'Se Administro 100 Gramos de Dextrosa.'
        ],
        'campos_adicionales' => [
            'resultado_ayunas' => [
                'tipo' => 'number',
                'label' => 'Glucosa en Ayunas',
                'placeholder' => 'Ingrese el valor en ayunas',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_1h' => [
                'tipo' => 'number',
                'label' => 'Glucosa 1 Hora después de la toma',
                'placeholder' => 'Ingrese el valor a la 1 hora',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_2h' => [
                'tipo' => 'number',
                'label' => 'Glucosa 2 Hora después de la toma',
                'placeholder' => 'Ingrese el valor a las 2 horas',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_3h' => [
                'tipo' => 'number',
                'label' => 'Glucosa 3 Hora después de la toma',
                'placeholder' => 'Ingrese el valor a las 3 horas',
                'required' => true,
                'step' => '0.01'
            ],
        ]
    ],

    'ca15_3' => [
        'nombre' => 'CA 15-3 (Carcinoma Mamario)',
        'categoria' => 'INMUNOLOGÍA',
        'unidad_medida' => 'U/mL',
        'valores_referencia' => [
            'normal' => [
                'label' => 'Normal',
                'rango' => 'Hasta 25 U/mL',
                'min' => null,
                'max' => 25
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'ca19_9' => [
        'nombre' => 'CA 19-9 Páncreas Gastrointestinales',
        'categoria' => 'INMUNOLOGÍA',
        'unidad_medida' => 'U/mL',
        'valores_referencia' => [
            'normal' => [
                'label' => 'Normal',
                'rango' => '0.0 a 39.0 U/mL',
                'min' => 0.0,
                'max' => 39.0
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'cea' => [
        'nombre' => 'CEA (Antígeno Carcinoembriogénico)',
        'categoria' => 'INMUNOLOGÍA',
        'unidad_medida' => 'ng/mL',
        'valores_referencia' => [
            'no_fumadores' => [
                'label' => 'No Fumadores',
                'rango' => '0.0 a 5.0 ng/mL',
                'min' => 0.0,
                'max' => 5.0
            ],
            'fumadores' => [
                'label' => 'Fumadores',
                'rango' => '0.0 a 10.0 ng/mL',
                'min' => 0.0,
                'max' => 10.0
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'ca125' => [
        'nombre' => 'CA 125 (Cáncer de Ovario)',
        'categoria' => 'INMUNOLOGÍA',
        'unidad_medida' => 'U/mL',
        'valores_referencia' => [
            'normal' => [
                'label' => 'Normal',
                'rango' => '0.0 a 35.0 U/mL',
                'min' => 0.0,
                'max' => 35.0
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'ferritina' => [
        'nombre' => 'Ferritina',
        'categoria' => 'PROTEINAS NEOPLASICAS',
        'unidad_medida' => 'ng/mL',
        'valores_referencia' => [
            'hastal_1_ano' => [
                'label' => 'Hasta 1 año',
                'rango' => '12 a 327 ng/mL',
            ],
            '1_a_6_anos' => [
                'label' => '1 a 6 años',
                'rango' => '4 a 67 ng/mL',
            ],
            '7_a_12_anos_ninas' => [
                'label' => 'Niñas 7 a 12 años',
                'rango' => '7 a 84 ng/mL',
            ],
            '7_a_12_anos_ninos' => [
                'label' => 'Niños 7 a 12 años',
                'rango' => '14 a 124 ng/mL',
            ],
            '13_a_17_anos_ninas' => [
                'label' => 'Niñas 13 a 17 años',
                'rango' => '13 a 68 ng/mL',
            ],
            '13_a_17_anos_ninos' => [
                'label' => 'Niños 13 a 17 años',
                'rango' => '14 a 152 ng/mL',
            ],
            'mujeres' => [
                'label' => 'Mujeres',
                'rango' => '13 a 150 ng/mL',
            ],
            'hombres' => [
                'label' => 'Hombres',
                'rango' => '30 a 400 ng/mL',
            ],
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01',
            ],
        ],
    ],

    'celulas_le' => [
        'nombre' => 'Células L.E',
        'categoria' => 'INMUNOHEMTOLOGIA',
        'unidad_medida' => null, // Examen cualitativo
        'valores_referencia' => [
            'normal' => [
                'label' => 'Normal',
                'rango' => 'Negativo',
                'valor' => 'negativo'
            ],
            'positivo' => [
                'label' => 'Positivo',
                'rango' => 'Positivo',
                'valor' => 'positivo'
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'select',
                'label' => 'Resultado',
                'placeholder' => 'Seleccione el resultado',
                'required' => true,
                'opciones' => [
                    'negativo' => 'Negativo',
                    'positivo' => 'Positivo'
                ]
            ]
        ]
    ],

    'citomegalovirus' => [
        'nombre' => 'Citomegalovirus (CMV - IgM)',
        'categoria' => 'QUIMICA E INMUNOLOGIA',
        'unidad_medida' => 'COI',
        'valores_referencia' => [
            'no_reactivo' => [
                'label' => 'No Reactivo',
                'rango' => 'Menor a 0.7 COI',
                'min' => null,
                'max' => 0.7
            ],
            'indeterminado' => [
                'label' => 'Indeterminado',
                'rango' => '0.7 a 1.0 COI',
                'min' => 0.7,
                'max' => 1.0
            ],
            'reactivo' => [
                'label' => 'Reactivo',
                'rango' => 'Mayor a 1.0 COI',
                'min' => 1.0,
                'max' => null
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'hdl_ldl' => [
        'nombre' => 'Colesterol HDL Y LDL',
        'categoria' => 'HDL Y LDL',
        'unidad_medida' => 'mg/dL',
        'valores_referencia' => [
            'ldl' => [
                'label' => 'Colesterol de Baja Densidad (LDL)',
                'rango' => implode("\n", [
                    'Óptimo: Menor de 100 mg/dL',
                    'Levemente Elevado: 100 a 129 mg/dL',
                    'Limite entre normal y alto: 130 a 159 mg/dL',
                    'Alto: 160 a 189 mg/dL',
                    'Muy Alto: Mayor a 190 mg/dL',
                ]),
                'unidad' => 'mg/dL',
            ],
            'hdl' => [
                'label' => 'Colesterol de Alta Densidad (HDL)',
                'rango' => implode("\n", [
                    'Mujeres:',
                    'Sin Riesgo Cardiovascular Mayor de 65 mg/dL',
                    'Riesgo Cardiovascular Moderado 45 a 65 mg/dL',
                    'Alto Riesgo Cardiovascular Menor a 45 mg/dL',
                    'Hombre:',
                    'Sin Riesgo Cardiovascular Mayor de 55 mg/dL',
                    'Riesgo Cardiovascular Moderado 35 a 55 mg/dL',
                    'Alto Riesgo Cardiovascular Menor a 35 mg/dL',
                ]),
                'unidad' => 'mg/dL',
            ]
        ],
        'campos_adicionales' => [
            'resultado_ldl' => [
                'tipo' => 'number',
                'label' => 'Colesterol de Baja Densidad (LDL)',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_hdl' => [
                'tipo' => 'number',
                'label' => 'Colesterol de Alta Densidad (HDL)',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'deshidrogenasa_ldh' => [
        'nombre' => 'Deshidrogenasa Láctica (DHL)(LDH)',
        'categoria' => 'ENZIMAS',
        'unidad_medida' => 'U/L',
        'valores_referencia' => [
            'general' => [
                'label' => 'General',
                'rango' => '135.0 a 214.0 U/L',
                'min' => 135.0,
                'max' => 214.0,
            ],
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01',
            ],
        ],
    ],

    'colinesterasa' => [
        'nombre' => 'Colinesterasa',
        'categoria' => 'ENZIMAS',
        'unidad_medida' => 'U/L',
        'valores_referencia' => [
            'general' => [
                'label' => 'General',
                'rango' => '5,320 a 12,920 U/L',
                'min' => 5320,
                'max' => 12920
            ],
            'embarazadas_anticonceptivos' => [
                'label' => 'Embarazadas o tomando Anticonceptivos',
                'rango' => '3,650 a 13,977 U/L',
                'min' => 3650,
                'max' => 13977
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'dimero_d' => [
        'nombre' => 'Dimero D',
        'categoria' => 'COAGULACIÓN',
        'unidad_medida' => 'µg UEF/mL',
        'valores_referencia' => [
            'general' => [
                'label' => 'General',
                'rango' => '0.0 a 0.5 µg UEF/mL',
                'min' => 0.0,
                'max' => 0.5,
            ],
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01',
            ],
        ],
    ],

    'electrolitos_minerales' => [
        'nombre' => 'Electrolitos / Minerales',
        'categoria' => 'ELECTROLITOS / MINERALES',
        'unidad_medida' => 'mEq/L',
        'valores_referencia' => [
            'cloro' => [
                'label' => 'Cloro',
                'rango' => '96 – 115 mEq/L',
                'min' => 96,
                'max' => 115,
            ],
            'sodio' => [
                'label' => 'Sodio',
                'rango' => '135 – 145 mEq/L',
                'min' => 135,
                'max' => 145,
            ],
            'potasio' => [
                'label' => 'Potasio',
                'rango' => '3.5 – 5.2 mEq/L',
                'min' => 3.5,
                'max' => 5.2,
            ],
            'magnesio' => [
                'label' => 'Magnesio',
                'rango' => '0.7 – 1.2 mEq/L',
                'unidad' => 'mEq/L',
                'min' => 0.7,
                'max' => 1.2,
            ],
            'calcio' => [
                'label' => 'Calcio',
                'rango' => '',
                'unidad' => 'mEq/L',
            ],
            'fosforo' => [
                'label' => 'Fósforo',
                'rango' => '',
                'unidad' => '',
            ],
        ],
        'campos_adicionales' => [
            'resultado_cloro' => [
                'tipo' => 'number',
                'label' => 'Cloro',
                'placeholder' => 'Ingrese Cloro',
                'required' => true,
                'step' => '0.01',
            ],
            'resultado_sodio' => [
                'tipo' => 'number',
                'label' => 'Sodio',
                'placeholder' => 'Ingrese Sodio',
                'required' => true,
                'step' => '0.01',
            ],
            'resultado_potasio' => [
                'tipo' => 'number',
                'label' => 'Potasio',
                'placeholder' => 'Ingrese Potasio',
                'required' => true,
                'step' => '0.01',
            ],
            'resultado_magnesio' => [
                'tipo' => 'number',
                'label' => 'Magnesio',
                'placeholder' => 'Ingrese Magnesio',
                'required' => true,
                'step' => '0.01',
            ],
            'resultado_calcio' => [
                'tipo' => 'number',
                'label' => 'Calcio',
                'placeholder' => 'Ingrese Calcio',
                'required' => false,
                'step' => '0.01',
            ],
            'resultado_fosforo' => [
                'tipo' => 'number',
                'label' => 'Fósforo',
                'placeholder' => 'Ingrese Fósforo',
                'required' => false,
                'step' => '0.01',
            ],
        ],
    ],

    'fibrinogeno' => [
        'nombre' => 'Fibrinogeno',
        'categoria' => 'COAGULACION',
        'unidad_medida' => null, // No se muestra unidad ni rango en la hoja
        'valores_referencia' => [],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01',
            ],
        ],
    ],

    'colesterol_total' => [
        'nombre' => 'COLESTEROL TOTAL',
        'categoria' => 'QUIMICA CLINICA',
        'unidad_medida' => 'mg/dL',
        'valores_referencia' => [
            'deseable' => [
                'label' => 'Deseable',
                'rango' => 'Menor de 200 mg/dL',
                'min' => null,
                'max' => 200
            ],
            'limite_normal_alto' => [
                'label' => 'Limite entre normal y alto',
                'rango' => '200 a 239 mg/dL',
                'min' => 200,
                'max' => 239
            ],
            'alto' => [
                'label' => 'Alto',
                'rango' => 'Mayor de 240 mg/dL',
                'min' => 240,
                'max' => null
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'coombs_directo_indirecto' => [
        'nombre' => 'Coombs Directo y Indirecto',
        'categoria' => 'HEMATOLOGÍA E INMUNOHEMATOLOGÍA',
        'unidad_medida' => null,
        'valores_referencia' => [
            'coombs_indirecto' => [
                'label' => 'Coombs Indirecto',
                'rango' => 'Negativo / Positivo',
                'unidad' => '',
            ],
            'coombs_directo' => [
                'label' => 'Coombs Directo',
                'rango' => 'Negativo / Positivo',
                'unidad' => '',
            ]
        ],
        'campos_adicionales' => [
            'resultado_coombs_indirecto' => [
                'tipo' => 'text',
                'label' => 'Coombs Indirecto',
                'placeholder' => 'Ingrese el resultado (Negativo/Positivo)',
                'required' => true,
            ],
            'resultado_coombs_directo' => [
                'tipo' => 'text',
                'label' => 'Coombs Directo',
                'placeholder' => 'Ingrese el resultado (Negativo/Positivo)',
                'required' => true,
            ]
        ]
    ],

    'coprocultivo' => [
        'nombre' => 'COPROCULTIVO',
        'categoria' => 'MICROBIOLOGÍA',
        'unidad_medida' => null,
        'valores_referencia' => [
            'susceptible' => [
                'label' => 'Susceptible',
                'rango' => '',
                'unidad' => '',
            ],
            'intermedio' => [
                'label' => 'Intermedio',
                'rango' => '',
                'unidad' => '',
            ],
            'resistente' => [
                'label' => 'Resistente',
                'rango' => '',
                'unidad' => '',
            ],
            'tipo_muestra' => [
                'label' => 'Tipo de Muestra',
                'rango' => '',
                'unidad' => '',
            ]
        ],
        'campos_adicionales' => [
            'resultado_susceptible' => [
                'tipo' => 'textarea',
                'label' => 'Susceptible',
                'placeholder' => 'Ej: Ingrese los antibióticos susceptibles',
                'required' => false,
            ],
            'resultado_intermedio' => [
                'tipo' => 'textarea',
                'label' => 'Intermedio',
                'placeholder' => 'Ej: Ingrese los antibióticos intermedios',
                'required' => false,
            ],
            'resultado_resistente' => [
                'tipo' => 'textarea',
                'label' => 'Resistente',
                'placeholder' => 'Ej: Ingrese los antibióticos resistentes',
                'required' => false,
            ],
            'resultado_tipo_muestra' => [
                'tipo' => 'text',
                'label' => 'Tipo de Muestra',
                'placeholder' => 'Ej: Heces',
                'required' => false,
            ]
        ]
    ],

    'urocultivo' => [
        'nombre' => 'UROCULTIVO (Cultivo de Orina)',
        'categoria' => 'UROCULTIVO (CULTIVO DE ORINA)',
        'unidad_medida' => 'UFC/mL',
        'valores_referencia' => [
            'recuento_bacteriano' => [
                'label' => 'Recuento Bacteriano',
                'unidad' => 'UFC/mL',
                'rango' => '',
            ],
            'susceptible' => [
                'label' => 'Susceptible',
                'rango' => '',
            ],
            'intermedio' => [
                'label' => 'Intermedio',
                'rango' => '',
            ],
            'resistente' => [
                'label' => 'Resistente',
                'rango' => '',
            ],
        ],
        'campos_adicionales' => [
            'resultado_recuento_bacteriano' => [
                'tipo' => 'number',
                'label' => 'Recuento Bacteriano',
                'placeholder' => 'Ej: 100000',
                'required' => true,
            ],
            'resultado_susceptible' => [
                'tipo' => 'textarea',
                'label' => 'Susceptible',
                'placeholder' => 'Ej: Ingrese los antibióticos susceptibles',
                'required' => false,
            ],
            'resultado_intermedio' => [
                'tipo' => 'textarea',
                'label' => 'Intermedio',
                'placeholder' => 'Ej: Ingrese los antibióticos intermedios',
                'required' => false,
            ],
            'resultado_resistente' => [
                'tipo' => 'textarea',
                'label' => 'Resistente',
                'placeholder' => 'Ej: Ingrese los antibióticos resistentes',
                'required' => false,
            ],
        ],
    ],

    'general_orina' => [
        'nombre' => 'Examen General de Orina',
        'categoria' => 'ORINA COMPLETA',
        'unidad_medida' => '',
        'valores_referencia' => [
            'color' => [
                'label' => 'Color',
                'rango' => '---',
                'tipo' => 'texto',
            ],
            'aspecto' => [
                'label' => 'Aspecto',
                'rango' => '---',
                'tipo' => 'texto',
            ],
            'densidad' => [
                'label' => 'Densidad',
                'rango' => '1.003 a 1.030',
                'tipo' => 'texto',
            ],
            'ph' => [
                'label' => 'pH',
                'rango' => '5.0 a 7.5',
                'tipo' => 'texto',
            ],
            'urobilinogeno' => [
                'label' => 'Urobilinógeno',
                'rango' => '0 a 1.0 mg/dL',
                'tipo' => 'texto',
            ],
            'bilirrubina' => [
                'label' => 'Bilirrubina',
                'rango' => 'Negativo',
                'tipo' => 'texto',
            ],
            'cuerpos_cetonicos' => [
                'label' => 'Cuerpos Cetónicos',
                'rango' => '0 mg/dL',
                'tipo' => 'texto',
            ],
            'nitritos' => [
                'label' => 'Nitritos',
                'rango' => 'Negativo',
                'tipo' => 'texto',
            ],
            'sangre_hemoglobina' => [
                'label' => 'Sangre/Hemoglobina',
                'rango' => '0 Cel / uL',
                'tipo' => 'texto',
            ],
            'esterasa_leucocitaria' => [
                'label' => 'Esterasa Leucocitaria',
                'rango' => '0 Cel / uL',
                'tipo' => 'texto',
            ],
            'proteinas' => [
                'label' => 'Proteínas',
                'rango' => '0 a 20 mg/dL',
                'tipo' => 'texto',
            ],
            'glucosa' => [
                'label' => 'Glucosa',
                'rango' => '0 mg/dL',
                'tipo' => 'texto',
            ],
            'celulas_epiteliales' => [
                'label' => 'Células Epiteliales',
                'rango' => '---',
                'tipo' => 'texto',
            ],
            'filamentos_mucoides' => [
                'label' => 'Filamentos Mucoides',
                'rango' => '---',
                'tipo' => 'texto',
            ],
            'bacterias' => [
                'label' => 'Bacterias',
                'rango' => '---',
                'tipo' => 'texto',
            ],
            'leucocitos' => [
                'label' => 'Leucocitos',
                'rango' => '0 a 5 x Campo',
                'tipo' => 'texto',
            ],
            'eritrocitos' => [
                'label' => 'Eritrocitos',
                'rango' => '0 x Campo',
                'tipo' => 'texto',
            ],
            'cilindros' => [
                'label' => 'Cilindros',
                'rango' => '---',
                'tipo' => 'texto',
            ],
            'cristales' => [
                'label' => 'Cristales',
                'rango' => '---',
                'tipo' => 'texto',
            ],
            'levaduras' => [
                'label' => 'Levaduras',
                'rango' => '---',
                'tipo' => 'texto',
            ],
        ],
        'campos_adicionales' => [
            'color' => [
                'tipo' => 'text',
                'label' => 'Color',
                'placeholder' => 'Ej: Amarillo',
                'required' => true,
            ],
            'aspecto' => [
                'tipo' => 'text',
                'label' => 'Aspecto',
                'placeholder' => 'Ej: Claro',
                'required' => true,
            ],
            'densidad' => [
                'tipo' => 'text',
                'label' => 'Densidad',
                'placeholder' => 'Ej: 1.015',
                'required' => true,
            ],
            'ph' => [
                'tipo' => 'text',
                'label' => 'pH',
                'placeholder' => 'Ej: 6.0',
                'required' => true,
            ],
            'urobilinogeno' => [
                'tipo' => 'text',
                'label' => 'Urobilinógeno',
                'placeholder' => 'Ej: Negativo',
                'required' => true,
            ],
            'bilirrubina' => [
                'tipo' => 'text',
                'label' => 'Bilirrubina',
                'placeholder' => 'Ej: Negativo',
                'required' => true,
            ],
            'cuerpos_cetonicos' => [
                'tipo' => 'text',
                'label' => 'Cuerpos Cetónicos',
                'placeholder' => 'Ej: Negativo',
                'required' => true,
            ],
            'nitritos' => [
                'tipo' => 'text',
                'label' => 'Nitritos',
                'placeholder' => 'Ej: Negativo',
                'required' => true,
            ],
            'sangre_hemoglobina' => [
                'tipo' => 'text',
                'label' => 'Sangre/Hemoglobina',
                'placeholder' => 'Ej: Negativo',
                'required' => true,
            ],
            'esterasa_leucocitaria' => [
                'tipo' => 'text',
                'label' => 'Esterasa Leucocitaria',
                'placeholder' => 'Ej: Negativo',
                'required' => true,
            ],
            'proteinas' => [
                'tipo' => 'text',
                'label' => 'Proteínas',
                'placeholder' => 'Ej: Negativo',
                'required' => true,
            ],
            'glucosa' => [
                'tipo' => 'text',
                'label' => 'Glucosa',
                'placeholder' => 'Ej: Negativo',
                'required' => true,
            ],
            'celulas_epiteliales' => [
                'tipo' => 'text',
                'label' => 'Células Epiteliales',
                'placeholder' => 'Ej: Escasas',
                'required' => false,
            ],
            'filamentos_mucoides' => [
                'tipo' => 'text',
                'label' => 'Filamentos Mucoides',
                'placeholder' => 'Ej: Escasos',
                'required' => false,
            ],
            'bacterias' => [
                'tipo' => 'text',
                'label' => 'Bacterias',
                'placeholder' => 'Ej: Escasas',
                'required' => false,
            ],
            'leucocitos' => [
                'tipo' => 'text',
                'label' => 'Leucocitos',
                'placeholder' => 'Ej: 0 a 5 x Campo',
                'required' => false,
            ],
            'eritrocitos' => [
                'tipo' => 'text',
                'label' => 'Eritrocitos',
                'placeholder' => 'Ej: 0 x Campo',
                'required' => false,
            ],
            'cilindros' => [
                'tipo' => 'text',
                'label' => 'Cilindros',
                'placeholder' => 'Ej: No se observa',
                'required' => false,
            ],
            'cristales' => [
                'tipo' => 'text',
                'label' => 'Cristales',
                'placeholder' => 'Ej: No se observa',
                'required' => false,
            ],
            'levaduras' => [
                'tipo' => 'text',
                'label' => 'Levaduras',
                'placeholder' => 'Ej: No se observa',
                'required' => false,
            ],
        ],
    ],

    'frotis_sangre_periferica' => [
        'nombre' => 'FROTIS DE SANGRE PERIFÉRICA',
        'categoria' => 'HEMATOLOGÍA',
        'unidad_medida' => null,
        'valores_referencia' => [
            'serie_roja' => [
                'label' => 'Serie Roja',
                'rango' => '',
                'unidad' => '',
            ],
            'serie_blanca' => [
                'label' => 'Serie Blanca',
                'rango' => '',
                'unidad' => '',
            ],
            'serie_plaquetaria' => [
                'label' => 'Serie Plaquetaria',
                'rango' => '',
                'unidad' => '',
            ]
        ],
        'campos_adicionales' => [
            'resultado_serie_roja' => [
                'tipo' => 'textarea',
                'label' => 'Serie Roja',
                'placeholder' => 'Ej: Se Observa: Normocítica, Normocrómica',
                'required' => true,
            ],
            'resultado_serie_blanca' => [
                'tipo' => 'textarea',
                'label' => 'Serie Blanca',
                'placeholder' => 'Ej: Se Observa: Normal en Numero y Maduración.',
                'required' => true,
            ],
            'resultado_serie_plaquetaria' => [
                'tipo' => 'textarea',
                'label' => 'Serie Plaquetaria',
                'placeholder' => 'Ej: Se Observa: Normal en número, Tamaño y Forma.',
                'required' => true,
            ]
        ]
    ],

    'glucosa' => [
        'nombre' => 'Glucosa',
        'categoria' => 'BIOQUIMICA CLINICA',
        'unidad_medida' => 'mg/dL',
        'valores_referencia' => [
            'ayunas' => [
                'label' => 'Glucosa en Ayunas',
                'rango' => '70 a 110 mg/dL',
                'min' => 70,
                'max' => 110
            ],
            'post_prandial' => [
                'label' => 'Glucosa Post-Prandial',
                'rango' => 'Hasta 140 mg/dL',
                'min' => null,
                'max' => 140
            ]
        ],
        'campos_adicionales' => [
            'resultado_ayunas' => [
                'tipo' => 'number',
                'label' => 'Resultado Glucosa en Ayunas',
                'placeholder' => 'Ingrese el valor de Glucosa en Ayunas',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_post_prandial' => [
                'tipo' => 'number',
                'label' => 'Resultado Glucosa Post-Prandial',
                'placeholder' => 'Ingrese el valor de Glucosa Post-Prandial',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'glucosa_post_pandrial_2h' => [
        'nombre' => 'Glucosa Post Pandrial 2 Horas',
        'categoria' => 'BIOQUIMICA CLINICA',
        'unidad_medida' => 'mg/dL',
        'valores_referencia' => [
            'ayunas' => [
                'label' => 'Glucosa en Ayunas',
                'rango' => '70 a 110 mg/dL',
                'unidad' => 'mg/dL',
                'min' => 70,
                'max' => 110
            ],
            'post_prandial' => [
                'label' => 'Glucosa Post-Prandial',
                'rango' => 'Hasta 140 mg/dL',
                'unidad' => 'mg/dL',
                'min' => null,
                'max' => 140
            ]
        ],
        'campos_adicionales' => [
            'resultado_ayunas' => [
                'tipo' => 'number',
                'label' => 'Glucosa en Ayunas',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_post_prandial' => [
                'tipo' => 'number',
                'label' => 'Glucosa Post-Prandial',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'helicobacter_pylori' => [
        'nombre' => 'Ac. Anti-Helicobacter pylori',
        'categoria' => 'INMUNOLOGIA INFECCIOSAS',
        'unidad_medida' => 'RLU',
        'valores_referencia' => [
            'igg' => [
                'label' => 'Ac. Anti-Helicobacter pylori IgG',
                'rango' => implode("\n", [
                    'Negativo: Menor de 0.9 RLU',
                    'Dudoso: 0.9 a 1.1 RLU',
                    'Positivo: Mayor de 1.1 RLU'
                ]),
                'negativo_max' => 0.9,
                'dudoso_min' => 0.9,
                'dudoso_max' => 1.1,
                'positivo_min' => 1.1
            ],
            'igm' => [
                'label' => 'Ac. Anti-Helicobacter pylori IgM',
                'rango' => implode("\n", [
                    'Negativo: Menor de 0.9 RLU',
                    'Dudoso: 0.9 a 1.1 RLU',
                    'Positivo: Mayor de 1.1 RLU'
                ]),
                'negativo_max' => 0.9,
                'dudoso_min' => 0.9,
                'dudoso_max' => 1.1,
                'positivo_min' => 1.1
            ]
        ],
        'campos_adicionales' => [
            'resultado_igg' => [
                'tipo' => 'number',
                'label' => 'Resultado Ac. Anti-Helicobacter pylori IgG',
                'placeholder' => 'Ingrese el valor de IgG',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_igm' => [
                'tipo' => 'number',
                'label' => 'Resultado Ac. Anti-Helicobacter pylori IgM',
                'placeholder' => 'Ingrese el valor de IgM',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'antigenos_helicobacter_pylori' => [
        'nombre' => 'Antígenos Helicobacter pylori',
        'categoria' => 'COPROLOGÍA',
        'unidad_medida' => null,
        'valores_referencia' => [
            'rango' => 'Negativo / Positivo',
            'opciones' => ['Negativo', 'Positivo']
        ]
    ],

    'anticuerpos_totales_helicobacter_pylori' => [
        'nombre' => 'Anticuerpos Totales Helicobacter pylori',
        'categoria' => 'INMUNOLOGIA INFECCIOSAS',
        'unidad_medida' => null,
        'valores_referencia' => [
            'rango' => 'Negativo / Positivo',
            'opciones' => ['Negativo', 'Positivo']
        ]
    ],

    'hematocrito_hemoglobina' => [
        'nombre' => 'Hematocrito y Hemoglobina',
        'categoria' => 'HEMATOLOGÍA',
        'unidad_medida' => null,
        'valores_referencia' => [
            'hematocrito' => [
                'label' => 'Hematocrito',
                'rango' => '32% a 51%',
                'min' => 32,
                'max' => 51,
                'unidad' => '%'
            ],
            'hemoglobina' => [
                'label' => 'Hemoglobina',
                'rango' => '11 a 17 g/dL',
                'min' => 11,
                'max' => 17,
                'unidad' => 'g/dL'
            ]
        ],
        'campos_adicionales' => [
            'resultado_hematocrito' => [
                'tipo' => 'number',
                'label' => 'Hematocrito',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_hemoglobina' => [
                'tipo' => 'number',
                'label' => 'Hemoglobina',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'hemoglobina_glicosilada' => [
        'nombre' => 'Hemoglobina Glicosilada A1c',
        'categoria' => 'PRUEBAS METABOLICAS',
        'unidad_medida' => '%',
        'valores_referencia' => [
            'rango' => implode("\n", [
                '4-5.6% Paciente no Diabético',
                '5.7 a 6.9% Diabético Controlado',
                'Mayor o Igual a 7% En diabéticos no controlados'
            ]),
            'no_diabetico_min' => 4,
            'no_diabetico_max' => 5.6,
            'controlado_min' => 5.7,
            'controlado_max' => 6.9,
            'no_controlado_min' => 7
        ]
    ],

    'hemograma' => [
        'nombre' => 'Hemograma',
        'categoria' => 'HEMATOLOGIA COMPLETA (HEMOGRAMA)',
        'unidad_medida' => null,
        'valores_referencia' => [
            'globulos_blancos' => [
                'label' => 'Recuento de Glóbulos Blancos',
                'rango' => '5,000 a 10,000 / uL',
                'unidad' => '/ uL'
            ],
            'neutrofilos' => [
                'label' => 'Neutrófilos %',
                'rango' => '40 a 65%',
                'unidad' => '%'
            ],
            'eosinofilos' => [
                'label' => 'Eosinófilos %',
                'rango' => '0 a 5%',
                'unidad' => '%'
            ],
            'basofilos' => [
                'label' => 'Basófilos %',
                'rango' => '0 a 2%',
                'unidad' => '%'
            ],
            'linfocitos' => [
                'label' => 'Linfocitos %',
                'rango' => '25 a 45%',
                'unidad' => '%'
            ],
            'monocitos' => [
                'label' => 'Monocitos %',
                'rango' => '2 a 10%',
                'unidad' => '%'
            ],
            'globulos_rojos' => [
                'label' => 'Recuento de Glóbulos Rojos',
                'rango' => '3.1 a 6.5 x 10^6 / uL',
                'unidad' => 'x 10^6 / uL'
            ],
            'hematocrito' => [
                'label' => 'Hematocrito',
                'rango' => '32 a 51%',
                'unidad' => '%'
            ],
            'hemoglobina' => [
                'label' => 'Hemoglobina',
                'rango' => '11 a 17 g/dL',
                'unidad' => 'g/dL'
            ],
            'vcm' => [
                'label' => 'VCM',
                'rango' => '80 a 100 fL',
                'unidad' => 'fL'
            ],
            'chcm' => [
                'label' => 'CHCM',
                'rango' => '32 a 36 g/dL',
                'unidad' => 'g/dL'
            ],
            'hcm' => [
                'label' => 'HCM',
                'rango' => '26 a 34 pg',
                'unidad' => 'pg'
            ],
            'plaquetas' => [
                'label' => 'Plaquetas',
                'rango' => '150,000 a 500,000 /uL',
                'unidad' => '/uL'
            ]
        ],
        'campos_adicionales' => [
            'resultado_globulos_blancos' => [
                'tipo' => 'number',
                'label' => 'Recuento de Glóbulos Blancos',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_neutrofilos' => [
                'tipo' => 'number',
                'label' => 'Neutrófilos %',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_eosinofilos' => [
                'tipo' => 'number',
                'label' => 'Eosinófilos %',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_basofilos' => [
                'tipo' => 'number',
                'label' => 'Basófilos %',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_linfocitos' => [
                'tipo' => 'number',
                'label' => 'Linfocitos %',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_monocitos' => [
                'tipo' => 'number',
                'label' => 'Monocitos %',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_globulos_rojos' => [
                'tipo' => 'number',
                'label' => 'Recuento de Glóbulos Rojos',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_hematocrito' => [
                'tipo' => 'number',
                'label' => 'Hematocrito',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_hemoglobina' => [
                'tipo' => 'number',
                'label' => 'Hemoglobina',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_vcm' => [
                'tipo' => 'number',
                'label' => 'VCM',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_chcm' => [
                'tipo' => 'number',
                'label' => 'CHCM',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_hcm' => [
                'tipo' => 'number',
                'label' => 'HCM',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_plaquetas' => [
                'tipo' => 'number',
                'label' => 'Plaquetas',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'hepatitis_a_igm' => [
        'nombre' => 'Hepatitis A IgM',
        'categoria' => 'INMUNOLOGIA HEPATITIS',
        'unidad_medida' => 'U',
        'valores_referencia' => [
            'rango' => implode("\n", [
                'Negativo Hasta 1.50 U',
                'Dudoso de 1.51 a 2.50 U',
                'Positivo Mayor de 2.50 U'
            ]),
            'negativo_max' => 1.50,
            'dudoso_min' => 1.51,
            'dudoso_max' => 2.50,
            'positivo_min' => 2.51
        ]
    ],

    'hormona_crecimiento' => [
        'nombre' => 'Hormona del Crecimiento Pre-Ejercicio (HGh)',
        'categoria' => 'HORMONAS',
        'unidad_medida' => 'ng/mL',
        'valores_referencia' => [
            'rango' => '0.06 a 5.00 ng/mL',
            'min' => 0.06,
            'max' => 5.00
        ]
    ],

    'hormona_folículo_estimulante' => [
        'nombre' => 'Hormona Folículo Estimulante (FSH)',
        'categoria' => 'HORMONAS FERTILIDAD',
        'unidad_medida' => 'mUI/mL',
        'valores_referencia' => [
            'rango' => implode("\n", [
                'Hombres: 0.7 a 12.4',
                'Niños 0 - 3 años: 0 a 10',
                'Niños 4 - 9 Años: 0 a 1.8',
                'Fase Folicular: 3.5 a 12.5',
                'Fase Folicular Día 2-3: 3.0 a 14.4',
                'Ovulación + 3 días: 4.7 a 21.5',
                'Fase Lutea: 1.7 a 7.7',
                'Post Menopausia: 25.8 a 134.8',
                'Anticonceptivos Orales: 0 a 4.9',
                'Todos en mUI/mL'
            ])
        ]
    ],

    'hormona_luteinizante' => [
        'nombre' => 'Hormona Luteinizante (LH)',
        'categoria' => 'HORMONAS FERTILIDAD',
        'unidad_medida' => 'mUI/mL',
        'valores_referencia' => [
            'rango' => implode("\n", [
                'Hombres: 0.8 a 8.6',
                'Niños 0 - 9 años: 0.0 a 3.7',
                'Fase Folicular: 1.1 a 12.6',
                'Ovulación + 3 días: 14.0 a 95.6',
                'Fase Lutea: 0.0 a 11.4',
                'Perimenstrual + 8 días: 0.0 a 12.0',
                'Post Menopausia: 7.7 a 58.5',
                'Anticonceptivos Orales: 0.0 a 8.0',
                'Todos en mUI/mL'
            ])
        ]
    ],

    'insulina' => [
        'nombre' => 'Insulina B',
        'categoria' => 'HORMONAS',
        'unidad_medida' => 'ulU/ml',
        'valores_referencia' => [
            'rango' => '2.44 - 24.43 ulU/ml',
            'min' => 2.44,
            'max' => 24.43
        ]
    ],

    'prueba_azul_metileno' => [
        'nombre' => 'Prueba de Azul de Metileno (P.A.M.)',
        'categoria' => 'COPROLOGÍA',
        'unidad_medida' => null,
        'valores_referencia' => [
            'leucocitos_polimorfonucleares' => [
                'label' => 'Leucocitos Polimorfonucleares',
                'rango' => '',
                'unidad' => '%'
            ],
            'leucocitos_mononucleares' => [
                'label' => 'Leucocitos Mononucleares',
                'rango' => '',
                'unidad' => '%'
            ]
        ],
        'campos_adicionales' => [
            'resultado_leucocitos_polimorfonucleares' => [
                'tipo' => 'number',
                'label' => 'Leucocitos Polimorfonucleares',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_leucocitos_mononucleares' => [
                'tipo' => 'number',
                'label' => 'Leucocitos Mononucleares',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'proteina_c_reactiva' => [
        'nombre' => 'Proteína C Reactiva (PCR)',
        'categoria' => 'PRUEBAS TIROIDEAS',
        'unidad_medida' => 'mg/L',
        'valores_referencia' => [
            'rango' => 'Menor o Igual a 6 mg/L',
            'max' => 6
        ]
    ],

    'perfil_bioquimica_clinica' => [
        'nombre' => 'Perfil Bioquímica Clínica',
        'categoria' => 'BIOQUIMICA CLINICA',
        'unidad_medida' => null,
        'valores_referencia' => [
            'glucosa_ayunas' => [
                'label' => 'Glucosa en Ayunas',
                'rango' => '60 a 110 mg/dL',
                'unidad' => 'mg/dL',
                'min' => 60,
                'max' => 110,
            ],
            'colesterol' => [
                'label' => 'Colesterol',
                'rango' => 'Hasta 200 mg/dL',
                'unidad' => 'mg/dL',
                'min' => null,
                'max' => 200,
            ],
            'trigliceridos' => [
                'label' => 'Triglicéridos',
                'rango' => 'Hasta 150 mg/dL',
                'unidad' => 'mg/dL',
                'min' => null,
                'max' => 150,
            ],
            'acido_urico' => [
                'label' => 'Acido Úrico',
                'rango' => implode("\n", [
                    'Hombre de 3.0 a 7.0 mg/dL',
                    'Mujeres de 2.0 a 5.7 mg/dL',
                ]),
                'unidad' => 'mg/dL',
                'hombres_min' => 3.0,
                'hombres_max' => 7.0,
                'mujeres_min' => 2.0,
                'mujeres_max' => 5.7,
            ],
            'creatinina' => [
                'label' => 'Creatinina',
                'rango' => implode("\n", [
                    'Hombres: 0.7 a 1.4 mg/dL',
                    'Mujeres: 0.5 a 1.1 mg/dL',
                ]),
                'unidad' => 'mg/dL',
                'hombres_min' => 0.7,
                'hombres_max' => 1.4,
                'mujeres_min' => 0.5,
                'mujeres_max' => 1.1,
            ],
            // Segunda hoja del perfil químico
            'urea' => [
                'label' => 'Urea',
                'rango' => 'Adultos: 12.8 – 42.8 mg/dL',
                'unidad' => 'mg/dL',
                'min' => 12.8,
                'max' => 42.8,
            ],
            'nitrogeno_ureico' => [
                'label' => 'Nitrógeno Ureico',
                'rango' => implode("\n", [
                    'Lactantes menores a 1 año: 4 a 19 mg/dL',
                    'Niños: 4 a 19 mg/dL',
                    'Adultos de 18 a 60 años: 6 a 23 mg/dL',
                    'Adultos mayores de 60 años: 8 a 23 mg/dL',
                ]),
                'unidad' => 'mg/dL',
            ],
        ],
        'campos_adicionales' => [
            'resultado_glucosa_ayunas' => [
                'tipo' => 'number',
                'label' => 'Glucosa en Ayunas',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01',
            ],
            'resultado_colesterol' => [
                'tipo' => 'number',
                'label' => 'Colesterol',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01',
            ],
            'resultado_trigliceridos' => [
                'tipo' => 'number',
                'label' => 'Triglicéridos',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01',
            ],
            'resultado_acido_urico' => [
                'tipo' => 'number',
                'label' => 'Acido Úrico',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01',
            ],
            'resultado_creatinina' => [
                'tipo' => 'number',
                'label' => 'Creatinina',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01',
            ],
            // Campos de la segunda hoja
            'resultado_urea' => [
                'tipo' => 'number',
                'label' => 'Urea',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01',
            ],
            'resultado_nitrogeno_ureico' => [
                'tipo' => 'number',
                'label' => 'Nitrógeno Ureico',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01',
            ],
        ],
    ],

    'bhcg_cualitativa' => [
        'nombre' => 'B-hCG Cualitativa en Sangre',
        'categoria' => 'HORMONAS',
        'unidad_medida' => null, // Examen cualitativo, no tiene unidad
        'valores_referencia' => [
            'normal' => [
                'label' => 'Normal',
                'rango' => 'Negativo',
                'valor' => 'negativo'
            ],
            'positivo' => [
                'label' => 'Positivo',
                'rango' => 'Positivo',
                'valor' => 'positivo'
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'select',
                'label' => 'Resultado',
                'placeholder' => 'Seleccione el resultado',
                'required' => true,
                'opciones' => [
                    'negativo' => 'Negativo',
                    'positivo' => 'Positivo'
                ]
            ]
        ]
    ],

    'prolactina' => [
        'nombre' => 'Prolactina (PRL)',
        'categoria' => 'HORMONAS FERTILIDAD',
        'unidad_medida' => 'ng/mL',
        'valores_referencia' => [
            'rango' => implode("\n", [
                'Hombres: 4.04 a 15.2 ng/mL',
                'Mujeres No Embarazadas: 4.79 a 23.3 ng/mL'
            ]),
            'hombres_min' => 4.04,
            'hombres_max' => 15.2,
            'mujeres_min' => 4.79,
            'mujeres_max' => 23.3
        ]
    ],

    'proteinas_orina_azar' => [
        'nombre' => 'Proteinas en Orina al Azar',
        'categoria' => 'UROLOGIA',
        'unidad_medida' => 'mg/dL',
        'valores_referencia' => [
            'rango' => 'Hasta: 15 mg/dL',
            'max' => 15
        ]
    ],

    'psa_total' => [
        'nombre' => 'Antígeno Prostático Específico Total (PSA)',
        'categoria' => 'PROTEINAS NEOPLASICAS',
        'unidad_medida' => 'ng/mL',
        'valores_referencia' => [
            'rango' => implode("\n", [
                'Normal o Bajo Riesgo: 0 a 4 ng/mL',
                'Mediano Riesgo: 4 a 10 ng/mL',
                'Alto Riesgo: Mayor de 10 ng/mL'
            ]),
            'normal_min' => 0,
            'normal_max' => 4,
            'mediano_min' => 4,
            'mediano_max' => 10,
            'alto_min' => 10
        ]
    ],

    'sangre_oculta_heces' => [
        'nombre' => 'Sangre Oculta en Heces',
        'categoria' => 'COPROLOGIA',
        'unidad_medida' => null, // Examen cualitativo, no tiene unidad
        'valores_referencia' => [
            'normal' => [
                'label' => 'Normal',
                'rango' => 'Negativo',
                'valor' => 'negativo'
            ],
            'positivo' => [
                'label' => 'Positivo',
                'rango' => 'Positivo',
                'valor' => 'positivo'
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'select',
                'label' => 'Resultado',
                'placeholder' => 'Seleccione el resultado',
                'required' => true,
                'opciones' => [
                    'negativo' => 'Negativo',
                    'positivo' => 'Positivo'
                ]
            ]
        ]
    ],

    'pruebas_tiroideas' => [
        'nombre' => 'Pruebas Tiroideas',
        'categoria' => 'PRUEBAS TIROIDEAS',
        'unidad_medida' => null, // Examen multi-parámetro
        'valores_referencia' => [
            'tsh' => [
                'unidad' => 'uUI/mL',
                'adultos' => '0.4 - 4.0',
                'rango_simple' => '0.4 - 4.0 uUI/mL'
            ],
            't4' => [
                'unidad' => 'nmol/L',
                'adultos' => '60 - 120',
                'rango_simple' => '60 - 120 nmol/L'
            ],
            't3' => [
                'unidad' => 'ng/mL',
                'adultos' => '0.5 - 5.0',
                'rango_simple' => '0.5 - 5.0 ng/mL'
            ]
        ],
        'campos_adicionales' => [
            'resultado_tsh' => [
                'tipo' => 'number',
                'label' => 'Hormona Estimulante de Tiroides (TSH)',
                'placeholder' => 'Ingrese el valor de TSH',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_t4' => [
                'tipo' => 'number',
                'label' => 'Tiroxina (T4)',
                'placeholder' => 'Ingrese el valor de T4',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_t3' => [
                'tipo' => 'number',
                'label' => 'Triyodotironina (T3)',
                'placeholder' => 'Ingrese el valor de T3',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'tiroideas_t3_t4_tsh' => [
        'nombre' => 'Pruebas Tiroideas (T3, T4, TSH)',
        'categoria' => 'PRUEBAS TIROIDEAS',
        'unidad_medida' => null, // Examen multi-parámetro
        'valores_referencia' => [
            't3' => [
                'unidad' => 'ng/mL',
                'rango' => '0.5 - 5.0 ng/mL',
                'min' => 0.5,
                'max' => 5.0
            ],
            't4' => [
                'unidad' => 'nmol/L',
                'rango' => '60 - 120 nmol/L',
                'min' => 60,
                'max' => 120
            ],
            'tsh' => [
                'unidad' => 'uUI/mL',
                'rango' => '0.4 - 4.0 uUI/mL',
                'min' => 0.4,
                'max' => 4.0
            ]
        ],
        'campos_adicionales' => [
            'resultado_t3' => [
                'tipo' => 'number',
                'label' => 'Triyodotironina (T3)',
                'placeholder' => 'Ingrese el valor de T3',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_t4' => [
                'tipo' => 'number',
                'label' => 'Tiroxina (T4)',
                'placeholder' => 'Ingrese el valor de T4',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_tsh' => [
                'tipo' => 'number',
                'label' => 'Hormona Estimulante de Tiroides (TSH)',
                'placeholder' => 'Ingrese el valor de TSH',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'tiroideas_ninos' => [
        'nombre' => 'Pruebas Tiroideas para Niños',
        'categoria' => 'PRUEBAS TIROIDEAS',
        'unidad_medida' => null, // Examen multi-parámetro
        'valores_referencia' => [
            't3' => [
                'unidad' => 'Ng/mL',
                'rango' => '0.90 - 4.0 Ng/mL',
                'min' => 0.90,
                'max' => 4.0
            ],
            't4' => [
                'unidad' => 'ug/dl',
                'rango' => '9.2 - 15.1 ug/dl',
                'min' => 9.2,
                'max' => 15.1
            ],
            'tsh' => [
                'unidad' => 'uUI/mL',
                'rango' => '0.85 - 6.55 uUI/mL',
                'min' => 0.85,
                'max' => 6.55
            ],
            'nota_edad' => 'Valores de referencia en niños de 1 – 6 años'
        ],
        'campos_adicionales' => [
            'resultado_t3' => [
                'tipo' => 'number',
                'label' => 'Triyodotironina (T3)',
                'placeholder' => 'Ingrese el valor de T3',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_t4' => [
                'tipo' => 'number',
                'label' => 'Tiroxina (T4)',
                'placeholder' => 'Ingrese el valor de T4',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_tsh' => [
                'tipo' => 'number',
                'label' => 'Hormona Estimulante de Tiroides (TSH)',
                'placeholder' => 'Ingrese el valor de TSH',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'pruebas_tiroideas_completo' => [
        'nombre' => 'Pruebas Tiroideas (TSH, T4 Libre, T3 Libre)',
        'categoria' => 'PRUEBAS TIROIDEAS',
        'unidad_medida' => null, // Examen multi-parámetro
        'valores_referencia' => [
            'tsh' => [
                'unidad' => 'uUI/mL',
                'rango' => 'Menor de 12 meses: 1.36 a 8.8' . "\n" . 
                           '1 a 6 años: 0.85 a 6.55' . "\n" . 
                           '7 a 12 años: 0.28 a 4.3' . "\n" . 
                           'Adultos: 0.27 a 4.20' . "\n" . 
                           'Todo en uUI/mL'
            ],
            'ft4' => [
                'unidad' => 'ng/dl',
                'rango' => 'Adultos: 0.93-1.70 ng/dl' . "\n" . 
                           'Embarazadas 1er-3er Trimestre: 0.7-1.5 ng/dl' . "\n" . 
                           'Neonatos: 0.86-2.49 ng/dl' . "\n" . 
                           '6 Días a 3 Meses: 0.89-2.20 ng/dl' . "\n" . 
                           '4 a 12 Meses: 0.92-1.99 ng/dl' . "\n" . 
                           '1 a 20 años: 0.96-1.77 ng/dl'
            ],
            'ft3' => [
                'unidad' => 'pg/ml',
                'rango' => 'Adultos: 2.0-4.4 pg/ml' . "\n" . 
                           'Embarazadas 1er-3er Trimestre: 2.0-3.9 pg/ml' . "\n" . 
                           'Neonatos: 1.73-6.30 pg/ml' . "\n" . 
                           '6 Días a 3 Meses: 1.95-6.04 pg/ml' . "\n" . 
                           '4 a 12 Meses: 2.15-5.83 pg/ml' . "\n" . 
                           '1 a 20 años: 2.41-5.50 pg/ml'
            ]
        ],
        'campos_adicionales' => [
            'resultado_tsh' => [
                'tipo' => 'number',
                'label' => 'Hormona Estimulante de Tiroides (TSH)',
                'placeholder' => 'Ingrese el valor de TSH',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_ft4' => [
                'tipo' => 'number',
                'label' => 'T4 Libre (FT4)',
                'placeholder' => 'Ingrese el valor de T4 Libre',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_ft3' => [
                'tipo' => 'number',
                'label' => 'T3 Libre (FT3)',
                'placeholder' => 'Ingrese el valor de T3 Libre',
                'required' => true,
                'step' => '0.01'
            ],
        ]
    ],

    'vih' => [
        'nombre' => 'Virus Inmunodeficiencia Humana (HIV)',
        'categoria' => 'INMUNOLOGIA INFECCIOSAS',
        'unidad_medida' => null, // Examen cualitativo, no tiene unidad
        'valores_referencia' => [
            'no_reactivo' => [
                'label' => 'No Reactivo',
                'rango' => 'NO REACTIVO A LA FECHA',
                'valor' => 'no_reactivo'
            ],
            'reactivo' => [
                'label' => 'Reactivo',
                'rango' => 'REACTIVO',
                'valor' => 'reactivo'
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'select',
                'label' => 'Resultado',
                'placeholder' => 'Seleccione el resultado',
                'required' => true,
                'opciones' => [
                    'no_reactivo' => 'NO REACTIVO A LA FECHA',
                    'reactivo' => 'REACTIVO'
                ]
            ],
            'marca_reactivo' => [
                'tipo' => 'text',
                'label' => 'Marca del Reactivo',
                'placeholder' => 'Ej: Abbott',
                'required' => false
            ],
            'metodo_prueba' => [
                'tipo' => 'text',
                'label' => 'Método de Prueba',
                'placeholder' => 'Ej: Rápida',
                'required' => false
            ]
        ]
    ],

    'vdrl_rpr' => [
        'nombre' => 'VDRL-RPR',
        'categoria' => 'SEROLOGÍA',
        'unidad_medida' => null, // Examen cualitativo, no tiene unidad
        'valores_referencia' => [
            'no_reactivo' => [
                'label' => 'No Reactivo',
                'rango' => 'No Reactivo',
                'valor' => 'no_reactivo'
            ],
            'reactivo' => [
                'label' => 'Reactivo',
                'rango' => 'Reactivo',
                'valor' => 'reactivo'
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'select',
                'label' => 'Resultado',
                'placeholder' => 'Seleccione el resultado',
                'required' => true,
                'opciones' => [
                    'no_reactivo' => 'No Reactivo',
                    'reactivo' => 'Reactivo'
                ]
            ],
            'marca_reactivo' => [
                'tipo' => 'text',
                'label' => 'Marca del Reactivo',
                'placeholder' => 'Ej: Abbott, Becton Dickinson, etc.',
                'required' => false
            ],
            'metodo_prueba' => [
                'tipo' => 'text',
                'label' => 'Método de Prueba',
                'placeholder' => 'Ej: Rápida, ELISA, etc.',
                'required' => false
            ]
        ]
    ],

    'vdrl_cardiolipina' => [
        'nombre' => 'V.D.R.L. (Cardiolipina)',
        'categoria' => 'INMUNOLOGIA INFECCIOSAS',
        'unidad_medida' => null, // Examen cualitativo, no tiene unidad
        'valores_referencia' => [
            'no_reactivo' => [
                'label' => 'No Reactivo a la Fecha',
                'rango' => 'No Reactivo a la Fecha',
                'valor' => 'no_reactivo'
            ],
            'reactivo' => [
                'label' => 'Reactivo',
                'rango' => 'Reactivo',
                'valor' => 'reactivo'
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'select',
                'label' => 'Resultado',
                'placeholder' => 'Seleccione el resultado',
                'required' => true,
                'opciones' => [
                    'no_reactivo' => 'No Reactivo a la Fecha',
                    'reactivo' => 'Reactivo'
                ]
            ]
        ]
    ],

    'velocidad_eritrosedimentacion' => [
        'nombre' => 'Velocidad de Eritrosedimentación',
        'categoria' => 'HEMATOLOGÍA',
        'unidad_medida' => 'mm/h',
        'valores_referencia' => [
            'hombres' => [
                'label' => 'Hombre',
                'rango' => '0 a 15 mm/h',
                'min' => 0,
                'max' => 15
            ],
            'mujeres' => [
                'label' => 'Mujeres',
                'rango' => '0 a 20 mm/h',
                'min' => 0,
                'max' => 20
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'test_osullivan' => [
        'nombre' => 'Test de O\'Sullivan',
        'categoria' => 'BIOQUIMICA CLINICA',
        'unidad_medida' => 'mg/dL',
        'valores_referencia' => [
            'ayunas' => [
                'label' => 'Glucosa en Ayunas',
                'rango' => '70 a 100 mg/dL',
                'unidad' => 'mg/dL',
                'normal_min' => 70,
                'normal_max' => 100
            ],
            '1h' => [
                'label' => 'Glucosa 1 Hora después de la toma',
                'rango' => 'Menor a 140 mg/dL',
                'unidad' => 'mg/dL',
                'normal_max' => 140
            ],
            '2h' => [
                'label' => 'Glucosa 2 Hora después de la toma',
                'rango' => 'Menor a 140 mg/dL',
                'unidad' => 'mg/dL',
                'normal_max' => 140
            ],
            'nota_dextrosa' => 'Se Administro 50 Gramos de Dextrosa.'
        ],
        'campos_adicionales' => [
            'resultado_ayunas' => [
                'tipo' => 'number',
                'label' => 'Glucosa en Ayunas',
                'placeholder' => 'Ingrese el valor en ayunas',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_1h' => [
                'tipo' => 'number',
                'label' => 'Glucosa 1 Hora después de la toma',
                'placeholder' => 'Ingrese el valor a la 1 hora',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_2h' => [
                'tipo' => 'number',
                'label' => 'Glucosa 2 Hora después de la toma',
                'placeholder' => 'Ingrese el valor a las 2 horas',
                'required' => true,
                'step' => '0.01'
            ],
        ]
    ],

    'testosterona' => [
        'nombre' => 'Testosterona (Te)',
        'categoria' => 'HORMONAS FERTILIDAD',
        'unidad_medida' => 'ng/mL',
        'valores_referencia' => [
            'hombres' => [
                'label' => 'Hombres',
                'rango' => '2.80 a 8.00',
                'min' => 2.80,
                'max' => 8.00
            ],
            'mujeres' => [
                'label' => 'Mujeres',
                'rango' => '0.06 a 0.82',
                'min' => 0.06,
                'max' => 0.82
            ],
            'ninos_hasta_1_ano' => [
                'label' => 'Niños hasta 1 año',
                'rango' => '0.12 a 0.21',
                'min' => 0.12,
                'max' => 0.21
            ],
            'ninos_1_a_6_anos' => [
                'label' => 'Niños 1 a 6 años',
                'rango' => '0.03 a 0.32',
                'min' => 0.03,
                'max' => 0.32
            ],
            'ninos_7_a_12_anos' => [
                'label' => 'Niños 7 a 12 años',
                'rango' => '0.03 a 0.68',
                'min' => 0.03,
                'max' => 0.68
            ],
            'ninos_13_a_17_anos' => [
                'label' => 'Niños 13 a 17 años',
                'rango' => '0.28 a 11.1',
                'min' => 0.28,
                'max' => 11.1
            ],
            'nota' => 'Todos en ng/mL'
        ]
    ],

    'tgo_tgp' => [
        'nombre' => 'TGO Y TGP',
        'categoria' => 'BIOQUIMICA CLINICA',
        'unidad_medida' => 'U/L',
        'valores_referencia' => [
            'tgo' => [
                'unidad' => 'U/L',
                'rango' => 'Hombres: < 35 U/L' . "\n" . 'Mujeres: < 31 U/L',
                'hombres' => [
                    'label' => 'Hombres',
                    'rango' => '< 35 U/L',
                    'max' => 35
                ],
                'mujeres' => [
                    'label' => 'Mujeres',
                    'rango' => '< 31 U/L',
                    'max' => 31
                ]
            ],
            'tgp' => [
                'unidad' => 'U/L',
                'rango' => 'Hombres: < 45 U/L' . "\n" . 'Mujeres: < 34 U/L',
                'hombres' => [
                    'label' => 'Hombres',
                    'rango' => '< 45 U/L',
                    'max' => 45
                ],
                'mujeres' => [
                    'label' => 'Mujeres',
                    'rango' => '< 34 U/L',
                    'max' => 34
                ]
            ]
        ],
        'campos_adicionales' => [
            'resultado_tgo' => [
                'tipo' => 'number',
                'label' => 'Transaminasa Glutámico Oxalacética (TGO)',
                'placeholder' => 'Ingrese el valor de TGO',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_tgp' => [
                'tipo' => 'number',
                'label' => 'Transaminasa Glutámico Pirúvica (TGP)',
                'placeholder' => 'Ingrese el valor de TGP',
                'required' => true,
                'step' => '0.01'
            ],
        ]
    ],

    'tiempo_sangrado' => [
        'nombre' => 'Tiempo de Sangrado',
        'categoria' => 'HEMOSTASIA',
        'unidad_medida' => 'minutos',
        'valores_referencia' => [
            'rango' => '2 a 8 minutos',
            'min' => 2,
            'max' => 8
        ]
    ],

    'tiroglobulina' => [
        'nombre' => 'Tiroglobulina (TG)',
        'categoria' => 'QUIMICA E INMUNOLOGIA',
        'unidad_medida' => 'ng/mL',
        'valores_referencia' => [
            'rango' => '3.5 a 77.0 ng/mL',
            'min' => 3.5,
            'max' => 77.0
        ]
    ],

    'toxoplasma_gondii' => [
        'nombre' => 'Toxoplasma gondii IgM IgG',
        'categoria' => 'TORCH',
        'unidad_medida' => null, // Cada parámetro tiene su propia unidad
        'valores_referencia' => [
            'igg' => [
                'label' => 'Toxoplasma gondii IgG',
                'rango' => implode("\n", [
                    'Negativo Menor de 1.0 UI/mL',
                    'Indeterminado 1.0 a 3.0 UI/mL',
                    'Positivo Mayor de 3.0 UI/mL',
                ]),
                'unidad' => 'UI/mL',
                'nota' => 'Si el resultado fuera indeterminado, Se recomienda analizar otra Muestra pasadas 2 a 3 semanas.',
            ],
            'igm' => [
                'label' => 'Toxoplasma gondii IgM',
                'rango' => implode("\n", [
                    'Negativo: Menor de 0.8 COI',
                    'Indeterminado: 0.8 a 1.0 COI',
                    'Positivo: Mayor de 1.0 COI',
                ]),
                'unidad' => 'COI',
                'nota' => 'Si el resultado fuera indeterminado, Se recomienda analizar otra Muestra pasadas 2 a 3 semanas.',
            ]
        ],
        'campos_adicionales' => [
            'resultado_igg' => [
                'tipo' => 'number',
                'label' => 'Toxoplasma gondii IgG',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ],
            'resultado_igm' => [
                'tipo' => 'number',
                'label' => 'Toxoplasma gondii IgM',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'tpt_tp' => [
        'nombre' => 'TPT Y TP',
        'categoria' => 'COAGULACION',
        'unidad_medida' => 'segundos',
        'valores_referencia' => [
            'tpt' => [
                'label' => 'Tiempo de Tromboplastina Parcial (TTP)',
                'rango' => '20 - 33 segundos',
                'min' => 20,
                'max' => 33,
                'unidad' => 'segundos'
            ],
            'tp' => [
                'label' => 'Tiempo de protombina (TP)',
                'rango' => '11,1 - 14,3 segundos',
                'min' => 11.1,
                'max' => 14.3,
                'unidad' => 'segundos'
            ],
            'inr' => [
                'label' => 'INR',
                'rango' => null, // No se especifica rango en el documento
                'unidad' => null
            ]
        ],
        'campos_adicionales' => [
            'resultado_tpt' => [
                'tipo' => 'number',
                'label' => 'Tiempo de Tromboplastina Parcial (TTP)',
                'placeholder' => 'Ingrese el valor en segundos',
                'required' => true,
                'step' => '0.1'
            ],
            'resultado_tp' => [
                'tipo' => 'number',
                'label' => 'Tiempo de protombina (TP)',
                'placeholder' => 'Ingrese el valor en segundos',
                'required' => true,
                'step' => '0.1'
            ],
            'resultado_inr' => [
                'tipo' => 'number',
                'label' => 'INR',
                'placeholder' => 'Ingrese el valor de INR',
                'required' => false,
                'step' => '0.01'
            ]
        ]
    ],

    'ggt' => [
        'nombre' => 'Gamma Glutamil Transpeptidasa (GGT)',
        'categoria' => 'PRUEBAS HEPATICAS',
        'unidad_medida' => 'U/L',
        'valores_referencia' => [
            'hombres' => [
                'label' => 'Hombres',
                'rango' => '8 a 61 U/L',
                'min' => 8,
                'max' => 61
            ],
            'mujeres' => [
                'label' => 'Mujeres',
                'rango' => '5 a 36 U/L',
                'min' => 5,
                'max' => 36
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'number',
                'label' => 'Resultado',
                'placeholder' => 'Ingrese el valor',
                'required' => true,
                'step' => '0.01'
            ]
        ]
    ],

    'trypanosoma_cruzi' => [
        'nombre' => 'Ac. Anti-Trypanosoma cruzi Totales (Chagas)',
        'categoria' => 'INMUNOLOGIA INFECCIOSAS',
        'unidad_medida' => null, // Examen cualitativo, no tiene unidad
        'valores_referencia' => [
            'normal' => [
                'label' => 'Normal',
                'rango' => 'Negativo',
                'valor' => 'negativo'
            ],
            'positivo' => [
                'label' => 'Positivo',
                'rango' => 'Positivo',
                'valor' => 'positivo'
            ]
        ],
        'campos_adicionales' => [
            'resultado' => [
                'tipo' => 'select',
                'label' => 'Resultado',
                'placeholder' => 'Seleccione el resultado',
                'required' => true,
                'opciones' => [
                    'negativo' => 'Negativo',
                    'positivo' => 'Positivo'
                ]
            ]
        ]
    ],

    'tipo_sanguineo' => [
        'nombre' => 'TIPEO SANGUINEO',
        'categoria' => 'TIPEO SANGUINEO',
        'unidad_medida' => null,
        'valores_referencia' => [
            'grupo' => [
                'label' => 'Grupo',
                'rango' => '',
                'unidad' => '',
            ],
            'factor_rh' => [
                'label' => 'Factor RH',
                'rango' => '',
                'unidad' => '',
            ]
        ],
        'campos_adicionales' => [
            'resultado_grupo' => [
                'tipo' => 'text',
                'label' => 'Grupo',
                'placeholder' => 'Ingrese el grupo (A, B, AB, O)',
                'required' => true,
            ],
            'resultado_factor_rh' => [
                'tipo' => 'text',
                'label' => 'Factor RH',
                'placeholder' => 'Ingrese el factor (POSITIVO, NEGATIVO)',
                'required' => true,
            ]
        ]
    ],

    'concentrado_strout' => [
        'nombre' => 'Concentrado de strout',
        'categoria' => 'INMUNOHEMTOLOGIA',
        'unidad_medida' => null,
        'valores_referencia' => [
            'resultado' => [
                'label' => 'Resultado',
                'rango' => implode("\n", [
                    'POSITIVO: Se observan formas flageladas móviles de trypomastigotes de Trypanosoma cruzy',
                    'Negativo: no Se observan formas de trypomastigotes de Trypanosoma cruzy',
                ]),
                'unidad' => '',
            ]
        ],
        'campos_adicionales' => [
            'resultado_resultado' => [
                'tipo' => 'textarea',
                'label' => 'Resultado',
                'placeholder' => 'Ej: POSITIVO: Se observan formas flageladas móviles de trypomastigotes de Trypanosoma cruzy',
                'required' => true,
            ]
        ]
    ],

    'pam_azul_metileno' => [
        'nombre' => 'Prueba de Azul de Metileno (P.A.M.)',
        'categoria' => 'COPROLOGÍA',
        'unidad_medida' => '%',
        'valores_referencia' => [
            'leucocitos_polimorfonucleares' => [
                'label' => 'Leucocitos Polimorfonucleares',
                'rango' => '',
                'unidad' => '%',
            ],
            'leucocitos_mononucleares' => [
                'label' => 'Leucocitos Mononucleares',
                'rango' => '',
                'unidad' => '%',
            ]
        ],
        'campos_adicionales' => [
            'resultado_leucocitos_polimorfonucleares' => [
                'tipo' => 'number',
                'label' => 'Leucocitos Polimorfonucleares',
                'placeholder' => 'Ej: 0',
                'required' => true,
            ],
            'resultado_leucocitos_mononucleares' => [
                'tipo' => 'number',
                'label' => 'Leucocitos Mononucleares',
                'placeholder' => 'Ej: 0',
                'required' => true,
            ]
        ]
    ],

    'heces_completo' => [
        'nombre' => 'HECES COMPLETO',
        'categoria' => 'COPROLOGÍA',
        'unidad_medida' => null,
        'valores_referencia' => [
            'color' => [
                'label' => 'Color',
                'rango' => '',
                'unidad' => '',
            ],
            'consistencia' => [
                'label' => 'Consistencia',
                'rango' => '',
                'unidad' => '',
            ],
            'mucus' => [
                'label' => 'Mucus',
                'rango' => '',
                'unidad' => '',
            ],
            'restos_alimenticios_macroscopicos' => [
                'label' => 'Restos alimenticios Macroscópicos',
                'rango' => '',
                'unidad' => '',
            ],
            'restos_alimenticios_microscopicos' => [
                'label' => 'Restos alimenticios Microscópicos',
                'rango' => '',
                'unidad' => '',
            ],
            'metazoarios' => [
                'label' => 'Metazoarios',
                'rango' => '',
                'unidad' => '',
            ],
            'protozoarios_quistes' => [
                'label' => 'Protozoarios Quistes',
                'rango' => '',
                'unidad' => '',
            ],
            'protozoarios_activos' => [
                'label' => 'Protozoarios Activos',
                'rango' => '',
                'unidad' => '',
            ],
            'hematies' => [
                'label' => 'Hematíes',
                'rango' => '',
                'unidad' => '',
            ],
            'leucocitos' => [
                'label' => 'Leucocitos',
                'rango' => '',
                'unidad' => '',
            ],
            'levaduras' => [
                'label' => 'Levaduras',
                'rango' => '',
                'unidad' => '',
            ]
        ],
        'campos_adicionales' => [
            'resultado_color' => [
                'tipo' => 'textarea',
                'label' => 'Color',
                'placeholder' => 'Ej: Café',
                'required' => true,
            ],
            'resultado_consistencia' => [
                'tipo' => 'textarea',
                'label' => 'Consistencia',
                'placeholder' => 'Ej: Pastosa',
                'required' => true,
            ],
            'resultado_mucus' => [
                'tipo' => 'textarea',
                'label' => 'Mucus',
                'placeholder' => 'Ej: Negativo',
                'required' => true,
            ],
            'resultado_restos_alimenticios_macroscopicos' => [
                'tipo' => 'textarea',
                'label' => 'Restos alimenticios Macroscópicos',
                'placeholder' => 'Ej: Escasos',
                'required' => true,
            ],
            'resultado_restos_alimenticios_microscopicos' => [
                'tipo' => 'textarea',
                'label' => 'Restos alimenticios Microscópicos',
                'placeholder' => 'Ej: Moderados',
                'required' => true,
            ],
            'resultado_metazoarios' => [
                'tipo' => 'textarea',
                'label' => 'Metazoarios',
                'placeholder' => 'Ej: No se observan',
                'required' => true,
            ],
            'resultado_protozoarios_quistes' => [
                'tipo' => 'textarea',
                'label' => 'Protozoarios Quistes',
                'placeholder' => 'Ej: No se observan',
                'required' => true,
            ],
            'resultado_protozoarios_activos' => [
                'tipo' => 'textarea',
                'label' => 'Protozoarios Activos',
                'placeholder' => 'Ej: No se observan',
                'required' => true,
            ],
            'resultado_hematies' => [
                'tipo' => 'textarea',
                'label' => 'Hematíes',
                'placeholder' => 'Ej: 0 - 1 x Campo',
                'required' => true,
            ],
            'resultado_leucocitos' => [
                'tipo' => 'textarea',
                'label' => 'Leucocitos',
                'placeholder' => 'Ej: 1 - 2 x Campo',
                'required' => true,
            ],
            'resultado_levaduras' => [
                'tipo' => 'textarea',
                'label' => 'Levaduras',
                'placeholder' => 'Ej: No se observa',
                'required' => true,
            ]
        ]
    ],

    // Aquí se pueden agregar más plantillas de exámenes
];

