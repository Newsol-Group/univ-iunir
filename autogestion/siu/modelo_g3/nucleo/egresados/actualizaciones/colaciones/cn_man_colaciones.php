<?php
/**
 *
 *
 * @package Egresados
 * @subpackage Actualizaciones
 */
class cn_man_colaciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'colaciones';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_colaciones')->get_listado($filtro);			
	}
}

?>