<?php
/* 
 * Agregar los usuarios correspondientes que coincidan con los configurados 
 * en el archivo "instalacion/i__desarrollo/p__guarani/rest/servidor_usuarios.ini"
 */

$usuarios_admin = ['admin'];
$usuarios_tehuelche = ['tehuelche'];
$usuarios_sq = ['sq'];
$usuarios_pilaga = ['pilaga'];
$usuarios_3w = ['3w'];
$usuarios_sidcer = ['sidcer'];
$usuarios_verificador = ['verificador'];
$usuarios_preinscriptos = ['preinscriptos'];
$usuarios_sube = ['sube'];

$todos = array_merge($usuarios_tehuelche, $usuarios_sq, $usuarios_pilaga ,$usuarios_3w, $usuarios_sidcer, $usuarios_verificador, $usuarios_preinscriptos);

$recursos_usuarios = [

/*
 * Ejemplos de uso
 * 
//alumnos
	'alumnos' => [
		'GET' => ['usuarios' => array_merge($usuarios_sq, $usuarios_sidcer)] // Solo podrán acceder los usuarios de SQ y Sidcer al endpoint GET /alumnos
	],

//notificaciones-pagos
	'notificaciones-pagos' => [
		'POST' => ['usuarios' => array_merge($todos)] // Todos los usuarios podrán acceder al endpoint POST /notificaciones-pagos
	],
*/

//api-docs
	'api-docs' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $todos)]
	],
	
//status
	'status' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $todos)]
	],
	
//info
	'info' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $todos)]
	],
	
//alumnos
	'alumnos' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)]
	],
	'alumnos/id/certificados/id' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq, $usuarios_3w)]
	],

//convocatorias
	'convocatorias' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_tehuelche)]
	],
	'convocatorias/id' => [
		'PUT' => ['usuarios' => array_merge($usuarios_admin, $usuarios_tehuelche)]
	],
	'convocatorias/id/aspirantes' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_tehuelche)]
	],
	'convocatorias/id/definicion' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_tehuelche)]
	],

//cursos	
	'cursos' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)],
		'POST' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)]
	],
	'cursos/id' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)],
		'POST' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)],
		'DELETE' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)]
	],
	'cursos/id/alumnos' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)]
	],
	'cursos/id/comisiones/id' => [
		'PUT' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)],
		'DELETE' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)]
	],

//inscripciones
	'inscripciones' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq, $usuarios_verificador)]
	],
	'inscripciones/id' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)]
	],

//inscripciones-mesas
	'inscripciones-mesas' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_verificador)]
	],

//morosos
	'morosos' => [
		'POST' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)]
	],

//notificaciones-pagos
	'notificaciones-pagos' => [
		'POST' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)]
	],


//oferta-cursos
	'oferta-cursos' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin)]
	],


//comisiones
	'comisiones' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin)]
	],
	'comisiones/id' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin)]
	],
	'comisiones/id/docentes' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin)]
	],

//docentes
	'docentes' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_verificador)]
	],
	'docentes/id' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_verificador)]
	],
	'docentes/id/comisiones' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_verificador)]
	],
	'docentes/id/mesas' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_verificador)]
	],

//personas
	'personas' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sidcer, $usuarios_pilaga)]
	],
	'personas/id/datosanalitico' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sidcer)]
	],
	'personas/id/datospersonales' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sidcer, $usuarios_pilaga)]
	],
	'personas/id/agenda' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin)]
	],

//propuestas-formativas
	'propuestas-formativas' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_tehuelche)]
	],
	'propuestas-formativas/id' => [
		'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_tehuelche)]
	],

//Preinscriptos
    'preinscriptos' => [
        'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_preinscriptos)]
    ],
    'preinscriptos/id/agenda' => [
        'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_preinscriptos)]
    ],
	
//planes-versiones
    'planes-versiones' => [
        'GET' => ['usuarios' => array_merge($usuarios_admin)]
    ],
    'planes-versiones/id/actividades' => [
        'GET' => ['usuarios' => array_merge($usuarios_admin)]
    ],

//licencias-estudiantiles
    'licencias-estudiantiles' => [
        'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)]
    ],

//alumnos-no-regulares
    'alumnos-no-regulares' => [
        'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)]
    ],

//alumnos-pasivos
    'alumnos-pasivos' => [
        'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)]
    ],

//alumnos-cambios-calidades
    'alumnos-cambios-calidades' => [
        'GET' => ['usuarios' => array_merge($usuarios_admin, $usuarios_sq)]
    ],
	
//reaperturas-convocatorias
    'reaperturas-convocatorias' => [
        'POST' => ['usuarios' => array_merge($usuarios_admin, $usuarios_tehuelche)]
    ],

];

return $recursos_usuarios;