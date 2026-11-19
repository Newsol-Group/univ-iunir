<?php
/**
 * 
 * @package Propuestas Formativas
 */
class co_propuestas_relaciones
{
	function get_listado($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = "SELECT		sga_propuestas_relacion.anio_academico,
							sga_propuestas_relacion_grupo.relacion_grupo,
							sga_propuestas_relacion_plan.relacion_plan,
							sga_propuestas.nombre || ' - ' || sga_planes.nombre as relacion_plan_nombre
				FROM		sga_propuestas_relacion
							JOIN sga_propuestas_relacion_grupo ON sga_propuestas_relacion.relacion = sga_propuestas_relacion_grupo.relacion
							JOIN sga_propuestas_relacion_plan ON sga_propuestas_relacion_grupo.relacion_grupo = sga_propuestas_relacion_plan.relacion_grupo
							JOIN sga_planes ON sga_propuestas_relacion_plan.plan = sga_planes.plan
							JOIN sga_propuestas ON sga_planes.propuesta = sga_propuestas.propuesta
				$where
				ORDER BY	sga_propuestas_relacion_grupo.relacion_grupo,
							sga_propuestas.nombre,
							sga_planes.nombre
		";
		$datos = guarani_db::consultar($sql);
		$grupo_anterior = 0;
		$opcion = 0;
		foreach ($datos as $clave => $valor) {
			if ($valor['relacion_grupo'] != $grupo_anterior) {
				$grupo_anterior = $valor['relacion_grupo'];
				$opcion++;
			}
			$datos[$clave]['relacion_grupo_nombre'] = "Opción $opcion";
		}
		return $datos;
	}

	function get_listado_propuestas($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_propuestas.propuesta,
							sga_propuestas.nombre,
							sga_propuestas_tipos.descripcion as tipo_descripcion,
							(	SELECT	COUNT(DISTINCT sga_propuestas_relacion_grupo.relacion_grupo)
								FROM	sga_propuestas_relacion_grupo,
										sga_propuestas_relacion
								WHERE	sga_propuestas_relacion_grupo.relacion = sga_propuestas_relacion.relacion AND
										sga_propuestas_relacion.propuesta = sga_propuestas.propuesta AND
										sga_propuestas_relacion.plan IS NULL) as vinculaciones_propuesta,
							(	SELECT	COUNT(DISTINCT sga_propuestas_relacion_grupo.relacion_grupo)
								FROM	sga_propuestas_relacion_grupo,
										sga_propuestas_relacion
								WHERE	sga_propuestas_relacion_grupo.relacion = sga_propuestas_relacion.relacion AND
										sga_propuestas_relacion.propuesta = sga_propuestas.propuesta AND
										sga_propuestas_relacion.plan IS NOT NULL) as vinculaciones_plan
				FROM		sga_propuestas,
							sga_propuestas_tipos
				WHERE		sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
							$where
				ORDER BY	sga_propuestas.nombre
		";
		$datos = guarani_db::consultar($sql);
		foreach ($datos as $clave => $valor) {
			$vinculaciones = $valor['vinculaciones_propuesta'] + $valor['vinculaciones_plan'];
			$datos[$clave]['vinculaciones'] = "$vinculaciones ({$valor['vinculaciones_propuesta']} generales - {$valor['vinculaciones_plan']} por plan)";
		}
		return $datos;
	}

