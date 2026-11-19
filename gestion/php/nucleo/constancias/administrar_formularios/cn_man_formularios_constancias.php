<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Formularios
 * 
 */
class cn_man_formularios_constancias extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'formulario_constancia';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_constancias')->get_constancias_formularios($filtro);			
	}

}
?>