<?php
/**
 *
 *
 * @package Matricula
 * @subpackage Actualizaciones
 */
class co_motivos_de_cambio_de_calidad
{
	/**
        Obtiene una lista de motivos de cambio de calidad según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}

		$sql = "	SELECT 	sga_alumnos_motivos_calidad.motivo_calidad,
							sga_alumnos_motivos_calidad.nombre,
							sga_alumnos_motivos_calidad.descripcion,
							sga_alumnos_calidad.nombre as calidad,
							".guarani_sql::get_case('sga_alumnos_motivos_calidad','estado')."
					FROM 	sga_alumnos_motivos_calidad,
							sga_alumnos_calidad
					WHERE 	sga_alumnos_motivos_calidad.calidad = sga_alumnos_calidad.calidad
							$where
					ORDER BY 2
							;";
		return guarani_db::consultar($sql);
	}
	
	/**
        Valida si un nombre de motivo de cambio de calidad se puede usar o no.
    */
	function validar_nombre_motivo_de_cambio_de_calidad($nombre, $motivo)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);		
		$sql = 'SELECT	count(sga_alumnos_motivos_calidad.motivo_calidad) as cant
                FROM 	sga_alumnos_motivos_calidad
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_alumnos_motivos_calidad.nombre', $nombre).'
				';
		
		if (!is_null($motivo)) {
			$motivo = toba::db()->quote($motivo);
			$sql .= " AND sga_alumnos_motivos_calidad.motivo_calidad <> $motivo ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
	/*
	 * El siguiente método se utiliza en el Reporte de alumnos
	 */
	function get_motivos_cambio_calidad_reporte()
	{
		$sql = "SELECT sga_alumnos_motivos_calidad.motivo_calidad, sga_alumnos_motivos_calidad.nombre || ' (' || sga_alumnos_calidad.nombre || ')' as display,
				sga_alumnos_motivos_calidad.nombre,
				sga_alumnos_calidad.nombre as calidad_nombre
				FROM sga_alumnos_motivos_calidad
				JOIN sga_alumnos_calidad ON sga_alumnos_calidad.calidad = sga_alumnos_motivos_calidad.calidad
				WHERE sga_alumnos_motivos_calidad.estado = '".alumno::calidad_activo."'
				ORDER BY sga_alumnos_calidad.nombre, sga_alumnos_motivos_calidad.nombre";
		
		return guarani_db::consultar($sql);
		
	}
}

?>