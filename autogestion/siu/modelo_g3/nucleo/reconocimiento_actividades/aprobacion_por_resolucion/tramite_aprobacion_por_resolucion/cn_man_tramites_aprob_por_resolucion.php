<?php
/**
 *
 * 
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Aprobación por Resolución
 * 
 */
class cn_man_tramites_aprob_por_resolucion extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'tramite_aprobacion_por_resolucion';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_aprobacion_por_resolucion')->get_listado($filtro);
	}
}
?>