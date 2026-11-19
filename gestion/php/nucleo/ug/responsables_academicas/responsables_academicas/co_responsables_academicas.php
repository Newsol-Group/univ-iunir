<?php
class co_responsables_academicas
{

	/**
	 * Obtiene un listado de Responsables Académicas según filtro especificado
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_listado($where = '', $filtrar_perfil_de_datos=true)
	{
		if ($where) {
			$where = 'AND ' . $where;
		}

		$sql = "SELECT 				
					sga_responsables_academicas.responsable_academica, 
					sga_responsables_academicas.nombre,
					sga_responsables_academicas.nombre as resp_acad_nombre,
					sga_responsables_academicas.nombre as entidad_descripcion,
					sga_responsables_academicas.codigo,
					sga_responsables_academicas.responsable_academica_tipo, 						 
					sga_responsables_academicas_tipos.nombre as responsable_acad_tipo_nombre,
					sga_responsables_academicas.institucion, 
					sga_instituciones.nombre_abreviado as institucion_nombre,
					sga_instituciones.nombre as institucion_nombre_completo,
					sga_responsables_academicas.institucion_araucano, 
					sga_responsables_academicas.calle, 
					sga_responsables_academicas.numero,
					sga_responsables_academicas.localidad as localidad, 
					mug_localidades.nombre as localidad_nombre, 
					sga_responsables_academicas.codigo_postal, 
					sga_responsables_academicas.telefono, 
					sga_responsables_academicas.fax, 
					sga_responsables_academicas.email,
					sga_g3entidades.entidad
  				FROM 		
					sga_responsables_academicas
	  				LEFT JOIN mug_localidades ON sga_responsables_academicas.localidad = mug_localidades.localidad,
	  				sga_g3entidades,
					sga_g3entidades_subtipos,
					sga_g3entidades_tipos,
					sga_responsables_academicas_tipos,
					sga_instituciones
  				WHERE		
					sga_responsables_academicas.responsable_academica_tipo = sga_responsables_academicas_tipos.responsable_academica_tipo AND
					sga_instituciones.institucion = sga_responsables_academicas.institucion AND
					sga_g3entidades.entidad = sga_responsables_academicas.entidad AND
					sga_g3entidades_subtipos.entidad_subtipo = sga_g3entidades.entidad_subtipo AND
					sga_g3entidades_tipos.entidad_tipo = sga_g3entidades_subtipos.entidad_tipo
					$where
  				ORDER BY	
					responsable_acad_tipo_nombre,
					sga_responsables_academicas.nombre
  				;";
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}

	/**
	 * Obtiene una lista de tipos de Responsables Académicas según el filtro recibido
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_listado_tipos($where = '')
	{
		if ($where) {
			$where = 'WHERE ' . $where . ' ';
		}

		$sql = "SELECT 	sga_responsables_academicas_tipos.responsable_academica_tipo,
						sga_responsables_academicas_tipos.nombre,
						sga_responsables_academicas_tipos.descripcion as descr
  				FROM 	sga_responsables_academicas_tipos 
				$where 
				ORDER BY sga_responsables_academicas_tipos.nombre
				;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado completo de Responsables Académicas.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_responsables_academicas($where='', $filtrar_perfil_de_datos=true)
	{
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = "SELECT		sga_responsables_academicas.responsable_academica as valor,
							sga_responsables_academicas.nombre as descr
				FROM		sga_responsables_academicas
				$where
				ORDER BY	sga_responsables_academicas.nombre
		";
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}

	/**
	 * Retorna el listado de responsables académicas sin aplicar perfil de datos.
	 * 
	 * @return array
	 */
	function get_responsables_academicas_sin_perfil_datos()
	{
		return $this->get_responsables_academicas('', false);
	}

	/**
	 * Retorna el listado de responsables académicas correspondientes a la institución dueña de la instalación.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_ras_institucion_instalacion($where = '')
	{
		$filtro = empty($where) ? 'true' : $where;
		$institucion_instalacion = toba::db()->quote(guarani_configuracion::instancia()->get_valor_config('institucion_instalacion'));
		$filtro .= " AND sga_responsables_academicas.institucion = $institucion_instalacion";
		return $this->get_responsables_academicas($filtro);
	}

	/**
	 * Obtiene los ids y descripciones de todas las Responsables Académicas cuyos nombres contengan al string ingresado como
	 * filtro (mediante un combo editable).
	 *
	 * @param str $filtro porción del nombre de la/las Responsable/s Académica/s ingresada por el usuario.
	 */
	function get_descripciones_ras($filtro = '')
	{
		$condicion = '';
		if (isset($filtro) && trim($filtro) != '') {
			$filtro = guarani_texto::limpiar_acentos($filtro);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$condicion = $filtro_condicion->get_sql('f_limpiar_acentos(sga_responsables_academicas.nombre::varchar)', $filtro);
		}
		return $this->get_ras_institucion_instalacion($condicion);	
	}

