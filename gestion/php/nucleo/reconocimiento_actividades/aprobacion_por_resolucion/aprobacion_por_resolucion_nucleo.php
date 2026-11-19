<?php
/**
 * Constantes para Aprobación por Resolución
 *
 * @package Reconocimiento de Actividades
 * @subpackage Aprobación por Resolución
 */
class aprobacion_por_resolucion_nucleo
{	
	// Tipos de Actividades que se pueden reconocer
	const ayudantia 		= 'A';
	const curso 			= 'C';
	const investigacion 		= 'I';
	const pasantia			= 'P';
	const trabajo_profesional	= 'R';
	const seminario		= 'S';
	const tutoria			= 'T';
	
	// Tipos de Reconocimientos
	const activ_modulos = 'AM';		// La aprobación por resolución reconoce actividades, módulos y genéricas.
	const cred_globales = 'CG';		// La aprobación por resolución reconoce créditos globales para el título.
	const cred_globales_desc	= 'Créditos Globales';
	const activ_modulos_desc	= 'Actividades/Modulos/Genéricas';
	
	// Estados de Trámites de Aprobación por Resolución
	const estado_abierto		= 'A';
	const estado_anulado		= 'B';
	const estado_cerrado		= 'C';

	const estado_abierto_desc	= 'Abierto';
	const estado_anulado_desc	= 'Anulado';
	const estado_cerrado_desc	= 'Cerrado';
	
	//Estado de reconocimiento de las actividades
	const estado_actividad_invalidado	= 'I';
	const estado_actividad_activo	= 'A';
	const estado_actividad_baja	= 'B';
	

	// Reconocimiento de la actividad/modulo
	const rec_total_desc	= 'Total';
	const rec_parcial_desc	= 'Parcial';
	
}
?>
