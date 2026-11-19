<?php
/**
 * Constantes para Circuitos de Trámite
 *
 * @package Administración
 * 
 */
class circuito_tramite_nucleo
{	
	//Tipos de Circuito de Trámite
	const tipo_egreso = 'Egreso';
	const tipo_tesis = 'Tesis';
	
	// Estados de la tabla 'mce_estados'
	const NO_HAY_ESTADO_ANTERIOR = 1;
	const ADELANTADO = 2;
	const OBSERVADO = 3;
	const AREA_TITULOS = 4;
	const SEC_ACADEMICA = 5;
	const SEC_GRAL_ACADEMICA = 6;
	const RECTORADO = 7;
	const DIPLOMADO = 8;
	const SIN_ESTADO_ANTERIOR = 98;
	const SIN_ESTADO_NUEVO = 99;
	
}
?>