<?php
/**
 * Catalogo de constantes para Períodos de Inscripción
 *
 * @package Calendario
 * @subpackage Períodos de Inscripción
 */

class periodo_de_inscripcion_nucleo
{
	// Tipos de Períodos de Inscripción a Propuesta.
	const tipo_inscripcion = 1;
	const tipo_reinscripcion = 2;
	const tipo_todos = 3;
	const tipo_preinscripcion = 4;
	const tipo_solicitud_readmision = 5;

	// Tipos de alumno.
	const tipo_alumno_todos = 'T';
	const tipo_alumno_ingresantes = 'I';
	const tipo_alumno_reinscriptos = 'R';

	// Tipos de cursado.
	const tipo_cursado_todos = 'T';
	const tipo_cursado_primera_vez = '1';
	const tipo_cursado_recursantes = 'R';
}
?>