	/**
	 * Obtiene el nombre de una Responsable Académica cuyo id se da como parámetro, retornando la string para el método
	 * descripción de un combo editable.
	 *
	 * @param integer $ra id de la responsable académica para la cual se desea recuperar la descripción.
	 */
	static function get_descripcion_ra($ra = null)
	{
		if (!isset($ra)) {
			return '';
		}
		$ra = toba::db()->quote($ra);
		$result = toba::consulta_php('co_responsables_academicas')->get_ras_institucion_instalacion("sga_responsables_academicas.responsable_academica = $ra");
		return empty($result) ? '' : $result[0]['descr'];
	}

	/**
	 * Retorna los datos de una Responsable Académica
	 * 
	 * @param int $responsable_academica
	 * @return array
	 */
	function get_datos_responsable_academica($responsable_academica = '')
	{
		$responsable_academica = toba::db()->quote($responsable_academica);
		return $this->get_listado("sga_responsables_academicas.responsable_academica = $responsable_academica ");
	}

	/**
	 * Retorna los datos de una Responsable Académica en formato RecordSet, en caso de ser un array retorna
	 * los datos de la/s responsables academicas segun la condicion del array (para la carga masiva).
	 * 
	 * @param $responsable_academica
	 * @return array
	 */
	function get_datos_resp_academica($responsable_academica = '')
	{
		if (is_array($responsable_academica)) {
			$filtro = guarani_sql::armar_condicion_in($responsable_academica, 'sga_responsables_academicas', 'responsable_academica');
			$datos = $this->get_listado($filtro, false);
			return $datos;
		}
		$responsable_academica = toba::db()->quote($responsable_academica);
		$datos = $this->get_listado("sga_responsables_academicas.responsable_academica = $responsable_academica", false);
		return $datos[0];
	}

	/**
	 * Retorna los datos de las Propuestas Curriculares con planes activos, asociadas a las Unidades Responsables Académicas.
	 * 
	 * @param string $filtro
	 * @return array
	 */
	function get_responsables_academicas_propuestas($filtro = '')
	{
		$where = '';
		if (!empty($filtro)) {
			$where = ' AND ' . $filtro . ' ';
		}

		$sql = "SELECT 		sga_propuestas_ra.responsable_academica,
							sga_propuestas_ra.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.nombre_abreviado as propuesta_nombre_abrev,
							sga_planes.nombre as plan_nombre,
							sga_planes.codigo as plan_codigo,
							sga_planes_versiones.version as plan_version
  				FROM 		sga_propuestas_ra,
  							sga_propuestas,
  							sga_planes,
  							sga_planes_versiones
  				WHERE		sga_propuestas_ra.propuesta = sga_propuestas.propuesta AND
  							sga_propuestas.propuesta = sga_planes.propuesta AND
  							sga_planes.plan = sga_planes_versiones.plan AND
  							sga_planes.estado in ('" . planes::estado_activo . "', '" . planes::estado_vigente . "') AND 
  							sga_planes_versiones.estado in ('" . planes::estado_activo . "', '" . planes::estado_vigente . "')
  				$where
				ORDER BY	sga_propuestas.nombre
				;";

		return guarani_db::consultar($sql);
	}

	function get_descripciones_propuestas($filtro = '')
	{
		if (isset($filtro) && trim($filtro) != '') {
			toba::db()->quote($filtro);
			$filtro = "sga_propuestas.nombre ILIKE '%$filtro%'";
		}
		return $this->get_responsables_academicas_propuestas($filtro);
	}

