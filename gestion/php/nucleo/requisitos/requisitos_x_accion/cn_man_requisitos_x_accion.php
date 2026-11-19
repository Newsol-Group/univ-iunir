<?php 
/**
 * Representa las restricciones aplicadas para realizar una Acción.
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Acción
 * 
 */
class cn_man_requisitos_x_accion extends cn_manejador_entidad
{
	
	function get_indice_instancia()
	{
		return 'requisito_por_accion';
	}

	function get_proceso_aplanado()
	{
		return 'proceso_aplanado_requisitos_x_accion';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_acciones')->get_listado($filtro);
	}
	
}
?>