<?php 
/**
 * 
 *
 *
 * @package Propuestas
 * @subpackage ValidadorPlanes
 */
class cn_man_abm_validador extends cn_manejador_entidad
{
	
	function get_indice_instancia()
	{
		return 'validador_planes';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_casos_testeo')->get_listado($filtro);
	}
}
?>