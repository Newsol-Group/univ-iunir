<?php
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Actualizaciones
 * 
 */
class cn_man_categorias_actividades extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'categorias_actividades';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_categorias_actividades')->get_listado($filtro);
	}
}
?>