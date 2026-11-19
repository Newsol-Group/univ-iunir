<?php
/**
 *
 *
 * @package 	Matricula
 * @subpackage 	Alumnos
 * 
 */
class co_motivos_licencia
{
	/**
	 * Retorna la lista de motivos de licencia estudiantil según el filtro especificado.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_listado($where='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = "SELECT		sga_licencias_motivos.motivo_licencia,
							sga_licencias_motivos.nombre,
							sga_licencias_motivos.descripcion
				FROM		sga_licencias_motivos
				$where
				ORDER BY	sga_licencias_motivos.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Valida si un nombre de motivo de licencia estudiantil está duplicado.
	 *
	 * @param string $nombre
	 * @param integer $motivo
	 * @return boolean
	 */
	function validar_nombre($nombre, $motivo)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);		
		$sql = 'SELECT	count(sga_licencias_motivos.motivo_licencia) as cant
                FROM 	sga_licencias_motivos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_licencias_motivos.nombre', $nombre).'
				';
		
		if (!is_null($motivo)) {
			$motivo = toba::db()->quote($motivo);
			$sql .= " AND sga_licencias_motivos.motivo_licencia <> $motivo ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);		
	}
}
?>