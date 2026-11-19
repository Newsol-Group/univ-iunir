<?php
/**
 *
 * @package Examenes
 * @subpackage Inscripciones
 */
class co_inscripciones_examenes
{
	function get_listado($where = '', $filtro_tope_baja_vigente = true)
	{
		$filtro = "";
		if ($filtro_tope_baja_vigente) {
			/*
			$interfaz = comunes::interfaz_gestion;
			$filtro = " -- Se controla si existe periodo de inscripcion habilitado para dar de baja la inscripcion. 
				       AND f_mesa_con_tope_bajas_vigente(sga_insc_examen.plan_version, sga_llamados_mesa.llamado_mesa, $interfaz, sga_alumnos.alumno) ";
			*/
			
		} else {
			// No estoy con el filtro vigente de bajas, entonces solo listo inscripciones de mesas con fecha y hora mayor o igual a la fecha y hora actual.
			$filtro = " -- La fecha y hora de la mesa es mayor o igual a la fecha y hora actual 
                        AND CAST(sga_llamados_mesa.fecha as timestamptz) + CAST(sga_llamados_mesa.hora_inicio as time) >= CURRENT_TIMESTAMP ";
		}

		if ($where) {
			$filtro .= ' AND ' . $where;
		}

		$formato_hora_visual = toba::db()->quote(comunes::formato_hora_visual);

		$sql = "SELECT
					sga_insc_examen.inscripcion,
					sga_insc_examen.nro_transaccion,
					sga_insc_examen.fecha_inscripcion,
					'(' || vw_actividades_plan.codigo || ') ' || vw_actividades_plan.nombre AS actividad_nombre,
					sga_mesas_examen.nombre AS mesa_nombre,
					sga_periodos_llamado.nombre AS llamado_nombre,
					sga_periodos_llamado.anio_academico,
					sga_periodos_turno.nombre as turno_nombre,
					sga_llamados_mesa.fecha AS fecha_examen,
					to_char(CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), '" . comunes::formato_fecha_hora_visual_sin_segundos . "') AS fecha_examen_visual,
					sga_instancias.nombre AS instancia_nombre,
					sga_catedras.nombre AS catedra_nombre,
					sga_ubicaciones.nombre AS ubicacion_nombre,
					f_modalidad_cursada_examen(sga_mesas_examen.mesa_examen) AS modalidades_nombre,
					vw_personas.persona,
					vw_personas.apellido_nombres AS apellido_y_nombre,
					vw_personas.tipo_nro_documento AS nro_identificacion,
					sga_alumnos.alumno,
					sga_alumnos.legajo,
					sga_alumnos.propuesta,
					sga_alumnos.plan_version,
					sga_propuestas.nombre AS propuesta_nombre,
					sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
					sga_inscripciones_estados.nombre as estado_nombre,
					vw_actividades_plan.elemento,
					sga_llamados_mesa.fecha AS mesa_examen_fecha,
					CONCAT_WS(' a ', to_char(date '2001-01-01' + sga_llamados_mesa.hora_inicio, $formato_hora_visual),
                    to_char(date '2001-01-01' + sga_llamados_mesa.hora_finalizacion, $formato_hora_visual)) as horario_examen

				FROM
					sga_insc_examen
					JOIN sga_inscripciones_estados ON sga_insc_examen.estado = sga_inscripciones_estados.estado
					JOIN sga_instancias ON sga_insc_examen.instancia = sga_instancias.instancia
					JOIN sga_alumnos ON sga_insc_examen.alumno = sga_alumnos.alumno
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
					JOIN sga_llamados_mesa ON sga_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
					JOIN sga_llamados_turno ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
					JOIN sga_turnos_examen ON sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
					JOIN sga_periodos as sga_periodos_turno ON sga_turnos_examen.periodo = sga_periodos_turno.periodo
					JOIN sga_periodos as sga_periodos_llamado ON sga_llamados_turno.periodo = sga_periodos_llamado.periodo
					JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
					JOIN vw_actividades_plan ON (sga_mesas_examen.elemento = vw_actividades_plan.elemento AND sga_insc_examen.plan_version = vw_actividades_plan.plan_version)
					JOIN sga_ubicaciones ON sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
					LEFT JOIN sga_catedras ON sga_mesas_examen.catedra = sga_catedras.catedra

				WHERE
					-- La inscripción no debe estar en algún acta de exámen de la mesa
					-- Caso 1: Acta Cerrada
					NOT EXISTS(	SELECT 	1
									FROM 	sga_actas
				                    JOIN	sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta AND sga_actas_detalle.alumno = sga_insc_examen.alumno
									WHERE	sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa
				                    AND		sga_actas.estado = 'C')
					-- Caso 2: Acta abierta
					AND NOT EXISTS(	SELECT 	1
									FROM 	sga_actas
				                    JOIN	sga_eval_detalle_examenes ON sga_eval_detalle_examenes.id_acta = sga_actas.id_acta AND sga_eval_detalle_examenes.alumno = sga_insc_examen.alumno
									WHERE	sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa
				                    AND		sga_actas.estado = 'A')
					$filtro						
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de inscripciones a examenes según filtro especificado
	 *
	 */
	function get_listado_inscripciones($where = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}

