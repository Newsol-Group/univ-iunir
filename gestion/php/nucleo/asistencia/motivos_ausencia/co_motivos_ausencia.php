<?php
/**
 *
 *
 * @package 	Asistencia
 * @subpackage 	-
 * 
 */
class co_motivos_ausencia
{
	
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}

		$sql = "SELECT 
					sga_motivos_ausencias.motivo_ausencia,
					sga_motivos_ausencias.nombre,
					sga_motivos_ausencias.descripcion
				FROM 
					sga_motivos_ausencias
				$where
				ORDER BY
					sga_motivos_ausencias.nombre
				";
		
		return guarani_db::consultar($sql);
	}	
	
	/**
	 * Obtiene el nombre asociado a un id de motivo de ausencia dado.
	 * @param int $motivo motivo de ausencia para el cual se desea recuperar el nombre.
	 */
	function get_motivo_ausencia_nombre($motivo)
	{
		$motivo = toba::db()->quote($motivo);
		$filtro = "sga_motivos_ausencias.motivo_ausencia = $motivo";
		$datos = $this->get_listado($filtro);
		if (!empty($datos)) {
			return $datos[0]['nombre'];
		}
	}
	
	/**
     *  Valida si un nombre de motivo de ausencia se puede usar o no.
     */
	function validar_nombre_motivo_ausencia($nombre, $motivo)
	{
		if (empty($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count (sga_motivos_ausencias.motivo_ausencia) as cant
                FROM 	sga_motivos_ausencias
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_motivos_ausencias.nombre', $nombre).'
				';
		
		if (!is_null($motivo)) {
			$motivo = toba::db()->quote($motivo);
			$sql .= " AND sga_motivos_ausencias.motivo_ausencia <> $motivo ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
}
?>