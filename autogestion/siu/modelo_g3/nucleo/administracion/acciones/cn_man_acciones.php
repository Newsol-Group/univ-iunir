<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Acciones
 */
class cn_man_acciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'acciones';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_acciones')->get_listado($filtro);
	}

	function get_listado_requisitos($filtro)
	{
		$req_pres = requisito::tipo_documentales;
		if ($filtro) {
			$filtro .= ' AND ';
		}
		$filtro .= "sga_requisitos_tipos.requisito_tipo NOT IN ($req_pres) ";
		return toba::consulta_php('co_requisitos')->get_listado($filtro);
	}
	
	function get_listado_acciones_operaciones($filtro)
	{
		return toba::consulta_php('co_acciones')->get_acciones_operacion($filtro);			
	}

	function get_listado_operaciones($filtro)
	{
		return toba::consulta_php('co_operaciones')->get_operaciones_menu($filtro);
	}
	
	
}

?>