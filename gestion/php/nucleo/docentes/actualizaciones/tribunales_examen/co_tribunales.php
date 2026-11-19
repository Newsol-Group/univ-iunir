<?php
/**
 * @package Docentes
 * @subpackage Actualizaciones
 */
class co_tribunales
{
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}

		$sql = "SELECT 		sga_tribunal_examen.tribunal,
							sga_tribunal_examen.nombre,
							sga_tribunal_examen.estado
				FROM 		sga_tribunal_examen
				$where
				ORDER BY	sga_tribunal_examen.nombre"; 
		
		return guarani_db::consultar($sql);
	}

	function get_tribunales_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$where_gatillo = 'true';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_tribunal_actividades', 'sga_tribunal_actividades');
		}
		$sql = "SELECT		sga_tribunal_examen.tribunal,
							sga_tribunal_examen.nombre,
							sga_tribunal_examen.estado
				FROM		sga_tribunal_examen
							LEFT JOIN sga_tribunal_actividades ON sga_tribunal_examen.tribunal = sga_tribunal_actividades.tribunal
				WHERE		-- Tribunales sin actividades.
							(sga_tribunal_actividades.elemento IS NULL OR
							-- Tribunales con actividades filtradas por perfil de datos.
							$where_gatillo)
							$where
				GROUP BY	sga_tribunal_examen.tribunal
				ORDER BY	sga_tribunal_examen.nombre
		";
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Retorna todos los ids y nombres de los tribunales de exámen activos definidos para una actividad dada, según 
	 * el filtro dado.
	 *
	 * @param integer $actividad	id de la actividad para la cual se quieren recuperar los tribunales asociados.
	 * @param str	  $where		filtro sobre los tribunales a recuperar.
	 */
	function get_tribunales_activos_para_actividad($actividad, $where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$actividad = toba::db()->quote($actividad);
		
		$sql = "SELECT		sga_tribunal_actividades.tribunal,
							sga_tribunal_actividades.elemento,
							sga_tribunal_actividades.estado,
							sga_tribunal_examen.nombre
				FROM		sga_tribunal_actividades,
							sga_tribunal_examen
				WHERE		sga_tribunal_actividades.tribunal = sga_tribunal_examen.tribunal AND
							sga_tribunal_actividades.elemento = $actividad AND
							sga_tribunal_actividades.estado = '" . comunes::estado_activo . "' AND
							sga_tribunal_examen.estado = '" . comunes::estado_activo . "'
							$where
				ORDER BY	sga_tribunal_examen.nombre
				;";
		
		return  guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos de todos los docentes activos del tribunal pasado como parámetro y sus roles, según el filtro dado.
	 *
	 * @param str $where filtro sobre los docentes a retornar.
	 */
	function get_docentes_activos_tribunal($tribunal, $where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$tribunal = toba::db()->quote($tribunal);
		$sql = "SELECT	sga_tribunal_docentes.docente,
						sga_tribunal_docentes.rol
				FROM	sga_tribunal_docentes
				WHERE	sga_tribunal_docentes.tribunal = $tribunal AND
						sga_tribunal_docentes.estado = '" . comunes::estado_activo . "'
						$where 
				;";
		
		return  guarani_db::consultar($sql);
	}
	
	function validar_nombre_tribunal($nombre, $tribunal)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_tribunal_examen.tribunal) as cant
                FROM 	sga_tribunal_examen
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_tribunal_examen.nombre', $nombre).'
				';
		
		if (!is_null($tribunal)) {
			$tribunal = toba::db()->quote($tribunal);
			$sql .= " AND sga_tribunal_examen.tribunal <> $tribunal ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
}
?>