<?php
/**
 *
 *
 * @package		Docentes
 * @subpackage	Actualizaciones
 * 
 */
class co_catedras
{
	/**
	 * Obtiene una lista de cátedras según el filtro recibido
	 */
	function get_listado($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where . ' ';
		}

		$sql = '	SELECT 	sga_catedras.catedra,
							sga_catedras.nombre,
							sga_catedras.catedra_tipo,
							sga_catedras_tipos.nombre AS nombre_tipo,
							sga_catedras.estado,
							' . guarani_sql::get_case('sga_catedras', 'estado', 'nombre_estado', toba::consulta_php('co_comunes')->get_estado_activo_baja()) . ",
							f_actividades_catedra(sga_catedras.catedra) AS actividades
					FROM 	sga_catedras,
							sga_catedras_tipos
					WHERE	sga_catedras.catedra_tipo = sga_catedras_tipos.catedra_tipo
							$where
					ORDER BY sga_catedras.nombre
							;";
		return guarani_db::consultar($sql);
	}

	function get_catedras_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$where_gatillo = 'true';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_elementos', 'sga_elementos');
		}
		$sql = 'SELECT		sga_catedras.catedra,
							sga_catedras.nombre,
							sga_catedras.catedra_tipo,
							sga_catedras_tipos.nombre as nombre_tipo,
							sga_catedras.estado,
							' . guarani_sql::get_case('sga_catedras', 'estado', 'nombre_estado', toba::consulta_php('co_comunes')->get_estado_activo_baja()) . ",
							f_actividades_catedra(sga_catedras.catedra) as actividades
				FROM		sga_catedras
							LEFT JOIN sga_catedras_actividades ON sga_catedras.catedra = sga_catedras_actividades.catedra
							LEFT JOIN sga_elementos ON sga_catedras_actividades.elemento = sga_elementos.elemento,
							sga_catedras_tipos
				WHERE		sga_catedras.catedra_tipo = sga_catedras_tipos.catedra_tipo AND
							-- Cátedras sin actividades.
							(sga_catedras_actividades.elemento IS NULL OR
							-- Cátedras con actividades filtradas por perfil de datos.
							$where_gatillo)
							$where
				GROUP BY	sga_catedras.catedra,
							sga_catedras_tipos.nombre
				ORDER BY	sga_catedras.nombre
		";
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Valida si un nombre de cátedra está siendo usado
	 */
	function nombre_repetido($nombre, $catedra)
	{
		if (!isset($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT	count(sga_catedras.catedra) as cant
                FROM 	sga_catedras
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('sga_catedras.nombre', $nombre) . '
				';

		if (!is_null($catedra)) {
			$catedra = toba::db()->quote($catedra);
			$sql .= " AND sga_catedras.catedra <> $catedra ";
		}
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 1);
	}

	/**
	 * Retorna los tipos de cátedras
	 */
	function get_catedras_tipos($filtro = '')
	{
		$filtro = toba::db()->quote("%$filtro%");
		
		$sql = "SELECT	sga_catedras_tipos.catedra_tipo as valor, 
						sga_catedras_tipos.nombre as descr 
  				FROM 	sga_catedras_tipos
  				WHERE 	sga_catedras_tipos.nombre ILIKE $filtro OR
						sga_catedras_tipos.descripcion ILIKE $filtro 
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Valida si un nombre de cátedra se puede usar o no.
	 */
	function validar_nombre_catedra($nombre, $catedra)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT	count(sga_catedras.catedra) as cant
                FROM 	sga_catedras
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('sga_catedras.nombre', $nombre) . '
				';
		if (!is_null($catedra)) {
			$catedra = toba::db()->quote($catedra);
			$sql .= " AND sga_catedras.catedra <> $catedra ";
		}
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Retorna el nombre de una cátedra basandose en su id
	 */
	function get_nombre($id = null)
	{
		if (!is_null($id)) {
			$id = toba::db()->quote($id);
			$sql = "SELECT	sga_catedras.catedra,
		   					sga_catedras.nombre as catedra_nombre
					FROM 	sga_catedras
					WHERE 	sga_catedras.catedra = $id
				   ;";
			$resultado = guarani_db::consultar_fila($sql);
			return $resultado['catedra_nombre'];
		} else {
			return '';
		}
	}

	/**
	 * Retorna el listado de cátedras en estado A
	 */
	function get_catedras_activas()
	{
		$sql = "	SELECT 	sga_catedras.catedra as valor,
							sga_catedras.nombre as descr
					FROM 	sga_catedras
					WHERE	sga_catedras.estado = 'A'
					ORDER BY 2
							;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene el valor y la descripción de todas las cátedras activas definidas para una Actividad dada, que sean del tipo
	 * apropiado para Comisiones.
	 *
	 * @param integer $actividad id de la actividad (elemento) para la cual se recuperan las cátedras definidas.
	 */
	function get_catedras_actividad_para_comisiones($actividad = null)
	{
		$where = '';
		if (!is_null($actividad)) {
			$actividad = toba::db()->quote($actividad);
			$where = " AND sga_catedras_actividades.elemento = $actividad ";
		}
		$sql = "SELECT		sga_catedras.catedra as valor,
							sga_catedras.nombre as descr
				FROM		sga_catedras,
							sga_catedras_tipos,
							sga_catedras_actividades
				WHERE		sga_catedras.catedra = sga_catedras_actividades.catedra AND
							sga_catedras.catedra_tipo = sga_catedras_tipos.catedra_tipo AND
							sga_catedras.estado = '" . comunes::estado_activo . "' AND
							sga_catedras_tipos.catedra_tipo IN ('" . comision::cat_tipo_cursado . "', '" . comision::cat_tipo_cursado_examen . "')
							$where
				ORDER BY	sga_catedras.nombre
		";
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Obtiene el valor y la descripción de todas las cátedras activas definidas para una Actividad dada, que sean del tipo
	 * apropiado para Mesas de Exámen.
	 *
	 * @param integer $actividad id de la actividad (elemento) para la cual se recuperan las cátedras definidas.
	 */
	function get_catedras_actividad_para_mesas($actividad = null)
	{
		$where = '';
		if (!is_null($actividad)) {
			$actividad = toba::db()->quote($actividad);
			$where = " AND sga_catedras_actividades.elemento = $actividad ";
		}
		$sql = "SELECT		sga_catedras.catedra as valor,
							sga_catedras.nombre as descr
				FROM		sga_catedras,
							sga_catedras_tipos,
							sga_catedras_actividades
				WHERE		sga_catedras.catedra = sga_catedras_actividades.catedra AND
							sga_catedras.catedra_tipo = sga_catedras_tipos.catedra_tipo AND
							sga_catedras.estado = '" . comunes::estado_activo . "' AND
							sga_catedras_tipos.catedra_tipo IN ( '" . mesa_de_examen::cat_tipo_examen . "', '" . mesa_de_examen::cat_tipo_cursado_examen . "') 
							$where
				ORDER BY	sga_catedras.nombre
				;";
		return guarani_db::consultar($sql);
	}

	/*
	 * 	Retorna el listado de las cátedras habilitadas. Si por parámetro viene la Cátedra Original 
	 * 	proveniente del ABM de Cátedras Equivalentes entonces la quita del conjunto de datos a devolver.
	 */
	function get_catedras($catedra_original = '')
	{
		$where = '';
		if ($catedra_original) {
			$catedra_original = toba::db()->quote($catedra_original);
			$where = ' AND sga_catedras.catedra <> ' . $catedra_original;
		}
		$sql = "	SELECT 	sga_catedras.catedra as valor,
							sga_catedras.nombre as descr
					FROM 	sga_catedras
					WHERE	sga_catedras.estado = 'A'
					$where
					ORDER BY 2
							;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los docentes de la cátedra dada como parámetro, según el filtro dado.
	 *
	 * @param integer	$id_catedra	id de la cátedra para la cual se desean recuperar los docentes asociados.
	 * @param str		$where		filtro sobre los docentes a recuperar.
	 */
	function get_docentes_catedra($id_catedra, $where = '')
	{
		$id_catedra = toba::db()->quote($id_catedra);
		$filtro = " WHERE sga_catedras_docentes.catedra = $id_catedra ";
		if ($where) {
			$filtro .= ' AND ' . $where;
		}

		$sql = "SELECT	sga_catedras_docentes.catedra,
						sga_catedras_docentes.docente,
						sga_catedras_docentes.responsabilidad,				
						sga_catedras_docentes.estado
				FROM	sga_catedras_docentes
				$filtro
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si el docente dado es parte de la cátedra pasada como argumento.
	 */
	function es_docente_catedra($id_catedra, $id_docente)
	{
		$id_docente = toba::db()->quote($id_docente);
		$where = " sga_catedras_docentes.docente = $id_docente ";
		$docentes_catedras = $this->get_docentes_catedra($id_catedra, $where);
		if (!empty($docentes_catedras)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Retorna el estado de la catedra dada.
	 *
	 * @param 	integer $catedra
	 * @return 	boolean
	 */
	function get_estado_catedra($catedra)
	{
		$catedra = toba::db()->quote($catedra);
		$where = "sga_catedras.catedra = $catedra ";
		$datos = current($this->get_listado($where));
		return !empty($datos) ? $datos['estado'] : null;
	}

}
?>