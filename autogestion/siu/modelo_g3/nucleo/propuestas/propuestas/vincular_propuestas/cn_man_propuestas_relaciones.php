<?php
/**
 * 
 * @package Propuestas Formativas
 */
class cn_man_propuestas_relaciones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'propuestas_relacion';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_propuestas_relaciones')->get_listado($filtro);
	}

	function get_listado_propuestas($filtro)
	{
		return toba::consulta_php('co_propuestas_relaciones')->get_listado_propuestas($filtro);
	}

	function get_listado_planes_propuesta($propuesta, $filtro)
	{
		return toba::consulta_php('co_propuestas_relaciones')->get_listado_planes_propuesta($propuesta, $filtro);
	}
}
?>