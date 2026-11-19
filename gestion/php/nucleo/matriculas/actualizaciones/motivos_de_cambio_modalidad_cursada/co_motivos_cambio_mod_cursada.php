<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class co_motivos_cambio_mod_cursada
{
	
	function get_listado($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = 'SELECT	sga_alumnos_motivos_modalidad.motivo_modalidad,
						sga_alumnos_motivos_modalidad.nombre,
					  	sga_alumnos_motivos_modalidad.descripcion,
				'.guarani_sql::get_case('sga_alumnos_motivos_modalidad', 'estado')."
				FROM 	sga_alumnos_motivos_modalidad
				$where
				ORDER BY sga_alumnos_motivos_modalidad.nombre
				";
		
		return guarani_db::consultar($sql);
	}	
	
	/**
     *  Valida si un nombre de motivo de CAMBIO DE MODALIDAD se puede usar o no.
     */
	function validar_nombre_motivo_de_cambio_de_modalidad($nombre, $motivo_modalidad)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_alumnos_motivos_modalidad.motivo_modalidad) as cant
                FROM 	sga_alumnos_motivos_modalidad
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_alumnos_motivos_modalidad.nombre', $nombre).'
				';
		
		if (!is_null($motivo_modalidad)) {
			$motivo_modalidad = toba::db()->quote($motivo_modalidad);
			$sql .= " AND sga_alumnos_motivos_modalidad.motivo_modalidad <> $motivo_modalidad ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
}
?>