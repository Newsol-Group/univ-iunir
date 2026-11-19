<?php
/**
 *
 *
 * @package Unidades de Gestión
 * @subpackage Espacios
 */
class co_bandas_horarias
{
	//------------------------------------------------------------------------
	//-- Bandas Horarias
	//------------------------------------------------------------------------

	/**
	 * Retorna el listado de bandas horarias con sus datos asociados.
	 *
	 * @param  string $where filtro sobre las bandas horarias a retornar. La descripción de la Bh se forma por el nombre
	 * de la Comisión y Elemento a los que está asociada.
	 * @return array
	 */
	function get_listado($where='')
	{
		if (! empty($where)) {
			$where = " AND $where";
		}
		$sql = 'SELECT		sga_asignaciones.asignacion,
							sga_asignaciones.dia_semana,
							'.guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
							sga_asignaciones.fecha_desde,
							to_char(sga_asignaciones.fecha_desde, '".comunes::formato_fecha_visual."') as fecha_desde_visual,
							sga_asignaciones.fecha_hasta,
							to_char(sga_asignaciones.fecha_hasta, '".comunes::formato_fecha_visual."') as fecha_hasta_visual,
							sga_asignaciones.periodicidad,
							sga_asignaciones.hora_inicio,
							to_char(sga_asignaciones.hora_inicio, '".comunes::formato_hora_visual."') as hora_inicio_visual,
							sga_asignaciones.hora_finalizacion,
							to_char(sga_asignaciones.hora_finalizacion, '".comunes::formato_hora_visual."') as hora_finalizacion_visual,
							sga_asignaciones.cantidad_horas,
							sga_asignaciones.observaciones,
							sga_comisiones_bh.banda_horaria,
							sga_comisiones_bh.comision,
							sga_comisiones_bh.tipo_clase,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							sga_comisiones.nombre || ' ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as descripcion_banda_horaria,
							sga_comisiones.nombre as comision_nombre,
							get_nombre_actividad_comision(sga_comisiones.comision,1) as elemento_nombre,
							(	SELECT	CASE
											WHEN count(1) > 0 THEN 1
											ELSE 0
										END
								FROM	sga_comisiones_bh as bh_compartidas
								WHERE	bh_compartidas.asignacion = sga_asignaciones.asignacion AND
										bh_compartidas.comision <> sga_comisiones_bh.comision
							) as compartida,
							get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria) as espacio_edificacion_nombre,
							CASE sga_asignaciones.dia_semana
								WHEN 'Lunes' THEN 1
								WHEN 'Martes' THEN 2
								WHEN 'Miercoles' THEN 3
								WHEN 'Jueves' THEN 4
								WHEN 'Viernes' THEN 5
								WHEN 'Sabado' THEN 6
								WHEN 'Domingo' THEN 7
								ELSE 8
							END as dia_semana_orden
				FROM		sga_comisiones_bh
							JOIN sga_clases_tipos ON sga_comisiones_bh.tipo_clase = sga_clases_tipos.tipo_clase
							JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
							JOIN sga_comisiones ON sga_comisiones_bh.comision = sga_comisiones.comision
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
				$where
				ORDER BY	dia_semana_orden,
							sga_asignaciones.hora_inicio
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de bandas horarias con sus datos asociados. La descripción de la Bh se forma por la asignación
	 * de la misma y su periodicidad.
	 *
	 * @param  string $where filtro sobre las bandas horarias a retornar.
	 * @return array
	 */
	function get_listado_bh_propias($where='')
	{
		if (! empty($where)) {
			$where = " WHERE $where";
		}
		$sql = "SELECT		sga_comisiones_bh.banda_horaria,
							COALESCE(sga_asignaciones.dia_semana, ' ') 
							|| '  ' || to_char(sga_asignaciones.fecha_desde,'".comunes::formato_fecha_visual."') 
							|| ' al ' || to_char(sga_asignaciones.fecha_hasta,'".comunes::formato_fecha_visual."') 
							|| COALESCE('  De ' || to_char(sga_asignaciones.hora_inicio, '".comunes::formato_hora_visual."')
										|| ' a ' || to_char(sga_asignaciones.hora_finalizacion, '".comunes::formato_hora_visual."') || ' hs.', ' ')
							|| '  -  ' || sga_asignaciones.periodicidad as descripcion_banda_horaria,
							CASE sga_asignaciones.dia_semana
								WHEN 'Lunes' THEN 1
								WHEN 'Martes' THEN 2
								WHEN 'Miercoles' THEN 3
								WHEN 'Jueves' THEN 4
								WHEN 'Viernes' THEN 5
								WHEN 'Sabado' THEN 6
								WHEN 'Domingo' THEN 7
								ELSE 8
							END as dia_semana_orden
				FROM		sga_comisiones_bh
							JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
				$where
				ORDER BY	dia_semana_orden,
							sga_asignaciones.hora_inicio
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el listado de bandas horarias asociadas a subcomisiones con sus datos asociados. La descripción de la Bh se 
	 * forma por la asignación de la misma y su periodicidad.
	 *
	 * @param  string $where filtro sobre las bandas horarias a retornar. 
	 * @return array
	 */
	function get_listado_bh_propias_subcomisiones($where='')
	{
		if (! empty($where)) {
			$where = " WHERE $where";
		}
		$sql = "SELECT		sga_subcomisiones_bh.banda_horaria,
							COALESCE(sga_asignaciones.dia_semana, ' ') 
							|| '  ' || to_char(sga_asignaciones.fecha_desde,'".comunes::formato_fecha_visual."') 
							|| ' al ' || to_char(sga_asignaciones.fecha_hasta,'".comunes::formato_fecha_visual."') 
							|| COALESCE('  De ' || to_char(sga_asignaciones.hora_inicio, '".comunes::formato_hora_visual."')
										|| ' a ' || to_char(sga_asignaciones.hora_finalizacion, '".comunes::formato_hora_visual."') || ' hs.', ' ')
							|| '  -  ' || sga_asignaciones.periodicidad as descripcion_banda_horaria,
							CASE sga_asignaciones.dia_semana
								WHEN 'Lunes' THEN 1
								WHEN 'Martes' THEN 2
								WHEN 'Miercoles' THEN 3
								WHEN 'Jueves' THEN 4
								WHEN 'Viernes' THEN 5
								WHEN 'Sabado' THEN 6
								WHEN 'Domingo' THEN 7
								ELSE 8
							END as dia_semana_orden
				FROM		sga_subcomisiones_bh
							JOIN sga_comisiones_bh ON sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria
							JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
				$where
				ORDER BY	dia_semana_orden,
							sga_asignaciones.hora_inicio
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna la descripción (nombre de comisión + nombre de actividad) de una banda horaria.
	 *
	 * @param integer $banda_horaria
	 * @return string
	 */
	static function get_descripcion_banda_horaria($banda_horaria)
	{
		$banda_horaria = toba::db()->quote($banda_horaria);
		$sql = "SELECT	sga_comisiones.nombre || ' ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as descripcion_banda_horaria
				FROM	sga_comisiones_bh,
						sga_comisiones
				WHERE	sga_comisiones_bh.comision = sga_comisiones.comision AND
						sga_comisiones_bh.banda_horaria = $banda_horaria
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['descripcion_banda_horaria'];
	}

	/**
	 * Retorna la descripción (fecha y hora de la asignación + periodicidad) correspondiente de una banda horaria.
	 *
	 * @param integer $banda_horaria
	 * @return string
	 */
	function get_descripcion_asignacion_banda_horaria($banda_horaria)
	{
		$banda_horaria = toba::db()->quote($banda_horaria);
		$sql = "SELECT	COALESCE(sga_asignaciones.dia_semana, ' ') 
						|| '  ' || to_char(sga_asignaciones.fecha_desde,'".comunes::formato_fecha_visual."') 
						|| ' al ' || to_char(sga_asignaciones.fecha_hasta,'".comunes::formato_fecha_visual."') 
						|| COALESCE('  De ' || to_char(sga_asignaciones.hora_inicio, '".comunes::formato_hora_visual."')
									|| ' a ' || to_char(sga_asignaciones.hora_finalizacion, '".comunes::formato_hora_visual."') || ' hs.', ' ')
						|| '  -  ' || sga_asignaciones.periodicidad AS descripcion_banda_horaria
				FROM	sga_asignaciones
				JOIN	sga_comisiones_bh ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
				WHERE	sga_comisiones_bh.banda_horaria = $banda_horaria
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['descripcion_banda_horaria'];
	}
	
	/**
	 * Retorna los datos de una banda horaria en formato RecordSet.
	 * Tiene en cuenta el caso de la carga masiva cuando el parámetro es un arreglo.
	 *
	 * @param integer/array $banda_horaria
	 * @return array
	 */
	function get_datos_banda_horaria($banda_horaria)
	{
		if (is_array($banda_horaria)) {
			$filtro = guarani_sql::armar_condicion_in($banda_horaria, 'sga_comisiones_bh', 'banda_horaria');
		} else {
			$banda_horaria = toba::db()->quote($banda_horaria);
			$filtro = "sga_comisiones_bh.banda_horaria = $banda_horaria";
		}
		$sql = 'SELECT	sga_comisiones_bh.banda_horaria,
						'.guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
						sga_asignaciones.dia_semana,
						sga_asignaciones.hora_inicio,
						sga_asignaciones.hora_finalizacion,
						sga_asignaciones.cantidad_horas,
						to_char(sga_asignaciones.hora_inicio, '".comunes::formato_hora_visual."') as hora_inicio_visual,
						to_char(sga_asignaciones.hora_finalizacion, '".comunes::formato_hora_visual."') as hora_finalizacion_visual,
						sga_comisiones_bh.tipo_clase,
						sga_comisiones_bh.modalidad,
						sga_comisiones_bh.asistencia,
						sga_clases_tipos.nombre as tipo_clase_nombre,
						(	SELECT	CASE
										WHEN count(1) > 0 THEN 1
										ELSE 0
									END
							FROM	sga_comisiones_bh as bh_compartidas
							WHERE	bh_compartidas.asignacion = sga_asignaciones.asignacion AND
									bh_compartidas.comision <> sga_comisiones_bh.comision
						) as compartida
				FROM	sga_comisiones_bh
						JOIN sga_clases_tipos ON sga_comisiones_bh.tipo_clase = sga_clases_tipos.tipo_clase
						JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
				WHERE	$filtro
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de subcomisiones asociadas a una banda horaria dada.
	 *
	 * @param integer $banda_horaria
	 * @return array
	 */
	function get_subcomisiones_bh($banda_horaria)
	{
		$sql = "SELECT	sga_subcomisiones_bh.subcomision
				FROM	sga_subcomisiones_bh
				WHERE	sga_subcomisiones_bh.banda_horaria = $banda_horaria
		";
		return guarani_db::consultar($sql);
	}
	
	//------------------------------------------------------------------------
	//-- Asignaciones
	//------------------------------------------------------------------------

	/**
	 * Retorna el listado de asignaciones.
	 *
	 * @param string $where filtro sobre las asignaciones a retornar.
	 * @return array
	 */
	function get_listado_asignaciones($where='')
	{
		if (! empty($where)) {
			$where = " WHERE $where";
		}
		$sql = "SELECT	sga_asignaciones.asignacion,
						sga_asignaciones.dia_semana,
						sga_asignaciones.fecha_desde,
						sga_asignaciones.fecha_hasta,
						sga_asignaciones.periodicidad,
						sga_asignaciones.hora_inicio,
						sga_asignaciones.hora_finalizacion,
						sga_asignaciones.cantidad_horas,
						sga_asignaciones.asignacion_tipo,
						sga_asignaciones.observaciones
				FROM	sga_comisiones_bh
						JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
				$where
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el listado de asignaciones de una comisión dada.
	 * Si se recibe un array de comisiones como parámetro retorna el listado de asignaciones para 
	 * todas las comisiones dadas.
	 *
	 * @param int/array $comision
	 * @return array
	 */
	function get_listado_asignaciones_comision($comision, $where='')
	{
		$filtro = '';
		if (is_array($comision)) {
			$filtro .= ' AND ' . guarani_sql::armar_condicion_in($comision, 'sga_comisiones_bh', 'comision');
		} else {
			$comision = toba::db()->quote($comision);
			$filtro .= " AND sga_comisiones_bh.comision = $comision";
		}
		if ($where) {
			$filtro .= " AND $where";
		}
		$sql = "SELECT		sga_comisiones_bh.banda_horaria,
							sga_asignaciones.asignacion,
							sga_asignaciones.dia_semana,
							".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
							sga_asignaciones.fecha_desde,
							sga_asignaciones.fecha_hasta,
							sga_asignaciones.periodicidad,
							sga_asignaciones.hora_inicio,
							to_char(sga_asignaciones.hora_inicio, '".comunes::formato_hora_visual."') as hora_inicio_visual,
							sga_asignaciones.hora_finalizacion,
							to_char(sga_asignaciones.hora_finalizacion, '".comunes::formato_hora_visual."') as hora_finalizacion_visual,
							sga_clases_tipos.nombre as tipo_clase_nombre,
							get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria) as espacio_edificacion_nombre,
							CASE sga_asignaciones.dia_semana
								WHEN 'Lunes' THEN 1
								WHEN 'Martes' THEN 2
								WHEN 'Miercoles' THEN 3
								WHEN 'Jueves' THEN 4
								WHEN 'Viernes' THEN 5
								WHEN 'Sabado' THEN 6
								WHEN 'Domingo' THEN 7
								ELSE 8
							END as dia_semana_orden
				FROM		sga_comisiones_bh
							JOIN sga_clases_tipos ON sga_comisiones_bh.tipo_clase = sga_clases_tipos.tipo_clase
							JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
				WHERE		true
							$filtro
				ORDER BY	dia_semana_orden,
							sga_asignaciones.hora_inicio
 		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el listado de asignaciones de una comisión dada.
	 *
	 * @param int $comision
	 * @return array
	 */
	function get_listado_asignaciones_subcomision($subcomision)
	{
		$subcomision = toba::db()->quote($subcomision);
		$sql = "SELECT	sga_comisiones_bh.banda_horaria,
						sga_asignaciones.asignacion,
						sga_asignaciones.dia_semana,
						".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
						sga_asignaciones.fecha_desde,
				        sga_asignaciones.fecha_hasta,
				        sga_asignaciones.periodicidad,
				        sga_asignaciones.hora_inicio,
						to_char(sga_asignaciones.hora_inicio, '".comunes::formato_hora_visual."') as hora_inicio_visual,
						sga_asignaciones.hora_finalizacion,
						to_char(sga_asignaciones.hora_finalizacion, '".comunes::formato_hora_visual."') as hora_finalizacion_visual,
						sga_clases_tipos.nombre as tipo_clase_nombre,
						(CASE sga_asignaciones.dia_semana
				            WHEN 'Lunes' THEN 1
				            WHEN 'Martes' THEN 2
				            WHEN 'Miercoles' THEN 3
				            WHEN 'Jueves' THEN 4
				            WHEN 'Viernes' THEN 5
				            WHEN 'Sabado' THEN 6
				            WHEN 'Domingo' THEN 7
							ELSE 8
						   END) as dia_semana_orden
				FROM 	sga_subcomisiones_bh
				JOIN	sga_comisiones_bh ON sga_comisiones_bh.banda_horaria = sga_subcomisiones_bh.banda_horaria
				JOIN    sga_asignaciones ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
				JOIN    sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_comisiones_bh.tipo_clase
				WHERE	sga_subcomisiones_bh.subcomision = $subcomision
				ORDER BY dia_semana_orden,
						 sga_asignaciones.hora_inicio
				";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna verdadero si la asignación dada es compartida por otra comisión (diferente a la recibida como parámetro)
	 * y falso en caso contrario.
	 *
	 * @param integer $asignacion
	 * @param integer $banda_horaria
	 * @return boolean
	 */
	function asignacion_compartida($comision=null, $asignacion=null)
	{
		if (is_null($comision) || is_null($asignacion)) {
			return false;
		}
		$comision = toba::db()->quote($comision);
		$asignacion = toba::db()->quote($asignacion);
		$sql = "SELECT 	CASE
							WHEN count(sga_comisiones_bh.banda_horaria) > 0 THEN 1
							ELSE 0
						END as compartida
                FROM 	sga_comisiones_bh
				WHERE 	sga_comisiones_bh.asignacion = $asignacion AND
						sga_comisiones_bh.comision <> $comision";
		return guarani_db::consultar($sql);
	}

	/**
	 * Dada una asignación existente y los datos de otra asignación que se está intentado
	 * editar/dar de alta, que se solapan en fechas y horarios, verifica si existen 
	 * clases superpuestas entre ambas (control de superposición en fechas calendario según su
	 * periodicidad).
	 * @param int $asignacion_existente
	 * @param date $fecha_desde_nueva
	 * @param date $fecha_hasta_nueva
	 * @param string $periodicidad_nueva
	 * @param string $dia_semana_nueva
	 * @return boolean true or false dependiendo de si existen o no clases superpuestas para ambas asignaciones.
	 */
	function existen_clases_superpuestas_en_asignaciones_comisiones($asignacion_existente, $fecha_desde_nueva, $fecha_hasta_nueva, $periodicidad_nueva, $dia_semana_nueva)
	{
		$asignacion_existente = toba::db()->quote($asignacion_existente);
		$fecha_desde_nueva = toba::db()->quote($fecha_desde_nueva);
		$fecha_hasta_nueva = toba::db()->quote($fecha_hasta_nueva);
		$periodicidad_nueva = toba::db()->quote($periodicidad_nueva);
		$dia_semana_nueva = toba::db()->quote($dia_semana_nueva);
		
		$sql = "SELECT	COUNT(sga_clases.clase) as cant
				FROM	sga_comisiones_bh,
						sga_clases
				WHERE	sga_comisiones_bh.asignacion = $asignacion_existente
						AND sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria
						AND sga_clases.valido = 1
						AND sga_clases.fecha IN (SELECT	*
												 FROM	f_clases_banda_horaria($fecha_desde_nueva, $fecha_hasta_nueva, $periodicidad_nueva, $dia_semana_nueva)
												)
						;";
		
		$result = guarani_db::consultar_fila($sql);
		if (!empty($result)) {
			return $result['cant'] > 0;
		}
	}
		
	/**
	 * 
	 * @param date $fecha_desde		fecha inicial de la asignación
	 * @param date $fecha_hasta		fecha final de la asignación
	 * @param string $periodicidad	periodicidad de la asignación
	 * @param string $dia_semana	día de la semana correspondiente a la asginación
	 * @param where string filtro sobre las fechas de clases que se recuperan.
	 * @return array recupera las fechas de clases correspondientes a una asignación de una banda
	 * horaria que se está  intentando crear o modificar. Las fechas calendario de las clases se
	 * calculan a partir de los datos de la asignación, ya que, al no estar la bh sincronizada,
	 * no se encuentran correctamente almacenadas en la tabla sga_clases (dichos datos se generarán 
	 * por trigger al sincronizar la bh).
	 */
	function get_clases_asignacion_bh_no_sincronizada($fecha_desde, $fecha_hasta, $periodicidad, $dia_semana, $where='')
	{
		$fecha_desde = toba::db()->quote($fecha_desde);
		$fecha_hasta = toba::db()->quote($fecha_hasta);
		$periodicidad = toba::db()->quote($periodicidad);
		$dia_semana = toba::db()->quote($dia_semana);
		if ($where) {
			$where = ' WHERE ' . $where; 
		}
		
		$sql = "SELECT	*
				FROM	f_clases_banda_horaria($fecha_desde, $fecha_hasta, $periodicidad, $dia_semana) as fecha
						$where
				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el listado de periodicidades posibles para una asignación.
	 *
	 * @return array
	 */
	function get_periodicidades()
	{
		return array(	array('valor' => banda_horaria::periodicidad_mensual, 'descr' => 'Mensual'),
						array('valor' => banda_horaria::periodicidad_quincenal, 'descr' => 'Quincenal'),
						array('valor' => banda_horaria::periodicidad_semanal, 'descr' => 'Semanal')
						);
	}
	
	/**
	 * Recupera las inscripciones para el alumno dado, en una comisión distinta a la recibida 
	 * como parámetro, tales que las asignaciones asociadas a las comisiones de las mismas estén
	 * superpuestas con el día de la semana, rango de fechas y rango horario dados.
	 *
	 * @param integer $alumno		alumno asociado a la inscripcion.
	 * @param integer $comision		comision asociada a la inscripcion.
	 * @param string $dia_semana	día de semana correspondiente a la asignación actual
	 * @param time $hora_inicio		hora de inicio correspondiente a la asignación actual
	 * @param time $hora_finalizacion	hora de finalizacion correspondiente a la asignación actual
	 * @param date $fecha_desde		fecha de inicio correspondiente a la asignación actual
	 * @param date $fecha_hasta		fecha de finalizacion correspondiente a la asignación actual
	 * @return boolean array asignaciones superpuestas con la dada, para otras inscripciones a comisiones del alumno.
	 */
	function get_inscripciones_superpuestas($alumno, $comision, $dia_semana, $hora_inicio, $hora_finalizacion, $fecha_desde, $fecha_hasta, $where='')
	{
		$alumno = toba::db()->quote($alumno);
		$comision = toba::db()->quote($comision);
		$dia_semana = toba::db()->quote($dia_semana);
		$hora_inicio = toba::db()->quote($hora_inicio);
		$hora_finalizacion = toba::db()->quote($hora_finalizacion);
		$fecha_desde = toba::db()->quote($fecha_desde);
		$fecha_hasta = toba::db()->quote($fecha_hasta);
		if ($where) {
			$where = ' AND ' . $where; 
		}
		
		$sql = "-- Inscripciones a comisiones sin subcomisiones.
				SELECT 	sga_insc_cursada.inscripcion,
						sga_comisiones_bh.comision,
						sga_comisiones_bh.banda_horaria,
						sga_asignaciones.asignacion
				FROM 	sga_insc_cursada
						JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
						JOIN sga_comisiones_bh ON sga_insc_cursada.comision = sga_comisiones_bh.comision
						JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
				WHERE	-- La inscripción NO es por prioridades.
						(sga_insc_cursada.tipo = '".inscripcion_cursada::tipo_inscripcion."' OR
						(sga_insc_cursada.tipo = '".inscripcion_cursada::tipo_preinscripcion."' AND sga_insc_cursada.estado_preinscripcion = '".inscripcion_cursada::preinsc_aceptada."')) AND
						-- No hay inscripción a subcomisión.
						NOT EXISTS (SELECT	1
									FROM	sga_insc_subcomision
									WHERE	sga_insc_subcomision.inscripcion = sga_insc_cursada.inscripcion) AND
						sga_alumnos.persona = (	SELECT	alumnos.persona
												FROM	sga_alumnos as alumnos
												WHERE	alumnos.alumno = $alumno) AND
						sga_comisiones_bh.comision <> $comision AND
						sga_asignaciones.dia_semana = $dia_semana AND
						((	sga_asignaciones.hora_inicio > $hora_inicio AND sga_asignaciones.hora_inicio < $hora_finalizacion)
							OR
							(sga_asignaciones.hora_finalizacion > $hora_inicio AND sga_asignaciones.hora_finalizacion < $hora_finalizacion)
							OR
							(sga_asignaciones.hora_inicio <= $hora_inicio AND sga_asignaciones.hora_finalizacion >= $hora_finalizacion)) AND
						((	sga_asignaciones.fecha_desde BETWEEN $fecha_desde AND $fecha_hasta)
							OR
							(sga_asignaciones.fecha_hasta BETWEEN $fecha_desde AND $fecha_hasta)
							OR
							(sga_asignaciones.fecha_desde <= $fecha_desde AND sga_asignaciones.fecha_hasta >= $fecha_hasta)) 
						
						$where
							
				UNION ALL
				-- Inscripciones a subcomisiones.
				SELECT 	sga_insc_cursada.inscripcion,
						sga_comisiones_bh.comision,
						sga_subcomisiones_bh.banda_horaria,
						sga_asignaciones.asignacion
				FROM 	sga_insc_cursada
						JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
						JOIN sga_insc_subcomision ON sga_insc_cursada.inscripcion = sga_insc_subcomision.inscripcion
						JOIN sga_subcomisiones_bh ON sga_insc_subcomision.subcomision = sga_subcomisiones_bh.subcomision
						JOIN sga_comisiones_bh ON sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria
						JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
				WHERE	-- La inscripción NO es por prioridades.
						(sga_insc_cursada.tipo = '".inscripcion_cursada::tipo_inscripcion."' OR
						(sga_insc_cursada.tipo = '".inscripcion_cursada::tipo_preinscripcion."' AND sga_insc_cursada.estado_preinscripcion = '".inscripcion_cursada::preinsc_aceptada."')) AND
						sga_alumnos.persona = (	SELECT	alumnos.persona
												FROM	sga_alumnos as alumnos
												WHERE	alumnos.alumno = $alumno) AND
						sga_comisiones_bh.comision <> $comision AND
						sga_asignaciones.dia_semana = $dia_semana AND
						((	sga_asignaciones.hora_inicio > $hora_inicio AND sga_asignaciones.hora_inicio < $hora_finalizacion)
							OR
							(sga_asignaciones.hora_finalizacion > $hora_inicio AND sga_asignaciones.hora_finalizacion < $hora_finalizacion)
							OR
							(sga_asignaciones.hora_inicio <= $hora_inicio AND sga_asignaciones.hora_finalizacion >= $hora_finalizacion)) AND
						((	sga_asignaciones.fecha_desde BETWEEN $fecha_desde AND $fecha_hasta)
							OR
							(sga_asignaciones.fecha_hasta BETWEEN $fecha_desde AND $fecha_hasta)
							OR
							(sga_asignaciones.fecha_desde <= $fecha_desde AND sga_asignaciones.fecha_hasta >= $fecha_hasta)) 
								
						$where
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Verifica si existen clases superpuestas entre dos asignaciones de bandas horaras dadas, 
	 * de comisiones o subcomisiones, que se solapan en rangos de fechas y horarios.
	 * @param integer $bh_origen
	 * @param integer $bh_otra
	 * @return boolean true si existen clases en la misma fecha calendario y false en caso contrario.
	 */
	function hay_clases_superpuestas_en_bh_solapadas($bh_origen, $bh_otra)
	{
		$bh_origen = toba::db()->quote($bh_origen);
		$bh_otra = toba::db()->quote($bh_otra);
		
		$sql = "SELECT	COUNT(*) as cant
				FROM	sga_clases as c_origen,
						sga_clases as c_otra
				WHERE	c_origen.banda_horaria = $bh_origen
						AND c_otra.banda_horaria = $bh_otra
						AND c_otra.fecha = c_origen.fecha
						AND c_otra.valido = 1
						AND c_origen.valido = 1
				;";
		$res = guarani_db::consultar_fila($sql);
		return $res['cant'] > 0;
	}
	
	function get_cant_bandas_horarias_asignacion($asignacion)
	{
		$asignacion = toba::db()->quote($asignacion);
		
		$sql = "SELECT	COUNT(sga_comisiones_bh.banda_horaria) as cant_bh
				FROM	sga_comisiones_bh
				WHERE	sga_comisiones_bh.asignacion = $asignacion
				;";
		
		$datos = guarani_db::consultar_fila($sql);
		if (!empty($datos)) {
			return $datos['cant_bh'];
		}
	}
	
	function get_clases_de_bh_entre_fechas($banda_horaria, $fecha_desde, $fecha_hasta)
	{
		$banda_horaria = toba::db()->quote($banda_horaria);
		$fecha_desde = toba::db()->quote($fecha_desde);
		$fecha_hasta = toba::db()->quote($fecha_hasta);
		
		$sql = "
			SELECT	clase, fecha
			FROM	sga_clases
			WHERE	sga_clases.banda_horaria = $banda_horaria AND
					sga_clases.fecha >= $fecha_desde AND
					sga_clases.fecha <= $fecha_hasta";
		
		return guarani_db::consultar($sql);
	}
}
?>
