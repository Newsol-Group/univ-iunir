<?php
/**
 *	
 *
 * @package Calendario
 * @subpackage Períodos Lectivos
 */
class cn_man_periodos_lectivos_tipo extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'periodo_lectivo_tipo';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_periodos_lectivos')->get_listado_tipos($filtro);
	}
}

?>