	function get_listado_planes($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_planes.plan,
							sga_planes.nombre as plan_nombre,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas_tipos.descripcion as propuesta_tipo_desc
				FROM		sga_planes,
							sga_propuestas,
							sga_propuestas_tipos
				WHERE		sga_planes.propuesta = sga_propuestas.propuesta AND
							sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo AND
							sga_planes.estado <> '".planes::estado_baja."'
							$where
				ORDER BY	sga_propuestas.nombre,
							sga_planes.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_listado_planes_propuesta($propuesta, $where='')
	{
		$propuesta = toba::db()->quote($propuesta);
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_planes.plan,
							sga_planes.nombre,
							(	SELECT	COUNT(DISTINCT sga_propuestas_relacion_grupo.relacion_grupo)
								FROM	sga_propuestas_relacion_grupo,
										sga_propuestas_relacion
								WHERE	sga_propuestas_relacion_grupo.relacion = sga_propuestas_relacion.relacion AND
										sga_propuestas_relacion.propuesta = sga_planes.propuesta AND
										sga_propuestas_relacion.plan = sga_planes.plan) as vinculaciones
				FROM		sga_planes
				WHERE		sga_planes.propuesta = $propuesta AND
							sga_planes.estado <> '".planes::estado_baja."'
							$where
				ORDER BY	sga_planes.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_propuestas_relacion_tipos()
	{
		return array(	array('valor' => propuesta::relacion_tipo_propuestas, 'descr' => 'Vinculación entre Propuestas'),
						array('valor' => propuesta::relacion_tipo_planes, 'descr' => 'Vinculación entre Propuestas - Planes'));
	}

	function get_cant_planes_por_relacion($relacion_plan)
	{
		$relacion_plan = toba::db()->quote($relacion_plan);
		$sql = "SELECT	count(sga_propuestas_relacion_plan.relacion_plan) as cant
				FROM	sga_propuestas_relacion_plan
				WHERE	sga_propuestas_relacion_plan.relacion_grupo = (	SELECT	rp.relacion_grupo
																		FROM	sga_propuestas_relacion_plan as rp
																		WHERE	rp.relacion_plan = $relacion_plan)	
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}

	function get_cant_relaciones_por_propuesta_plan($propuesta, $plan=null)
	{
		$where = ' AND sga_propuestas_relacion.propuesta = ' . toba::db()->quote($propuesta);
		$where .= empty($plan) ? ' AND sga_propuestas_relacion.plan IS NULL' : ' AND sga_propuestas_relacion.plan = ' . toba::db()->quote($plan);

		$sql = "SELECT	count(sga_propuestas_relacion.relacion) as cant
				FROM	sga_propuestas_relacion,
						sga_propuestas_relacion_grupo,
						sga_propuestas_relacion_plan
				WHERE	sga_propuestas_relacion.relacion = sga_propuestas_relacion_grupo.relacion AND
						sga_propuestas_relacion_grupo.relacion_grupo = sga_propuestas_relacion_plan.relacion_grupo
						$where
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}

	function get_cant_grupos_por_relacion($relacion)
	{
		$relacion = toba::db()->quote($relacion);
		$sql = "SELECT	count(sga_propuestas_relacion_grupo.relacion) as cant
				FROM	sga_propuestas_relacion_grupo
				WHERE	sga_propuestas_relacion_grupo.relacion = $relacion
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}

	/**
	 * Retorna la relación entre propuestas que le corresponde a un alumno según el año de ingreso a la propuesta.
	 * 
	 * @param integer $alumno
	 * @return integer
	 */
	function get_relacion_por_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT get_relacion_propuesta($alumno) as relacion";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['relacion'];
	}

	function get_relaciones_por_anio_academico_propuesta_plan($anio_academico, $propuesta, $plan)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$propuesta = toba::db()->quote($propuesta);
		$plan = toba::db()->quote($plan);
		$sql = "SELECT	DISTINCT sga_planes.propuesta,
						sga_planes.plan
				FROM	sga_propuestas_relacion,
						sga_propuestas_relacion_grupo,
						sga_propuestas_relacion_plan,
						sga_planes
				WHERE	sga_propuestas_relacion.relacion = sga_propuestas_relacion_grupo.relacion AND
						sga_propuestas_relacion_grupo.relacion_grupo = sga_propuestas_relacion_plan.relacion_grupo AND
						sga_propuestas_relacion_plan.plan = sga_planes.plan AND
						sga_propuestas_relacion.propuesta = $propuesta AND
						(sga_propuestas_relacion.plan IS NULL OR sga_propuestas_relacion.plan = $plan) AND
						sga_propuestas_relacion.anio_academico = (	SELECT	MAX(propuesta_relacion.anio_academico)
																	FROM	sga_propuestas_relacion as propuesta_relacion
																	WHERE	propuesta_relacion.propuesta = $propuesta AND
																			(propuesta_relacion.plan IS NULL OR propuesta_relacion.plan = $plan) AND
																			propuesta_relacion.anio_academico <= $anio_academico) AND
						sga_propuestas_relacion_grupo.inscripcion_automatica = '".comunes::si."' AND
						sga_planes.estado <> '".planes::estado_baja."'
		";
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Retorna la relación entre propuestas para una propuesta y version de plan de estudios. Relacion que haya sido definida en un 
	 * año académico igual o anterior al del ingreso del alumno
	 * Solo retorna una relacion entre propuestas. ???
	 * 
	 * @param integer $anio_academico	Año academico de la inscripción en la propuesta del alumno 
	 * @param integer $propuesta		Propuesta del alumno
	 * @param integer $plan_version		Version de plan actual del alumno.
	 * @return integer					Relación entre la propuesta del alumno y propuesta relacionada.
	 */
	function get_relacion_por_anio_academico_propuesta_plan_version($anio_academico, $propuesta, $plan_version)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$propuesta = toba::db()->quote($propuesta);
		$plan_version = toba::db()->quote($plan_version);
		$sql = "SELECT		sga_propuestas_relacion.relacion 
				FROM		sga_propuestas_relacion
				WHERE		sga_propuestas_relacion.propuesta = $propuesta AND
							sga_propuestas_relacion.anio_academico <= $anio_academico AND
							COALESCE(sga_propuestas_relacion.anio_academico_hasta, $anio_academico) >= $anio_academico AND
							(sga_propuestas_relacion.plan IS NULL OR sga_propuestas_relacion.plan = (SELECT plan FROM sga_planes_versiones WHERE plan_version = $plan_version))
				ORDER BY	sga_propuestas_relacion.anio_academico DESC,
							sga_propuestas_relacion.plan NULLS LAST
				LIMIT		1
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['relacion'] ?? '');
	}

	/**
	 * Retorna la lista de grupos (u opciones) asociados a una relación entre propuestas.
	 *
	 * @param integer $relacion
	 * @return array
	 */
	function get_grupos_por_relacion($relacion)
	{
		$relacion = toba::db()->quote($relacion);
		$sql = "SELECT		sga_propuestas_relacion_grupo.relacion_grupo
				FROM		sga_propuestas_relacion_grupo
				WHERE		sga_propuestas_relacion_grupo.relacion = $relacion
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de planes asociados a un grupo.
	 *
	 * @param integer $relacion_grupo
	 * @return array
	 */
	function get_planes_por_grupo($relacion_grupo, $propuesta_tipo=null)
	{
		$relacion_grupo = toba::db()->quote($relacion_grupo);
		$where = '';
		if (! is_null($propuesta_tipo)) {
			$propuesta_tipo = toba::db()->quote($propuesta_tipo);
			$where = " AND sga_propuestas.propuesta_tipo = $propuesta_tipo";
		}
		$sql = "SELECT		sga_propuestas_relacion_plan.plan,
							sga_planes.propuesta
				FROM		sga_propuestas_relacion_plan
							JOIN sga_planes ON sga_propuestas_relacion_plan.plan = sga_planes.plan
							JOIN sga_propuestas ON sga_planes.propuesta = sga_propuestas.propuesta
				WHERE		sga_propuestas_relacion_plan.relacion_grupo = $relacion_grupo
							$where
		";
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Retorna la concatenación de nombres de planes para todos los grupos asociados a una relación.
	 * 
	 * @param integer $relacion
	 * @return string
	 */
	function get_nombre_grupos_por_relacion($relacion)
	{
		$relacion = toba::db()->quote($relacion);
		$sql = "SELECT get_nombre_grupos_relacion($relacion) as nombres";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['nombres'];
	}
}