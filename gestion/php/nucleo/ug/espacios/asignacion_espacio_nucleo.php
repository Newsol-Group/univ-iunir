<?php
/**
 * Constantes para Asignaciones de Espacios
 *
 * @package Unidades de Gestión
 * @subpackage Espacios
 */
class asignacion_espacio_nucleo
{	
	// Tipos de asignaciones de espacios.
	const tipo_comision = 1;
	const tipo_comision_descripcion = 'Comision';
	
	const tipo_agente_externo = 2;
	const tipo_agente_externo_descripcion = 'Agente externo';
	
	const tipo_examen = 3;
	const tipo_examen_descripcion = 'Examen';
	
	// Estados de asignaciones de espacios.
	const estado_confirmada = 'C';
	const estado_confirmada_descripcion = 'Confirmada';
	
	const estado_provisoria = 'P';
	const estado_provisoria_descripcion = 'Provisoria';
}
?>