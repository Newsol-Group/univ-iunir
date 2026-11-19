<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class co_motivos_cambio_ubicacion
{
	
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}

		$sql = 'SELECT 	sga_alumnos_motivos_ubicacion.motivo_ubicacion,
						sga_alumnos_motivos_ubicacion.nombre,
						sga_alumnos_motivos_ubicacion.descripcion,
			   '.guarani_sql::get_case('sga_alumnos_motivos_ubicacion', 'estado')."
				FROM 	sga_alumnos_motivos_ubicacion
				$where
				ORDER BY sga_alumnos_motivos_ubicacion.nombre
				";	

		return guarani_db::consultar($sql);
	}	
	
	/**
     *  Valida si un nombre de Motivo de Cambio de Ubicación se puede usar o no.
     */
	function validar_nombre_motivo_cambio_ubicacion($nombre, $motivo)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);		
		$sql = 'SELECT	count(sga_alumnos_motivos_ubicacion.motivo_ubicacion) as cant
                FROM 	sga_alumnos_motivos_ubicacion
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_alumnos_motivos_ubicacion.nombre', $nombre).'
				';
		
		if (!is_null($motivo)) {
			$motivo = toba::db()->quote($motivo);
			$sql .= " AND sga_alumnos_motivos_ubicacion.motivo_ubicacion <> $motivo ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);		
	}
}
?>