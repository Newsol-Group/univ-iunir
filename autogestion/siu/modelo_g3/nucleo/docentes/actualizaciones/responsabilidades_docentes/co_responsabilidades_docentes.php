<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Actualizaciones
 */
class co_responsabilidades_docentes
{
	function get_listado($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "	SELECT 	sga_docentes_responsabilidades.responsabilidad,
							sga_docentes_responsabilidades.nombre, 
							sga_docentes_responsabilidades.descripcion,
							".guarani_sql::get_case_si_no('sga_docentes_responsabilidades', 'firma').",
							sga_docentes_responsabilidades.orden_listado,
							sga_docentes_responsabilidades.orden_acta
					FROM 	sga_docentes_responsabilidades
							$where 
					ORDER BY 2
							;"; 
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de las responsabilidades que un docente puede tener en una cátedra
    */
	function get_responsabilidades($id=null)
	{
		$where = '';
		if ($id != null) {
			$id = toba::db()->quote($id);
			$where = " WHERE sga_docentes_responsabilidades.responsabilidad = $id";
		}
	   	$sql = "SELECT		sga_docentes_responsabilidades.responsabilidad as valor,
	   						sga_docentes_responsabilidades.nombre as descr,
							sga_docentes_responsabilidades.firma
				FROM 		sga_docentes_responsabilidades				
							$where
	   			ORDER BY	sga_docentes_responsabilidades.nombre
							;";
		return guarani_db::consultar($sql);
	}
	
	/**
        Valida si un nombre de responsabilidad de cátedra se puede usar o no.
    */
	function validar_nombre_responsabilidad_docente($nombre, $responsabilidad)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_docentes_responsabilidades.responsabilidad) as cant
                FROM 	sga_docentes_responsabilidades
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_docentes_responsabilidades.nombre', $nombre).'
				';
		
		if (!is_null($responsabilidad)) {
			$responsabilidad = toba::db()->quote($responsabilidad);
			$sql .= " AND sga_docentes_responsabilidades.responsabilidad <> $responsabilidad ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
}

?>