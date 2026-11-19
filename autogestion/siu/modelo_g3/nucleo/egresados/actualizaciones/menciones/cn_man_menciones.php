<?php
/**
 *
 *
 * @package Egresados
 * @subpackage Actualizaciones
 */
class cn_man_menciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'menciones';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_menciones')->get_listado($filtro);			
	}
}
?>