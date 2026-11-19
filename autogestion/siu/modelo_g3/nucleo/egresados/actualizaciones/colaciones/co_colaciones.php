<?php
/**
 *
 *
 * @package Egresados
 * @subpackage Actualizaciones
 */
class co_colaciones
{
	function get_listado($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "	SELECT 	sga_colaciones.colacion,
							sga_colaciones.nombre, 
							sga_colaciones.fecha,
							sga_colaciones.localidad,
							sga_colaciones.calle,
							sga_colaciones.numero,
							sga_colaciones.descripcion
					FROM 	sga_colaciones
							$where
					ORDER BY 2
							;"; 
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de colaciones, usando la fecha como descripción.
	 */
	function get_colaciones()
	{
		$sql = "SELECT	sga_colaciones.colacion as valor,
						to_char(sga_colaciones.fecha, '".comunes::formato_fecha_visual."') as descr,
						sga_colaciones.nombre as nombre,
						sga_colaciones.descripcion as descripcion
				FROM	sga_colaciones
				ORDER BY	sga_colaciones.fecha DESC
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de colaciones para una propuesta (todas o futuras) ordenadas por fecha en forma descendente
	 *
	 * @param integer $propuesta
	 * @param boolean $colaciones_futuras
 	 * @return array
	 */
	function get_colaciones_una_propuesta($propuesta, $colaciones_futuras = true)
	{
		$filtro_fecha = '';
		if ($colaciones_futuras) {
			$filtro_fecha = " AND sga_colaciones.fecha >= CURRENT_DATE ";
		}
			
		$sql = "SELECT	sga_colaciones.colacion as valor,
						to_char(sga_colaciones.fecha, '".comunes::formato_fecha_visual."') || ' - ' || sga_colaciones.nombre as descr,
						sga_colaciones.nombre as nombre,
						sga_colaciones.descripcion as descripcion
				FROM	sga_colaciones
				JOIN	sga_colaciones_propuestas ON sga_colaciones_propuestas.colacion = sga_colaciones.colacion
				WHERE	sga_colaciones_propuestas.propuesta = $propuesta
				$filtro_fecha
				ORDER BY	sga_colaciones.fecha DESC
		";		
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de colaciones para una responsable académica ordenadas por fecha en forma descendente.
	 *
	 * @param integer $responsable_academica
 	 * @return array
	 */
	function get_colaciones_responsable_academica($responsable_academica)
	{
		$sql = "SELECT		sga_colaciones.colacion as valor,
							to_char(sga_colaciones.fecha, '".comunes::formato_fecha_visual."') || ' - ' || sga_colaciones.nombre as descr
				FROM		sga_colaciones
							JOIN sga_colaciones_propuestas ON sga_colaciones.colacion = sga_colaciones_propuestas.colacion
							JOIN sga_propuestas_ra ON sga_colaciones_propuestas.propuesta = sga_propuestas_ra.propuesta
				WHERE		sga_propuestas_ra.responsable_academica = $responsable_academica
				ORDER BY	sga_colaciones.fecha DESC
		";		
		return guarani_db::consultar($sql);
	}

	/**
        Valida si un nombre de colación se puede usar o no.
    */
	function validar_nombre_colacion($nombre, $colacion)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_colaciones.colacion) as cant
                FROM 	sga_colaciones
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_colaciones.nombre', $nombre).'
				';
		
		if (!is_null($colacion)) {
			$colacion = toba::db()->quote($colacion);
			$sql .= " AND sga_colaciones.colacion <> $colacion ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
}

?>