		$sql = "SELECT
					sga_insc_examen.inscripcion,
					sga_insc_examen.instancia,
					sga_insc_examen.alumno,
					sga_mesas_examen.elemento,
					sga_llamados_mesa.llamado_mesa,
					sga_mesas_examen.mesa_examen,
					sga_insc_examen.nro_transaccion,
					sga_insc_examen.fecha_inscripcion,
					sga_insc_examen.plan_version,
					sga_llamados_turno.turno_examen,
					vw_actividades_plan.nombre as elemento_nombre,
					'(' || vw_actividades_plan.codigo || ') ' || vw_actividades_plan.nombre AS actividad_nombre,
					sga_mesas_examen.nombre AS mesa_nombre,
					sga_periodos_llamado.nombre AS llamado_nombre,
					sga_periodos_llamado.anio_academico,
					sga_periodos_turno.nombre as turno_nombre,
					sga_llamados_mesa.fecha AS fecha_examen,
					to_char(sga_llamados_mesa.fecha, '" . comunes::formato_fecha_hora_visual . "') AS fecha_examen_visual,
					sga_instancias.nombre AS instancia_nombre,
					sga_catedras.nombre AS catedra_nombre,
					sga_ubicaciones.nombre AS ubicacion_nombre,
					f_modalidad_cursada_examen(sga_mesas_examen.mesa_examen) AS modalidades_nombre,
					vw_personas.apellido_nombres AS apellido_y_nombre,
					vw_personas.tipo_nro_documento AS nro_identificacion,
					sga_alumnos.persona,
					sga_alumnos.legajo,
					sga_propuestas.nombre AS propuesta_nombre,
					sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
					sga_alumnos.propuesta
				FROM
					sga_insc_examen
					JOIN sga_instancias ON sga_insc_examen.instancia = sga_instancias.instancia
					JOIN sga_alumnos ON sga_insc_examen.alumno = sga_alumnos.alumno
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
					JOIN sga_llamados_mesa ON sga_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
					JOIN sga_llamados_turno ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
					JOIN sga_turnos_examen ON sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
					JOIN sga_periodos as sga_periodos_turno ON sga_turnos_examen.periodo = sga_periodos_turno.periodo
					JOIN sga_periodos as sga_periodos_llamado ON sga_llamados_turno.periodo = sga_periodos_llamado.periodo
					JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
					JOIN vw_actividades_plan ON (sga_mesas_examen.elemento = vw_actividades_plan.elemento AND sga_insc_examen.plan_version = vw_actividades_plan.plan_version)
					JOIN sga_ubicaciones ON sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
					LEFT JOIN sga_catedras ON sga_mesas_examen.catedra = sga_catedras.catedra

				$where
				ORDER BY 
					sga_periodos_turno.anio_academico DESC
		";

