<?php
/**
 *
 *
 * @package 	Unidades de Gestión
 * @subpackage 	Unidades de Gestión
 * 
 */
class cn_man_unidades_gestion extends cn_manejador_entidad
{
	
	function get_indice_instancia()
	{
		return 'unidad_gestion';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_unidades_gestion')->get_listado($filtro);
	}
	
}
?>