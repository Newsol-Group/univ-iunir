<?php
/**
 *
 *
 * @package Calendario
 * @subpackage Períodos Lectivos
 */
class cn_man_periodos_lectivos extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'periodo';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_periodos_lectivos')->get_listado($filtro);
	}
	
	function get_proceso_aplanado()
	{
		return 'proceso_aplanar_periodo_inscripcion';
	}
}

?>