		return guarani_db::consultar($sql);
	}

	function get_listado_reporte_insc_examen($where = '',$requisito_no_cumplido = '')
	{
		$sql = " -- Inscripciones aceptadas y pendientes.
				SELECT
					-- Grupo 1
					sga_ubicaciones.ubicacion	AS ubicacion,
					sga_ubicaciones.nombre 		AS ubicacion_nombre,

					-- Grupo 2
					sga_mesas_examen.elemento			AS elemento,
                    sga_elementos_plan.nombre			AS actividad_nombre,
					'(' || sga_elementos.codigo || ') ' || sga_elementos_plan.nombre AS actividad_codigo_y_nombre,
					
					-- Campos que se muestran en el reporte
					sga_alumnos.legajo 					AS legajo,
					vw_insc_examen.inscripcion			AS inscripcion,
					vw_personas.apellido_nombres 		AS apellido_y_nombre,
					vw_personas.tipo_nro_documento 		AS documento_principal,
					sga_mesas_examen.nombre 			AS mesa_nombre,
					sga_periodos.nombre 				AS llamado_nombre,
					sga_instancias.nombre 				AS instancia_nombre,
					sga_inscripciones_estados.nombre 	AS estado_nombre,
					sga_llamados_mesa.fecha		as fecha_examen,
					sga_llamados_mesa.hora_inicio		as hora_inicio,
					sga_llamados_mesa.hora_finalizacion	as hora_finalizacion,
					vw_insc_examen.fecha_inscripcion	as fecha_inscripcion
				FROM
					sga_periodos
					JOIN sga_llamados_turno ON sga_periodos.periodo = sga_llamados_turno.periodo
					JOIN sga_turnos_examen ON sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
					JOIN sga_llamados_mesa ON sga_llamados_turno.llamado = sga_llamados_mesa.llamado
					JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
					JOIN vw_insc_examen ON sga_llamados_mesa.llamado_mesa = vw_insc_examen.llamado_mesa

					-- JOIN vw_actividades_plan ON (sga_mesas_examen.elemento = vw_actividades_plan.elemento AND vw_insc_examen.plan_version = vw_actividades_plan.plan_version)
					JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_mesas_examen.elemento
					JOIN sga_elementos_plan ON (sga_elementos_plan.plan_version = vw_insc_examen.plan_version AND sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision)
					JOIN sga_elementos ON sga_elementos.elemento = sga_mesas_examen.elemento
					
					JOIN sga_ubicaciones ON sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
					JOIN sga_instancias ON vw_insc_examen.instancia = sga_instancias.instancia
					JOIN sga_inscripciones_estados ON vw_insc_examen.estado = sga_inscripciones_estados.estado
					JOIN sga_alumnos ON vw_insc_examen.alumno = sga_alumnos.alumno
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
				Where $where
				
				UNION 
				
				-- Inscripciones rechazadas, bajas o suspendida
				SELECT
					-- Grupo 1
					sga_ubicaciones.ubicacion	AS ubicacion,
					sga_ubicaciones.nombre 		AS ubicacion_nombre,

					-- Grupo 2
					sga_mesas_examen.elemento	AS elemento,
                    sga_elementos_plan.nombre	AS actividad_nombre,
					'(' || sga_elementos.codigo || ') ' || sga_elementos_plan.nombre AS actividad_codigo_y_nombre,
					
					-- Campos que se muestran en el reporte
					sga_alumnos.legajo					AS legajo,
					vw_insc_examen.inscripcion			AS inscripcion,
					vw_personas.apellido_nombres 		AS apellido_y_nombre,
					vw_personas.tipo_nro_documento 		AS documento_principal,
					sga_mesas_examen.nombre 			AS mesa_nombre,
					sga_periodos.nombre 				AS llamado_nombre,
					sga_instancias.nombre 				AS instancia_nombre,
					CASE vw_insc_examen.operacion
						WHEN '".inscripcion_examen::estado_rechazo."' THEN '".inscripcion_examen::estado_rechazo_desc."'
						WHEN '".inscripcion_examen::estado_baja."' THEN '".inscripcion_examen::estado_baja_desc."'
						WHEN '".inscripcion_examen::estado_suspendido."' THEN '".inscripcion_examen::estado_suspendido_desc."'
					END	AS estado_nombre,
					sga_llamados_mesa.fecha		as fecha_examen,
					sga_llamados_mesa.hora_inicio		as hora_inicio,
					sga_llamados_mesa.hora_finalizacion	as hora_finalizacion,
					vw_insc_examen.fecha_inscripcion	as fecha_inscripcion
				FROM
					sga_periodos
					JOIN sga_llamados_turno ON sga_periodos.periodo = sga_llamados_turno.periodo
					JOIN sga_turnos_examen ON sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
					JOIN sga_llamados_mesa ON sga_llamados_turno.llamado = sga_llamados_mesa.llamado
					JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
					JOIN vw_insc_examen_log as vw_insc_examen ON sga_llamados_mesa.llamado_mesa = vw_insc_examen.llamado_mesa
					
					-- JOIN vw_actividades_plan ON (sga_mesas_examen.elemento = vw_actividades_plan.elemento AND vw_insc_examen.plan_version = vw_actividades_plan.plan_version)
					JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_mesas_examen.elemento
					JOIN sga_elementos_plan ON (sga_elementos_plan.plan_version = vw_insc_examen.plan_version AND sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision)
					JOIN sga_elementos ON sga_elementos.elemento = sga_mesas_examen.elemento
					
					JOIN sga_ubicaciones ON sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
					JOIN sga_instancias ON vw_insc_examen.instancia = sga_instancias.instancia
					JOIN sga_alumnos ON vw_insc_examen.alumno = sga_alumnos.alumno
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
				Where					
				$where
				$requisito_no_cumplido
				ORDER BY
				    ubicacion_nombre,
				    actividad_nombre,
				    apellido_y_nombre
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de inscriptos a una mesa, junto con los resultado de la última cursada.
	 *
	 * @param 	int 	$mesa_examen 	Mesa de Examen seleccionada
	 */
	function get_resultados_cursada_inscriptos_mesa($mesa_examen, $llamado_mesa, $where = '')
	{
		$mesa_examen = toba::db()->quote($mesa_examen);
		$llamado_mesa = toba::db()->quote($llamado_mesa);

		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT		vw_insc_examen.inscripcion,
							vw_personas.apellido_nombres AS apellido_y_nombres,
							vw_personas.tipo_nro_documento AS nro_identificacion,
							sga_propuestas.nombre_abreviado AS propuesta_nombre,
							vw_insc_examen.instancia,
							sga_instancias.nombre AS instancia_nombre,
							get_ultima_cursada(vw_insc_examen.alumno, sga_mesas_examen.elemento) AS datos_cursada,
							sga_mesas_examen.elemento,
							vw_insc_examen.alumno
				FROM		vw_insc_examen
							JOIN sga_instancias ON vw_insc_examen.instancia = sga_instancias.instancia
							JOIN sga_alumnos ON vw_insc_examen.alumno = sga_alumnos.alumno
							JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
							JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
							JOIN sga_llamados_mesa ON vw_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
							JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				WHERE		sga_llamados_mesa.llamado_mesa = $llamado_mesa
							AND sga_mesas_examen.mesa_examen = $mesa_examen
							AND vw_insc_examen.estado = '" . inscripcion_examen::estado_aceptada . "'
							$where
				ORDER BY	vw_personas.apellido,
							vw_personas.nombres
		";
		$datos = guarani_db::consultar($sql);

		// Procesamiento de los datos de cursada separados por '|'
		foreach ($datos as $key => $dato) {
			$datos_inscripcion = explode('$PARAM$', $dato['datos_cursada']);
			$datos[$key]['anio_academico'] = $datos_inscripcion[0];
			$datos[$key]['periodo_lectivo'] = $datos_inscripcion[1];
			$datos[$key]['fecha_regular'] = $datos_inscripcion[2];
			$datos[$key]['condicion_regularidad'] = $datos_inscripcion[3];
			$datos[$key]['nota'] = $datos_inscripcion[4];
			$datos[$key]['resultado_desc'] = $datos_inscripcion[5];
			$datos[$key]['fecha_vigencia'] = $datos_inscripcion[6];
			$datos[$key]['vigente'] = $datos_inscripcion[7];
			$datos[$key]['ubicacion'] = $datos_inscripcion[8];
			$datos[$key]['catedra'] = isset($datos_inscripcion[9]) ? $datos_inscripcion[9] : '';
		}

		return $datos;
	}

	/**
	 * Opciones para el combo "Inscripto en"
	 *
	 */
	function get_opciones_inscripto_en()
	{
		return array(array('valor' => 'N', 'descr' => 'En un período de Inscripción'), array('valor' => 'S', 'descr' => 'Fuera del período de Inscripción'));
	}

	function get_datos_log($nro_transaccion_original)
	{
		$nro_transaccion_original = toba::db()->quote($nro_transaccion_original);

		$sql = "SELECT 	sga_insc_examen_log.inscripcion,
						sga_insc_examen_log.alumno,
						sga_insc_examen_log.llamado_mesa,
						sga_insc_examen_log.plan_version,
						sga_insc_examen_log.instancia,
						sga_insc_examen_log.fecha_inscripcion,
						sga_insc_examen_log.nro_transaccion,
						sga_insc_examen_log.estado,
						sga_insc_examen_log.operacion,
						sga_insc_examen_log.nro_transaccion_log
				FROM 	sga_insc_examen_log
				WHERE 	sga_insc_examen_log.nro_transaccion = $nro_transaccion_original
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los registros de la tabla sga_insc_examen_log
	 */
	function get_insc_examen_log($where = '')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}

		$sql = "SELECT 
					sga_insc_examen_log.inscripcion,
					sga_insc_examen_log.alumno,
					sga_insc_examen_log.llamado_mesa,
					sga_insc_examen_log.plan_version,
					sga_insc_examen_log.instancia,
					sga_insc_examen_log.fecha_inscripcion,
					sga_insc_examen_log.nro_transaccion,
					sga_insc_examen_log.estado,
					sga_insc_examen_log.motivo_rechazo,
					sga_insc_examen_log.operacion,
					sga_insc_examen_log.fecha_operacion,
					sga_insc_examen_log.nro_transaccion_log
				FROM 
					sga_insc_examen_log
				$where
  			   ";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la cantidad de inscripciones a examen (en estado Pendiente o Aceptado) registradas para la mesa en un llamado
	 * dado, de acuerdo al filtro especificado. 
	 */
	function get_cant_inscripciones_para_un_llamado_mesa($llamado_mesa, $where = '')
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$filtro = "";

		if ($where) {
			$filtro .= ' AND ' . $where;
		}

		$sql = "SELECT	count(sga_insc_examen.inscripcion) as cant_inscripciones
				FROM	sga_insc_examen
				JOIN	sga_llamados_mesa ON (sga_llamados_mesa.llamado_mesa = sga_insc_examen.llamado_mesa)
				WHERE	sga_insc_examen.llamado_mesa = $llamado_mesa
				$filtro
		";

		$datos = guarani_db::consultar_fila($sql);
		return $datos['cant_inscripciones'];
	}

	/**
	 * Retorna la cantidad de inscripciones a examen (en estado Pendiente o Aceptado) registradas para los 
	 * llamados de una mesa dada, de acuerdo al filtro especificado. 
	 */
	function get_cant_inscripciones_para_llamados_mesa($mesa, $where = '')
	{
		$mesa = toba::db()->quote($mesa);
		$filtro = " AND sga_llamados_mesa.mesa_examen = $mesa ";

		if ($where) {
			$filtro .= ' AND ' . $where;
		}

		$sql = "SELECT	count(sga_insc_examen.inscripcion) as cant_inscripciones
				FROM	sga_insc_examen,
						sga_llamados_mesa
				WHERE	sga_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
						$filtro
		";

		$datos = guarani_db::consultar_fila($sql);
		return $datos['cant_inscripciones'];
	}

	/**
	 * Retorna la cantidad de inscripciones a examen en estado de Baja o Rechazadas, registradas para los llamados de una 
	 * mesa dada, de acuerdo al filtro especificado. Para esto se consulta la tabla sga_insc_examen_log en lugar de 
	 * sga_insc_examen.
	 */
	function get_cant_inscripciones_bajas_para_llamados_mesa($mesa, $where = '')
	{
		$mesa = toba::db()->quote($mesa);
		$filtro = " AND sga_llamados_mesa.mesa_examen = $mesa ";

		if ($where) {
			$filtro .= ' AND ' . $where;
		}

		$sql = "SELECT	count(sga_insc_examen_log.inscripcion) as cant_inscripciones
				FROM	sga_insc_examen_log,
						sga_llamados_mesa
				WHERE	sga_insc_examen_log.llamado_mesa = sga_llamados_mesa.llamado_mesa
						$filtro
		";

		$datos = guarani_db::consultar_fila($sql);
		return $datos['cant_inscripciones'];
	}

	/**
	 * Retorna los registros de la tabla sga_insc_examen
	 */
	function get_insc_examen($where = '')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}

		$sql = "SELECT 
					sga_insc_examen.inscripcion,
					sga_insc_examen.alumno,
					sga_insc_examen.llamado_mesa,
					sga_insc_examen.plan_version,
					sga_insc_examen.instancia,
					sga_insc_examen.fecha_inscripcion,
					sga_insc_examen.fuera_de_termino,
					sga_insc_examen.autorizado_por,
					sga_insc_examen.nro_transaccion,
					sga_insc_examen.motivo_excepcion,
					sga_insc_examen.estado
				FROM 
					sga_insc_examen
				$where
  			   ";

		return guarani_db::consultar($sql);
	}

	function get_datos_inscripcion($nro_transaccion)
	{
		$nro_transaccion = toba::db()->quote($nro_transaccion);

		$sql = "SELECT 	sga_insc_examen.inscripcion,
						sga_insc_examen.alumno,
						sga_insc_examen.llamado_mesa,
						sga_insc_examen.plan_version,
						sga_insc_examen.instancia,
						sga_insc_examen.fecha_inscripcion,
						to_char(sga_insc_examen.fecha_inscripcion, '" . comunes::formato_fecha_hora_visual . "') as fecha_inscripcion_visual,
						sga_insc_examen.fuera_de_termino,
						sga_insc_examen.autorizado_por,
						sga_insc_examen.nro_transaccion,
						sga_insc_examen.exceptuado,
						sga_insc_examen.estado,
						sga_insc_examen.interfaz,
						sga_inscripciones_estados.nombre as estado_nombre
				FROM	sga_insc_examen
						JOIN sga_inscripciones_estados ON sga_inscripciones_estados.estado = sga_insc_examen.estado
				WHERE	sga_insc_examen.nro_transaccion = $nro_transaccion
		";

		return guarani_db::consultar_fila($sql);
	}

	function get_nuevo_nro_transaccion()
	{
		return comunes::get_nuevo_nro_transaccion();
	}

	function get_estados_inscripcion()
	{
		$sql = "SELECT		sga_inscripciones_estados.estado as valor,
							sga_inscripciones_estados.nombre as descr
				FROM		sga_inscripciones_estados
				ORDER BY 	descr
				;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna todas las posibles opciones correspondientes al estado de una inscripción 
	 * 
	 */
	function get_opciones_combo_estados_inscripcion()
	{
		$estados_inscripcion = $this->get_estados_inscripcion();

		$operaciones = [['valor' => inscripcion_examen::estado_rechazo, 'descr' => inscripcion_examen::estado_rechazo_desc],
						['valor' => inscripcion_examen::estado_baja, 'descr' => inscripcion_examen::estado_baja_desc],
						['valor' => inscripcion_examen::estado_suspendido, 'descr' => inscripcion_examen::estado_suspendido_desc]];
		$estados = array_merge($estados_inscripcion, $operaciones);
		
		//para ordenar
		$keys = array_column($estados, 'valor');
		array_multisort($keys, SORT_ASC, $estados);

		return $estados;
	}

	/**
	 * Retorna el datos de la inscripcion para un alumno en un llamado mesa determiado
	 * @param <type> $alumno
	 */
	function get_datos_inscripcion_llamado($alumno, $llamado_mesa)
	{
		$alumno = toba::db()->quote($alumno);
		$llamado_mesa = toba::db()->quote($llamado_mesa);

		$sql = "SELECT	sga_insc_examen.estado
				FROM	sga_insc_examen
				WHERE	sga_insc_examen.alumno			= $alumno
				AND		sga_insc_examen.llamado_mesa	= $llamado_mesa
				;";

		return guarani_db::consultar_fila($sql);
	}

	function get_cant_inscripciones($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	count(sga_insc_examen.inscripcion) as cantidad
				FROM	sga_insc_examen
				WHERE	sga_insc_examen.alumno = $alumno
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cantidad'];
	}

	/**
	 * Retorna las actividades disponibles para inscribirse a examen de un alumno
	 * @param int		$alumno							alumno en cuestión.
	 * @param int		$plan_version					plan versión del alumno.
	 * @param int		$ubicacion						ubicación del alumno.
	 * @param boolean	$fuera_de_termino				Indica si filtra por períodos vigentes (false) o vencidos (true).
	 * @param str		$where							filtro a utilizar.
	 */
	function get_actividades_disponibles_inscripcion($param_exa_filtro_actividades_con_mesas, $param_exa_rinde_en_cualquier_ubicacion, $param_exa_rendir_regularizada_plan_anterior, $param_exa_multiples_actas_examen,
													$propuesta, $plan, $alumno, $plan_version, $ubicacion, $fuera_de_termino=false, $where=null)
	{
		// Si el parámetro exa_filtro_actividades_con_mesas = S
		// sólo recuperar las actividades con mesa de examen definida y con períodos de inscripción vigentes.
		if ($param_exa_filtro_actividades_con_mesas == comunes::si) {
			$solo_con_mesas_vigentes = true;
			// Si el parámetro del sistema :exa_rinde_en_cualquier_ubicacion = N
			// sólo recuperar las mesas de examen de la ubicación del alumno.
			if ($param_exa_rinde_en_cualquier_ubicacion == comunes::si) {
				$ubicacion = 'null';
			}
		} else {
			$solo_con_mesas_vigentes = false;
		}

		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);
		$en_periodo_inscripcion = (!$fuera_de_termino) ? 'true' : 'false';
		$sin_acta_generada = ($param_exa_multiples_actas_examen == comunes::si) ? 'false' : 'true';

		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT	vw_actividades_plan.elemento as id,
						vw_actividades_plan.codigo,
						vw_actividades_plan.nombre,
						'(' || vw_actividades_plan.codigo || ') ' || vw_actividades_plan.nombre as descr,
						vw_actividades_plan.entidad_subtipo_nombre,
						sga_elementos_plan.creditos,
						vw_actividades_plan.plan_version as plan_version,
						vw_actividades_plan.plan, 
						'PLAN_ACTUAL' as tipo_plan
				FROM	vw_actividades_plan
						JOIN sga_elementos_plan ON (vw_actividades_plan.plan_version = sga_elementos_plan.plan_version AND vw_actividades_plan.elemento_revision = sga_elementos_plan.elemento_revision)

				WHERE	vw_actividades_plan.plan_version = $plan_version
						AND	vw_actividades_plan.estado = '" . comunes::estado_activo . "'
						-- Se obvian las actividades aprobadas
						AND	vw_actividades_plan.elemento
							NOT IN (SELECT	vw_hist_academica_basica.elemento
									FROM	vw_hist_academica_basica
									WHERE	vw_hist_academica_basica.alumno = $alumno
									AND		vw_hist_academica_basica.resultado = '" . comunes::resultado_nota_aprobado . "')
				";

		if ($solo_con_mesas_vigentes) {
			$interfaz = comunes::interfaz_gestion;
			$sql .= "	-- Con mesas de examen vigentes
           			AND vw_actividades_plan.elemento IN (SELECT * FROM f_actividades_con_mesas_vigentes($propuesta, $plan, $plan_version, $en_periodo_inscripcion, $sin_acta_generada, $ubicacion, $interfaz, $alumno))";
		}

		$sql .= "		$where
						";
		// Si se permite rendir actividades regularizadas en un plan anterior que ya no existe en el plan actual del alumno.				
		// Regularidades vigentes (para rendir examen regular) y no vigentes (para exámenes de reválidas de cursadas)
		if ($param_exa_rendir_regularizada_plan_anterior == comunes::si || $param_exa_rendir_regularizada_plan_anterior == 'G') {
			$sql .= "  
				UNION
					SELECT	vw_regularidades_basica.elemento as id,
							vw_actividades_plan.codigo as codigo,
							vw_actividades_plan.nombre as nombre,
							'(' || vw_actividades_plan.codigo || ') ' || vw_actividades_plan.nombre as descr,
							vw_actividades_plan.entidad_subtipo_nombre as entidad_subtipo_nombre,
							vw_regularidades_basica.creditos as creditos,
							vw_actividades_plan.plan_version as plan_version,
							sga_planes_versiones.plan, 
							'PLAN_ANTERIOR' as tipo_plan
					FROM	vw_regularidades_basica
							JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = vw_regularidades_basica.plan_version
							JOIN sga_elementos_revision ON sga_elementos_revision.elemento = vw_regularidades_basica.elemento
							JOIN vw_actividades_plan ON (vw_actividades_plan.elemento_revision = sga_elementos_revision.elemento_revision AND 
							                             vw_actividades_plan.plan_version      = vw_regularidades_basica.plan_version)

					WHERE	
							vw_regularidades_basica.alumno     = $alumno 
					AND		vw_regularidades_basica.resultado  = '" . comunes::resultado_nota_aprobado . "'
					-- AND		vw_regularidades_basica.es_vigente = 1
					AND		vw_regularidades_basica.origen_otra_propuesta = false
					AND		vw_actividades_plan.estado  = '" . comunes::estado_activo . "'
					-- No se listan las que esten en el plan actual
					AND		vw_actividades_plan.elemento NOT IN (SELECT	elemento FROM vw_actividades_plan WHERE plan_version = $plan_version)
				
					-- No se listan las que esten aprobadas
					AND		vw_actividades_plan.elemento NOT IN (SELECT vw_hist_academica_basica.elemento
																FROM vw_hist_academica_basica
																WHERE vw_hist_academica_basica.alumno = $alumno
																	AND vw_hist_academica_basica.resultado = '" . comunes::resultado_nota_aprobado . "')
					ORDER BY nombre;
		";
		}
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de alumnos sin inscripción automática a examen
	 *
	 */
	function get_listado_rep_alumnos_sin_insc_auto_examen($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		// Sólo comisiones que tengan definida una mesa de examen para inscripción automática.
		$sql = "SELECT
					-- Grupo 1
					sga_ubicaciones.ubicacion,
					sga_ubicaciones.nombre AS nombre_ubicacion,
					-- Grupo 2
					vw_actividades.elemento,
					'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_codigo_y_nombre,
					-- Grupo 3
					sga_comisiones.comision,
					sga_comisiones.nombre AS comision_nombre,
					
					-- Datos a exhibir en el reporte
					vw_personas.apellido_nombres AS apellido_y_nombre,
					vw_personas.tipo_nro_documento AS identificacion,
					sga_alumnos.legajo,
					sga_propuestas.nombre_abreviado AS propuesta_nombre

				FROM
					sga_comisiones
					JOIN vw_actividades ON sga_comisiones.elemento = vw_actividades.elemento
					JOIN sga_actas ON sga_comisiones.comision = sga_actas.comision
					JOIN sga_actas_detalle ON sga_actas.id_acta = sga_actas_detalle.id_acta
					JOIN sga_alumnos ON sga_actas_detalle.alumno = sga_alumnos.alumno
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
					JOIN sga_insc_auto_examen ON sga_comisiones.comision = sga_insc_auto_examen.comision
					JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
					JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
					JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo

				WHERE
					sga_actas.origen = '" . acta::origen_regulares . "' -- Acta de Cursadas
					AND sga_actas.estado = '" . acta::estado_cerrada . "' -- Cerradas
					-- Sólo inscripciones procesadas
					AND sga_insc_auto_examen.cantidad > 0
					-- Aprobo la cursada
					AND sga_actas_detalle.resultado = '" . comunes::resultado_nota_aprobado . "' -- Aprobado
					-- Activo
					AND sga_actas_detalle.estado    = '" . acta::detalle_estado_activo . "' -- Activo
					-- No rectificado
					AND sga_actas_detalle.rectificado = '" . comunes::no . "'
					-- No tiene la inscripcion a examen en la mesa asociada a la comision.
					AND NOT EXISTS (SELECT 	1
								  	FROM 	vw_insc_examen
								    WHERE 	vw_insc_examen.llamado_mesa = sga_insc_auto_examen.llamado_mesa
								       		AND vw_insc_examen.alumno = sga_alumnos.alumno )
					$where
					
				ORDER BY
					ubicacion,
					vw_actividades.codigo,
					vw_actividades.nombre,
					comision_nombre,
					vw_personas.apellido,
					vw_personas.nombres
				";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos de las relaciones de asociación entre Comisiones y Llamados de Mesas de Examen para la 
	 * Inscripción automática de los alumnos al examen. Las condiciones que deben verificarse para esto son:
	 * Que las comisiones tengan mesa de examen asociada para inscripcion automática, que las comisiones tengan actas 
	 * de regulares (originales o rectificativas) cerradas, y que las mesas de exámen tengan una fecha mayor o igual a
	 * la actual.
	 *
	 * @param str $where	Filtro sobre las relaciones a recuperar, aplicados a las mesas, llamados o comisiones.
	 */
	function get_datos_asociacion_comisiones_mesas_para_inscripcion($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT		sga_insc_auto_examen.comision,
							sga_insc_auto_examen.llamado_mesa,
				            sga_insc_auto_examen.cantidad,
				            CASE 
				             WHEN sga_insc_auto_examen.cantidad = 0 
				              THEN '" . comunes::no_desc . "'
				              ELSE '" . comunes::si_desc . "'
				            END as comision_procesada,
				            sga_comisiones.nombre as comision_nombre,
				            sga_mesas_examen.nombre as mesa_nombre,
				            sga_ubicaciones.nombre as ubicacion_nombre,
				            sga_llamados_mesa.fecha as fecha_examen,
				            sga_elementos.elemento,
				            '(' || sga_elementos.codigo || ') ' || get_nombre_actividad_mesa_examen(sga_mesas_examen.mesa_examen, 1) as elemento_descr,
				            periodos_turno.nombre as turno_nombre,
				            periodos_llamado.nombre as llamado_nombre,
				            periodos_comision.nombre as periodo_lectivo_nombre
				FROM		sga_insc_auto_examen
							JOIN sga_llamados_mesa ON sga_insc_auto_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
							JOIN sga_llamados_turno ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
							JOIN sga_periodos as periodos_llamado ON sga_llamados_turno.periodo = periodos_llamado.periodo
							JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
							JOIN sga_turnos_examen ON sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
							JOIN sga_periodos as periodos_turno ON sga_turnos_examen.periodo = periodos_turno.periodo
							JOIN sga_elementos ON sga_mesas_examen.elemento = sga_elementos.elemento
							JOIN sga_ubicaciones ON sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_comisiones ON sga_insc_auto_examen.comision = sga_comisiones.comision
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos as periodos_comision ON sga_periodos_lectivos.periodo = periodos_comision.periodo
				WHERE		sga_llamados_mesa.fecha 			>= '" . guarani_fecha::get_hoy() . "'	AND
							sga_llamados_mesa.estado			= '" . mesa_de_examen::mesa_estado_activo . "' AND
							EXISTS 
							(	SELECT	sga_actas.id_acta 
								FROM	sga_actas
								WHERE	sga_actas.comision	= sga_comisiones.comision			AND
										sga_actas.estado	= '" . acta::estado_cerrada . "'	AND
										sga_actas.origen	= '" . acta::origen_regulares . "'
							)
			            	$where
				ORDER BY	sga_llamados_mesa.fecha
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los últimos detalles (originales o rectificados) para los alumnos de Actas de Regulares cerradas de la 
	 * Comisión dada, que estén aptos para la inscripción en el Llamado Mesa dado, es decir que: hayan aprobado la 
	 * cursada, su nota esta activa (no invalidada), en el caso de que exista un acta de promoción que el alumno no 
	 * este promocionado, y que el alumno no este ya inscripto en el llamado mesa dado. 
	 *
	 * @param integer	$comision	Filtro sobre las relaciones a recuperar, aplicados a las mesas, llamados o comisiones.
	 * @param integer	$llamado	Filtro sobre las relaciones a recuperar, aplicados a las mesas, llamados o comisiones.
	 * @param str 		$where		Filtro sobre las relaciones a recuperar, aplicados a las mesas, llamados o comisiones.
	 */
	function get_detalles_alumnos_comision_para_inscripcion_llamado($comision, $llamado, $where = '')
	{
		$comision = toba::db()->quote($comision);
		$llamado = toba::db()->quote($llamado);

		if ($where) {
			$where = ' AND ' . $where;
		}

		// No es necesario chequear que el estado de las actas sea Cerrado porque ya se verifica en la vista.
		// Sí debe controlarse el estado de los detalles del acta para ver que no estén invalidados.
		// No recupera el alumno si ya aprobo la actividad por examen o por promocion.
		// No recupera el alumno si ya esta inscripto a examen en ese llamado de la mesa de examen.

		$sql = "SELECT	vw_actas.comision,
						vw_actas.id_acta,
						vw_actas.id_acta_original,
						vw_actas.alumno,
						sga_alumnos.persona,
						sga_alumnos.propuesta,
						sga_alumnos.plan_version
				FROM	vw_actas
						JOIN sga_alumnos ON vw_actas.alumno = sga_alumnos.alumno
						JOIN sga_comisiones ON vw_actas.comision = sga_comisiones.comision
				WHERE	vw_actas.comision = $comision
						AND	vw_actas.origen = '" . acta::origen_regulares . "' 
						AND vw_actas.resultado = '" . escalas_notas::resultado_aprobado . "' 
						AND vw_actas.estado = '" . acta::detalle_estado_activo . "' 
						AND sga_alumnos.calidad = '" . alumno::calidad_activo . "' 
						/* 
						-- Que el alumno no tenga aprobada la actividad
						AND NOT EXISTS (SELECT	1
										FROM	vw_hist_academica_basica
										WHERE	vw_hist_academica_basica.alumno = sga_alumnos.alumno 
												AND vw_hist_academica_basica.elemento  = sga_comisiones.elemento 
												AND vw_hist_academica_basica.resultado = '" . escalas_notas::resultado_aprobado . "') 
						*/								
						-- Que el alumno no este inscripto en la mesa de examen al que se lo va a inscribir automáticamente.
						AND sga_alumnos.alumno NOT IN (	SELECT 	sga_insc_examen.alumno 
														FROM 	sga_insc_examen
														WHERE 	sga_insc_examen.llamado_mesa = $llamado)
			            $where
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Inscripciones automaticas a examen. 
	 * Se recuperan los alumnos que desaprobaron algun examen de una mesa anterior a la mesa que se pasa por parámetro
	 * y que esta asociada con la comision.
	 *
	 * @param integer	$comision		Comision de donde se toman los alumnos para inscribir a examen
	 * @param integer	$llamado_mesa	Mesa de Examen en el llamado
	 */
	function get_alumnos_desaprobados_inscripcion_automatica_examen($comision, $llamado_mesa, $alumnos_not_in)
	{
		$comision = toba::db()->quote($comision);
		$llamado_mesa = toba::db()->quote($llamado_mesa);

		// Recupera la lista de alumnos que desaprobaron un examen de una mesa anterior que estaba asociada a la comision
		$sql = "SELECT	sga_actas_detalle.alumno,
						sga_alumnos.persona,
						sga_alumnos.propuesta,
						sga_alumnos.plan_version
				FROM	sga_insc_auto_examen
						JOIN vw_mesas_examen ON sga_insc_auto_examen.llamado_mesa = vw_mesas_examen.llamado_mesa
						JOIN sga_actas ON sga_insc_auto_examen.llamado_mesa = sga_actas.llamado_mesa
						JOIN sga_actas_detalle ON sga_actas.id_acta = sga_actas_detalle.id_acta
						JOIN sga_alumnos ON sga_actas_detalle.alumno = sga_alumnos.alumno
				WHERE	sga_insc_auto_examen.comision = $comision
						AND " . guarani_sql::armar_condicion_not_in($alumnos_not_in, 'sga_alumnos', 'alumno') . "
						AND   vw_mesas_examen.llamado_mesa  <> $llamado_mesa
						AND   vw_mesas_examen.mesa_examen_fecha < (SELECT a.mesa_examen_fecha FROM vw_mesas_examen as a WHERE a.llamado_mesa = $llamado_mesa)
						AND	sga_actas.estado               = '" . acta::estado_cerrada . "'
						AND	sga_actas_detalle.estado       = '" . acta::detalle_estado_activo . "'
						AND	sga_actas_detalle.rectificado  = '" . comunes::no . "'
						AND	sga_actas_detalle.resultado    = '" . escalas_notas::resultado_reprobado . "'
		";

		return guarani_db::consultar($sql);
	}

	function get_listado_motivos_rechazo_inscripcion_examen_activos()
	{
		$where = "(sga_inscripciones_motivos_rechazo.alcance = 'Examenes' OR sga_inscripciones_motivos_rechazo.alcance = 'Todos')";
		return guarani::co('co_inscripciones')->get_listado_motivos_rechazo_inscripcion_activos($where);
	}

	function get_insc_examen_vigentes_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT
					sga_insc_examen.inscripcion,
					sga_insc_examen.nro_transaccion,
					sga_insc_examen.fecha_inscripcion,
					'(' || vw_actividades_plan.codigo || ') ' || vw_actividades_plan.nombre AS actividad_nombre,
					sga_mesas_examen.nombre AS mesa_nombre,
					sga_periodos_llamado.nombre AS llamado_nombre,
					sga_periodos_llamado.anio_academico,
					sga_periodos_turno.nombre as turno_nombre,
					sga_llamados_mesa.fecha AS fecha_examen,
					to_char(CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), '" . comunes::formato_fecha_hora_visual_sin_segundos . "') AS fecha_examen_visual,
					sga_instancias.nombre AS instancia_nombre,
					sga_catedras.nombre AS catedra_nombre,
					sga_ubicaciones.nombre AS ubicacion_nombre,
					
					f_modalidad_cursada_examen(sga_mesas_examen.mesa_examen) AS modalidades_nombre,
					
					vw_personas.persona,
					vw_personas.apellido_nombres AS apellido_y_nombre,
					vw_personas.tipo_nro_documento AS nro_identificacion,
					sga_alumnos.alumno,
					sga_alumnos.legajo,
					sga_alumnos.propuesta,
					sga_alumnos.plan_version,
					sga_propuestas.nombre AS propuesta_nombre,
					sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
					sga_inscripciones_estados.nombre as estado_nombre,
					vw_actividades_plan.elemento

				FROM
					sga_insc_examen
					JOIN sga_inscripciones_estados ON sga_insc_examen.estado = sga_inscripciones_estados.estado
					JOIN sga_instancias ON sga_insc_examen.instancia = sga_instancias.instancia
					JOIN sga_alumnos ON sga_insc_examen.alumno = sga_alumnos.alumno
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
					JOIN sga_llamados_mesa ON sga_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
					JOIN sga_llamados_turno ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
					JOIN sga_turnos_examen ON sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
					JOIN sga_periodos as sga_periodos_turno ON sga_turnos_examen.periodo = sga_periodos_turno.periodo
					JOIN sga_periodos as sga_periodos_llamado ON sga_llamados_turno.periodo = sga_periodos_llamado.periodo
					JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
					JOIN vw_actividades_plan ON (sga_mesas_examen.elemento = vw_actividades_plan.elemento AND sga_insc_examen.plan_version = vw_actividades_plan.plan_version)
					JOIN sga_ubicaciones ON sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
					LEFT JOIN sga_catedras ON sga_mesas_examen.catedra = sga_catedras.catedra

				WHERE
				    CAST(sga_llamados_mesa.fecha as timestamptz) + CAST(sga_llamados_mesa.hora_inicio as time) >= CURRENT_TIMESTAMP
					AND sga_alumnos.alumno = $alumno 
		";

		return guarani_db::consultar($sql);
	}

	function get_listado_rest($where='', $solo_vigentes=true)
	{
		if ($where) {
			$where = "WHERE $where";
		}
		if ($solo_vigentes) {
			$where .= " AND CAST(sga_llamados_mesa.fecha as timestamptz) + CAST(sga_llamados_mesa.hora_inicio as time) >= CURRENT_TIMESTAMP";
		}
		
		$orden_tipo_email = persona::orden_tipo_email();
		$orden_tipo_telefono = persona::orden_tipo_telefono();
		
		$sql = " -- Inscripciones aceptadas y pendientes.
				SELECT	insc_examen.inscripcion,
						insc_examen.nro_transaccion,
						insc_examen.fecha_inscripcion,
						insc_examen.llamado_mesa,
						vw_personas.apellido,
						vw_personas.nombres,
						vw_personas.sexo as genero,
						vw_personas.nro_documento,
						vw_personas.tipo_documento,
						vw_personas.pais_documento,
						(SELECT		mdp_personas_contactos.email
						FROM		mdp_personas_contactos
						WHERE		mdp_personas_contactos.persona = vw_personas.persona AND
									mdp_personas_contactos.contacto_tipo IN ('".persona::mail_primario."','".persona::mail_secundario."','".persona::mail_institucional."') AND
									mdp_personas_contactos.email IS NOT NULL
						ORDER BY	$orden_tipo_email
						LIMIT		1) as email,
						(SELECT		mdp_personas_contactos.telefono_codigo_area || '|' || mdp_personas_contactos.telefono_numero
						FROM		mdp_personas_contactos
						WHERE		mdp_personas_contactos.persona = vw_personas.persona AND
									mdp_personas_contactos.contacto_tipo IN ('".persona::tel_celular."','".persona::tel_familiar."','".persona::tel_fijo."','".persona::tel_laboral."') AND
									mdp_personas_contactos.telefono_codigo_area IS NOT NULL AND
									mdp_personas_contactos.telefono_numero IS NOT NULL
						ORDER BY	$orden_tipo_telefono
						LIMIT		1) as telefono,
						sga_inscripciones_estados.nombre as nombre_estado
				FROM	vw_insc_examen as insc_examen
						JOIN sga_llamados_mesa ON insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
						JOIN sga_inscripciones_estados ON insc_examen.estado = sga_inscripciones_estados.estado
						JOIN sga_alumnos ON insc_examen.alumno = sga_alumnos.alumno
						JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
				$where
				UNION 
				-- Inscripciones rechazadas y dadas de baja.
				SELECT	insc_examen.inscripcion,
						insc_examen.nro_transaccion,
						insc_examen.fecha_inscripcion,
						insc_examen.llamado_mesa,
						vw_personas.apellido,
						vw_personas.nombres,
						vw_personas.sexo as genero,
						vw_personas.nro_documento,
						vw_personas.tipo_documento,
						vw_personas.pais_documento,
						(SELECT		mdp_personas_contactos.email
						FROM		mdp_personas_contactos
						WHERE		mdp_personas_contactos.persona = vw_personas.persona AND
									mdp_personas_contactos.contacto_tipo IN ('".persona::mail_primario."','".persona::mail_secundario."','".persona::mail_institucional."') AND
									mdp_personas_contactos.email IS NOT NULL
						ORDER BY	$orden_tipo_email
						LIMIT		1) as email,
						(SELECT		mdp_personas_contactos.telefono_codigo_area || '|' || mdp_personas_contactos.telefono_numero
						FROM		mdp_personas_contactos
						WHERE		mdp_personas_contactos.persona = vw_personas.persona AND
									mdp_personas_contactos.contacto_tipo IN ('".persona::tel_celular."','".persona::tel_familiar."','".persona::tel_fijo."','".persona::tel_laboral."') AND
									mdp_personas_contactos.telefono_codigo_area IS NOT NULL AND
									mdp_personas_contactos.telefono_numero IS NOT NULL
						ORDER BY	$orden_tipo_telefono
						LIMIT		1) as telefono,
						CASE insc_examen.operacion
							WHEN '".inscripcion_examen::operacion_baja."' THEN 'Dada de baja'
							WHEN '".inscripcion_examen::operacion_rechazo."' THEN 'Rechazada'
						END	as nombre_estado
				FROM	vw_insc_examen_log as insc_examen
						JOIN sga_llamados_mesa ON insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
						JOIN sga_alumnos ON insc_examen.alumno = sga_alumnos.alumno
						JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
				$where
		";
		return guarani_db::consultar($sql);
	}
}
