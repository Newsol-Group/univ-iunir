<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Convenios
 */
class cn_man_convenios extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'convenio';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_convenios')->get_listado($filtro);
	}	

	function get_listado_instituciones($filtro)
	{
		return toba::consulta_php('co_instituciones')->get_listado($filtro);			
	}	
	
	function get_listado_ra($filtro)
	{
		return toba::consulta_php('co_responsables_academicas')->get_listado($filtro);
	}

	function get_listado_prop($filtro, $filtro_ext)
	{
		$propuestas = toba::consulta_php('co_propuestas')->get_propuestas_propias($filtro);
		$propuestas_ext = toba::consulta_php('co_propuestas_ext')->get_listado($filtro_ext);
		return array_merge($propuestas, $propuestas_ext);
	}
	
	function get_listado_actividades($filtro='')
	{
		$filtro .= " AND vw_actividades_plan.estado = '".comunes::estado_activo."'";
		return toba::consulta_php('co_elementos')->get_listado_actividades_convenio($filtro);
	}
}
?>