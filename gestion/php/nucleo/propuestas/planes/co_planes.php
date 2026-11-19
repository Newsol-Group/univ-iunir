<?php
/**
 * Consultas sobre planes de estudios
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class co_planes
{
	/**
	  Obtiene una lista de planes según el filtro recibido
	 */
	function get_listado($where='', $order='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		if (!$order) {
			$order = 'plan_nombre, propuesta_nombre';
		}

		$sql = "	SELECT 	sga_planes.plan,
							sga_planes.propuesta,
							sga_planes.nombre,
							sga_planes.nombre as				plan_nombre,
							sga_propuestas.nombre || ' (' || sga_propuestas.codigo || ')' || ' | ' || sga_planes.nombre || ' (' || sga_planes.codigo || ')' as entidad_descripcion,
							sga_planes.codigo,
							sga_planes.version_actual,
							sga_planes.duracion_teorica,
							sga_planes.duracion_en_anios,
							sga_planes.duracion_en_meses,
							sga_planes.vigencia_minima,
							sga_planes.tope_creditos_cursado,
							sga_planes.tope_creditos_reg_vigentes,
							sga_planes.documento_alta,
							sga_planes.fecha_entrada_vigencia,
							sga_planes.documento_baja,
							sga_planes.fecha_baja,
							sga_planes.inscripcion_habilitada,
							sga_planes.entidad,
							sga_planes.estado,
							sga_propuestas.nombre as			propuesta_nombre,
							sga_propuestas.nombre_abreviado as	propuesta_nombre_abreviado,
							sga_planes_versiones.nombre as		version_nombre,
							sga_planes_versiones.version as		version_codigo
	  				
					FROM 	sga_planes
							JOIN sga_propuestas ON sga_planes.propuesta = sga_propuestas.propuesta
		  					LEFT JOIN sga_planes_versiones ON sga_planes.version_actual = sga_planes_versiones.plan_version

					$where

					ORDER BY $order";

		return guarani_db::consultar($sql);
	}

	function get_lista_planes($where='')
	{
		if ($where) {
			$where = "WHERE $where";
		}

		$sql = "	SELECT	sga_propuestas.propuesta as						propuesta,
							sga_propuestas.codigo as						propuesta_codigo,
							sga_propuestas.nombre as						propuesta_nombre,
							sga_propuestas.escala_cumplimiento,
							sga_escalas_cumplimiento.activo as				escalas_cumplimiento_activo,
							sga_planes.plan as								plan,
							sga_planes.nombre as							nombre,
							sga_planes.nombre as							plan_nombre,
							sga_propuestas.nombre || ' (' || sga_propuestas.codigo || ')' || ' | ' || sga_planes.nombre || ' (' || sga_planes.codigo || ')' || ' | ' || sga_planes_versiones.nombre || ' (' || sga_planes_versiones.version || ')' as entidad_descripcion,
							sga_planes.codigo as							plan_codigo,
							sga_planes.tipo_plan,
							sga_planes.administra_cursos,
							sga_planes.inscripcion_habilitada as 			inscripcion_habilitada,
							sga_planes.version_actual as					version_actual,
							sga_planes_versiones_actuales.nombre as			version_actual_nombre,
							sga_planes_versiones.plan_version as			plan_version,
							sga_planes_versiones.version as					version,
							sga_planes_versiones.nombre as					version_nombre,
							sga_planes_versiones.fecha_entrada_vigencia as	fecha_entrada_vigencia,
							sga_planes.estado as 							plan_estado,
							p_sga_planes_estados.descripcion as 			plan_estado_descripcion,
							sga_planes_versiones.estado as 					version_estado, 
							v_sga_planes_estados.descripcion as 			version_estado_descripcion,
							sga_g3entidades.entidad,
							CASE 
							WHEN (sga_planes_versiones.estado IN ('".planes::estado_vigente."','".planes::estado_activo."') 
								AND NOT EXISTS (SELECT 1 FROM sga_planes_versiones as sga_planes_versiones2 WHERE sga_planes_versiones2.plan = sga_planes.plan AND sga_planes_versiones2.estado = '".planes::estado_nuevo."')) 
								THEN '".comunes::si."' 
								ELSE '".comunes::no."'
							END	as											versionable 
					FROM	sga_propuestas
							JOIN sga_planes ON sga_propuestas.propuesta = sga_planes.propuesta
							JOIN sga_planes_estados as p_sga_planes_estados ON sga_planes.estado = p_sga_planes_estados.estado
							JOIN sga_planes_versiones ON sga_planes.plan = sga_planes_versiones.plan
							JOIN sga_planes_estados as v_sga_planes_estados ON sga_planes_versiones.estado = v_sga_planes_estados.estado
							JOIN sga_g3entidades ON sga_planes_versiones.entidad = sga_g3entidades.entidad
							LEFT JOIN sga_planes_versiones as sga_planes_versiones_actuales ON sga_planes.version_actual = sga_planes_versiones_actuales.plan_version
							LEFT JOIN sga_escalas_cumplimiento ON sga_propuestas.escala_cumplimiento = sga_escalas_cumplimiento.escala_cumplimiento
					$where
					ORDER BY propuesta, plan, plan_version;";

		return guarani_db::consultar($sql);
	}

	function get_lista_planes_activos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_propuestas.codigo as propuesta_codigo,
							sga_propuestas.nombre as propuesta_nombre,
							sga_planes.plan,
							sga_planes.nombre as plan_nombre,
							('(' || sga_planes.codigo || ') ' || sga_planes.nombre) as plan_codigo_nombre,
							sga_planes.inscripcion_habilitada,
							sga_planes_estados.descripcion as plan_estado_descripcion
				FROM		sga_propuestas,
							sga_planes,
							sga_planes_estados
				WHERE		sga_propuestas.propuesta = sga_planes.propuesta	AND
							sga_planes.estado = sga_planes_estados.estado AND
							sga_planes_estados.estado IN ('".planes::estado_vigente."','".planes::estado_activo."')
							$where
				ORDER BY	plan
		";
		return guarani_db::consultar($sql);
	}

	/**
	 *   Obtiene una lista de nombres de planes segï¿½n el filtro recibido
	 */
	static function get_planes_nombres($where='')
	{
		if ($where) {
			$where = 'WHERE ' . $where . ' ';
		}

		$sql = "SELECT 	sga_planes.plan as valor,
						sga_planes.nombre as descr
  				FROM 	sga_planes
				$where
				ORDER BY sga_planes.nombre
				;";
		return guarani_db::consultar($sql);
	}

	/**
	 *   Obtiene el nombre de un id de plan version.
	 */
	function get_plan_version_nombre($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);

		$sql = "SELECT 	sga_propuestas.nombre || ' ' || sga_planes.nombre || ' - ' || sga_planes_versiones.version AS plan_version_nombre
  				FROM 	sga_planes_versiones,
  						sga_planes,
  						sga_propuestas
				WHERE	sga_planes_versiones.plan = sga_planes.plan		AND
						sga_planes.propuesta = sga_propuestas.propuesta AND
						sga_planes_versiones.plan_version = $plan_version
				;";
		return guarani_db::consultar_fila($sql);
	}

	/**
	  Obtiene una lista de nombres de planes según la propuesta recibida
	 */
	function get_planes_propuesta($propuesta, $filtro='')
	{
		if (!is_null($propuesta)) {
			$propuesta = toba::db()->quote($propuesta);
			$where = " WHERE sga_planes.propuesta = $propuesta";
		}
		if (isset($filtro) && trim($filtro) != '') {
			if (isset($where)) {
				$where .= ' AND ' . $filtro;
			} else {
				$where = ' WHERE ' . $filtro;
			}
		}
		$sql = "SELECT 	sga_planes.plan as valor,
						sga_planes.nombre as descr,
						sga_planes.plan,
						sga_planes.nombre,
						sga_planes.codigo, 
						sga_planes.version_actual, 
						sga_planes.fecha_entrada_vigencia, 
						sga_planes.fecha_baja, 
						sga_planes.inscripcion_habilitada, 
						sga_planes.entidad, 
						sga_planes.estado
  				FROM 	sga_planes
				$where
				ORDER BY sga_planes.nombre
				;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene la lista de planes Activos (Vigentes o No vigentes) para la propuesta dada según el filtro recibido.
	 */
	function get_planes_activos_propuesta($propuesta, $filtro='')
	{
		$where = "sga_planes.estado IN ('" . planes::estado_activo . "', '" . planes::estado_activo_vigente . "')";
		if ($filtro) {
			$where .= ' AND ' . $filtro;
		}
		return $this->get_planes_propuesta($propuesta, $where);
	}

	/**
	 * Obtiene los ids y descripciones de todos los Planes de estudio asociados a la Propuesta dada como
	 * parámetro, cuyos nombres contengan al string ingresado como filtro (mediante un combo editable), y 
	 * con calidad de Activos (vigentes o no). 
	 *
	 * @param str $filtro porción del nombre del Plan ingresada por el usuario.
	 * @param integer $propuesta id de la Propuesta Curricular para la cual se quieren recuperar los Planes que la contienen.
	 */
	function get_descripciones_planes_propuesta($filtro='',$propuesta)
	{
		if (isset($filtro) && trim($filtro) != '') {
			toba::db()->quote($filtro);
			$filtro = " AND sga_planes.nombre ILIKE '%$filtro%'";
		}
		$result = $this->get_planes_propuesta($propuesta, "sga_planes.estado in ('".planes::estado_activo."', '".planes::estado_vigente."') " . $filtro);
		return $result;
	}

	function get_descripciones_planes($filtro='')
	{
		if (isset($filtro) && trim($filtro) != '') {
			toba::db()->quote($filtro);
			$filtro = " AND sga_planes.nombre ILIKE '%$filtro%'";
		}
		return $this->get_planes_propuesta(null, "sga_planes.estado in ('".planes::estado_activo."', '".planes::estado_vigente."') " . $filtro);
	}

	/**
	 * Obtiene el nombre de un Plan cuyo id se da como parámetro, retornando la string para el método
	 * descripción de un combo editable.
	 *
	 * @param integer $plan id del plan para la cual se desea recuperar la descripción.
	 */
	static function get_descripcion_plan($plan = null)
	{
		if (!isset($plan)) {
			return '';
		}
		$plan = toba::db()->quote($plan);

		$result = toba::consulta_php('co_planes')->get_planes_nombres("sga_planes.plan = $plan");
		return empty($result) ? '' : $result[0]['descr'];
	}

	/**
	 * Retorna el tope de créditos de regularidades vigentes definido para el plan a partir de un plan-versión dado.
	 *
	 * @param integer $plan_version
	 * @return float
	 */
	function get_tope_creditos_reg_vigentes($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	COALESCE(sga_planes.tope_creditos_reg_vigentes, 0) as tope_creditos_reg_vigentes
				FROM	sga_planes_versiones,
						sga_planes
				WHERE	sga_planes_versiones.plan = sga_planes.plan AND
						sga_planes_versiones.plan_version = $plan_version
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['tope_creditos_reg_vigentes'];
	}

	/**
	 * Retorna el tope de créditos de cursados simultáneos definido para el plan a partir de un plan-versión dado.
	 *
	 * @param integer $plan_version
	 * @return float
	 */
	function get_tope_creditos_cursado($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	COALESCE(sga_planes.tope_creditos_cursado, 0) as tope_creditos_cursado
				FROM	sga_planes_versiones,
						sga_planes
				WHERE	sga_planes_versiones.plan = sga_planes.plan AND
						sga_planes_versiones.plan_version = $plan_version
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['tope_creditos_cursado'];
	}

	/**
	 *	Lista de elementos que cuelgan de un PLAN VERSION
	 */
	function get_contenido_plan_version($plan_version, $parms = [])
	{
		$planv = toba::db()->quote($plan_version);

		$from_optativas = '';
		$optativas_compartidas = '';
		if (isset($parms['conocer_optativas_compartidas']) && $parms['conocer_optativas_compartidas']) {
			$alumno						= toba::db()->quote($parms['alumno']);
			$subtipo_materia_generica	= toba::db()->quote(elemento::subtipo_materia_generica);

			$from_optativas = "
			        JOIN sga_elementos_revision ON sga_elementos_revision.elemento_revision = get_plan_contenido.elemento_revision
					LEFT JOIN sga_elementos AS padre ON (get_plan_contenido.elemento_padre = padre.elemento)
					LEFT JOIN sga_g3entidades_subtipos AS padre_subtipos ON (padre.entidad_subtipo = padre_subtipos.entidad_subtipo) ";
			
			$optativas_compartidas = ",CASE 
			                              WHEN padre.entidad_subtipo = $subtipo_materia_generica THEN f_optativa_es_compartida(get_plan_contenido.elemento, $planv, $alumno, get_plan_contenido.elemento_padre) 
										  ELSE 0 
									   END AS optativa_compartida
									  ,CASE WHEN padre.entidad_subtipo = $subtipo_materia_generica THEN 1 ELSE 0 END AS es_optativa 
									  ,CASE get_plan_contenido.entidad_subtipo
                                          WHEN $subtipo_materia_generica THEN get_regla_mensaje(sga_elementos_revision.regla, sga_elementos_revision.parametros)  -- SI ES GENERICA.
										  ELSE cast(null as text)
									   END as forma_cumplimiento									   
					                  ,sga_elementos_revision.regla
					                  ,sga_elementos_revision.parametros
									";
		}

		$mostrar_hijos_mg = 'true';
		if (isset($parms['incluir_componentes_genericas']) && !$parms['incluir_componentes_genericas']) {
			$mostrar_hijos_mg = 'false';
		}
		
		$sql = "SELECT	get_plan_contenido.elemento,
						get_plan_contenido.entidad_tipo,
						get_plan_contenido.entidad_subtipo,
						get_plan_contenido.entidad_clase_gui as elemento_clase_gui,
						get_plan_contenido.elemento_padre,
						get_plan_contenido.codigo,
						get_plan_contenido.nombre,
						get_plan_contenido.elemento_revision,
						get_plan_contenido.elemento_comp,
						get_plan_contenido.orden
						$optativas_compartidas
				FROM	get_plan_contenido($planv, true, $mostrar_hijos_mg)
				JOIN	sga_g3entidades_subtipos ON (get_plan_contenido.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo)
						$from_optativas
				WHERE	true ".$this->armar_where($parms);

		$datos = guarani_db::consultar($sql);
		if(!isset($datos[0]['elemento'])) {
			throw new guarani_error("El plan_version [$plan_version] no posee contenido asociado");
		}
		return $datos;
	}

	function get_contenido_plan_version_ids( $plan_version, $parms=array())
	{
		$planv = toba::db()->quote($plan_version);

		$sql = " Select	get_plan_contenido.elemento,
						get_plan_contenido.entidad_tipo
				from get_plan_contenido( $planv )";
		$datos = guarani_db::consultar($sql);
		if(!isset($datos[0]['elemento'])) {
			throw new guarani_error("El plan_version [$plan_version] no posee contenido asociado");
		}
		return $datos;
	}

	function get_planes_elemento($elemento)
	{
		$ele = toba::db()->quote($elemento);
		$sql = "SELECT	DISTINCT	sga_elementos_plan.plan_version,
									sga_planes_versiones.estado
						FROM sga_elementos_plan,
							 sga_planes_versiones,
							 sga_elementos_revision


						WHERE	sga_elementos_plan.plan_version = sga_planes_versiones.plan_version
						AND		sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
						AND		sga_elementos_revision.elemento = $ele";
		$datos = guarani_db::consultar($sql);
		if(!isset($datos)) {
			throw new guarani_error("Error al recuperar los planes a los que pertenece el elemento");
		}
		return $datos;
		
	}

	/**
	 * Determina si una materia genérica dada pertenece a algún plan-versión no visible al perfil de datos del usuario logueado.
	 * 
	 * @param int $elemento
	 * @return boolean
	 */
	function generica_en_planes_no_visibles($elemento)
	{
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_planes', 'sga_planes');
			$elemento = toba::db()->quote($elemento);
			$sql = "SELECT	sga_elementos_plan.plan_version
					FROM	sga_elementos_plan
							JOIN sga_planes_versiones ON sga_elementos_plan.plan_version = sga_planes_versiones.plan_version
							JOIN sga_elementos_revision ON sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
					WHERE	sga_elementos_revision.elemento = $elemento AND
							sga_planes_versiones.plan NOT IN (	SELECT	sga_planes.plan
																FROM	sga_planes
																WHERE	$where_gatillo)
			";
			$rs = guarani_db::consultar($sql, null, false);
			return (!empty($rs));
		}
		return false;
	}

	function armar_where($parms=array()) 
	{
		if ($parms == array()) return '';

		$where = '';

		if (isset($parms['solo_actividades']) && $parms['solo_actividades'])
			$where .= " and sga_g3entidades_subtipos.entidad_tipo = ".elemento::tipo_actividad." ";

		if (isset($parms['solo_modulos']) && $parms['solo_modulos'])
			$where .= " and sga_g3entidades_subtipos.entidad_tipo = ".elemento::tipo_modulo." ";

		if (isset($parms['sin_genericas']) && $parms['sin_genericas'])
			$where .= " and not (sga_g3entidades_subtipos.entidad_tipo = ".elemento::tipo_modulo." and get_plan_contenido.entidad_subtipo = ".elemento::subtipo_materia_generica.") ";

		return $where;
	}

	function get_contenido_elemento($elemento_revision, $plan_version , $muestro_elemento, $muestro_hijos)
	{	//TODO Cambiar asterisco por los campos que recupera la funcion
		
		$muestro_elemento = 'true';
		$muestro_hijos = 'true';
		if ($plan_version == null) {
			$plan_version = 'null';
		}
		$sql = "SELECT *
				FROM get_elemento_contenido($elemento_revision, $plan_version, $muestro_elemento, $muestro_hijos )
		;";


		/* 		$sql = "SELECT get_elemento_contenido.*
		  FROM 	get_elemento_contenido($elemento_revision, $plan_version, $muestro_elemento, $muestro_hijos ),
		  sga_elementos,
		  sga_g3entidades_subtipos
		  WHERE	get_elemento_contenido.elemento = sga_elementos.elemento
		  and 	sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo;";
		 */
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de actividades y genericas de un módulo de una version de plan de estudios. 
	 * Recupera todas incluyendo la de sus submodulos. Aplana el conjunto de componentes de sus diferentes niveles de sumbodulos.
	 *
	 * @param integer	$modulo			código de elemento correspondiente al módulo.
	 * @param integer	$plan_version	código de plan versión.
	 * @param boolean	$devolver_submodulos	Indica si se devuelven los ids de los submodulos
	 * @param boolean	$devolver_genericas		Indica si se devuelven los ids de las actividades genericas
	 * @param boolean	$devolver_optativas		Indica si se devuelven los ids de las actividades optativas
	 * @param string	$where			filtro sobre los componentes a retornar.
	 * @return array
	 * 
	 */
	function get_actividades_y_genericas_modulo_aplanado($modulo, $plan_version, $devolver_submodulos = false, $devolver_genericas = true, $devolver_optativas = false,  $where = '')
	{
		$modulo = toba::db()->quote($modulo);
		$plan_version = toba::db()->quote($plan_version);
		if (!empty($where)) {
			$where = " AND $where";
		}
		
		$entidad_subtipo_generica = elemento::subtipo_materia_generica;
		$elemento_tipo_modulo = elemento::tipo_modulo;
		
		// Devolver submodulos
		$where_submodulos = ""; 
		if (!$devolver_submodulos){
			// No se devuelven los submodulos que hay en el modulo. Las genericas son modulos, esos si los devuelvo por este filtro.
			// Siempre devuelvo el id del modulo que se consulta.
			$where_submodulos = " AND (c.elemento = $modulo OR c.entidad_tipo <> $elemento_tipo_modulo OR c.entidad_subtipo = $entidad_subtipo_generica) ";
		}	
		
		// Devolver actividades genericas
		$where_genericas = ""; 
		if (!$devolver_genericas){
			// No se devuelven las genericas que hay en el modulo
			$where_genericas = " AND c.entidad_subtipo <> $entidad_subtipo_generica ";
		}	
		
		// Devolver actividades optativas
		$where_optativas = ""; 
		if (!$devolver_optativas){
			// No se devuelven las optativas de las genericas que haya dentro del módulo
			$where_optativas = " AND c.elemento NOT IN (SELECT op.elemento FROM vw_optativas_plan as op WHERE op.plan_version = $plan_version) "; 
		}	
		
		// Obtengo la revision del modulo en la version del plan de estudios
		$sql = "SELECT elemento_revision FROM vw_elementos_plan WHERE plan_version = $plan_version AND elemento = $modulo;";
		$dato_modulo = guarani_db::consultar_fila($sql);
		$revision_modulo = $dato_modulo['elemento_revision'];

		// Obtengo las actividades y genericas del módulo y sus submodulos en la version del plan de estudios.
		// No recupero las actividades optativas de las genericas para no contarlas. Estas se evaluan a traves de las genericas.
		$sql = "SELECT DISTINCT c.elemento_revision, c.elemento, c.entidad_tipo, c.entidad_subtipo, 
		               ep.horas_totales as horas, ep.creditos, 
					   er.regla, er.parametros, er.origen_creditos
			    FROM get_elemento_contenido ($revision_modulo, $plan_version, true, true) as c
				JOIN sga_elementos_revision as er ON er.elemento_revision = c.elemento_revision
				JOIN sga_elementos_plan as ep ON ep.plan_version = $plan_version AND ep.elemento_revision = er.elemento_revision
				WHERE TRUE
				$where
				$where_submodulos
				$where_genericas
				$where_optativas
				";
		return guarani_db::consultar($sql);
	}


	/**
	 * Retorna la lista de componentes de un módulo en un plan versión (sólo un nivel de profundidad).
	 * No se aplica perfil de datos a la consulta SQL porque el método es utilizado desde la reglas
	 * de cumplimiento de módulos y la detección de egresados, donde no debe tenerse en cuenta.
	 *
	 * @param integer	$modulo			código de elemento correspondiente al módulo.
	 * @param integer	$plan_version	código de plan versión.
	 * @param string	$where			filtro sobre los componentes a retornar.
	 * @return array
	 * 
	 */
	function get_componentes_modulo($modulo, $plan_version, $where = '')
	{
		$modulo = toba::db()->quote($modulo);
		$plan_version = toba::db()->quote($plan_version);
		if (!empty($where)) {
			$where = " AND $where";
		}
		$sql = "SELECT	sga_elementos_comp.elemento_hijo as elemento_revision,
						sga_elementos_comp.puntaje,
						sga_elementos.elemento,
						sga_g3entidades_subtipos.entidad_tipo,
						sga_elementos_revision.regla,
						sga_elementos_revision.parametros,
						sga_elementos_revision.origen_creditos,
						sga_elementos_plan.creditos,
						sga_elementos_plan.horas_totales as horas
				FROM	vw_elementos_plan
				JOIN	sga_elementos_comp ON sga_elementos_comp.elemento_padre = vw_elementos_plan.elemento_revision
				JOIN	sga_elementos_revision ON sga_elementos_revision.elemento_revision = sga_elementos_comp.elemento_hijo
				JOIN	sga_elementos_plan ON (sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision 
				                          AND sga_elementos_plan.plan_version = vw_elementos_plan.plan_version) 
				JOIN	sga_elementos ON sga_elementos.elemento = sga_elementos_revision.elemento
				JOIN	sga_g3entidades_subtipos ON sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo
				WHERE	vw_elementos_plan.plan_version = $plan_version
				  AND	vw_elementos_plan.elemento = $modulo
						$where
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_detalles_componentes_modulos($modulo, $plan_version)
	{
		$modulo = toba::db()->quote($modulo);
		$plan_version = toba::db()->quote($plan_version);

		$sql = "SELECT		vw_elementos_plan.codigo as codigo,
							vw_elementos_plan.nombre as nombre, 
							vw_elementos_plan.nombre_abreviado as nombre_abreviado,
							vw_elementos_plan.entidad_subtipo_nombre as subtipo_nombre,
							ecm.puntaje as puntaje,
							ecm.orden as orden
				FROM 		sga_elementos_revision as erm,
							sga_elementos_plan as epm,
							sga_elementos_comp as ecm,
							vw_elementos_plan
				WHERE 		erm.elemento = $modulo 
							AND epm.plan_version = $plan_version
							AND epm.elemento_revision = erm.elemento_revision
							AND ecm.elemento_padre = erm.elemento_revision 
							AND vw_elementos_plan.plan_version = epm.plan_version
							AND vw_elementos_plan.elemento_revision = ecm.elemento_hijo
				ORDER BY 	ecm.orden
				";

		return guarani_db::consultar($sql);
	}

	function plan_posee_versiones_activas($plan)
	{
		$plan = toba::db()->quote($plan);
		$sql = "	SELECT 	count(*) as cantidad
					FROM 	sga_planes p,
							sga_planes_versiones v
					WHERE 	p.plan = v.plan
					AND 	v.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "')
					AND 	p.plan = $plan;";
		$planes = guarani_db::consultar_fila($sql);
		return ($planes['cantidad'] > 0);
	}

	function plan_posee_version_nueva($plan)
	{
		$plan = toba::db()->quote($plan);
		$sql = "	SELECT 	count(*) as cantidad
					FROM 	sga_planes p,
							sga_planes_versiones v
					WHERE 	p.plan = v.plan
					AND 	v.estado = '" . planes::estado_nuevo . "'
					AND 	p.plan = $plan;";
		$planes = guarani_db::consultar_fila($sql);
		return ($planes['cantidad'] > 0);
	}

	/**
	 * Determina si un plan dado tiene al menos una versión que no fue dada de baja (nueva o activa).
	 *
	 * @param integer $plan
	 * @return boolean
	 */
	function plan_posee_version_no_dada_de_baja($plan, $where = '')
	{
		$plan = toba::db()->quote($plan);
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT	count(*) as cantidad
				FROM	sga_planes,
						sga_planes_versiones
				WHERE	sga_planes.plan = sga_planes_versiones.plan AND
						sga_planes.plan = $plan AND
						sga_planes_versiones.estado <> '" . planes::estado_baja . "'
						$where
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cantidad'] > 0);
	}

	/**
	 * Determina para el id de plan versión dado, si la versión corresponde a la última versión vigente del plan.
	 *
	 * @param integer $plan_version
	 * @return boolean
	 */
	function plan_version_es_actual($plan_version)
	{
		$sql = "SELECT	sga_planes.version_actual
				FROM	sga_planes_versiones,
						sga_planes
				WHERE	sga_planes_versiones.plan = sga_planes.plan AND
						sga_planes_versiones.plan_version = $plan_version
		";
		$rs = guarani_db::consultar_fila($sql);
		if (isset($rs['version_actual'])) {
			return ($rs['version_actual'] == $plan_version);
		}
	}

	function get_plan_certificados($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = " SELECT sga_planes_certificados.certificado as certificado,
						sga_certificados.entidad as entidad
				FROM 	sga_planes_certificados,
						sga_certificados  
				WHERE 	sga_planes_certificados.certificado = sga_certificados.certificado
				AND 	sga_planes_certificados.plan_version = $plan_version
					;";
		return guarani_db::consultar($sql);
	}

	function get_actividades_certificados_plan($plan_version, $where)
	{
		$plan_version = toba::db()->quote($plan_version);
		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT		DISTINCT
							sga_condiciones_requisitos.entidad,
							sga_elementos.elemento,
							sga_elementos.nombre
				FROM		sga_planes_certificados,
							sga_certificados,
							sga_condiciones,
							sga_condiciones_grupos,
							sga_condiciones_requisitos,
							sga_elementos
				WHERE		sga_planes_certificados.plan_version = $plan_version AND
							sga_planes_certificados.certificado = sga_certificados.certificado AND
							sga_certificados.entidad = sga_condiciones.entidad AND
							sga_condiciones.plan_version = sga_planes_certificados.plan_version AND
							sga_condiciones.condicion = sga_condiciones_grupos.condicion AND
							sga_condiciones.condicion_tipo = '" . condicion::tipo_certificado . "' AND
							sga_condiciones_grupos.grupo_condicion = sga_condiciones_requisitos.grupo_condicion AND
							sga_condiciones_requisitos.entidad = sga_elementos.entidad
							$where
		;";
		return guarani_db::consultar($sql);
	}

	function actividad_asociada_a_certificado_plan_version($plan_version, $actividad)
	{
		$actividad = toba::db()->quote($actividad);
		$filtro = "sga_elementos.elemento = $actividad";
		$actividades_certificados_plan = $this->get_actividades_certificados_plan($plan_version, $filtro);
		return (!empty($actividades_certificados_plan));
	}

	function get_plan_version_estado_edicion($plan_version)
	{
		if (!isset($plan_version))
			return false;
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	sga_planes_versiones.estado,
						sga_planes.tipo_plan,
						sga_planes.administra_cursos,
						sga_propuestas.propuesta_tipo
				FROM	sga_planes_versiones
						JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
						JOIN sga_propuestas ON sga_planes.propuesta = sga_propuestas.propuesta
				WHERE	sga_planes_versiones.plan_version = $plan_version
		";
		$res = guarani_db::consultar_fila($sql);
		if (!isset($res)) {
			return false;
		}
		return $res;
	}

	function get_plan_version_existe($plan_version)
	{
		if (!isset($plan_version))
			return false;
		$plan_version = toba::db()->quote($plan_version);
		$sql = " SELECT count(*) as cant
					FROM sga_planes_versiones as pv
					WHERE pv.plan_version = $plan_version
					;";
		$res = guarani_db::consultar_fila($sql);
		$cant = $res['cant'];
		return ($cant == 1);
	}

	/**
	 * Retorna los datos del conjunto de orientaciones definidas para un plan-versión dado.
	 *
	 * @param integer $plan_version
	 * @return array
	 */
	function get_plan_version_orientaciones($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	vw_elementos_plan.elemento,
						vw_elementos_plan.nombre as elemento_nombre
				FROM	vw_elementos_plan
				WHERE	vw_elementos_plan.entidad_subtipo = " . g3entidad::subtipo_orientacion . " AND
						vw_elementos_plan.plan_version = $plan_version
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * 
	 * @param int $plan_version
	 * @param int $elemento
	 * @return array
	 */
	function get_orientaciones_actividad($plan_version, $elemento)
	{
		$plan_version = toba::db()->quote($plan_version);
		$elemento = toba::db()->quote($elemento);

		$sql = "SELECT * FROM get_orientaciones_actividad($plan_version, $elemento) AS orientacion;";

		return guarani_db::consultar($sql);
	}

	/**
	 * @todo verificar cuál es la responsable académica correspondiente a la propuesta.
	 * Modificación temporal para que sigan funcionando las operaciones involucradas.
	 */
	function get_datos_plan($plan)
	{
		$plan = toba::db()->quote($plan);
		$sql = "SELECT	sga_responsables_academicas.codigo as			responsable_academica_codigo,
						sga_responsables_academicas.nombre as			responsable_academica_nombre,
						sga_propuestas.propuesta as						propuesta,
						sga_propuestas.codigo as						propuesta_codigo,
						sga_propuestas.nombre as						propuesta_nombre,
						sga_propuestas.nombre_abreviado as				propuesta_nombre_abreviado,
						sga_propuestas.propuesta_tipo,
						sga_planes.plan as								plan,
						sga_planes.nombre as							nombre,
						sga_planes.codigo as							codigo,
						sga_planes.version_actual as					version_actual,
						sga_planes_versiones.version as					version,
						sga_planes_versiones.nombre as					version_nombre,
						sga_planes.documento_alta as					documento_alta,
						sga_documentos_alta.documento_numero as			documento_alta_numero,
						sga_planes.fecha_entrada_vigencia as			fecha_entrada_vigencia,
						sga_planes.documento_baja as					documento_baja,
						sga_documentos_baja.documento_numero as			documento_baja_numero,
						sga_planes.fecha_baja as						fecha_baja,
						sga_planes.estado as 							estado,
						p_sga_planes_estados.descripcion as 			plan_estado_descripcion,
						sga_planes_versiones.estado as 					version_estado, 
						v_sga_planes_estados.descripcion as 			version_estado_descripcion

				FROM sga_planes
				JOIN sga_propuestas ON sga_planes.propuesta = sga_propuestas.propuesta
				JOIN sga_responsables_academicas ON TRUE 
				JOIN sga_planes_estados as p_sga_planes_estados ON sga_planes.estado = p_sga_planes_estados.estado
				LEFT JOIN sga_documentos as sga_documentos_alta ON sga_planes.documento_alta = sga_documentos_alta.documento
				LEFT JOIN sga_documentos as sga_documentos_baja ON sga_planes.documento_baja = sga_documentos_baja.documento
				LEFT JOIN sga_planes_versiones ON sga_planes.version_actual = sga_planes_versiones.plan_version
				LEFT JOIN sga_planes_estados as v_sga_planes_estados ON sga_planes_versiones.estado = v_sga_planes_estados.estado

				WHERE sga_planes.plan = $plan
				  AND sga_responsables_academicas.responsable_academica = get_ra_inst_araucano_propuesta(sga_propuestas.propuesta, 'RA')
				LIMIT 1  
		";
		return guarani_db::consultar($sql);
	}

	/*
	 * Retorna el Tipo de Plan del Plan al que pertenece el Plan Versión dado.
	 *
	 */
	function get_tipo_y_estado_plan_de_plan_version($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "	SELECT	sga_planes.plan as plan,
							sga_planes.tipo_plan,
							sga_planes.administra_cursos,
							sga_planes_versiones.estado,
							sga_propuestas.propuesta_tipo
					FROM	sga_planes_versiones
							JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
							JOIN sga_propuestas ON sga_planes.propuesta = sga_propuestas.propuesta
					WHERE	sga_planes_versiones.plan_version = $plan_version
		";
		return guarani_db::consultar_fila($sql);
	}

	/*
	 * Retorna la propuesta a la que pertenece el plan recibido
	 *
	 */
	function get_propuesta_de_plan($plan)
	{
		$plan = toba::db()->quote($plan);
                $propuesta = '';

		$sql = "SELECT	sga_planes.propuesta as propuesta
					FROM	sga_planes
					WHERE	sga_planes.plan = $plan";

		$datos = guarani_db::consultar_fila($sql);
                
                if (!empty($datos)) {
                    $propuesta = $datos['propuesta'];
                }
                
		return $propuesta;
	}

	function get_datos_plan_version($plan_version = null, $where = '')
	{
		$filtro = 'WHERE true';
		if (!is_null($plan_version)) {
			$plan_version = toba::db()->quote($plan_version);
			$filtro .= " AND	sga_planes_versiones.plan_version = $plan_version";
		}
		if ($where) {
			$filtro .= " AND	$where";
		}
		$sql = "	SELECT	sga_responsables_academicas.codigo as			responsable_academica_codigo,
							sga_responsables_academicas.nombre as			responsable_academica_nombre,
							sga_propuestas.propuesta as						propuesta,
							sga_propuestas.codigo as						propuesta_codigo,
							sga_propuestas.nombre as						propuesta_nombre,
							sga_planes.plan as								plan,
							sga_planes.nombre as							plan_nombre,
							sga_planes.codigo as							plan_codigo,
							sga_planes.version_actual as					version_actual,
							sga_planes.cobrable  as							plan_cobrable,
							sga_planes_versiones.plan_version as			plan_version,
							sga_planes_versiones.version as					version,
							sga_planes_versiones.nombre as					version_nombre,
							sga_documentos.documento as						documento_alta,
							sga_documentos.documento_numero as				documento_alta_numero,
							sga_planes_versiones.fecha_entrada_vigencia as	fecha_entrada_vigencia,
							sga_documentos_b.documento as					documento_baja,
							sga_documentos_b.documento_numero as			documento_baja_numero,
							sga_planes_versiones.fecha_baja as				fecha_baja,
							sga_planes.estado as 							plan_estado,
							p_sga_planes_estados.descripcion as 			plan_estado_descripcion,
							sga_planes_versiones.estado as 					estado, 
							v_sga_planes_estados.descripcion as 			version_estado_descripcion,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_descripcion,
							to_char(sga_planes_versiones.fecha_entrada_vigencia,'" . comunes::formato_fecha_visual . "') as fecha_entrada_vigencia_format
					FROM	sga_propuestas
							JOIN sga_propuestas_ra ON sga_propuestas.propuesta = sga_propuestas_ra.propuesta
							JOIN sga_responsables_academicas ON sga_propuestas_ra.responsable_academica = sga_responsables_academicas.responsable_academica
							JOIN sga_planes ON sga_propuestas.propuesta = sga_planes.propuesta
							JOIN sga_planes_estados as p_sga_planes_estados ON sga_planes.estado = p_sga_planes_estados.estado
							JOIN sga_planes_versiones ON sga_planes.plan = sga_planes_versiones.plan
							JOIN sga_planes_estados as v_sga_planes_estados ON sga_planes_versiones.estado = v_sga_planes_estados.estado
							LEFT JOIN sga_documentos ON sga_planes_versiones.documento_alta = sga_documentos.documento
							LEFT JOIN sga_documentos as sga_documentos_b ON sga_planes_versiones.documento_baja = sga_documentos_b.documento					
					$filtro		
		";
		return guarani_db::consultar($sql);
	}

	/*
	 * Retorna el plan al que pertenece el plan versión recibido
	 *
	 */
	function get_plan_de_plan_version($plan_version, $filtrar_perfil_de_datos=true)
	{
		$plan_version = toba::db()->quote($plan_version);

		$sql = "	SELECT	sga_planes.plan as plan
					FROM	sga_planes_versiones,
							sga_planes
					WHERE	sga_planes_versiones.plan_version = $plan_version
					AND		sga_planes.plan = sga_planes_versiones.plan 
							 ;";

		$datos = guarani_db::consultar_fila($sql, null, $filtrar_perfil_de_datos);
		return $datos['plan'];
	}

	/**
	 * Retorna los datos de un Plan-Versión en formato RecordSet, en caso de que el parámetro sea un 
	 * array retorna los datos de los Planes-Versiones según la condición del array (para la carga masiva).
	 * 	@param integer/array	plan_version	id o arreglo de ids del o de los planes-versiones para los cuales se consultan los datos.
	 */
	function get_planes_versiones_datos($plan_version)
	{
		if (is_array($plan_version)) {
			$filtro = guarani_sql::armar_condicion_in($plan_version, 'sga_planes_versiones', 'plan_version');
			$datos = $this->get_datos_plan_version(null, $filtro);
			return $datos;
		}
		$datos = $this->get_datos_plan_version($plan_version);
		if (!empty($datos)) {
			return $datos[0];
		}
	}

	function get_planes_datos($plan)
	{
		if (is_array($plan)) {
			$filtro = guarani_sql::armar_condicion_in($plan, 'sga_planes', 'plan');
		} else {
			$plan = toba::db()->quote($plan);
			$filtro = "sga_planes.plan = $plan";
		}
		// Se evita invocar al método 'get_listado' porque trae demasiadas columnas innecesarias para la carga externa.
		$sql = "SELECT	sga_planes.plan,
						sga_planes.codigo as plan_codigo,
						sga_planes.nombre as plan_nombre,
						sga_planes.tipo_plan,
						COALESCE('(' || sga_planes.codigo || ') ', '') || sga_planes.nombre as plan_descr,
						sga_propuestas.codigo,
						sga_propuestas.nombre as propuesta_nombre,
						sga_propuestas.nombre_abreviado,
						sga_propuestas.propuesta_tipo,
						sga_propuestas_tipos.descripcion as propuesta_tipo_desc
				FROM	sga_planes,
						sga_propuestas,
						sga_propuestas_tipos
				WHERE	sga_planes.propuesta = sga_propuestas.propuesta AND
						sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo AND
						$filtro
		";
		return guarani_db::consultar($sql);
	}

	function get_modulo_revision_raiz($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT sga_planes_versiones.modulo,
					   sga_elementos_revision.elemento_revision
				FROM   sga_planes_versiones, 
					   sga_elementos_revision,
    				   sga_elementos_plan
				WHERE sga_planes_versiones.modulo = sga_elemento_revision.elemento
				AND sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
				AND sga_elementos_plan.plan_version = sga_planes_versiones.plan_version
				AND sga_planes_versiones.plan_version = $plan_version";

		return guarani_db::consultar_fila($sql);
	}

	function get_planes_estados($where='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = "SELECT	estado,
						descripcion
				FROM	sga_planes_estados
				$where
		";
		return guarani_db::consultar($sql);
	}

	function get_planes_tipos()
	{
		return array(array('valor' => planes::tipo_estructurado, 'descr' => planes::tipo_estructurado),
			array('valor' => planes::tipo_personalizado, 'descr' => planes::tipo_personalizado),
			array('valor' => planes::tipo_convenio, 'descr' => planes::tipo_convenio),
		);
	}

	function get_planes_tipos_pers()
	{
		return array(array('valor' => planes::tipo_convenio, 'descr' => planes::tipo_convenio),
			array('valor' => planes::tipo_personalizado, 'descr' => planes::tipo_personalizado)
		);
	}

	/**
	  Obtiene una lista de planes con modalidad y ubicacion segun los parametros
	  inscripcion_propuesta_plan e inscripcion_propuesta_version
	 */
	function get_planes_prop_version_mod($propuesta)
	{

		if (!is_null($propuesta)) {
			$propuesta = toba::db()->quote($propuesta);
			$where = " AND sga_planes.propuesta = $propuesta";
		}

		// Busco los valores de los parametros: inscripcion_propuesta_plan e inscripcion_propuesta_version
		$insc_prop_plan = toba::consulta_php('co_parametros')->get_valor_parametro('inscripcion_propuesta_plan');
		$insc_prop_ver = toba::consulta_php('co_parametros')->get_valor_parametro('inscripcion_propuesta_version');
		if ($insc_prop_plan == 'V') {
			$where .= " AND sga_planes.estado = '" . planes::estado_activo_vigente . "' ";
		} else {
			$where .= " AND (sga_planes.estado = '" . planes::estado_activo_vigente . "' OR sga_planes.estado = '" . planes::estado_activo . "' )";
		}
		if ($insc_prop_ver == 'V') {
			$where .= " AND sga_planes_versiones.estado = '" . planes::estado_activo_vigente . "' ";
		} else {
			$where .= " AND (sga_planes_versiones.estado = '" . planes::estado_activo_vigente . "' OR sga_planes_versiones.estado = '" . planes::estado_activo . "' )";
		}

		$sql = "SELECT 	sga_planes.plan as valor,
						sga_planes.nombre as descr,
						sga_planes.plan,
						sga_planes.nombre,
						sga_planes.codigo, 
						sga_planes.fecha_entrada_vigencia, 
						sga_planes.fecha_baja, 
						sga_planes.inscripcion_habilitada, 
						sga_planes.entidad, 
						sga_planes.estado,
						sga_planes_versiones.version,
						sga_planes_versiones.plan_version,
						sga_modalidad_cursada.nombre as modalidad_nombre,
						sga_planes_modalidad.modalidad
  				FROM 	sga_planes,
  						sga_planes_versiones,
  						sga_planes_modalidad,
  						sga_modalidad_cursada 
  				WHERE 
  						sga_planes.plan = sga_planes_versiones.plan 
  						AND sga_planes.plan = sga_planes_modalidad.plan
  						AND sga_modalidad_cursada.modalidad = sga_planes_modalidad.modalidad
  						AND sga_planes.inscripcion_habilitada = 'S' 
				$where
				ORDER BY sga_planes.nombre
				;";
		return guarani_db::consultar($sql);
	}

	function get_datos_modalidad($mod = null)
	{
		if (!is_null($mod)) {
			$mod = toba::db()->quote($mod);
			$where = " AND sga_planes_modalidad.modalidad = '" . modalidades::presencial . "' ";
		} else {
			$where = '';
		}

		$sql = "SELECT 
						sga_modalidad_cursada.nombre as modalidad_nombre,
						sga_planes_modalidad.modalidad
  				FROM 	
  						sga_planes_modalidad,
  						sga_modalidad_cursada 
  				WHERE 
  						sga_modalidad_cursada.modalidad = sga_planes_modalidad.modalidad
						$where ";

		return guarani_db::consultar_fila($sql);
	}

	function get_cantidad_casos_test_plan($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT COUNT(*) as cantidad
				FROM ct_plan
				WHERE plan_version = $plan_version";
		$datos = guarani_db::consultar_fila($sql);
		if ($datos) {
			return $datos['cantidad'];
		}
	}

	/**
	  Valida si un cï¿½digo se puede usar o no
	 */
	function es_codigo_plan_valido($codigo, $propuesta = null, $plan = null)
	{
		$codigo = toba::db()->quote($codigo);
		if (isset($propuesta))
			$propuesta = toba::db()->quote($propuesta);
		if (isset($plan))
			$plan = toba::db()->quote($plan);
		$sql = "SELECT 	count(*) as cant 	
                FROM 	sga_planes
				WHERE 	sga_planes.codigo = $codigo
				";
		if (isset($propuesta))
			$sql .= " AND sga_planes.propuesta = $propuesta ";
		if (isset($plan))
			$sql .= " AND sga_planes.plan <> $plan ";
		$res = guarani_db::consultar_fila($sql);
		return ( $res['cant'] == 0);
	}

	/**
	  Valida si un cï¿½digo de versiï¿½n se puede usar o no
	 */
	function es_codigo_plan_version_valido($codigo, $plan = null, $plan_version = null)
	{
		$codigo = toba::db()->quote($codigo);
		if (isset($plan))
			$plan = toba::db()->quote($plan);
		if (isset($plan_version))
			$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT 	count(*) as cant 	
                FROM 	sga_planes_versiones
				WHERE 	sga_planes_versiones.version = $codigo
				";
		if (isset($plan))
			$sql .= " AND sga_planes_versiones.plan = $plan ";
		if (isset($plan_version))
			$sql .= " AND sga_planes_versiones.plan_version <> $plan_version ";
		$res = guarani_db::consultar_fila($sql);
		return ( $res['cant'] == 0);
	}

	//-----------------------------------------------------------------------
	//-- Preguntas sobre elementos
	//-----------------------------------------------------------------------

	function get_modalidades($where = '')
	{
		if ($where) {
			$where = ' WHERE ' . $where . ' ';
		}

		$sql = "SELECT	sga_modalidad_cursada.modalidad,
                       	sga_modalidad_cursada.nombre as modalidad_nombre
                FROM 	sga_modalidad_cursada
                $where";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el nombre de una modalidad de cursada asociadas a un plan-version, en caso de que el parámetro 
	 * sea un array retorna los nombres de las modalidades del plan-version según la condición del array 
	 * (para la carga externa masiva).
	 * 	@param integer/array	modalidad	id o arreglo de ids de la o de las modalidades para las cuales se 
	 * consultan los nombres.
	 */
	function get_nombres_modalidades($modalidad)
	{
		if (is_array($modalidad)) {
			$filtro = guarani_sql::armar_condicion_in($modalidad, 'sga_modalidad_cursada', 'modalidad');
			$datos = $this->get_modalidades($filtro);
			return $datos;
		}
		$modalidad = toba::db()->quote($modalidad);
		$datos = $this->get_modalidades("sga_modalidad_cursada.modalidad = $modalidad");

		if (!empty($datos)) {
			return $datos[0];
		}
	}

	function get_info_modalidades($modalidad)
	{
		$modalidad = toba::db()->quote($modalidad);

		$sql = "SELECT	sga_modalidad_cursada.modalidad,
                       	sga_modalidad_cursada.nombre as modalidad_nombre
                FROM 	sga_modalidad_cursada
                WHERE	sga_modalidad_cursada.modalidad = $modalidad";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna todas las modalidades asociadas al conjunto de elementos-planes dado como parámetro.
	 * 
	 * @param array $elementos_planes
	 * @return array
	 */
	function get_modalidades_elementos_planes($elementos_planes)
	{
		$where = guarani_sql::armar_condicion_in($elementos_planes, 'sga_elementos_plan_modalidad', 'elemento_plan', 'elemento_plan');
		$sql = "SELECT	sga_elementos_plan_modalidad.elemento_plan,
						sga_elementos_plan_modalidad.modalidad,
						sga_modalidad_cursada.nombre as modalidad_nombre
                FROM 	sga_elementos_plan_modalidad
						JOIN sga_modalidad_cursada ON sga_elementos_plan_modalidad.modalidad = sga_modalidad_cursada.modalidad
                WHERE	$where
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene todas las modalidades asociadas a un elemento-plan.
	 *
	 */
	function get_modalidad_elemento_plan($elemento_plan)
	{
		$elemento_plan = toba::db()->quote($elemento_plan);

		$sql = "SELECT	sga_elementos_plan_modalidad.modalidad
                FROM 	sga_elementos_plan_modalidad
                WHERE	sga_elementos_plan_modalidad.elemento_plan = $elemento_plan";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene todas las modalidades asociadas en las tablas temporales a los elementos_planes dados como parametros.
	 *
	 */
	function get_modalidades_elementos_planes_temporales($elementos_planes)
	{
		$where = ' AND ' . guarani_sql::armar_condicion_in($elementos_planes, 'sga_elementos_plan_modalidad_aux', 'elemento_plan', 'elemento_plan');

		$sql = "SELECT	sga_elementos_plan_modalidad_aux.elemento_plan,
						sga_elementos_plan_modalidad_aux.modalidad,
						sga_modalidad_cursada.nombre AS modalidad_nombre
                FROM 	sga_elementos_plan_modalidad_aux,
						sga_modalidad_cursada
                WHERE	sga_elementos_plan_modalidad_aux.modalidad = sga_modalidad_cursada.modalidad
						$where";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene todas las modalidades asociadas a un elemento-plan definidas en la tabla de temporales.
	 *
	 */
	function get_modalidad_elemento_plan_temporal($elemento_plan)
	{
		$elemento_plan = toba::db()->quote($elemento_plan);

		$sql = "SELECT	sga_elementos_plan_modalidad_aux.elemento_plan,
						sga_elementos_plan_modalidad_aux.modalidad,
						sga_modalidad_cursada.nombre AS modalidad_nombre
                FROM 	sga_elementos_plan_modalidad_aux,
						sga_modalidad_cursada
                WHERE	sga_elementos_plan_modalidad_aux.elemento_plan = $elemento_plan
						AND sga_elementos_plan_modalidad_aux.modalidad = sga_modalidad_cursada.modalidad";

		return guarani_db::consultar($sql);
	}

	function get_valores_iniciales_actividad($elemento)
	{
		$elemento = toba::db()->quote($elemento);
		$sql = "SELECT	sga_elementos.nombre as nombre,
						sga_elementos.nombre_abreviado as nombre_abreviado,
						sga_elementos.entidad_subtipo as entidad_subtipo,
						sga_g3entidades_subtipos.entidad_tipo as	entidad_tipo,
						sga_elementos_atrib.*
				FROM	sga_elementos_atrib,
						sga_elementos,
						sga_g3entidades_subtipos
				WHERE	sga_elementos.elemento = sga_elementos_atrib.elemento
				AND		sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
				AND	sga_elementos.elemento = $elemento";
		return guarani_db::consultar_fila($sql);
	}

	function get_modalidades_cursadas($modalidad = null)
	{
		$where = '';
		if (is_array($modalidad) && !empty($modalidad)) {
			$where = 'WHERE ' . guarani_sql::armar_condicion_in($modalidad, 'sga_modalidad_cursada', 'modalidad');
		}

		$sql = "SELECT 		sga_modalidad_cursada.modalidad as valor,
                       		sga_modalidad_cursada.descripcion as descr
                FROM   		sga_modalidad_cursada
				$where
        		ORDER BY 	sga_modalidad_cursada.descripcion
				";

		return guarani_db::consultar($sql);
	}

	function get_modalidades_plan($plan)
	{
		$plan = toba::db()->quote($plan);
		$sql = "SELECT sga_planes_modalidad.modalidad
                FROM sga_planes_modalidad
				WHERE sga_planes_modalidad.plan = $plan;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Permite recuperar los datos de las modalidades de cursadas asociadas a un determinado plan de un plan versión dado.
	 *
	 * @param integer	$plan_version	id del plan versión asociado al plan para el cual se recuperan las modalidades.
	 * @param str		$where 			filtro sobre las modalidades a recuperar.
	 */
	function get_modalidades_plan_version($plan_version, $where = '')
	{
		$plan_version = toba::db()->quote($plan_version);
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT 		sga_planes_modalidad.modalidad,
							sga_planes_modalidad.plan,
							sga_modalidad_cursada.nombre as modalidad_nombre
                FROM 		sga_planes_modalidad,
                			sga_planes_versiones,
                			sga_modalidad_cursada
				WHERE 		sga_planes_modalidad.plan = sga_planes_versiones.plan AND
							sga_planes_modalidad.modalidad = sga_modalidad_cursada.modalidad AND
							sga_planes_versiones.plan_version = $plan_version
							$where
				ORDER BY	sga_modalidad_cursada.nombre
			;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de niveles de formación
	 */
	function get_niveles_formacion($where = '')
	{
		if ($where) {
			$where = 'WHERE ' . $where;
		}

		$sql = "SELECT 	
    				sga_niveles_formacion.nivel_formacion,
                    sga_niveles_formacion.nombre,
                    sga_niveles_formacion.descripcion
                FROM 	
                	sga_niveles_formacion
                $where
                ORDER BY
                	sga_niveles_formacion.nombre";

		return guarani_db::consultar($sql);
	}

	/**
	 *  Valida si un nombre de nivel de formación se puede usar o no.
	 */
	function validar_nombre_nivel_formacion($nombre, $nivel)
	{
		if (!isset($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);

		$sql = 'SELECT	count(sga_niveles_formacion.nivel_formacion) as cant
                FROM 	sga_niveles_formacion
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('sga_niveles_formacion.nombre', $nombre) . '
				';

		if (!is_null($nivel)) {
			$nivel = toba::db()->quote($nivel);
			$sql .= " AND sga_niveles_formacion.nivel_formacion <> $nivel ";
		}

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	//-----------------------------------------------------------------------
	//-- Proceso de aplanado.
	//-----------------------------------------------------------------------

	function get_planes_versiones_por_institucion($entidad)
	{
		$entidad = toba::db()->quote($entidad);

		$sql = "SELECT	sga_planes_versiones.plan_version
				FROM	sga_planes_versiones
				WHERE	sga_planes_versiones.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "') AND
						sga_planes_versiones.plan IN (
						SELECT	sga_planes.plan
						FROM	sga_planes
						WHERE	sga_planes.propuesta IN (
								SELECT	sga_propuestas_ra.propuesta
								FROM	sga_propuestas_ra,
										sga_propuestas
								WHERE	sga_propuestas_ra.propuesta = sga_propuestas.propuesta AND
										sga_propuestas_ra.responsable_academica IN (
										SELECT	sga_responsables_academicas.responsable_academica
										FROM	sga_responsables_academicas
										WHERE	sga_responsables_academicas.institucion = (
												SELECT	sga_instituciones.institucion
												FROM	sga_instituciones
												WHERE	sga_instituciones.entidad = $entidad)
				)))
		";
		return guarani_db::consultar($sql);
	}

	function get_planes_versiones_por_ra($entidades)
	{
		$where = guarani_sql::armar_condicion_in($entidades, 'sga_responsables_academicas', 'entidad', 'entidad');

		$sql = "SELECT	sga_planes_versiones.plan_version
				FROM	sga_planes_versiones
				WHERE	sga_planes_versiones.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "') AND
						sga_planes_versiones.plan IN (
						SELECT	sga_planes.plan
						FROM	sga_planes
						WHERE	sga_planes.propuesta IN (
								SELECT	sga_propuestas_ra.propuesta
								FROM	sga_propuestas_ra,
										sga_propuestas
								WHERE	sga_propuestas_ra.propuesta = sga_propuestas.propuesta AND
										sga_propuestas_ra.responsable_academica IN (
										SELECT	sga_responsables_academicas.responsable_academica
										FROM	sga_responsables_academicas
										WHERE	$where
				)))
		";
		return guarani_db::consultar($sql);
	}

	function get_planes_versiones_por_propuesta($subtipos, $entidades)
	{
		if (!empty($subtipos)) {
			$where = guarani_sql::armar_condicion_in($subtipos, 'sga_g3entidades', 'entidad_subtipo', 'entidad_subtipo');
		} else {
			$where = guarani_sql::armar_condicion_in($entidades, 'sga_propuestas', 'entidad', 'entidad');
		}

		$sql = "SELECT	sga_planes_versiones.plan_version
				FROM	sga_planes_versiones
				WHERE	sga_planes_versiones.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "') AND
						sga_planes_versiones.plan IN (
						SELECT	sga_planes.plan
						FROM	sga_planes
						WHERE	sga_planes.propuesta IN (
								SELECT	sga_propuestas.propuesta
								FROM	sga_propuestas,
										sga_g3entidades
								WHERE	sga_propuestas.entidad = sga_g3entidades.entidad AND
										$where
				))
		";
		return guarani_db::consultar($sql);
	}

	function get_planes_versiones_por_plan($entidades)
	{
		$where = guarani_sql::armar_condicion_in($entidades, 'sga_planes', 'entidad', 'entidad');

		$sql = "SELECT	sga_planes_versiones.plan_version
				FROM	sga_planes_versiones
				WHERE	sga_planes_versiones.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "') AND
						sga_planes_versiones.plan IN (
						SELECT	sga_planes.plan
						FROM	sga_planes
						WHERE	$where
				)
		";
		return guarani_db::consultar($sql);
	}

	function get_planes_versiones($entidades)
	{
		$where = guarani_sql::armar_condicion_in($entidades, 'sga_planes_versiones', 'entidad', 'entidad');

		$sql = "SELECT	sga_planes_versiones.plan_version
				FROM	sga_planes_versiones
				WHERE	sga_planes_versiones.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "') AND
						$where
		";
		return guarani_db::consultar($sql);
	}

	function get_versiones_por_plan($plan, $where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$plan = toba::db()->quote($plan);

		$sql = "SELECT 
					sga_planes_versiones.plan_version AS valor,
					sga_planes_versiones.nombre AS descr
				FROM 
					sga_planes_versiones
				WHERE
					sga_planes_versiones.plan = $plan
					$where
				;";

		return guarani_db::consultar($sql);
	}

	function propuesta_posee_plan_version_activo($propuesta)
	{
		$propuesta = toba::db()->quote($propuesta);

		$sql = "SELECT 		COUNT(1) as cant
				FROM 		sga_planes_versiones,
							sga_planes
				WHERE		sga_planes_versiones.plan = sga_planes.plan
							AND	sga_planes.propuesta = $propuesta
				            AND sga_planes_versiones.estado IN ('" . planes::estado_activo . "', '" . planes::estado_vigente . "')
				;";

		$res = guarani_db::consultar_fila($sql);
		return $res['cant'] > 0;
	}

	/**
	 * Determina si un plan-versión dado tiene al menos un alumno de calidad ACTIVO.
	 *
	 * @param integer $plan_version
	 * @return boolean
	 */
	function plan_version_posee_alumnos_activos($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	count(sga_alumnos.alumno) as cant
				FROM	sga_alumnos
				WHERE	sga_alumnos.plan_version = $plan_version AND
						sga_alumnos.calidad = '" . alumno::calidad_activo . "'
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'] > 0;
	}

	/**
	 * Retorna el módulo raíz del plan versión dado, junto a los datos de su forma de cumplimiento.
	 * 
	 * @param integer $plan_version
	 */
	function get_modulo_raiz($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	get_plan_contenido.elemento,
						sga_elementos_revision.regla,
						sga_elementos_revision.parametros
				FROM	get_plan_contenido($plan_version, false, false),
						sga_elementos_revision
				WHERE	get_plan_contenido.elemento_revision = sga_elementos_revision.elemento_revision
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna el plan-versión vigente para un plan de estudios dado.
	 * 
	 * @param integer $plan
	 * @return integer
	 */
	function get_version_actual($plan)
	{
		$sql = "SELECT	sga_planes.version_actual
				FROM	sga_planes
				WHERE	sga_planes.plan = $plan
		";
		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['version_actual'];
	}

	function get_planes_versiones_destino_cambio_alumnos($where = '')
	{
		if ($where) {
			$filtro = " AND		$where";
		}
		$sql = "	SELECT	sga_propuestas.propuesta as						propuesta,
							sga_propuestas.codigo as						propuesta_codigo,
							sga_propuestas.nombre as						propuesta_nombre,
							sga_planes.plan as								plan,
							sga_planes.nombre as							plan_nombre,
							sga_planes.codigo as							plan_codigo,
							sga_planes_versiones.plan_version as			plan_version_destino,
							sga_planes_versiones.version as					version,
							sga_planes_versiones.nombre as					version_nombre,
							sga_planes_versiones.fecha_entrada_vigencia as	fecha_entrada_vigencia,
							sga_planes.estado as 							plan_estado,
							p_sga_planes_estados.descripcion as 			plan_estado_descripcion,
							sga_planes_versiones.estado as 					estado, 
							v_sga_planes_estados.descripcion as 			version_estado_descripcion,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_descripcion,
							f_modalidad_cursada_plan(sga_planes.plan) as	modalidades_plan
					FROM	sga_propuestas,
							sga_planes,
							sga_planes_versiones,
							sga_planes_estados as p_sga_planes_estados,
							sga_planes_estados as v_sga_planes_estados 
				 	WHERE	sga_propuestas.propuesta = sga_planes.propuesta
					AND 	sga_planes_versiones.plan = sga_planes.plan
					AND 	sga_planes.estado = p_sga_planes_estados.estado
					AND 	sga_planes_versiones.estado = v_sga_planes_estados.estado
					$filtro
							;";

		return guarani_db::consultar($sql);
	}

	function get_opciones_planes_personalizados()
	{
		return array(array('valor' => 'S', 'descr' => 'Que acepten planes personalizados'),
			array('valor' => 'N', 'descr' => 'Que NO acepten planes personalizados')
		);
	}

	function get_opciones_planes_versiones_activos($plan)
	{
		$plan = toba::db()->quote($plan);

		$sql = "SELECT 
					sga_planes_versiones.plan_version AS valor,
					sga_planes_versiones.nombre AS descr
				FROM 
					sga_planes_versiones
				WHERE
					sga_planes_versiones.plan = $plan
					AND sga_planes_versiones.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "') 
				;";

		return guarani_db::consultar($sql);
	}

	function get_nombre_modulos($elemento, $plan_version)
	{
		$sql = "
				SELECT
					*
				FROM
					get_nombres_modulos($elemento, $plan_version, 1)
				";
		$datos = guarani_db::consultar_fila($sql);
		return $datos['get_nombres_modulos'];
	}

	/**
	 * Determina si un módulo existe dentro de un plan versión.
	 * 
	 * @param int $modulo
	 * @param int $plan_version
	 * @return boolean
	 */
	function existe_modulo_en_plan_version($modulo, $plan_version)
	{
		$modulo = toba::db()->quote($modulo);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT	COUNT(vw_elementos_plan.elemento) as cant
				FROM	vw_elementos_plan
				WHERE	vw_elementos_plan.elemento = $modulo AND
						vw_elementos_plan.plan_version = $plan_version
		";
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] > 0);
	}

	function get_planes_activos_vigentes_elemento($elemento)
	{
		$ele = toba::db()->quote($elemento);
		$estados = array(planes::estado_activo, planes::estado_nuevo, planes::estado_activo_vigente);

		$sql = "SELECT
					DISTINCT vw_actividades_plan.plan,
					sga_propuestas.nombre as propuesta_nombre,
					vw_actividades_plan.plan,
					sga_planes.nombre as plan_nombre,
					sga_planes.codigo as codigo
				FROM
					vw_actividades_plan
					,sga_planes_versiones
					,sga_propuestas
					,sga_planes
				WHERE
					vw_actividades_plan.plan_version = sga_planes_versiones.plan_version
					AND vw_actividades_plan.propuesta = sga_propuestas.propuesta
					AND sga_planes.plan = vw_actividades_plan.plan
					AND vw_actividades_plan.elemento = $elemento
					AND " . guarani_sql::armar_condicion_in($estados, 'sga_planes_versiones', 'estado') . "
				";

		$datos = guarani_db::consultar($sql);
		if (!isset($datos)) {
			throw new guarani_error("Error al recuperar los planes a los que pertenece el elemento");
		}
		return $datos;
	}

	/**
	 * Retorna un booleano diciendo si el plan es cobrable o no.
	 * 
	 * @param integer $plan
	 * @return boolean
	 */
	function es_plan_cobrable($plan)
	{
		$plan = toba::db()->quote($plan);

		$sql = "SELECT	sga_planes.cobrable
				FROM	sga_planes
				WHERE	sga_planes.plan = $plan
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cobrable'] == comunes::si) ? true : false;
	}

	/**
	 * Retorna el plan desde 'propuesta_aspira'.
	 * 
	 * @param integer $propuesta_aspira
	 * @return integer Plan
	 */
	function get_plan_propuesta_aspira($propuesta_aspira)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);

		$sql = "SELECT sga_planes_versiones.plan
				FROM sga_propuestas_aspira
				JOIN sga_planes_versiones ON (sga_propuestas_aspira.plan_version = sga_planes_versiones.plan_version)
				WHERE sga_propuestas_aspira.propuesta_aspira = $propuesta_aspira
		";

		$rs = guarani_db::consultar_fila($sql);
		return $rs['plan'];
	}

	/**
	 * Retorna la cantidad total de actividades que tiene un plan-versión en un año de cursada.
	 * 
	 * @param integer $plan_version
	 * @param integer $anio
	 * @return integer
	 */
	function get_cant_actividades_anio_cursada($plan_version, $anio, $operador_anio = '=')
	{
		$plan_version = toba::db()->quote($plan_version);
		$anio = toba::db()->quote($anio);
		$sql = "SELECT	COUNT(sga_elementos_plan.elemento_plan) as cant_actividades
				FROM	sga_elementos_plan,
						sga_elementos_revision,
						sga_elementos,
						sga_g3entidades_subtipos
				WHERE	sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision AND
						sga_elementos_revision.elemento = sga_elementos.elemento AND
						sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo AND
						sga_g3entidades_subtipos.entidad_tipo = " . g3entidad::tipo_actividad . " AND
						sga_elementos_plan.plan_version = $plan_version AND
						sga_elementos_plan.anio_de_cursada $operador_anio $anio
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant_actividades'];
	}

	/**
	 * Retorna las actividadades que tiene una version de plan de estudios en un año o hasta un año.
	 * 
	 * @param integer $plan_version
	 * @param integer $anio
	 * @param integer $tipo			# Valores posibles: N (Actividades Normales) / G (Actividades Genéricas)	 
	 * @param varchar $operador_anio  # Valores posibles: =, <=, <, >=, >, <>
	 * @return integer
	 */
	function get_actividades_anio_cursada($plan_version, $anio, $operador_anio = '=')
	{
		$plan_version = toba::db()->quote($plan_version);
		$anio = toba::db()->quote($anio);
		$tipo_actividad   = toba::db()->quote(elemento::tipo_actividad);
		$subtipo_generica = toba::db()->quote(elemento::subtipo_materia_generica);
		
		if ($tipo == 'N') {
			// Recupero actividades Normales (no optativas)
			$sql = "SELECT DISTINCT sga_elementos_revision.elemento as elemento
					FROM sga_elementos_plan
					JOIN sga_elementos_revision ON sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
					JOIN sga_elementos ON sga_elementos_revision.elemento = sga_elementos.elemento
					JOIN sga_g3entidades_subtipos ON sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
					WHERE sga_elementos_plan.plan_version = $plan_version
					AND sga_g3entidades_subtipos.entidad_tipo = $tipo_actividad
					AND sga_elementos_plan.anio_de_cursada $operador_anio $anio
					-- y no es una actividad optativa.
					AND NOT IN (SELECT DISTINCT elemento FROM vw_optativas_plan WHERE plan_version = $plan_version)
					";
		} 
		if ($tipo == 'G') {
			// Recupero actividades Genéricas
			$sql = "SELECT DISTINCT sga_elementos_revision.elemento as elemento, sga_elementos_revision.regla, sga_elementos_revision.parametros
					  FROM sga_elementos_plan
					  JOIN sga_elementos_revision ON sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
					  JOIN sga_elementos ON sga_elementos_revision.elemento = sga_elementos.elemento
					  JOIN sga_g3entidades_subtipos ON sga_elementos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
					 WHERE sga_elementos_plan.plan_version = $plan_version
					   AND sga_g3entidades_subtipos.entidad_subtipo = $subtipo_generica
					   AND sga_elementos_plan.anio_de_cursada $operador_anio $anio
					";
		} 
		
		// Retorno las actividades que se encontraron en la version del plan de estudios.		
		$rs = guarani_db::consultar($sql);
		return $rs;
	}





	/**
	 * Retorna la cantidad total de actividades que tiene una version de plan de estudios.
	 * Suma las actividades normales y una actividad por cada actividad generica (considera que cada generica se cumple con una optativa)
	 * Si tiene orientaciones, suma todas las actividades de todas las orientaciones.
	 * 
	 * @param integer $plan_version
	 * @return integer
	 */
	function get_cant_actividades_plan_version($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);

		// Actividades normales del plan de estudios. Quito las optativas.
		$sql = "SELECT COUNT(1) as cantidad
				  FROM vw_actividades_plan
				 WHERE vw_actividades_plan.plan_version = $plan_version 
				   AND vw_actividades_plan.elemento NOT IN (SELECT elemento  FROM vw_optativas_plan WHERE vw_optativas_plan.plan_version = $plan_version)
		";
		$rs = guarani_db::consultar_fila($sql);
		$cant_actividades_normales = $rs['cantidad'];

		// Actividades genericas del plan. Considero una actividad optativa por cada generica...(podria no ser lo correcto, pero en general es una optativa por cada generica.)
		$sql_gen = "SELECT COUNT(DISTINCT vw_optativas_plan.elemento_generica) as cantidad
				      FROM vw_optativas_plan 
				     WHERE vw_optativas_plan.plan_version = $plan_version
		       ";
		$rs_gen = guarani_db::consultar_fila($sql_gen);
		$cant_actividades_genericas = $rs_gen['cantidad'];
		
		return ($cant_actividades_normales + $cant_actividades_genericas);
	}


	/**
	 * Retorna datos de un plan filtrado por plan_version.
	 * 
	 * @param integer $plan_version
	 * @return array
	 */
	function get_datos_plan_by_plan_version($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);

		$sql = "SELECT 	sga_planes.plan,
						sga_planes.propuesta,
						sga_planes.nombre				AS plan_nombre,
						sga_planes.codigo				AS plan_codigo,
						sga_planes.estado				AS plan_estado,
						sga_planes.tipo_plan			AS plan_tipo,
						sga_planes.administra_cursos	AS plan_adm_cursos,
						sga_planes.cobrable				AS plan_cobrable,
						sga_planes.sq_sincronizado		AS plan_sq_sincronizado,
						sga_planes_versiones.plan_version,
						sga_planes_versiones.version	AS plan_version_version,
						sga_planes_versiones.nombre		AS plan_version_nombre,
						sga_planes_versiones.estado		AS plan_version_estado
				FROM sga_planes_versiones
				JOIN sga_planes ON (sga_planes_versiones.plan = sga_planes.plan)
				WHERE sga_planes_versiones.plan_version = $plan_version
		";
		return guarani_db::consultar_fila($sql);
	}

	/*
	 * Devuelve las actividades genericas que comparten optativas
	 */
	function get_actividades_genericas_con_optativas_compartidas($plan_version)
	{
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT		DISTINCT vw_actividades_genericas_plan.elemento,
							vw_actividades_genericas_plan.elemento_revision,
							vw_actividades_genericas_plan.nombre,
							vw_actividades_genericas_plan.nombre_abreviado,
							vw_actividades_genericas_plan.codigo,
							sga_elementos_revision.regla,
							sga_elementos_revision.parametros,
							sga_reglas.nombre as regla_nombre,
							get_regla_mensaje(sga_elementos_revision.regla, sga_elementos_revision.parametros) as forma_cumplimiento_generica	   
				FROM		vw_actividades_genericas_plan
							JOIN vw_optativas_plan ON vw_actividades_genericas_plan.elemento_revision = vw_optativas_plan.elemento_revision_generica AND vw_actividades_genericas_plan.plan_version = vw_optativas_plan.plan_version
							JOIN sga_elementos_revision ON vw_actividades_genericas_plan.elemento_revision = sga_elementos_revision.elemento_revision
							JOIN sga_reglas ON sga_elementos_revision.regla = sga_reglas.regla
				WHERE		vw_actividades_genericas_plan.plan_version = $plan_version AND
							EXISTS (SELECT	1
									FROM	vw_actividades_genericas_plan as otra_generica
											JOIN vw_optativas_plan as otra_optativa ON otra_generica.elemento_revision = otra_optativa.elemento_revision_generica AND otra_generica.plan_version = otra_optativa.plan_version
									WHERE	vw_actividades_genericas_plan.plan_version = otra_generica.plan_version AND
											vw_actividades_genericas_plan.elemento <> otra_generica.elemento AND
											vw_optativas_plan.elemento = otra_optativa.elemento)
				ORDER BY	vw_actividades_genericas_plan.nombre
		";
		return guarani::db()->consultar($sql);
	}

	function get_genericas_plan_version($plan_version)
	{
		$sql = "SELECT	vw_actividades_genericas_plan.elemento
				FROM	vw_actividades_genericas_plan 
				WHERE	vw_actividades_genericas_plan.plan_version = $plan_version
		";
		return guarani_db::consultar($sql);
	}

	/*
	 * Devuelve los modulos rtf segun el filtro especificado.
	 * @param string $where
	 * @return array
	 */
	function get_modulos_rtf($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT 
					sga_propuestas.propuesta as propuesta, 
					sga_propuestas.nombre as propuesta_nombre, 
					sga_propuestas.codigo as propuesta_codigo,
					sga_certificados.certificado as certificado,
					sga_certificados.nombre as certificado_nombre,
					vw_modulos_plan.elemento as elemento,
					vw_modulos_plan.elemento_revision as elemento_revision,
					sga_modulos_rtf.codigo as rtf_codigo,
					sga_modulos_rtf.nombre as rtf_nombre,
					sga_modulos_rtf.creditos as rtf_creditos,
					sga_elementos_revision.regla as regla,
					sga_elementos_revision.parametros as parametros,
					get_regla_mensaje(sga_elementos_revision.regla, sga_elementos_revision.parametros) as forma_cumplimiento,
					sga_alumnos.alumno as alumno,
					sga_alumnos.plan_version as plan_version

				FROM sga_alumnos
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_planes_certificados ON sga_planes_certificados.plan_version = sga_alumnos.plan_version
					JOIN sga_certificados ON sga_certificados.certificado = sga_planes_certificados.certificado
					JOIN get_actividades_certificado_alumno(sga_alumnos.alumno, sga_planes_certificados.certificado, sga_alumnos.plan_version, false, true) as e on true
					JOIN vw_modulos_plan ON (vw_modulos_plan.plan_version = sga_alumnos.plan_version AND vw_modulos_plan.elemento = e.elemento)
					JOIN sga_elementos_revision ON sga_elementos_revision.elemento_revision = vw_modulos_plan.elemento_revision
					JOIN sga_modulos_rtf ON sga_modulos_rtf.codigo = vw_modulos_plan.codigo
				WHERE true
				$where
				ORDER BY propuesta_nombre, certificado_nombre, rtf_nombre 
				";
		return guarani::db()->consultar($sql);		
	}
	
	
	/*
	 * Devuelve las actividades aprobadas de un modulo rtf.
	 * @param integer $alumno
	 * @param integer $elemento_rtf
	 * @return array
	 */
	public function get_actividades_de_rtf ($alumno, $elemento_revision_modulo_rtf)
	{
		$sql = "
			  SELECT 
				vw_hist_academica.actividad_nombre,
				vw_hist_academica.nota,
				to_char(vw_hist_academica.fecha,'DD/MM/YYYY') as fecha
			  FROM vw_hist_academica
			  WHERE vw_hist_academica.alumno = $alumno
				AND vw_hist_academica.resultado = 'A'
				AND vw_hist_academica.elemento IN (
				SELECT sga_elementos_revision.elemento 
				  FROM sga_elementos_comp 
				  JOIN sga_elementos_revision ON sga_elementos_revision.elemento_revision = sga_elementos_comp.elemento_hijo 
				 WHERE sga_elementos_comp.elemento_padre = $elemento_revision_modulo_rtf
				) -- Componentes del modulo RTF (Actividades)
			  ORDER BY 2 
				";
		return guarani::db()->consultar($sql);
	}

	/**
	 * Retorna los creditos de una actividad e una version de plan de estudios.
	 *
	 * @param integer $plan_version
	 * @param integer $actividad
	 * @return float
	 */
	function get_creditos_actividad_plan($plan_version, $actividad)
	{
		$plan_version = toba::db()->quote($plan_version);
		$actividad    = toba::db()->quote($actividad);
		$sql = "SELECT	COALESCE(sga_elementos_plan.creditos, 0) as creditos
				FROM	sga_elementos_revision
				JOIN 	sga_elementos_plan ON (sga_elementos_plan.plan_version = $plan_version AND sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision)
				WHERE	sga_elementos_revision.elemento = $actividad
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['creditos'];
	}
	
	/**
	 * Retorna las horas de una actividad en una version de plan de estudios.
	 *
	 * @param integer $plan_version
	 * @param integer $actividad
	 * @return float
	 */
	function get_horas_actividad_plan($plan_version, $actividad)
	{
		$plan_version = toba::db()->quote($plan_version);
		$actividad    = toba::db()->quote($actividad);
		$sql = "SELECT	COALESCE(sga_elementos_plan.horas_totales, 0) as horas
				FROM	sga_elementos_revision
				JOIN 	sga_elementos_plan ON (sga_elementos_plan.plan_version = $plan_version AND sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision)
				WHERE	sga_elementos_revision.elemento = $actividad
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['horas'];
	}
	
    /*
     * Trae el detalle de materias de un plan version
     */
    function get_detalle_materias_plan_version($plan_version, $parms = [])
    {

        $entidad_tipo_actividad = toba::db()->quote(elemento::tipo_actividad);
        $entidad_tipo_modulo = toba::db()->quote(elemento::tipo_modulo);
        $entidad_subtipo_materia_generica = toba::db()->quote(elemento::subtipo_materia_generica);

        $condicion_tipo_cursar = toba::db()->quote(condicion::tipo_cursar);
        $condicion_tipo_rendir = toba::db()->quote(condicion::tipo_rendir);
		
		
		$filtro_hijos_mg = "";
		if (isset($parms['incluir_componentes_genericas']) && !$parms['incluir_componentes_genericas']) {
			$filtro_hijos_mg = "AND vw_elementos_plan.elemento_revision NOT IN (
								-- Actividades optativas
								SELECT DISTINCT elemento_revision FROM vw_optativas_plan WHERE plan_version = $plan_version
								EXCEPT
								-- Actividades que forman parte de un modulo que no es una generica.
								SELECT er_actividad.elemento_revision
								  FROM sga_elementos_plan as ep 
								  JOIN sga_elementos_revision as er_modulo ON er_modulo.elemento_revision = ep.elemento_revision 
								  JOIN sga_elementos as modulo ON modulo.elemento = er_modulo.elemento
								  JOIN sga_elementos_comp as ec ON ec.elemento_padre = er_modulo.elemento_revision
								  JOIN sga_elementos_revision as er_actividad ON er_actividad.elemento_revision = ec.elemento_hijo
								  JOIN sga_elementos as actividad ON actividad.elemento = er_actividad.elemento
								  JOIN sga_g3entidades_subtipos as sa ON sa.entidad_subtipo = actividad.entidad_subtipo
								WHERE ep.plan_version = $plan_version 
								  AND modulo.entidad_subtipo <> $entidad_subtipo_materia_generica -- Generica
								  AND sa.entidad_tipo = $entidad_tipo_actividad -- Actividades
								)";
		}

		$join_solo_optativas = "";
		$where_solo_optativas = "";
		if (isset($parms['solo_optativas']) && $parms['solo_optativas']) {
			$join_solo_optativas = "JOIN vw_optativas_plan ON (vw_optativas_plan.elemento_revision = vw_elementos_plan.elemento_revision)";
			$where_solo_optativas = " AND vw_optativas_plan.elemento_generica = {$parms['materia_generica']}";
			$where_solo_optativas .= " AND vw_optativas_plan.plan_version = $plan_version";
		}

		// Recupera actividades y materias genericas
        $sql = "SELECT vw_elementos_plan.codigo as materia,
                       vw_elementos_plan.nombre as nombre_materia,
                       sga_elementos_plan.anio_de_cursada as anio,
                       sga_elementos_plan.periodo_de_cursada as periodo,
					   sga_periodos_genericos.nombre as periodo_cursada_nombre,
                       vw_elementos_plan.entidad_subtipo as tipo,
                       vw_elementos_plan.entidad_subtipo_nombre as tipo_detalle,
                       sga_elementos_plan.creditos as credito,
					   vw_elementos_plan.elemento as elemento,
					   vw_elementos_plan.entidad_subtipo as entidad_subtipo,
					   sga_g3entidades_subtipos.entidad_tipo as entidad_tipo,
					   (SELECT COUNT(1) FROM sga_condiciones 
					     WHERE plan_version = vw_elementos_plan.plan_version
						   AND entidad = sga_elementos.entidad
						   AND condicion_tipo IN ($condicion_tipo_cursar, $condicion_tipo_rendir)
						) as cant_correlativas,
						vw_elementos_plan.estado as estado
                FROM vw_elementos_plan
                     JOIN sga_elementos_plan ON (sga_elementos_plan.elemento_plan = vw_elementos_plan.elemento_plan)
					 LEFT JOIN sga_periodos_genericos ON (sga_periodos_genericos.periodo_generico = sga_elementos_plan.periodo_de_cursada)
                     JOIN sga_elementos_revision ON (vw_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision)
                     JOIN sga_elementos ON (sga_elementos.elemento = sga_elementos_revision.elemento)
                     JOIN sga_g3entidades_subtipos ON (sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo)
					 $join_solo_optativas
                WHERE vw_elementos_plan.plan_version = $plan_version
                  AND (sga_g3entidades_subtipos.entidad_tipo = $entidad_tipo_actividad OR  
                       (sga_g3entidades_subtipos.entidad_tipo = $entidad_tipo_modulo AND sga_g3entidades_subtipos.entidad_subtipo = $entidad_subtipo_materia_generica)
                      )
					$filtro_hijos_mg
					$where_solo_optativas
                ";

        $datos = guarani_db::consultar($sql, null, false);

		$materias_plan = [];
		foreach($datos as $dato){
            $materias_plan[$dato['elemento']] = $dato;
        }

        return $materias_plan;
    }
	
    /*
     * Funcion que devuelve las actividades de un plan version. Se utiliza en 
     * el ws planes-versiones.
     */
    function get_actividades_plan_version($plan_version)
    {
        $plan_version = toba::db()->quote($plan_version);        
        $sql = "
                SELECT
                    codigo,
                    nombre,
                    nombre_abreviado,
                    (CASE 
                       WHEN (SELECT COUNT(1) FROM vw_optativas_plan as opt  
                         WHERE opt.plan_version = vw_actividades_plan.plan_version 
                         AND opt.elemento     = vw_actividades_plan.elemento) > 0 THEN 1
                                  ELSE 0			
                                END) as optativa
                FROM 
                    vw_actividades_plan 
                WHERE plan_version = $plan_version
                ORDER BY nombre
                ";
        return guarani::db()->consultar($sql);
    }
	
	/**
	 * Nos dice si un elemento existe dentro de un Plan Versión.
	 *
	 * @param int $plan_version
	 * @param int $elemento
	 * @return bool TRUE si el elemento existe en el Plan Versión, False en caso contrario.
	 */
    function existe_elemento_en_plan_version($plan_version, $elemento)
    {    
        $sql = "
                SELECT COUNT(1) AS cant
				FROM sga_elementos_plan
				JOIN sga_elementos_revision ON (sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision)
				WHERE sga_elementos_plan.plan_version = $plan_version
				  AND sga_elementos_revision.elemento = $elemento
                ";
        $rs = guarani::db()->consultar_fila($sql);
		return ($rs['cant'] > 0);
    }
    
}