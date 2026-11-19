<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Resultados
 */
class co_resultados_por_area
{

	/**
        Obtiene una lista de Resultados por Área según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}

		$sql = "	SELECT 	sga_resultados_x_area.resultado_area,
							sga_resultados_x_area.ambito,
							sga_resultados_x_area.area_tematica,
							sga_resultados_x_area.resultado,
							sga_resultados_x_area.orden_listado,
							sga_resultados_aprendizaje.nombre as resultado_nombre,
							sga_resultados_areas_tem.nombre as area_tematica_nombre
					FROM 	sga_resultados_x_area,
							sga_resultados_aprendizaje,
							sga_resultados_areas_tem
					WHERE	sga_resultados_x_area.resultado = sga_resultados_aprendizaje.resultado AND
							sga_resultados_x_area.area_tematica = sga_resultados_areas_tem.area_tematica
							$where 
					ORDER BY sga_resultados_areas_tem.nombre
							;"; 
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene los datos de un Resultado por Área
    */
	function get_datos_resultado_por_area($resultado_por_area)
	{
		$resultado_por_area = toba::db()->quote($resultado_por_area);
		$where = " sga_resultados_x_area.resultado_area = $resultado_por_area ";
		$datos = current($this->get_listado($where));
		return $datos;
	}
}

?>