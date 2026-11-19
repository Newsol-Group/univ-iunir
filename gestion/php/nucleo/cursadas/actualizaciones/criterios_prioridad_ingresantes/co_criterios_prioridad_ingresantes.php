<?php
/**
 * 
 *
 * @package Cursadas
 * @subpackage Actualizaciones
 */
class co_criterios_prioridad_ingresantes
{
	/**
	 * Retorna un listado de criterios de prioridad de ingresantes, según el filtro recibido.
	 *
	 */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}
		$sql = "SELECT 		sga_criterios_prioridad_ing.criterio,
							sga_criterios_prioridad_ing.nombre,
							sga_criterios_prioridad_ing.descripcion,
							sga_criterios_prioridad_ing.activo,
							sga_criterios_prioridad_ing.orden
				FROM 		sga_criterios_prioridad_ing
				$where
				ORDER BY	sga_criterios_prioridad_ing.nombre"; 
      	return guarani_db::consultar($sql);
	}
	
	function get_listado_criterios_activos($where='')
	{
		$filtro = "sga_criterios_prioridad_ing.activo = 1";
		if ($where) {
			$filtro = ' AND '.$where;
		}
		return $this->get_listado($filtro);
	}

	/**
	 * Retorna un listado de criterios activos de prioridad de ingresantes, ordenados dea cuerdo al campo orden.
	 *
	 */
	function get_listado_criterios_activos_orden()
	{
		$sql = "SELECT 		sga_criterios_prioridad_ing.criterio,
							sga_criterios_prioridad_ing.nombre,
							sga_criterios_prioridad_ing.descripcion,
							sga_criterios_prioridad_ing.activo,
							sga_criterios_prioridad_ing.orden,
							sga_criterios_prioridad_ing.criterio_base
				FROM 		sga_criterios_prioridad_ing
				WHERE 		sga_criterios_prioridad_ing.activo = 1
				ORDER BY	sga_criterios_prioridad_ing.orden"; 
      	return guarani_db::consultar($sql);
	}
	
}
?>