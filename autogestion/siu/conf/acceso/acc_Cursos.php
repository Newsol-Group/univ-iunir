<?php
use siu\modelo\entidades\parametro;
return [
    'id' => 'Cursos',
    'parametros' => [
        'index' => 'inscripcion_cursos'
    ],
    'operaciones' => [
        'inscripcion_cursos' => [
            'activa' => true,
            'menu' => [
                'visible' => true
            ]
        ],
		'inscripcion_examen'  => [
            'activa' => true,
            'menu' => [
                'visible' => true
            ]
        ],
		'configuracion' => [
            'activa' => true
        ],
        'acceso' => [
            'activa' => true
        ],
        'mensajes' => [
            'activa' => true
        ],


        'constancias_cursos' => [
            'activa' => true,
            'menu' => [
                'submenu' => 'tramites',
                'visible' => true
            ]
        ],
		
        'preinscripcion' => [
            'activa' => (parametro::get_valor('pre_preinsc_alumnos_solo_cursos') == 'A'),
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
		
		'calendario' => [
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
		
        'notificador' => [ 'activa' => true, ],
		
		'listado_cursos' => [ 
			'activa' => true,
			 'menu' => [
                'submenu' => 'consultas',
                'visible' => true
			]
		],
		
		'validador_certificados' => [
			'activa' => true,
		],
		'datos_censales' => [
				'activa' => true,
				'menu' => [
					'visible' => true
				]
			],
	]
];