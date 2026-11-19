<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class cn_man_actividad extends cn_man_elemento
{
	function get_indice_instancia()
	{
		return 'actividad';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_elementos')->get_listado(elemento::tipo_actividad, null, $filtro);
	}
	
}
?>