<?php

namespace siu\modelo;

class planes
{
	// Estados
	const estado_nuevo = 'N';
	const estado_activo = 'A';
	const estado_vigente = 'V';
	const estado_activo_vigente = 'V';
	const estado_activo_no_vigente = 'A';
	const estado_baja = 'B';
	
	// Tipos
	const tipo_convenio = 'Convenio';
	const tipo_personalizado = 'Personalizado';
	const tipo_estructurado = 'Estructurado';
	const tipo_estruct_curso_descr = 'para Administrar Cursos';
	
	// Operación de Copia de Planes/Versiones
	const copia_plan = 'P';
	const copia_version = 'V';
	
	// Código Operación de Administrar Planes.
	const cod_op_admin_planes = '800SIUPRO002';
	const cod_op_asignar_correlativas = '800SIUPRO003';

	// Identificador de la operación Activar Planes.
	const item_activacion_planes = '9000016';
	
	// Sincronización del plan con SQ
	const no_aplica_sincronizado_sq		= 0;
	const sincronizado_sq				= 1;
	const no_sincronizado_sq			= 2;

	// Tipos de resolución
	const resolucion_tipo_ministerial = 'MINISTERIAL';
	const resolucion_tipo_ministerial_descr = 'Resolución Ministerial';
	const resolucion_tipo_coneau = 'CONEAU';
	const resolucion_tipo_coneau_descr = 'Resolución CONEAU';

}