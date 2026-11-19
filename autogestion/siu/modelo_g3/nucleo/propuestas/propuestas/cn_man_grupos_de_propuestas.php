<?php 
/**
 *
 *
 * @package Propuestas
 * @subpackage Propuestas
 */
class cn_man_grupos_de_propuestas extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'grupo_propuesta';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_propuestas')->get_propuestas_grupos($filtro);			
	}
	
	function get_listado_propuestas($filtro)
	{
		return toba::consulta_php('co_propuestas')->get_listado($filtro);			
	}
}
?>