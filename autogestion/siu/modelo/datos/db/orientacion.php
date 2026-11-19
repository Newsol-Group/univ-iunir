<?php
namespace siu\modelo\datos\db;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;

class orientacion
{
	/**
	 * Lista de orientaciones disponibles segun plan/certificado para que el alumno pueda elegir.
	 * No recupera la orientacion que el alumno ya tenga seleccionada.
	 * parametros: alumno, plan, plan_version, certificado
	 * cache: no
	 * filas: n
	 */
	function orientaciones_disponibles_x_certificado($parametros)
	{
		// Recupera las orientaciones disponibles que el alumno puede elegir segun el certificado seleccionado
		$sql = "SELECT 
				vw_modulos_plan.elemento as orientacion,
				vw_modulos_plan.nombre as orientacion_nombre
			FROM 
				get_orientaciones_certificado ({$parametros['certificado']} , {$parametros['plan_version']}) as orie,
				vw_modulos_plan
			WHERE 
				vw_modulos_plan.plan_version  = {$parametros['plan_version']}
			AND vw_modulos_plan.elemento_revision = orie.elemento_revision
			AND vw_modulos_plan.elemento NOT IN (SELECT ao.orientacion 
								FROM sga_alumnos_orient as ao
								WHERE ao.alumno = {$parametros['alumno']}
								  AND ao.plan   = {$parametros['plan']}
								 )
			";
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		
		return $datos;
	}
	
	/**
	 * parametros: alumno, plan, orientacion, observaciones
	 * param_null: observaciones
	 * cache: no
	 * filas: n
	 */
	function registrar_seleccion_orientacion($parametros)
	{
		// Registra la seleccion de una orientación en el plan de estudios
		$sql = "INSERT INTO sga_alumnos_orient (alumno, plan, orientacion)
			VALUES ({$parametros['alumno']}, {$parametros['plan']},{$parametros['orientacion']});
			";
				
		return kernel::db()->consultar($sql, db::FETCH_NUM);
	}

	/**
	 * parametros: alumno, plan, orientacion, observaciones
	 * param_null: observaciones
	 * cache: no
	 * filas: n
	 */
	function eliminar_seleccion_orientacion($parametros)
	{
		
		// Registra la eliminación de una orientacion del plan de estudios
		$sql = "DELETE FROM sga_alumnos_orient 
			WHERE alumno = {$parametros['alumno']}
			  AND plan   = {$parametros['plan']}
			  AND orientacion = {$parametros['orientacion']}"
			;

		return kernel::db()->consultar($sql, db::FETCH_NUM);
	}

	
	/**
	 * parametros: alumno, certificado
	 * cache: no
	 * filas: n
	 */
	function orientacion_actual($parametros)
	{	
		// Recupera la orientacion actual seleccionada por el alumno. 
		// La orientacion del título/certificado seleccionado y del plan actual del alumno
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		$sql = "SELECT 
				sga_alumnos_orient.certificado as certificado,
				sga_alumnos_orient.plan as plan, 
				vw_planes.plan_codigo as plan_codigo,
				sga_alumnos_orient.orientacion as orientacion,
				vw_modulos_plan.nombre as orientacion_nombre,
				to_char(sga_alumnos_orient.fecha, $formato_fecha_visual) as fecha
			FROM 
				sga_alumnos,
				vw_planes,
				sga_alumnos_orient,
				vw_modulos_plan  
			WHERE sga_alumnos.alumno = {$parametros['alumno']} 
			AND vw_planes.plan_version = sga_alumnos.plan_version
			AND sga_alumnos_orient.alumno = sga_alumnos.alumno
			AND sga_alumnos_orient.plan   = vw_planes.plan
			AND vw_modulos_plan.plan_version = sga_alumnos.plan_version
			AND vw_modulos_plan.elemento     = sga_alumnos_orient.orientacion
			AND vw_modulos_plan.elemento_revision IN (SELECT elemento_revision FROM get_orientaciones_certificado ({$parametros['certificado']} , sga_alumnos.plan_version))
			";
		
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		return $datos;
	}
	
	
	
	/**
	 * parametros: alumno
	 * cache: no
	 * filas: n
	 */
	function certificados_con_orientaciones($parametros)
	{		
		// Lista los titulos del plan de estudios del alumno que tienen orientaciones.
		// No lista los titulos que el alumno ya haya iniciado el trámite de egreso.
		// Muestra la orientacion actual elegida por el alumno.
		$sql = "
			SELECT
				sga_planes_certificados.certificado,
				sga_certificados.nombre as certificado_nombre,
				sga_certificados.titulo_nivel as nivel,
				(SELECT vw_modulos_plan.nombre 
				   FROM sga_alumnos_orient,
				        vw_modulos_plan
				 WHERE sga_alumnos_orient.alumno = sga_alumnos.alumno
				   AND sga_alumnos_orient.plan   = vw_planes.plan
				   AND vw_modulos_plan.plan_version = sga_alumnos.plan_version
				   AND vw_modulos_plan.elemento     = sga_alumnos_orient.orientacion
				   AND vw_modulos_plan.elemento_revision IN (SELECT elemento_revision FROM get_orientaciones_certificado (sga_planes_certificados.certificado, sga_alumnos.plan_version))
				 LIMIT 1 
				) as orientacion_elegida

			FROM
				sga_alumnos,
				vw_planes,
				sga_planes_certificados, 
				sga_certificados

			WHERE sga_alumnos.alumno = {$parametros['alumno']} 
			  AND vw_planes.plan_version = sga_alumnos.plan_version
			  AND sga_planes_certificados.plan_version = sga_alumnos.plan_version
			  AND sga_certificados.certificado = sga_planes_certificados.certificado
			  AND EXISTS (SELECT elemento_revision FROM get_orientaciones_certificado (sga_planes_certificados.certificado, sga_alumnos.plan_version))
			  AND NOT EXISTS (SELECT 1 FROM sga_certificados_otorg WHERE persona = sga_alumnos.persona AND certificado = sga_planes_certificados.certificado AND anulado = 0)
			";
		
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		return $datos;
	}
}
?>