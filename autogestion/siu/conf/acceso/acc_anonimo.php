<?php
return [
    'id' => 'anonimo',
    'parametros' => [
        'index' => 'acceso',
        'login' => 'acceso'
    ],
    'operaciones' => [

        'acceso' => [
            'activa' => true,
            'menu' => [
                'visible' => true
            ]
        ],

        'fecha_examen' => [
            'activa' => true,
            'menu' => [
                'visible' => true
            ]
        ],

        'horarios_cursadas' => [
            'activa' => true,
            'menu' => [
                'visible' => true
            ]
        ],
		
		'alta_usuario_cursos' => [
            'activa' => true,
            'menu' => [
                'visible' => false
            ]
        ],

        'pruebas_stress' => [
            'activa' => true,
            'menu' => [
                'visible' => false
            ]
        ],

        'validador_certificados' => [
            'activa' => true,
            'menu' => [
                'visible' => true
            ]
        ],
        
        'ayuda_menu' => [
            'activa' => true,
            'menu' => [
                'visible' => true
            ]
        ],
        
        'saml' => ['activa' => true],
		
        'notificador' => [ 'activa' => true, ],
    ]
];