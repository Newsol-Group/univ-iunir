<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Actualizaciones
 */
class co_reinscripciones
{
	function get_listado_reporte($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT 		' (' || vw_perfil_de_datos_propuestas.codigo || ') ' || vw_perfil_de_datos_propuestas.nombre as propuesta_datos,
							vw_personas.tipo_nro_documento as persona_documento,
							to_char(sga_reinscripciones.fecha_reinscripcion,'".comunes::formato_fecha_visual."') as fecha_reinscripcion_formateada,
							sga_reinscripciones.nro_transaccion,
							sga_reinscripciones.anio_academico,
							sga_reinscripciones.anio_academico as anio_academico_descr,
							sga_alumnos.propuesta,
							vw_personas.apellido,
							vw_personas.nombres,
							sga_alumnos.legajo
				FROM 		sga_reinscripciones,
							vw_personas,
							sga_alumnos,
							vw_perfil_de_datos_propuestas
				WHERE		sga_reinscripciones.alumno = sga_alumnos.alumno AND
							sga_alumnos.persona = vw_personas.persona AND
							sga_alumnos.propuesta = vw_perfil_de_datos_propuestas.propuesta
							$where
				ORDER BY 	anio_academico DESC,
							vw_perfil_de_datos_propuestas.nombre,
							apellido,
							nombres
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de alumnos incluyendo la columna 'reinscripto' para el año académico dado.
	 *
	 * @param float $anio_academico
	 * @param string $where
	 * @return array
	 */
	function get_alumnos_reinscripcion_anio_academico($anio_academico, $where='')
	{
		$anio_academico = toba::db()->quote($anio_academico);
		if (! empty($where)) {
			$where = "AND $where";
		}
		$sql = "SELECT		sga_alumnos.alumno,
							sga_alumnos.persona,
							sga_alumnos.plan_version,
							sga_alumnos.modalidad,
							sga_alumnos.ubicacion,
							vw_personas.apellido_nombres AS apellido_y_nombre,
							vw_personas.tipo_nro_documento AS documento_principal,
							vw_perfil_de_datos_propuestas.propuesta_descripcion,
							vw_perfil_de_datos_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_alumnos.legajo,
							sga_ubicaciones.nombre AS ubicacion_nombre,
							sga_modalidad_cursada.nombre AS modalidad_nombre,
							vw_perfil_de_datos_propuestas.propuesta,
							sga_planes.nombre AS plan_nombre,
							CASE
				        		WHEN sga_reinscripciones.reinscripcion IS NOT NULL THEN '".comunes::si_desc."'
					            ELSE '".comunes::no_desc."'
				    	    END AS reinscripto
				FROM		sga_alumnos
							LEFT OUTER JOIN sga_reinscripciones ON sga_alumnos.alumno = sga_reinscripciones.alumno AND sga_reinscripciones.anio_academico = $anio_academico,
							vw_personas,
							vw_perfil_de_datos_propuestas,
							sga_ubicaciones,
							sga_modalidad_cursada,
							sga_planes_versiones,
							sga_planes
				WHERE		sga_alumnos.persona = vw_personas.persona
				AND			sga_alumnos.propuesta = vw_perfil_de_datos_propuestas.propuesta
				AND			sga_planes_versiones.plan_version = sga_alumnos.plan_version
				AND			sga_planes_versiones.plan = sga_planes.plan
				AND			sga_alumnos.modalidad = sga_modalidad_cursada.modalidad
				AND			sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
				AND			sga_alumnos.calidad = '".alumno::calidad_activo."'
				AND			sga_planes.estado IN ('".planes::estado_activo."','".planes::estado_vigente."')
							$where
				ORDER BY	vw_personas.apellido,
							vw_personas.nombres,
							vw_perfil_de_datos_propuestas.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si existe un período de reinscripción para un año académico, plan-versión, modalidad y ubicación dados.
	 * Períodos de reinscripcion para interfaz Gestion.
	 *
	 * @param float $anio_academico
	 * @param integer $plan_version
	 * @param string $modalidad
	 * @param integer $ubicacion
	 * @return boolean
	 */
	function existe_periodo_reinscripcion($anio_academico, $plan_version, $modalidad, $ubicacion)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$plan_version = toba::db()->quote($plan_version);
		$modalidad = toba::db()->quote($modalidad);
		$ubicacion = toba::db()->quote($ubicacion);
		$sql = "SELECT	COUNT(sga_periodos_inscripcion_propuesta.periodo_inscripcion) as cant
				FROM	sga_periodos_inscripcion_propuesta,
						sga_periodos_inscripcion,
						sga_periodos_inscripcion_fechas,
						sga_periodos_inscripcion_aplanado,
						sga_per_insc_modalidad,
						sga_per_insc_ubicacion
				WHERE	sga_periodos_inscripcion_propuesta.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND
						sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion AND
						sga_periodos_inscripcion_fechas.periodo_insc = sga_periodos_inscripcion_aplanado.periodo_insc AND
						sga_periodos_inscripcion.periodo_inscripcion = sga_per_insc_modalidad.periodo_inscripcion AND
						sga_periodos_inscripcion.periodo_inscripcion = sga_per_insc_ubicacion.periodo_inscripcion AND
						sga_periodos_inscripcion_fechas.habilitado = '".comunes::si."' AND
						sga_periodos_inscripcion_fechas.habilitado_interfaz IN (0,1) AND
						(CURRENT_DATE BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND sga_periodos_inscripcion_fechas.fecha_fin) AND
						sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN (".periodo_de_inscripcion::tipo_reinscripcion.", ".periodo_de_inscripcion::tipo_todos.") AND
						sga_periodos_inscripcion_propuesta.anio_academico = $anio_academico AND
						sga_periodos_inscripcion_aplanado.plan_version = $plan_version AND
						sga_per_insc_modalidad.modalidad = $modalidad AND
						sga_per_insc_ubicacion.ubicacion = $ubicacion 
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}
	
	function get_anio_academico_de_ultimo_periodo_reinscripcion($alumno, $plan_version, $interfaz)
	{
		$sql = "
				SELECT p.periodo_inscripcion, p.anio_academico
				FROM sga_periodos_inscripcion_propuesta as p
				JOIN sga_periodos_inscripcion as i ON i.periodo_inscripcion = p.periodo_inscripcion
				JOIN sga_periodos_inscripcion_fechas as f ON f.periodo_inscripcion = i.periodo_inscripcion
				JOIN sga_periodos_inscripcion_aplanado as a ON a.periodo_insc = f.periodo_insc
				JOIN sga_alumnos ON sga_alumnos.alumno = $alumno
				JOIN sga_propuestas_aspira as pa ON (pa.propuesta = sga_alumnos.propuesta AND pa.persona = sga_alumnos.persona)
				JOIN sga_situacion_aspirante as sa ON sa.situacion_asp = pa.situacion_asp
				WHERE p.periodo_insc_tipo IN ('". periodo_de_inscripcion::tipo_reinscripcion . "', '" . periodo_de_inscripcion::tipo_todos . "')
				  AND a.plan_version = $plan_version
				  AND f.habilitado = '".comunes::si."'
				  AND f.habilitado_interfaz IN (".comunes::interfaz_todas.", $interfaz)
				  AND CURRENT_DATE >= f.fecha_inicio 
				  AND sa.resultado_asp IN ('".propuesta::inscripcion_aceptada."','".propuesta::inscripcion_pendiente."')           -- inscripcion aceptada o pendiente
				  AND p.anio_academico >= pa.anio_academico   -- solo periodos de reinscripcion de años academico mayor o igual al año academico de ingreso del alumno
				ORDER BY p.anio_academico DESC, f.fecha_fin DESC 
				LIMIT 1
				";
		$rs = guarani_db::consultar_fila($sql);
		return (isset($rs['anio_academico']))? $rs['anio_academico'] : null;
	}
}
?>