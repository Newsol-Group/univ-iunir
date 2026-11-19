<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Resultados
 */
class co_areas_tematicas
{
	/**
        Obtiene una lista de áreas temáticas según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "	SELECT 	sga_campo_disciplinar.campo_disciplinar,
							sga_campo_disciplinar.nombre,
							sga_campo_disciplinar.descripcion
					FROM 	sga_campo_disciplinar
							$where 
					ORDER BY sga_campo_disciplinar.nombre
							;"; 
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene los datos de un área temática
    */
	function get_datos_area_tematica($area_tem, $dato=null)
	{
		$area_tem = toba::db()->quote($area_tem);
		$where = " sga_resultados_areas_tem.area_tematica = $area_tem ";
		$datos = current($this->get_listado($where));
		return isset($dato) ? $datos[$dato] : $datos;
	}

	/**
        Obtiene el nombre de un área temática
    */
	function get_nombre_area_tematica($area_tem)
	{
		return $this->get_datos_area_tematica($area_tem, 'nombre');
	}
	
	/**
        Retorna a partir de un array de áreas temáticas dado, otro sin elementos repetidos
    */
	function get_datos_areas_tematicas_filtradas($areas_tem)
	{
		$condicion_in = guarani_sql::armar_condicion_in($areas_tem);
		$sql = "	SELECT	sga_resultados_areas_tem.area_tematica,
							sga_resultados_areas_tem.nombre as area_tematica_nombre
					FROM 	sga_resultados_areas_tem
					WHERE	sga_resultados_areas_tem.area_tematica $condicion_in
					ORDER BY sga_resultados_areas_tem.nombre
				";
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de nombres de áreas temáticas según el filtro recibido
    */
	function get_areas_tematicas_nombres($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "SELECT 	sga_campo_disciplinar.campo_disciplinar as valor,
						sga_campo_disciplinar.nombre as descr
  				FROM 	sga_campo_disciplinar
				$where
				ORDER BY sga_campo_disciplinar.nombre
				;";	
		return guarani_db::consultar($sql);
	}
	
	/**
        Valida si un nombre de área temática se puede usar o no.
    */
	function validar_nombre_area_tematica($nombre, $campo_disciplinar)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_campo_disciplinar.campo_disciplinar) as cant
                FROM 	sga_campo_disciplinar
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_campo_disciplinar.nombre', $nombre).'
				';
		
		if (!is_null($campo_disciplinar)) {
			$campo_disciplinar = toba::db()->quote($campo_disciplinar);
			$sql .= " AND sga_campo_disciplinar.campo_disciplinar <> $campo_disciplinar ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
		
	function get_rd_areas()
	{
		$sql = 'SELECT 
					sga_rd_areas.area,
					sga_rd_areas.nombre,
					sga_rd_areas.descripcion
				FROM 
					sga_rd_areas
					ORDER BY sga_rd_areas.nombre';
		
		return guarani_db::consultar($sql);
	}
}

?>