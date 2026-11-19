<?php
/**
 *
 *
 * @package	Tesis
 *
 */
class tesis_nucleo
{
	//Tipos de Tesis
	const tipo_por_actividad = 'A';
	const tipo_por_modulo 	 = 'M';
	
	//Estados de Tesis
	const estado_pendiente_entrega			= 20;
	const estado_entregado_a_tribunal_doc	= 21;
	const estado_aprobado_por_tribunal_doc	= 22;
	const estado_observado_por_tribunal_doc	= 23;
	const estado_rechazado_po_tribunal_doc	= 24;
	const estado_listo_para_defensa			= 25;
	const estado_tesis_aprobada				= 26;
	const estado_tesis_desaprobada			= 27;

	//Quita los saltos de linea que generan error.
	const expr_regular						= "/[\r\n|\n|\r]+/";
	
}
?>