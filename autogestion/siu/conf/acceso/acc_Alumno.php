<?php
return [
    'id' => 'Alumno',
    'parametros' => [
        'index' => 'inicio_alumno'
    ],
    'operaciones' => [
        'acceso' => [ 'activa' => true, ],

        'cursada' => [
            'activa' => true,
            'menu' => [
                'visible' => true
            ]
        ],

        'examen' => [
            'activa' => true,
            'menu' => [
                'visible' => true
            ]
        ],
        // REPORTES
        'actuacion_provisoria' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],
		'calendario' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],
        'encuestas_kolla' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'encuestas',
                'visible' => true
            ]
        ],
        'encuestas_respondidas' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'encuestas',
                'visible' => true
            ]
        ],
        'historia_academica' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],
        'reporte_inasistencias' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],
        'inscripciones' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],
		'cronograma_evaluaciones' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],
        'oferta_comisiones' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],
        'plan_estudio' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],
        'estado_titulo' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],
		
		'solicitudes_sidcer' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],
		
        'seguimiento_tesis' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],		
        'reporte_readmisiones' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],
		 'porcentaje_avance_carrera' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'consultas',
                'visible' => true
            ]
        ],

        // TRAMITES
        'orientacion' => [
                'activa' => true,
                'menu' => [
                        'submenu' => 'tramites',
                        'visible' => true
                ]
        ],
        'solicitudes' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'tramites',
                'visible' => true
            ]
        ],
		
		'preinscripcion' => [
            'activa' => true,
            'menu' => [
				'submenu' => 'tramites',
                'visible' => true
            ]
        ],

		'solicitar_certificacion' => [
            'activa' => true,
            'menu' => [
				'submenu' => 'tramites',
                'visible' => true
            ]
        ],
		
		'reasignar_optativa' => [
            'activa' => true,
            'menu' => [
				'submenu' => 'tramites',
                'visible' => true
            ]
        ],
		
		'portal_pagos' => [
            'activa' => true,
            'menu' => [
				'submenu' => 'tramites',
                'visible' => true
            ]
        ],
		
		'convocatorias_becas' => [
            'activa' => true,
            'menu' => [
				'submenu' => 'tramites',
                'visible' => true
            ]
        ],

		'diploma_digital' => [
            'activa' => true,
            'menu' => [
				'submenu' => 'tramites',
                'visible' => true
            ]
        ],
				
        'configuracion' => [ 'activa' => true, ],
        'controles' => [ 'activa' => true, ],

        'inicio_alumno' => [ 'activa' => true, ],
        'mensajes' => [ 'activa' => true, ],
        'requisitos_ingreso' => [ 'activa' => true, ],
        'reinscripcion' => [ 'activa' => true, ],
        'readmision' => [ 'activa' => true, ],
        
        'notificador' => [ 'activa' => true, ],
        
        'validador_certificados' => [
            'activa' => true,
        ],
        
        'boleto_estudiantil' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'tramites',
                'visible' => true
            ]
        ],
		'datos_censales' => [
            'activa' => true,
            'menu' => [
                'visible' => true
            ]
        ],

        'requisitos_becas' => ['activa' => true],
    ]
];
