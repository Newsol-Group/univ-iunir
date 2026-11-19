<?php
/**
 *
 *
 * @package Calendario
 * @subpackage Turnos de Examen
 */
class co_turnos_de_examen
{
	/**
        Obtiene una lista de los turnos de examen según el filtro recibido sin aplicar perfil de datos.
    */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}

		$sql = "	SELECT	sga_turnos_examen.turno_examen, 
							sga_periodos.periodo,
							sga_periodos.nombre,
							sga_periodos.nombre || ' / ' || sga_turnos_examen_tipos.nombre || ' ( ' || to_char(sga_periodos.fecha_inicio,'".comunes::formato_fecha_visual."') || ' - ' || to_char(sga_periodos.fecha_fin,'".comunes::formato_fecha_visual."') || ' )' as turno_descr,
							sga_periodos.descripcion,							
							sga_turnos_examen.turno_tipo,
							sga_turnos_examen_tipos.nombre as tipo_nombre,
							sga_turnos_examen.fecha_publicacion_mesas,
							sga_turnos_examen.fecha_inactivacion,
							sga_periodos.anio_academico,
							sga_periodos.periodo_generico,
							sga_periodos.fecha_inicio as fecha_inicio,
							sga_periodos.fecha_fin as fecha_fin,
							(SELECT count(*)
								FROM sga_llamados_turno
								WHERE sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
							) as cant_llamados,
							(SELECT count(*)
								FROM sga_llamados_mesa, sga_llamados_turno
								WHERE sga_llamados_mesa.llamado = sga_llamados_turno.llamado
								AND sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
							) as cant_mesas,
							(SELECT		count(*)
								FROM	sga_llamados_turno,
										sga_periodos
								WHERE	sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
								AND		sga_periodos.periodo = sga_llamados_turno.periodo
								AND		NOT EXISTS (SELECT		1
														FROM	sga_periodos_inscripcion
														WHERE	sga_periodos_inscripcion.periodo = sga_periodos.periodo)
							) as cant_llamados_sin_periodo_insc
					FROM	sga_turnos_examen,
							sga_periodos,
							sga_turnos_examen_tipos
					WHERE	sga_turnos_examen.periodo = sga_periodos.periodo
					AND		sga_turnos_examen_tipos.turno_tipo = sga_turnos_examen.turno_tipo
							$where
					ORDER BY 7,10 DESC, 2
							;";
		return guarani_db::consultar($sql, null, false);
	}

	function get_turnos_examen($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_turnos_examen.turno_examen, 
							sga_periodos.nombre
				FROM		sga_turnos_examen,
							sga_periodos
				WHERE		sga_turnos_examen.periodo = sga_periodos.periodo
							$where
				ORDER BY	sga_periodos.nombre
		";
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Retorna el listado de datos básicos de los turnos de examen filtrando por perfil de datos.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_turnos_examen_perfil_de_datos($where='', $incluir_cantidades=false)
	{
		$filtro = 'true';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_periodos', 'sga_periodos');
			$sql = "SELECT		sga_turnos_examen.turno_examen
					FROM		sga_turnos_examen
								LEFT JOIN sga_llamados_turno ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
								LEFT JOIN sga_periodos ON sga_llamados_turno.periodo = sga_periodos.periodo
								LEFT JOIN sga_periodos_inscripcion ON sga_periodos_inscripcion.periodo = sga_periodos.periodo
					WHERE		-- Turnos sin llamado.
								sga_llamados_turno.llamado IS NULL OR
								-- Turnos con llamado pero sin período de inscripción.
								sga_periodos_inscripcion.periodo_inscripcion IS NULL OR
								-- Turnos con llamado y período de inscripción filtrado por perfil de datos.
								$where_gatillo
					GROUP BY	sga_turnos_examen.turno_examen
			";
			$turnos_examen = guarani_db::consultar($sql, null, false);
			$filtro = guarani_sql::armar_condicion_in($turnos_examen, 'sga_turnos_examen', 'turno_examen');
		}
		if ($where) {
			$filtro .= " AND $where";
		}
		return $incluir_cantidades ? $this->get_listado($filtro) : $this->get_turnos_examen($filtro);
	}

	function get_turnos_anio_academico_perfil_de_datos($anio_academico)
	{
		$where = "sga_periodos.anio_academico = $anio_academico";
		return $this->get_turnos_examen_perfil_de_datos($where);
	}

	/**
        Obtiene una lista de los tipos de turnos de examen según el filtro recibido
    */
	function get_listado_tipos($where='')
	{
		
		if ($where) $where = ' WHERE '.$where.';';
		
		$sql = "SELECT 	sga_turnos_examen_tipos.turno_tipo as valor,
						sga_turnos_examen_tipos.nombre as descr
				FROM 	sga_turnos_examen_tipos
				$where
				ORDER BY 2
				";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene todos los ids y nombres de Turnos de Exámen que están definidos para Períodos de un Año Académico dado,
	 * según el filtro dado.
	 *
	 * @param integer	$anio_acad	año académico de los períodos s los que se desea que los turnos de exámen estén asociados.
	 * @param str		$where		filtro sobre los turnos de exámen a retornar. 
	 */
	function get_turnos_anio_academico($anio_acad=null, $where='')
	{
		$filtro = '';
		if (!is_null($anio_acad)) {
			$anio_acad = toba::db()->quote($anio_acad);
			$filtro .= " AND sga_periodos.anio_academico = $anio_acad "; 
		}
		if ($where) {
			$filtro .= ' AND '.$where.' ';
		}
		
		$sql = "SELECT 	sga_turnos_examen.turno_examen as valor,
						sga_periodos.nombre as descr
				FROM 	sga_turnos_examen,
						sga_periodos
				WHERE	sga_turnos_examen.periodo = sga_periodos.periodo
				$filtro
				ORDER BY sga_periodos.anio_academico
				;";

		return guarani_db::consultar($sql, null, false);		
	}
	
	/**
        Obtiene una lista de los turnos de examen con sus llamados 
    */
	function get_turnos_llamados_periodos($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}

		$sql = "	SELECT	sga_turnos_examen.turno_examen,
							sga_periodos_turno.nombre,
							sga_periodos_turno.nombre || ' / ' || sga_turnos_examen_tipos.nombre || ' ( ' || to_char(sga_periodos_turno.fecha_inicio,'".comunes::formato_fecha_visual."') || ' - ' || to_char(sga_periodos_turno.fecha_fin,'".comunes::formato_fecha_visual."') || ' )' as turno_descr,
							sga_turnos_examen.turno_tipo,
							sga_turnos_examen_tipos.nombre as tipo_nombre,
							sga_periodos_turno.anio_academico,
							sga_periodos_turno.fecha_inicio as fecha_inicio,
							sga_periodos_turno.fecha_fin as fecha_fin,
							sga_llamados_turno.llamado,
							sga_periodos_llamado.nombre as llamado_descr,
							sga_periodos_llamado.nombre || ' ( ' || to_char(sga_periodos_llamado.fecha_inicio,'".comunes::formato_fecha_visual."') || ' - ' || to_char(sga_periodos_llamado.fecha_fin,'".comunes::formato_fecha_visual."') || ' )' as llamado_descr2,
							sga_periodos_llamado.fecha_inicio as llamado_fecha_inicio,
							sga_periodos_llamado.fecha_fin as llamado_fecha_fin,
							sga_periodos_inscripcion.periodo_inscripcion,
							sga_periodos_inscripcion_fechas.periodo_insc,
							to_char(sga_periodos_inscripcion_fechas.fecha_inicio,'".comunes::formato_fecha_hora_visual_sin_segundos."') ||
								(CASE sga_periodos_inscripcion_fechas.tipo_fin
								  WHEN 1 THEN ' al ' || to_char(sga_periodos_inscripcion_fechas.fecha_fin,'".comunes::formato_fecha_hora_visual_sin_segundos."')
								  WHEN 2 THEN ' hasta ' || cast(sga_periodos_inscripcion_fechas.dias_previos_fin as text) || ' dias antes de cada mesa'
								  WHEN 3 THEN ' hasta ' || cast(sga_periodos_inscripcion_fechas.dias_previos_fin as text) || ' dias hábiles antes de cada mesa'
								  WHEN 4 THEN ' hasta ' || cast(sga_periodos_inscripcion_fechas.hs_previas_fin  as text) || ' hs antes de cada mesa'
								  WHEN 5 THEN ' hasta ' || cast(sga_periodos_inscripcion_fechas.hs_previas_fin  as text) || ' hs hábiles antes de cada mesa'
								END) as periodo_insc_descr,
							sga_periodos_inscripcion_fechas.tipo_fin,
							sga_periodos_inscripcion_fechas.tipo_tope_bajas,
							sga_periodos_inscripcion_fechas.fecha_inicio as inscripcion_fecha_inicio,
							sga_periodos_inscripcion_fechas.fecha_fin as inscripcion_fecha_fin,
							sga_periodos_inscripcion_fechas.fecha_tope_bajas as fecha_tope_bajas,
							sga_periodos_inscripcion_fechas.dias_previos_fin as dias_previos_fin,
							sga_periodos_inscripcion_fechas.hs_previas_fin as hs_previas_fin,
							sga_periodos_inscripcion_fechas.dias_previos_tope_bajas as dias_previos_tope_bajas,
							sga_periodos_inscripcion_fechas.hs_previas_tope_bajas as hs_previas_tope_bajas,
							sga_periodos_inscripcion_fechas.habilitado,
							sga_periodos_inscripcion_fechas.tipo,
							sga_periodos_inscripcion_subtipos.entidad_subtipo,
							sga_g3entidades_subtipos.nombre as entidad_subtipo_nombre,
							sga_periodos_inscripcion_entidad.entidad,
							CASE
							  WHEN (sga_periodos_inscripcion_fechas.tipo = 'S') THEN sga_periodos_inscripcion_subtipos.entidad_subtipo
							  WHEN (sga_periodos_inscripcion_fechas.tipo = 'E') THEN sga_periodos_inscripcion_entidad.entidad
							  ELSE null
							END as aplicacion,
							CASE
							  WHEN (sga_periodos_inscripcion_fechas.tipo = 'S') THEN sga_periodos_inscripcion_subtipos.entidad_subtipo || ',' || sga_periodos_inscripcion_fechas.periodo_insc
							  WHEN (sga_periodos_inscripcion_fechas.tipo = 'E') THEN sga_periodos_inscripcion_entidad.entidad || ',' || sga_periodos_inscripcion_fechas.periodo_insc
							  ELSE null
							END as id_aplicacion,
							CASE
							  WHEN (sga_periodos_inscripcion_fechas.tipo = 'S') IS NOT NULL THEN sga_g3entidades_subtipos.nombre
							  ELSE null
							END as aplicacion_descr
					FROM	sga_turnos_examen
						LEFT JOIN sga_llamados_turno ON sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
						LEFT JOIN sga_periodos as sga_periodos_llamado ON sga_periodos_llamado.periodo = sga_llamados_turno.periodo
						LEFT JOIN sga_periodos_inscripcion ON sga_periodos_inscripcion.periodo = sga_periodos_llamado.periodo
						LEFT JOIN sga_periodos_inscripcion_fechas ON sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion
						LEFT JOIN sga_periodos_inscripcion_subtipos ON sga_periodos_inscripcion_subtipos.periodo_insc = sga_periodos_inscripcion_fechas.periodo_insc
						LEFT JOIN sga_periodos_inscripcion_entidad ON sga_periodos_inscripcion_entidad.periodo_insc = sga_periodos_inscripcion_fechas.periodo_insc
						LEFT JOIN sga_g3entidades_subtipos ON sga_g3entidades_subtipos.entidad_subtipo = sga_periodos_inscripcion_subtipos.entidad_subtipo
						LEFT JOIN sga_g3entidades ON sga_g3entidades.entidad = sga_periodos_inscripcion_entidad.entidad,
							sga_periodos as sga_periodos_turno,
							sga_turnos_examen_tipos
					WHERE	sga_turnos_examen.periodo = sga_periodos_turno.periodo
					AND		sga_turnos_examen_tipos.turno_tipo = sga_turnos_examen.turno_tipo
							$where
					ORDER BY 1,9,14
							;";
		
		return guarani_db::consultar($sql);
	}

	function get_turnos_llamados_periodos_con_nombre_entidad($where='')
	{
		$datos = $this->get_turnos_llamados_periodos($where);
		foreach ($datos as $key => $dato) {
			if (isset($dato['tipo']) && $dato['tipo'] == 'E') {
				if ($dato['entidad']) {
					$datos_entidad = $this->get_descripcion_entidad($dato['entidad']);
					$datos[$key]['entidad_nombre'] = $datos_entidad['nombre'];
					$datos[$key]['aplicacion_descr'] = $datos[$key]['entidad_nombre'];
				} else {
					// Si la entidad no es visible (por el perfil de datos), excluir el período de inscripción.
					unset($datos[$key]['periodo_inscripcion']);
				}
			}
		}
		return rs_ordenar_por_columnas($datos, array('turno_examen', 'llamado_fecha_fin', 'inscripcion_fecha_fin', 'aplicacion_descr'));
	}

	/**
	 * Retorna el listado de llamados con los datos de su turno de examen.
	 *
	 * @param str $where
	 * @return array
	 */
	function get_turnos_llamados($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$where_gatillo = '';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (!empty($perfil_datos)) {
			$where_gatillo = ' AND ' . toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_periodos', 'periodos_llamados');
		}
		$sql = "SELECT		sga_turnos_examen.turno_examen,
							'Año: ' || periodos_turnos.anio_academico ||
							' - Turno: ' || periodos_turnos.nombre ||
							' - Inicio: ' || to_char(periodos_turnos.fecha_inicio,'".comunes::formato_fecha_visual."') ||
							' - Fin: ' || to_char(periodos_turnos.fecha_fin,'".comunes::formato_fecha_visual."') ||
							' - Mesas: ' || (	SELECT	COUNT(*)
												FROM	sga_llamados_mesa,
														sga_llamados_turno AS llamados_turno
												WHERE	llamados_turno.llamado = sga_llamados_mesa.llamado
												AND		llamados_turno.turno_examen = sga_turnos_examen.turno_examen) as turno_descripcion,
							periodos_turnos.anio_academico,
							periodos_turnos.nombre as turno_nombre,
							periodos_turnos.fecha_inicio as turno_fecha_inicio,
							to_char(periodos_turnos.fecha_inicio,'".comunes::formato_fecha_visual."') as turno_fecha_inicio_visual,
							periodos_turnos.fecha_fin as turno_fecha_fin,
							to_char(periodos_turnos.fecha_fin,'".comunes::formato_fecha_visual."') as turno_fecha_fin_visual,
							(SELECT	count(*)
								FROM	sga_llamados_mesa
								WHERE	sga_llamados_mesa.llamado = sga_llamados_turno.llamado
							) as turno_cant_mesas,
					        sga_llamados_turno.llamado,
					        sga_llamados_turno.periodo,
					        periodos_llamados.nombre as llamado_nombre,
					        periodos_llamados.fecha_inicio as llamado_fecha_inicio,
					        to_char(periodos_llamados.fecha_inicio,'".comunes::formato_fecha_visual."') as llamado_fecha_inicio_visual,
					        periodos_llamados.fecha_fin as llamado_fecha_fin,
					        to_char(periodos_llamados.fecha_fin,'".comunes::formato_fecha_visual."') as llamado_fecha_fin_visual
				FROM 		sga_turnos_examen
							JOIN sga_periodos as periodos_turnos ON sga_turnos_examen.periodo = periodos_turnos.periodo
        					JOIN sga_llamados_turno ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
        					JOIN sga_periodos as periodos_llamados ON sga_llamados_turno.periodo = periodos_llamados.periodo
				WHERE		true
							$where
							$where_gatillo
        		ORDER BY	periodos_turnos.fecha_inicio,
        					periodos_llamados.nombre
        		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_datos_turno($turno_examen)
	{
		$turno_examen = toba::db()->quote($turno_examen);
		$datos_turno = $this->get_listado("sga_turnos_examen.turno_examen = $turno_examen");
		return isset($datos_turno[0]) ? $datos_turno[0] : array();
	}

	//Obtiene el nombre correspondiente al turno de exámen dado como parámetro.
	function get_nombre_turno_examen($turno)
	{
		if (!is_null($turno)) {
			$turno = toba::db()->quote($turno);
			
			$sql = "SELECT	sga_periodos.nombre	
					FROM	sga_turnos_examen,
							sga_periodos
					WHERE	sga_turnos_examen.periodo = sga_periodos.periodo AND
							sga_turnos_examen.turno_examen = $turno
					;";
			
			$datos = guarani_db::consultar_fila($sql);
			
			if (!empty($datos)) {
				return current($datos);
			}
		}
		
	}
	
	function es_turno_finalizado($turno_examen)
	{
		$datos_turno = $this->get_datos_turno($turno_examen);
		return (!guarani_fecha::es_mayor($datos_turno['fecha_fin'], guarani_fecha::get_hoy())) ;
	}

	function get_datos_llamado($llamado)
	{
		if (is_array($llamado)) {
			$filtro = guarani_sql::armar_condicion_in($llamado, 'sga_llamados_turno', 'llamado');
			$datos = $this->get_datos_llamados($filtro);
			return $datos;
		}
		$llamado = toba::db()->quote($llamado);
		$datos = $this->get_datos_llamados("sga_llamados_turno.llamado = $llamado");
		if (!empty($datos)) {
			return $datos[0];
		}
	}

	function get_llamado_id_de_periodo($periodo)
	{
		$periodo = toba::db()->quote($periodo);
		$where = "sga_llamados_turno.periodo = $periodo";
		$datos_llamado = current($this->get_datos_llamados($where));
		return $datos_llamado['llamado'];
	}

	/**
	 * Retorna los datos de los llamados-turno y sus períodos asociados sin aplicar perfil de datos.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_datos_llamados($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		$sql = "	SELECT	sga_llamados_turno.llamado,
							sga_llamados_turno.turno_examen,
							sga_llamados_turno.periodo,
							sga_periodos.nombre,
							sga_periodos.descripcion,
							sga_periodos.anio_academico,
							sga_periodos.periodo_generico,
							sga_periodos.fecha_inicio AS fecha_inicio,
							sga_periodos.fecha_fin AS fecha_fin,
							(SELECT COUNT(*)
								FROM sga_llamados_mesa
								WHERE sga_llamados_mesa.llamado = sga_llamados_turno.llamado
							) AS cant_mesas 
					FROM	sga_llamados_turno,
							sga_periodos
					WHERE	sga_llamados_turno.periodo = sga_periodos.periodo
							$where
					";
		return guarani_db::consultar($sql, null, false);
	}

	function es_llamado_finalizado($llamado)
	{
		$datos_llamado = $this->get_datos_llamado($llamado);
		return (!guarani_fecha::es_mayor($datos_llamado['fecha_fin'], guarani_fecha::get_hoy())) ;
	}

	function get_periodos_inscripcion_fechas($periodo_inscripcion)
	{
		if (empty($periodo_inscripcion))
			return array();
		$periodo_inscripcion = toba::db()->quote($periodo_inscripcion);
		$sql = "SELECT	sga_periodos_inscripcion_fechas.periodo_insc,
						sga_periodos_inscripcion_fechas.entidad_tipo
				FROM	sga_periodos_inscripcion_fechas
				WHERE	sga_periodos_inscripcion_fechas.periodo_inscripcion = $periodo_inscripcion";
		return guarani_db::consultar($sql);
	}

	function get_llamados_turno($turno, $where='', $filtrar_perfil_de_datos=true)
	{
		$turno = toba::db()->quote($turno);
		if ($where) {
			$where = "AND $where";
		}
		$sql = "SELECT	sga_llamados_turno.llamado,
						sga_llamados_turno.periodo,
						sga_periodos.nombre,
						sga_periodos.descripcion,
						sga_periodos.anio_academico,
						sga_periodos.periodo_generico,
						sga_periodos.fecha_inicio as fecha_inicio,
						sga_periodos.fecha_fin as fecha_fin
				FROM	sga_llamados_turno,
						sga_periodos
				WHERE	sga_llamados_turno.periodo = sga_periodos.periodo AND
						sga_llamados_turno.turno_examen = $turno
						$where
		";
		return guarani_db::consultar($sql, null, $filtrar_perfil_de_datos);
	}

	function get_periodos_insc_llamado($llamado)
	{
		$llamado = toba::db()->quote($llamado);
		$sql = "SELECT	sga_periodos_inscripcion.periodo_inscripcion,
						sga_periodos_inscripcion.nombre
				FROM	sga_periodos_inscripcion,
						sga_llamados_turno
				WHERE	sga_periodos_inscripcion.periodo = sga_llamados_turno.periodo
				AND		sga_llamados_turno.llamado = $llamado
						;";
		return guarani_db::consultar($sql);
	}

	function hay_superposicion_llamados($turno,$llamado,$fecha_inicio,$fecha_fin)
	{
		$turno = toba::db()->quote($turno);
		if ($llamado) $llamado = toba::db()->quote($llamado);
		$fecha_inicio = toba::db()->quote($fecha_inicio);
		$fecha_fin = toba::db()->quote($fecha_fin);

		$sql = "SELECT	count(*) as cant
				FROM	sga_llamados_turno,
						sga_periodos
				WHERE	sga_llamados_turno.turno_examen = $turno
				AND		sga_llamados_turno.periodo = sga_periodos.periodo
						";
		if ($llamado) $sql = $sql . " AND sga_llamados_turno.llamado <> $llamado ";
		$sql = $sql . "	AND ((sga_periodos.fecha_inicio >= DATE $fecha_inicio
						AND sga_periodos.fecha_inicio <= DATE $fecha_fin )
						OR	(sga_periodos.fecha_fin >= DATE $fecha_inicio
						AND sga_periodos.fecha_fin <= DATE $fecha_fin ))
						;";

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] > 0);
	}

	function get_descripcion_entidad($entidad, $entidad_tipo=null) 
    {
		if (! $entidad_tipo) {
			$entidad_tipo = g3entidad::get_tipo_entidad($entidad);
		}
		$tabla_tipo = g3entidad::get_tabla_tipo($entidad_tipo);
		$datos_entidad = guarani::co('co_g3entidades')->get_listado_tipo_detallado($entidad_tipo, "$tabla_tipo.entidad = $entidad");
		return $datos_entidad[0];
	}

	function validar_mesas_en_rango_turno($turno,$fecha_inicio,$fecha_fin)
	{
		$turno = toba::db()->quote($turno);
		$fecha_inicio = toba::db()->quote($fecha_inicio);
		$fecha_fin = toba::db()->quote($fecha_fin);
		
		$sql = "	SELECT	count(*) as cant
					FROM	sga_turnos_examen,
							sga_llamados_turno,
							sga_llamados_mesa
					WHERE	sga_turnos_examen.turno_examen = $turno
					AND		sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
					AND		sga_llamados_mesa.llamado = sga_llamados_turno.llamado
					AND		(sga_llamados_mesa.fecha < DATE $fecha_inicio OR sga_llamados_mesa.fecha > DATE $fecha_fin)
							;";
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	function validar_llamados_en_rango($turno, $fecha_inicio, $fecha_fin)
	{
		$turno = toba::db()->quote($turno);
		$fecha_inicio = toba::db()->quote($fecha_inicio);
		$fecha_fin = toba::db()->quote($fecha_fin);
		
		$sql = "SELECT 
					COUNT (1) as cant
				FROM 
					sga_turnos_examen,
				    sga_llamados_turno,
				    sga_periodos as periodo_llamado
				WHERE
					sga_turnos_examen.turno_examen = $turno
					AND sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
				    AND periodo_llamado.periodo = sga_llamados_turno.periodo
					AND (	periodo_llamado.fecha_inicio < DATE $fecha_inicio 
					  	 OR	periodo_llamado.fecha_fin > DATE $fecha_fin )
				;";
		
		$res = guarani_db::consultar_fila($sql);

		return ($res['cant'] == 0);
	}

	function validar_mesas_en_rango_llamado($llamado,$fecha_inicio,$fecha_fin)
	{
		$llamado = toba::db()->quote($llamado);
		$fecha_inicio = toba::db()->quote($fecha_inicio);
		$fecha_fin = toba::db()->quote($fecha_fin);
		$sql = "	SELECT	count(*) as cant
					FROM	sga_llamados_turno,
							sga_llamados_mesa
					WHERE	sga_llamados_turno.llamado = $llamado
					AND		sga_llamados_mesa.llamado = sga_llamados_turno.llamado
					AND		(sga_llamados_mesa.fecha < DATE $fecha_inicio OR sga_llamados_mesa.fecha > DATE $fecha_fin)
							;";
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	// -------------------------------------------------------------------------------------------------------
	// ---- Consultas para llenado de combo editable Llamado -------------------------------------------------
	// -------------------------------------------------------------------------------------------------------
	
	/**
	 * Obtiene los ids y descripciones de todas los Llamadoscuyos nombres contengan al string ingresado como
	 * filtro (mediante un combo editable).
	 *
	 * @param str $filtro porción del nombre del llamado ingresado por el usuario.
	 */
	function get_descripciones_llamados($filtro='')
	{
		if (isset($filtro) && trim($filtro) != '') {
			toba::db()->quote($filtro);
			$filtro = "sga_periodos.nombre ILIKE '%$filtro%'";
			return $this->get_llamados_nombres($filtro);
		}
	}
	
	/**
     *  Obtiene una lista de nombres de llamados según el filtro recibido
     */
	function get_llamados_nombres($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "SELECT 	sga_periodos.periodo 	as valor,
						sga_periodos.nombre 	as descr
				FROM 	sga_periodos
				$where
				ORDER BY sga_periodos.nombre
				;";	
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene el nombre de un Llamado cuyo id se da como parámetro, retornando la string para el método
	 * descripción de un combo editable.
	 *
	 * @param integer $llamado id del llamado para la cual se desea recuperar la descripción.
	 */
	static function get_descripcion_llamado($llamado=null)
	{
		if (!isset($llamado)) {
			return '';
		}
		$llamado = toba::db()->quote($llamado);
		$result = toba::consulta_php('co_turnos_examen')->get_llamados_nombres("sga_periodos.periodo = $llamado");
		return empty($result) ? '' : $result[0]['descr'];
	}
	
	/**
     *  Obtiene una lista de nombres de llamados según el AÑO recibido
     */
	function get_llamados_nombres_por_anio($turno_examen='')
	{
		$turno_examen = toba::db()->quote($turno_examen);

		$sql = "SELECT  
     				sga_llamados_turno.llamado as valor,
					sga_periodos.nombre  as descr
    			FROM  
     				sga_llamados_turno,
     				sga_periodos
    			WHERE 
    				sga_llamados_turno.periodo = sga_periodos.periodo
      				AND sga_llamados_turno.turno_examen = $turno_examen
    			ORDER BY 
    				sga_periodos.fecha_inicio ASC
				;";	
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Recupera los llamados de un turno de examen determinado
	 * Este método es usado para llenar los combos de llamados que tienen como maestro un turno_examen
	 *
	 * @param <type> $turno_examen
	 */
	function get_llamados_x_turno_examen($turno_examen=null)
	{
		$filtro = '';
		if (!is_null($turno_examen)) {
			$turno_examen = toba::db()->quote($turno_examen);
			$filtro .= "AND sga_llamados_turno.turno_examen = $turno_examen ";
		}

		$sql = "SELECT	sga_llamados_turno.llamado as valor,
						sga_periodos.nombre  as descr
    			FROM	sga_llamados_turno,
						sga_periodos
				WHERE	sga_llamados_turno.periodo = sga_periodos.periodo
    			$filtro
    			ORDER BY sga_periodos.fecha_inicio ASC
				;";

		return guarani_db::consultar($sql);
	}
	
	function es_turno_con_llamados($turno_examen)
	{
		$datos = $this->get_llamados_x_turno_examen($turno_examen);
    	return (guarani_arreglos::count($datos) > 0);
	}
	
	function validar_nombre_periodo_anio($nombre, $anio_academico, $turno=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		$anio_academico = toba::db()->quote($anio_academico);
		
		$sql = "SELECT  COUNT   (sga_periodos.periodo) as cant
                        FROM    sga_periodos,
                                sga_turnos_examen
                        WHERE   sga_periodos.periodo = sga_turnos_examen.periodo AND
                                sga_periodos.anio_academico = $anio_academico AND " .
                                guarani_sql::armar_condicion_compara_cadenas('sga_periodos.nombre', $nombre);
		
		if (!is_null($turno)) {
			$turno = toba::db()->quote($turno);
			$sql .= " AND sga_turnos_examen.turno_examen <> $turno ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);		
	}
    
    function get_opciones_combo_tope() {
        return array(array('valor' => turno_de_examen::tope_fecha , 'descr' => turno_de_examen::tope_fecha_descr),
                     array('valor' => turno_de_examen::tope_dias_antes , 'descr' => turno_de_examen::tope_dias_antes_descr),
                     array('valor' => turno_de_examen::tope_dias_antes_habiles , 'descr' => turno_de_examen::tope_dias_antes_habiles_descr),
                     array('valor' => turno_de_examen::tope_hs_antes , 'descr' => turno_de_examen::tope_hs_antes_descr),
                     array('valor' => turno_de_examen::tope_hs_antes_habiles , 'descr' => turno_de_examen::tope_hs_antes_habiles_descr),
                     array('valor' => turno_de_examen::tope_fecha_hora_examen , 'descr' => turno_de_examen::tope_fecha_hora_examen_descr),
					);
    }

	/**
	 * Retorna la cantidad de turnos de examen no finalizados para el año académico dado.
	 * 
	 * @return integer
	 */
	function get_cant_turnos_examen_no_finalizados_por_anio_academico($anio_academico)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$sql = "SELECT	COUNT(1) as cant
				FROM	vw_turnos_examen
				WHERE	vw_turnos_examen.anio_academico = $anio_academico AND
						vw_turnos_examen.turno_examen_fecha_fin >= CURRENT_DATE
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}
	
	function get_datos_turnos_llamados($where = '')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		$sql = "
				SELECT 
					anio_academico, 
					turno_examen_nombre,
					llamado_nombre,
					llamado,
					llamado_fecha_inicio,
					llamado_fecha_fin					
				FROM 
					vw_turnos_examen
				$where
				ORDER BY
					anio_academico
				";
		return guarani::db()->consultar($sql);
	}
	
	/*
	 * Devuelve si el llamado esta vinculado y con cuales
	 */
	function get_vinculados($llamado)
	{
		$filtro = "llamado IN (SELECT llamado_vinculado FROM sga_llamados_turno_relacion WHERE llamado = $llamado)";
		return $this->get_datos_turnos_llamados($filtro);
		
	}
}
?>