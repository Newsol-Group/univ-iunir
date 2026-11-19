<?php

namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;

class reasignar_optativa
{
	/**
	 * parametros: plan_version, alumno
	 * cache: memoria
	 * filas: n
	 */
	function lista_optativas_con_genericas($parametros)
	{

		$elemento_estado_activo = kernel::db()->quote(\elemento::estado_activo);
		$si = kernel::db()->quote(\comunes::si);
		$no = kernel::db()->quote(\comunes::no);
		$resultado_aprobado = kernel::db()->quote(\escalas_notas::resultado_aprobado);
		$aprobada = kernel::db()->quote(kernel::traductor()->trans('reasignar_optativa.aprobada'));
		$regularizada = kernel::db()->quote(kernel::traductor()->trans('reasignar_optativa.regularizada'));

		// Devuelve las actividades optativas que se comparten en dos o mas genéricas
		$sql = "
			SELECT	vw_optativas_plan.elemento as optativa_elemento,
					vw_optativas_plan.codigo as optativa_codigo,
					vw_optativas_plan.nombre as optativa_nombre,
					vw_optativas_plan.nombre_abreviado as optativa_nombre_abreviado,
					CASE 
					  WHEN (SELECT COUNT(1) FROM vw_hist_academica_basica
					         WHERE alumno = {$parametros['alumno']} 
							   AND elemento = vw_optativas_plan.elemento 
							   AND resultado = $resultado_aprobado
							) > 0 THEN $aprobada
					  WHEN (SELECT COUNT(1) FROM vw_regularidades_basica
						     WHERE alumno     = {$parametros['alumno']} 
						       AND elemento	 = vw_optativas_plan.elemento 
							   AND resultado	 = $resultado_aprobado
							   AND es_vigente = 1
							) > 0 THEN $regularizada
					     ELSE ''
					END as optativa_estado,
					vw_optativas_plan.elemento_generica as generica_elemento,
					vw_optativas_plan.codigo_generica as generica_codigo,
					vw_optativas_plan.nombre_generica as generica_nombre,
					CASE 
					  WHEN (sga_alumnos_optativas.optativa IS NOT NULL) THEN $si
					  ELSE $no 
					END	as generica_asignada,
					 vw_optativas_plan.estado as estado

			FROM vw_optativas_plan
			LEFT JOIN sga_alumnos_optativas	ON (sga_alumnos_optativas.alumno		= {$parametros['alumno']}  AND
												sga_alumnos_optativas.plan_version	= vw_optativas_plan.plan_version AND 
												sga_alumnos_optativas.optativa		= vw_optativas_plan.elemento AND 
												sga_alumnos_optativas.generica		= vw_optativas_plan.elemento_generica)

			WHERE vw_optativas_plan.plan_version = {$parametros['plan_version']}
			  AND f_optativa_es_compartida(vw_optativas_plan.elemento, {$parametros['plan_version']}, NULL, NULL) = 1
			ORDER BY	optativa_nombre, optativa_elemento,
						generica_nombre, generica_elemento
        ";

		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: alumno, plan_version, optativa
	 * cache: memoria
	 * filas: 1
	 */
	function optativa_tiene_generica_asignada($parametros)
	{
		// Verifica si existe la opativa asignada a una generica en la version del plan de estudios actual del alumno.
		// La cache se limpia cuando se reasigna la optativa a alguna generica.
		$sql = "
			SELECT EXISTS(
				SELECT 1 
				FROM sga_alumnos_optativas 
				WHERE alumno		= {$parametros['alumno']} 
				  AND plan_version	= {$parametros['plan_version']} 
				  AND optativa		= {$parametros['optativa']}
			) AS asignada
        ";


		$result = kernel::db()->consultar_fila($sql);
		return ($result['asignada'] == 't') ? true : false;
	}

	/**
	 * parametros: alumno, plan_version, optativa, generica
	 * no_quote: alumno, plan_version, optativa, generica
	 * cache: no
	 * filas: 1
	 */
	function agregar_asignacion($parametros)
	{
		return \guarani::act('act_alumnos')->insertar_optativa($parametros['alumno'], $parametros['plan_version'], $parametros['optativa'], $parametros['generica']);
	}

	/**
	 * parametros: alumno, plan_version, optativa, generica
	 * no_quote: alumno, plan_version, optativa, generica
	 * cache: no
	 * filas: 1
	 */
	function modificar_asignacion($parametros)
	{
		return \guarani::act('act_alumnos')->actualizar_optativa($parametros['alumno'], $parametros['plan_version'], $parametros['optativa'], $parametros['generica']);
	}

}