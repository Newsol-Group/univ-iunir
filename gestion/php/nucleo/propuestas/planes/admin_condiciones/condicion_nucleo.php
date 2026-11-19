<?php
/**
 * Catálogo de Condiciones
 *
 * @package Propuestas
 * @subpackage Plan
 */
class condicion_nucleo
{
	// Tipos de Requisitos
	const tipo_cursar = 1;
	const tipo_rendir = 2;
	const tipo_certificado = 3;

	// Tipos de Condicion_requisito
	const requisito_entidad = 'E';
	const requisito_requisito = 'R';

	// Origen
	const origen_interno = 'I';
	const origen_externo = 'E';

	static function get_condiciones_correlativas()
	{
		return array(condicion::tipo_cursar, condicion::tipo_rendir);
	}
}
?>