	/**
	 * Retorna los Tipos de Responsables Académicas
	 * 
	 * @param string $filtro
	 * @return array
	 */
	function get_responsables_academicas_tipos($filtro = '')
	{
		$filtro = toba::db()->quote("%$filtro%");

		$sql = "SELECT 	sga_responsables_academicas_tipos.responsable_academica_tipo as valor,
						sga_responsables_academicas_tipos.nombre,
						sga_responsables_academicas_tipos.descripcion as descr
  				FROM 	sga_responsables_academicas_tipos 
				WHERE 	sga_responsables_academicas_tipos.nombre ILIKE $filtro 
				OR		sga_responsables_academicas_tipos.descripcion ILIKE $filtro 
				ORDER BY sga_responsables_academicas_tipos.nombre 
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Valida si un código de Responsable Académica se puede usar o no.
	 */
	function validar_codigo_responsable_academica($institucion, $codigo, $responsable_academica = null)
	{
		$institucion = toba::db()->quote($institucion);
		$codigo = toba::db()->quote(strtoupper($codigo));

		$sql = "SELECT 	COUNT(sga_responsables_academicas.responsable_academica) as cant 	
                FROM 	sga_responsables_academicas
				WHERE 	sga_responsables_academicas.institucion = $institucion
				AND 	upper(sga_responsables_academicas.codigo) = $codigo
				";
		if (!is_null($responsable_academica)) {
			$responsable_academica = toba::db()->quote($responsable_academica);
			$sql .= " AND sga_responsables_academicas.responsable_academica <> $responsable_academica ";
		}
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Valida si un nombre de Responsable Académica se puede usar o no.
	 */
	function validar_nombre_responsable_academica($institucion, $nombre, $responsable_academica = null)
	{
		$institucion = toba::db()->quote($institucion);
		$nombre = toba::db()->quote($nombre);

		$sql = "SELECT 	COUNT(sga_responsables_academicas.responsable_academica) as cant 	
                FROM 	sga_responsables_academicas
				WHERE 	sga_responsables_academicas.institucion = $institucion AND
						" . guarani_sql::armar_condicion_compara_cadenas('sga_responsables_academicas.nombre', $nombre);
		if (!is_null($responsable_academica)) {
			$responsable_academica = toba::db()->quote($responsable_academica);
			$sql .= " AND sga_responsables_academicas.responsable_academica <> $responsable_academica ";
		}
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Valida si un nombre de tipo de responsable académica se puede usar o no.
	 */
	function validar_nombre_resp_acad_tipo($nombre, $responsable_academica_tipo)
	{
		if (!isset($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);

		$sql = 'SELECT	count(sga_responsables_academicas_tipos.responsable_academica_tipo) as cant
                FROM 	sga_responsables_academicas_tipos
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('sga_responsables_academicas_tipos.nombre', $nombre) . '
				';

		if (!is_null($responsable_academica_tipo)) {
			$responsable_academica_tipo = toba::db()->quote($responsable_academica_tipo);
			$sql .= " AND sga_responsables_academicas_tipos.responsable_academica_tipo <> $responsable_academica_tipo ";
		}

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Obtiene los ids y descripciones de todas las Responsables Académicas de la institucion dada
	 *
	 * @param int $institucion institucion de la/las Responsable/s Académica/s.
	 */
	function get_ras_institucion($institucion)
	{
		$sql = 'SELECT		sga_responsables_academicas.responsable_academica as valor,
							sga_responsables_academicas.nombre as descr
  				FROM		sga_responsables_academicas
				WHERE		sga_responsables_academicas.institucion = ' . toba::db()->quote($institucion) . '
				ORDER BY	sga_responsables_academicas.nombre
		';
		$institucion_instalacion = guarani_configuracion::instancia()->get_valor_config('institucion_instalacion');
		$filtrar_perfil_de_datos = ($institucion == $institucion_instalacion) ? true : false;
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}

	/**
	 * Obtiene el conjunto de Responsables Académicas vinculadas a un conjunto de 
	 * propuestas recibida como parámetro
	 * 
	 * @param array $propuestas
	 * @return array
	 */
	function get_propuestas_ras($propuestas)
	{
		if (!is_array($propuestas)) {
			$where_in = 'sga_propuestas_ra.propuesta = ' . toba::db()->quote($propuestas);
		} else {
			$where_in = 'sga_propuestas_ra.propuesta IN (' . implode(',', $propuestas) . ')';
		}
		
		$sql = "SELECT 	DISTINCT responsable_academica
				FROM 	sga_propuestas_ra
				WHERE 	$where_in";
		
		$datos = guarani_db::consultar($sql);
		return aplanar_matriz($datos, 'responsable_academica');
	}
	
	/**
	 * Obtiene el conjunto de Responsables Académicas vinculadas a un conjunto de 
	 * propuestas recibida como parámetro
	 * 
	 * @param array $propuestas
	 * @return array
	 */
	function get_propuestas_ras_descripcion($propuestas)
	{
		if (!is_array($propuestas)) {
			$where = 'sga_propuestas_ra.propuesta = ' . toba::db()->quote($propuestas);
		} else {
			$where = 'sga_propuestas_ra.propuesta IN (' . implode(', ', $propuestas) . ')';
		}
		
		$sql = "SELECT 	DISTINCT sga_responsables_academicas.responsable_academica,
						sga_responsables_academicas.nombre,
						sga_responsables_academicas.codigo
				FROM	sga_responsables_academicas
				JOIN	sga_propuestas_ra ON (sga_propuestas_ra.responsable_academica = sga_responsables_academicas.responsable_academica)
				WHERE 	$where";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene el conjunto de nombrea Responsables Académicas  
	 * segun $actividad y $llamado recibida como parámetro
	 * 
	 * @param string $actividad,$llamado
	 * @return array
	 */
	function get_ras_actividad_llamado($actividad, $llamado) 
	{		
		$sql = "SELECT COALESCE(f_responsables_academicas_actividad($actividad) ,  f_responsables_academicas_mesa_examen($llamado) ) as resp_acad_nombre";
		return guarani_db::consultar_fila($sql);
	}
	/**
	 * Obtiene el conjunto de nombre de las Responsables Académicas  
	 * segun select recibida como parámetro
	 * 
	 * @param string $actividad, $comision
	 * @return array
	 */
	function get_ras_actividad_comision($actividad, $comision) 
	{		
		$sql = "SELECT COALESCE(f_responsables_academicas_actividad($actividad) ,  f_responsables_academicas_comision($comision) ) as resp_acad_nombre";
		return guarani_db::consultar_fila($sql);
	}
}
?>