<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Constancias
 * 
 */
class cn_man_constancias extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'constancia';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_constancias')->get_listado_constancias($filtro);			
	}

}
?>