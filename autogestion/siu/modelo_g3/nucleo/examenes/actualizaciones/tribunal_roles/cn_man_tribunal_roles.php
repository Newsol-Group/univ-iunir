<?php
/**
 *
 *
 * @package Egresados
 * @subpackage Actualizaciones
 */
class cn_man_tribunal_roles extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'tribunal_rol';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_tribunal_roles')->get_listado($filtro);			
	}
}

?>