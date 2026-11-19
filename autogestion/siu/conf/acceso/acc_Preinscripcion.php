<?php
return [
	'id' => 'Preinscripcion',
	'parametros' => [
		'index' => 'preinscripcion'
	],
	'operaciones' => [
		'acceso' => ['activa' => true,],
		// TRAMITES
		'preinscripcion' => [
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
		'configuracion' => ['activa' => true,],
		'mensajes' => ['activa' => true,],
	]
];