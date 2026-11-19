<?php
/**
 *
 * @package Examenes
 * @subpackage Inscripciones
 */
class co_inscripciones_examenes_nsg extends co_inscripciones_examenes
{
	// Newsol - Se agregó la fecha de la mesa de examen
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
					to_char(CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), '" . comunes::formato_fecha_hora_visual_sin_segundos . "') AS fecha_examen_visual

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
					to_char(CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), '" . comunes::formato_fecha_hora_visual_sin_segundos . "') AS fecha_examen_visual

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

}
?>