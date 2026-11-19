<?php 
/**
 *
 *
 * @package Responsables Académicas
 * @subpackage Sedes
 */
class cn_man_sede extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'sede';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_sedes')->get_listado($filtro);
	}	
}
?>