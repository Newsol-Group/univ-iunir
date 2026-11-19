<?php
/**
 * 
 * 
 * @package 	Calendario Académico
 * @subpackage 	Actualizaciones
 *
 */
class co_motivos_invalidacion_clases 
{
	
	/**
	 * Retorna el listado de Motivo de Invalidación de Clase 
	 *
	 * @param string $where filtro sobre los tipos de clase a retornar.
	 * @return array
	 */
	function get_listado($where='')
	{
		if (!empty($where)) {
			$where = " WHERE $where";
		}
		
		$sql = "SELECT 		sga_clases_motivos_inval.motivo_invalidacion,
							sga_clases_motivos_inval.nombre,
							sga_clases_motivos_inval.descripcion
				FROM 		sga_clases_motivos_inval 
				$where
				ORDER BY	sga_clases_motivos_inval.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
     *  Valida si un nombre de Motivo de Invalidación de Clase se puede usar o no.
     */
	function validar_nombre($nombre, $motivo_invalidacion)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);		
		$sql = 'SELECT	count(sga_clases_motivos_inval.motivo_invalidacion) as cant
                FROM 	sga_clases_motivos_inval
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_clases_motivos_inval.nombre', $nombre).'
				';
		
		if (!is_null($motivo_invalidacion)) {
			$motivo_invalidacion = toba::db()->quote($motivo_invalidacion);
			$sql .= " AND sga_clases_motivos_inval.motivo_invalidacion <> $motivo_invalidacion ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);		
	}
	
}
?>