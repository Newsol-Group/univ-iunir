<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Resultados
 */
class cn_man_areas_tematicas extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'area_tematica';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_areas_tematicas')->get_listado($filtro);			
	}
}

?>