<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Operación
 * 
 */
class cn_man_conf_requisitos_x_operacion extends cn_manejador_entidad
{
	
	function get_indice_instancia() 
	{ 
		return 'requisitos_x_operacion'; 
	}

	function get_listado_preseleccion($filtro)
	{
		$datos = toba::consulta_php('co_requisitos_x_operacion')->get_listado($filtro);
		return $datos;
	}

	function get_info_operacion($seleccion)
	{
		$filtro = "sga_acciones_operacion.operacion = '".$seleccion['operacion']."'";
		$datos = current(toba::consulta_php('co_requisitos_x_operacion')->get_listado($filtro));
		return $datos;
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_requisitos_x_operacion')->get_requisitos_x_accion_operacion($filtro);
	}
	
}

?>