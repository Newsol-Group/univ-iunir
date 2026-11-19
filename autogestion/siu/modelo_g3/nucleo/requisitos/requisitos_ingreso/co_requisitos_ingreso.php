<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Requisitos de Ingreso
 */
class co_requisitos_ingreso
{
	function get_listado($where='', $orden='')
	{
		if ($where) {
			$where = " AND $where";
		}
		if (!$orden) {
			$orden = 'sga_requisitos.nombre';
		}
		$sql = "SELECT 		sga_requisitos_propuestas.requisito_propuesta,
							sga_requisitos_propuestas.requisito,
							sga_requisitos.nombre as requisito_nombre,
							sga_requisitos_propuestas.tipo,
							sga_requisitos_propuestas.entidad_tipo,
							sga_g3entidades_tipos.nombre as entidad_tipo_nombre,
							COALESCE(subtipo_requisito.entidad_subtipo, subtipo_entidad.entidad_subtipo) as entidad_subtipo,
							COALESCE(subtipo_requisito.nombre, subtipo_entidad.nombre) as entidad_subtipo_nombre,
							sga_requisitos_propuestas_entidades.entidad
				FROM		sga_requisitos_propuestas
							LEFT JOIN sga_requisitos_propuestas_subtipos ON sga_requisitos_propuestas.requisito_propuesta = sga_requisitos_propuestas_subtipos.requisito_propuesta
							LEFT JOIN sga_g3entidades_subtipos as subtipo_requisito ON sga_requisitos_propuestas_subtipos.entidad_subtipo = subtipo_requisito.entidad_subtipo
							LEFT JOIN sga_requisitos_propuestas_entidades ON sga_requisitos_propuestas.requisito_propuesta = sga_requisitos_propuestas_entidades.requisito_propuesta
							LEFT JOIN sga_g3entidades ON sga_requisitos_propuestas_entidades.entidad = sga_g3entidades.entidad
							LEFT JOIN sga_g3entidades_subtipos as subtipo_entidad ON sga_g3entidades.entidad_subtipo = subtipo_entidad.entidad_subtipo,
							sga_requisitos,
							sga_g3entidades_tipos
				WHERE		sga_requisitos_propuestas.requisito = sga_requisitos.requisito AND
							sga_requisitos_propuestas.entidad_tipo = sga_g3entidades_tipos.entidad_tipo
							$where
  				ORDER BY 	$orden
		";
		return guarani_db::consultar($sql);
	}

	function get_requisitos_ingreso_perfil_datos($where='', $orden='')
	{
		if ($where) {
			$where = " AND $where";
		}
		if (!$orden) {
			$orden = 'sga_requisitos.nombre';
		}
		// Cuando el usuario logueado tiene perfil de datos hay que filtrar los tipos de propuesta.
		$filtro_subtipo_requisito_perfil_datos = '';
		$filtro_subtipo_entidad_perfil_datos = '';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$filtro_ugs_perfil_datos = guarani_sql::armar_condicion_in(toba_proyecto_implementacion::get_perfil_datos_restricciones(toba::proyecto()->get_id(), $perfil_datos), 'vw_ug_propuestas_tipos', 'unidad_gestion', 'clave');
			$filtro_subtipo_requisito_perfil_datos = " AND (subtipo_requisito.entidad_tipo <> ".g3entidad::tipo_propuesta." OR subtipo_requisito.entidad_subtipo IN (SELECT sga_propuestas_tipos.propuesta_tipo FROM sga_propuestas_tipos WHERE sga_propuestas_tipos.propuesta_tipo IN (SELECT vw_ug_propuestas_tipos.propuesta_tipo FROM vw_ug_propuestas_tipos WHERE $filtro_ugs_perfil_datos)))";
			$filtro_subtipo_entidad_perfil_datos = " AND (subtipo_entidad.entidad_tipo <> ".g3entidad::tipo_propuesta." OR subtipo_entidad.entidad_subtipo IN (SELECT sga_propuestas_tipos.propuesta_tipo FROM sga_propuestas_tipos WHERE sga_propuestas_tipos.propuesta_tipo IN (SELECT vw_ug_propuestas_tipos.propuesta_tipo FROM vw_ug_propuestas_tipos WHERE $filtro_ugs_perfil_datos)))";
		}
		$sql = "SELECT		sga_requisitos_propuestas.requisito_propuesta,
							sga_requisitos_propuestas.requisito,
							sga_requisitos_propuestas.tipo,
							sga_requisitos_propuestas.entidad_tipo,
							sga_requisitos.nombre as requisito_nombre,
							sga_g3entidades_tipos.nombre as entidad_tipo_nombre,
							COALESCE(subtipo_requisito.entidad_subtipo, subtipo_entidad.entidad_subtipo) as entidad_subtipo,
							COALESCE(subtipo_requisito.nombre, subtipo_entidad.nombre) as entidad_subtipo_nombre,
							sga_requisitos_propuestas_entidades.entidad
				FROM		sga_requisitos_propuestas
							LEFT JOIN sga_requisitos_propuestas_subtipos ON sga_requisitos_propuestas.requisito_propuesta = sga_requisitos_propuestas_subtipos.requisito_propuesta
							LEFT JOIN sga_g3entidades_subtipos as subtipo_requisito ON sga_requisitos_propuestas_subtipos.entidad_subtipo = subtipo_requisito.entidad_subtipo

							$filtro_subtipo_requisito_perfil_datos

							LEFT JOIN sga_requisitos_propuestas_entidades ON sga_requisitos_propuestas.requisito_propuesta = sga_requisitos_propuestas_entidades.requisito_propuesta
							LEFT JOIN sga_g3entidades ON sga_requisitos_propuestas_entidades.entidad = sga_g3entidades.entidad
							LEFT JOIN sga_g3entidades_subtipos as subtipo_entidad ON sga_g3entidades.entidad_subtipo = subtipo_entidad.entidad_subtipo

							$filtro_subtipo_entidad_perfil_datos,

							sga_requisitos,
							sga_g3entidades_tipos,
							sga_requisitos_propuestas_aplanado,
							sga_planes_versiones
				WHERE		sga_requisitos_propuestas.requisito = sga_requisitos.requisito AND
							sga_requisitos_propuestas.entidad_tipo = sga_g3entidades_tipos.entidad_tipo AND
							sga_requisitos_propuestas.requisito_propuesta = sga_requisitos_propuestas_aplanado.requisito_propuesta AND
							sga_requisitos_propuestas_aplanado.plan_version = sga_planes_versiones.plan_version
							$where
				GROUP BY	sga_requisitos_propuestas.requisito_propuesta,
							sga_requisitos.requisito,
							sga_g3entidades_tipos.entidad_tipo,
							subtipo_requisito.entidad_subtipo,
							subtipo_entidad.entidad_subtipo,
							sga_requisitos_propuestas_entidades.entidad
				ORDER BY	$orden
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos de todos los requisitos de ingreso solicitados a personas en inscripciones a propuesta ACEPTADAS y PENDIENTES,
	 * de acuerdo al filtro especificado.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_listado_reporte($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		vw_personas.persona,
							sga_propuestas_aspira.propuesta_aspira,
							sga_propuestas_aspira.plan_version,
							vw_personas.apellido_nombres 	as persona_nombre,
							vw_personas.apellido_elegido 	as apellido_elegido,
							vw_personas.nombres_elegido 	as nombres_elegido,
							vw_personas.tipo_nro_documento 	as persona_documento,

							sga_propuestas.propuesta,
							sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_nombre,

							vw_periodos_insc_propuesta.anio_academico as propuesta_anio,

							sga_requisitos.nombre as requisito_nombre,
							sga_requisitos_presentados.fecha_presentacion,
							sga_requisitos_presentados.fecha_vencimiento,
							".guarani_sql::get_case_si_no('sga_requisitos_aspirante','exceptuado').",
							sga_requisitos_aspirante.fecha_excepcion,
							sga_req_motivos_excepcion.nombre as motivo_excepcion_nombre,
							GREATEST(sga_requisitos_aspirante.fecha_prorroga, COALESCE(sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos)) as fecha_tope_presentacion,
							
							sga_requisitos.requisito_tipo,
							sga_requisitos_tipos.nombre as requisito_tipo_descr,
							sga_requisitos.regla,
							vw_periodos_insc_propuesta.anio_academico,
							sga_requisitos_aspirante.parametros

				FROM		sga_propuestas_aspira
							JOIN vw_personas ON sga_propuestas_aspira.persona = vw_personas.persona
							JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
							JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
							JOIN sga_propuestas ON sga_propuestas_aspira.propuesta = sga_propuestas.propuesta
							JOIN sga_requisitos_aspirante ON sga_propuestas_aspira.propuesta_aspira = sga_requisitos_aspirante.propuesta_aspira
							JOIN sga_requisitos ON sga_requisitos_aspirante.requisito = sga_requisitos.requisito
							JOIN sga_requisitos_tipos ON sga_requisitos_tipos.requisito_tipo = sga_requisitos.requisito_tipo
							LEFT JOIN sga_req_motivos_excepcion ON sga_requisitos_aspirante.motivo_excepcion = sga_req_motivos_excepcion.motivo_excepcion
							LEFT JOIN sga_requisitos_presentados ON sga_requisitos_presentados.persona = vw_personas.persona AND sga_requisitos_presentados.requisito = sga_requisitos.requisito
							LEFT JOIN sga_requisitos_ingreso_excep ON sga_requisitos_ingreso_excep.periodo_inscripcion = vw_periodos_insc_propuesta.periodo_inscripcion AND sga_requisitos_ingreso_excep.requisito = sga_requisitos.requisito

				WHERE   	sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."','".propuesta::inscripcion_pendiente."')
							$where
				ORDER BY	persona_nombre, sga_propuestas.nombre, requisito_nombre
		";
		
		$datos = guarani_db::consultar($sql);
		
		foreach ($datos as $key => $dato) {
			if (!empty($dato['fecha_excepcion'])) {
				// Si tiene fecha de excepción, se lo considera cumplido
				$datos[$key]['cumplido_descr'] = 'Excep.';	
			} else {
				if ($dato['requisito_tipo'] == requisito::tipo_procesos) {				
					$regla = guarani::regla($dato['regla']);
					$regla->set_parametros($dato['parametros']);

					$parametros_contexto['fecha'] = $dato['fecha_tope_presentacion'];
					$parametros_contexto['persona'] = $dato['persona'];
					$parametros_contexto['propuesta'] = $dato['propuesta'];
					$parametros_contexto['anio_academico'] = $dato['anio_academico'];
					$parametros_contexto['plan_version'] = $dato['plan_version'];
					$regla->set_parametros_contexto($parametros_contexto);

					$datos[$key]['cumplido_descr'] = ($regla->validar()) ? 'Sí' : 'No';	
				} else {
					// Un requisito de tipo documental se considera cumplido si:
					//  + Está presentado
					//  + No tiene fecha de vencimiento o la fecha de vencimiento es posterior a la fecha actual
					if ((!empty($dato['fecha_vencimiento'])) && guarani_fecha::es_menor_igual_a_fecha_actual($dato['fecha_vencimiento'])) {
						$datos[$key]['cumplido_descr'] = 'Venc.';
					} else {
						$datos[$key]['cumplido_descr'] = (!empty($dato['fecha_presentacion'])) ? 'Sí' : 'No';	
					}
				}
			}
		}
		
		return $datos;
	}

	function get_fecha_tope_presentacion($propuesta_aspira, $requisito)
	{
		return toba::consulta_php('co_requisitos')->get_fecha_limite_presentacion($propuesta_aspira, $requisito, true);
	}

	/**
	 * Retorna la menor fecha entre la de cierre de requisitos para el período y la excepción (cuando existe).
	 * 
	 * @param int $propuesta_aspira
	 * @param int $requisito
	 * @return date
	 */
	function get_menor_fecha_tope_presentacion($propuesta_aspira, $requisito)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);
		$requisito = toba::db()->quote($requisito);
		$sql = "SELECT  LEAST(sga_requisitos_ingreso_excep.fecha_tope, sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos) as fecha_tope_minima
				FROM	sga_propuestas_aspira
						JOIN sga_periodos_inscripcion_fechas ON sga_propuestas_aspira.periodo_insc = sga_periodos_inscripcion_fechas.periodo_insc
						JOIN sga_periodos_inscripcion_propuesta ON sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion
						LEFT JOIN sga_requisitos_ingreso_excep ON (sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_requisitos_ingreso_excep.periodo_inscripcion AND sga_requisitos_ingreso_excep.requisito = $requisito)
				WHERE	sga_propuestas_aspira.propuesta_aspira = $propuesta_aspira";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['fecha_tope_minima'];
	}

	/**
	 * Obtiene los requisitos de ingreso para le plan-versión asociado a la entidad recibida como parámetro.
	 * 
	 * @param int $entidad
	 * @return array
	 */
	function get_listado_plan_version($entidad, $where='')
	{
		$entidad = toba::db()->quote($entidad);
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT 	sga_requisitos_propuestas_aplanado.requisito_propuesta,
						sga_requisitos.nombre as nombre,
						sga_requisitos_tipos.nombre as requisito_tipo_nombre,
						sga_requisitos_propuestas.tipo,
						sga_requisitos_propuestas.entidad_tipo,
						sga_g3entidades.entidad_subtipo
				FROM 	sga_planes_versiones
						JOIN sga_requisitos_propuestas_aplanado ON sga_planes_versiones.plan_version = sga_requisitos_propuestas_aplanado.plan_version
						JOIN sga_requisitos_propuestas ON sga_requisitos_propuestas_aplanado.requisito_propuesta = sga_requisitos_propuestas.requisito_propuesta
						JOIN sga_requisitos ON sga_requisitos_propuestas.requisito = sga_requisitos.requisito
						JOIN sga_requisitos_tipos ON sga_requisitos.requisito_tipo = sga_requisitos_tipos.requisito_tipo
						JOIN sga_g3entidades ON sga_planes_versiones.entidad = sga_g3entidades.entidad
				WHERE 	sga_planes_versiones.entidad = $entidad
						$where
		";
		return guarani_db::consultar($sql);
	}

	function get_descripcion_entidad($entidad,$entidad_tipo=null)
	{
		if (! $entidad_tipo) {
			$entidad_tipo = g3entidad::get_tipo_entidad($entidad);
		}
		$tabla_tipo = g3entidad::get_tabla_tipo($entidad_tipo);
		$datos_entidad = guarani::co('co_g3entidades')->get_listado_tipo_detallado($entidad_tipo,"$tabla_tipo.entidad = $entidad");
		// La entidad puede no ser visible por el perfil de datos.
		return (isset($datos_entidad[0]) ? $datos_entidad[0] : '');
	}

	/*
	 * Verifica si el requisito se encuentra asociado al algún período de insc a propuestas Vigente y tiene una excepción en la fecha tope de presentación.
	 * En caso de eliminarse el requisito del periodo de insc, se debe eliminar estos registros.
	*/ 
	function tiene_fecha_tope_particular_en_periodo_vigente($requisito)
	 {
	  $requisito = toba::db()->quote($requisito); 
  
	  $sql = " SELECT  	count(*) as cantidad
	     		FROM  	vw_periodos_insc_propuesta,
	           			sga_requisitos_ingreso_excep
	     		WHERE  	sga_requisitos_ingreso_excep.requisito = $requisito
	     		AND 	vw_periodos_insc_propuesta.periodo_inscripcion = sga_requisitos_ingreso_excep.periodo_inscripcion
	     		AND 	".guarani_sql::fecha_actual." BETWEEN  vw_periodos_insc_propuesta.fecha_inicio AND vw_periodos_insc_propuesta.fecha_fin
	       				;";
	     
	  $per_asocs = guarani_db::consultar_fila($sql);
	
	  return ($per_asocs['cantidad'] > 0);
	 } 
	 
	 /*
	 * Verifica si el requisito se encuentra asociado a algun aspirante a propuesta en períodos de insc a propuestas Vigentes.
	 * No tiene en cuenta si la persona presentó el requisito o si lo tiene exceptuado.
	 */
	 function tiene_aspirante_asociado_periodo_vigente($requisito)
	 {
	  $requisito = toba::db()->quote($requisito); 
	
	  $sql = " SELECT  	count(*) as cantidad
			     FROM  	vw_periodos_insc_propuesta,
			           	sga_propuestas_aspira,
			           	sga_requisitos_aspirante
			     WHERE 	sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc 
			     AND 	sga_requisitos_aspirante.propuesta_aspira =  sga_propuestas_aspira.propuesta_aspira
			     AND 	".guarani_sql::fecha_actual." BETWEEN  vw_periodos_insc_propuesta.fecha_inicio AND vw_periodos_insc_propuesta.fecha_fin
	       				;";
	     
	  $asp_asocs = guarani_db::consultar_fila($sql);
	
	  return ($asp_asocs['cantidad'] > 0);
	}

	function get_por_periodo_inscripcion($periodo_inscripcion, $where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_requisitos_propuestas.requisito,
   							sga_requisitos.nombre,
							$periodo_inscripcion as periodo_inscripcion
				FROM		sga_requisitos_propuestas,
							sga_requisitos
				WHERE 		sga_requisitos_propuestas.requisito = sga_requisitos.requisito AND
							sga_requisitos_propuestas.requisito_para = '".requisito::requisito_propuesta_para_ingreso."'
							$where
				ORDER BY	sga_requisitos.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_datos_requisitos_ingreso($requisito_propuesta=null, $requisito_para=null)
	{
		$where = 'true';
		if (!empty($requisito_propuesta)) {
			$where .= ' AND sga_requisitos_propuestas.requisito_propuesta = '.toba::db()->quote($requisito_propuesta);
		}
		if (!empty($requisito_para)) {
			$where .= ' AND sga_requisitos_propuestas.requisito_para = '.toba::db()->quote($requisito_para);
		}
		$sql = "SELECT	sga_requisitos_propuestas.requisito_propuesta,
						sga_requisitos_propuestas.entidad_tipo
				FROM	sga_requisitos_propuestas
				WHERE	$where
		";
		return guarani_db::consultar($sql);
	}

	function get_entidades($requisito_propuesta)
	{
		if (empty($requisito_propuesta))
			return array();
		$requisito_propuesta = toba::db()->quote($requisito_propuesta);
		$sql = "SELECT	sga_requisitos_propuestas_entidades.entidad
				FROM	sga_requisitos_propuestas_entidades
				WHERE	sga_requisitos_propuestas_entidades.requisito_propuesta = $requisito_propuesta";
		return guarani_db::consultar($sql);
	}

	function get_subtipos($requisito_propuesta)
	{
		if (empty($requisito_propuesta))
			return array();
		$requisito_propuesta = toba::db()->quote($requisito_propuesta);
		$sql = "SELECT	sga_requisitos_propuestas_subtipos.entidad_subtipo
				FROM	sga_requisitos_propuestas_subtipos
				WHERE	sga_requisitos_propuestas_subtipos.requisito_propuesta = $requisito_propuesta";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de requisitos de ingreso por plan-versión según el filtro especificado.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_requisitos_ingreso_propuesta($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_requisitos.nombre as requisito_nombre,
							sga_propuestas.nombre as propuesta_nombre,
							sga_planes.nombre as plan_nombre,
							sga_planes_versiones.nombre as plan_version_nombre,
							sga_requisitos_propuestas.restrictivo
				FROM		sga_requisitos_propuestas_aplanado,
							sga_requisitos_propuestas,
							sga_planes_versiones,
							sga_planes,
							sga_propuestas,
							sga_requisitos
				WHERE		sga_requisitos_propuestas_aplanado.requisito_propuesta = sga_requisitos_propuestas.requisito_propuesta AND
							sga_requisitos_propuestas_aplanado.plan_version = sga_planes_versiones.plan_version AND
							sga_planes_versiones.plan = sga_planes.plan AND
							sga_planes.propuesta = sga_propuestas.propuesta AND
							sga_requisitos_propuestas.requisito = sga_requisitos.requisito AND
							sga_requisitos_propuestas.requisito_para = '".requisito::requisito_propuesta_para_ingreso."'
							$where
				ORDER BY	sga_requisitos.nombre,
							sga_propuestas.nombre,
							sga_planes.nombre,
							sga_planes_versiones.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de requisitos de ingreso.
	 *
	 * @return array
	 */
	function get_requisitos_ingreso()
	{
		$sql = "SELECT		sga_requisitos_propuestas.requisito as valor,
							sga_requisitos.nombre as descr	
				FROM		sga_requisitos_propuestas,
							sga_requisitos
				WHERE		sga_requisitos_propuestas.requisito = sga_requisitos.requisito AND
							sga_requisitos_propuestas.requisito_para = '".requisito::requisito_propuesta_para_ingreso."'
				ORDER BY	sga_requisitos.nombre
        ";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de alumnos que adeudan requisitos de ingreso según el filtro especificado.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_alumnos_deudores($where='')
	{	
		$where_requisitos = '';
		$where_otros = '';
		
		if ($where['otros']) {
			$where_otros = " AND ".$where['otros'];
		}
		if (!empty($where['requisitos'])) {
			$where_requisitos =  " AND ".$where['requisitos'];
		}

		$sql = " SELECT		sga_propuestas_aspira.persona,
							vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento,
							sga_alumnos.legajo,
							sga_ubicaciones.ubicacion,
							sga_ubicaciones.nombre as ubicacion_nombre,
							vw_perfil_de_datos_propuestas.propuesta,
							'(' || vw_perfil_de_datos_propuestas.codigo || ') ' || vw_perfil_de_datos_propuestas.nombre as propuesta_datos	    
				FROM		sga_propuestas_aspira
				join		vw_personas		on sga_propuestas_aspira.persona = vw_personas.persona
				join		sga_alumnos		on sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta
				join		sga_ubicaciones on sga_propuestas_aspira.ubicacion = sga_ubicaciones.ubicacion
				join		vw_perfil_de_datos_propuestas on sga_propuestas_aspira.propuesta = vw_perfil_de_datos_propuestas.propuesta  
				join		sga_situacion_aspirante on sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp 
				join		vw_periodos_insc_propuesta on  sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
				join		sga_propuestas_ra on sga_propuestas_aspira.propuesta = sga_propuestas_ra.propuesta 
				WHERE		sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_pendiente."' AND
							-- Existe un requisito que no esta exceptuado ni presentado
							-- (presentado sin vencimiento o con vencimiento posterior a la fecha de inicio de la inscripción a propuesta).
							EXISTS (SELECT	1
									FROM sga_requisitos_aspirante
									JOIN sga_requisitos ON sga_requisitos_aspirante.requisito = sga_requisitos.requisito
									LEFT JOIN sga_requisitos_ingreso_excep ON (sga_requisitos_ingreso_excep.periodo_inscripcion = vw_periodos_insc_propuesta.periodo_inscripcion AND sga_requisitos_ingreso_excep.requisito = sga_requisitos_aspirante.requisito)
									WHERE 	sga_requisitos_aspirante.propuesta_aspira = sga_propuestas_aspira.propuesta_aspira AND
											sga_requisitos_aspirante.exceptuado = '".comunes::no."' AND
											sga_requisitos.requisito_tipo = ".requisito::tipo_documentales." AND
											NOT f_requisito_presentado(sga_propuestas_aspira.persona, sga_requisitos_aspirante.requisito, CAST(vw_periodos_insc_propuesta.fecha_inicio as DATE), sga_propuestas_aspira.propuesta_aspira, GREATEST(sga_requisitos_aspirante.fecha_prorroga, COALESCE(sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos)))
											$where_requisitos
											)
							$where_otros
				ORDER BY	ubicacion_nombre,
							vw_perfil_de_datos_propuestas.nombre,
							apellido,
							nombres
  		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de alumnos que adeudan requisitos de egreso según el filtro especificado.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_alumnos_deudores_egreso($where='', $requisito=null)
	{	
		if ($where) {
			$where = " AND $where";
		}
		$where_requisito = '';
		if (!empty($requisito)) {
			$requisito = toba::db()->quote($requisito);
			$where_requisito .= " AND sga_requisitos_certificado.requisito = $requisito";
		}
		$sql = " SELECT		sga_certificados_otorg.nro_solicitud,
							vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento,
							sga_alumnos.legajo,
							CASE vw_personas.sexo
								WHEN '".persona::sexo_femenino."' THEN COALESCE(sga_certificados.nombre_femenino, sga_certificados.nombre)
								WHEN '".persona::sexo_no_binario."' THEN COALESCE(sga_certificados.nombre_no_binario, sga_certificados.nombre)
								ELSE sga_certificados.nombre
							END as certificado_nombre
				FROM		sga_certificados_otorg
							JOIN vw_personas ON sga_certificados_otorg.persona = vw_personas.persona
							JOIN sga_alumnos ON sga_certificados_otorg.alumno = sga_alumnos.alumno
							JOIN sga_certificados ON sga_certificados_otorg.certificado = sga_certificados.certificado
				WHERE		sga_certificados_otorg.anulado = 0 AND
							-- Existe un requisito que no está exceptuado ni presentado
							-- (presentado sin vencimiento o con vencimiento posterior a la fecha de inicio de la inscripción a propuesta).
							EXISTS (SELECT	1
									FROM	sga_requisitos_certificado
									WHERE 	sga_requisitos_certificado.nro_solicitud = sga_certificados_otorg.nro_solicitud AND
											sga_requisitos_certificado.exceptuado = '".comunes::no."' AND
											NOT f_requisito_egreso_presentado_exceptuado(sga_certificados_otorg.persona, sga_requisitos_certificado.requisito, CURRENT_DATE, sga_certificados_otorg.nro_solicitud)
											$where_requisito)
							$where
				ORDER BY	vw_personas.apellido,
							vw_personas.nombres,
							certificado_nombre
  		";
		return guarani_db::consultar($sql);
	}

	function get_requisitos_ingreso_cursos($propuesta=null)
	{
		if (!empty($propuesta)) {
			$where = ' AND sga_planes.propuesta = ' . toba::db()->quote($propuesta);
		} else {
			$where = '';
		}
			
		$sql = "SELECT DISTINCT
					sga_requisitos.requisito,
					sga_requisitos.nombre,
					CASE 
						WHEN sga_requisitos_propuestas.restrictivo = 'S' THEN sga_requisitos.nombre || ' (*)'
						ELSE sga_requisitos.nombre
					END AS nombre_descr,
					sga_requisitos_propuestas.restrictivo
				FROM sga_requisitos_propuestas_aplanado
				JOIN sga_requisitos_propuestas ON sga_requisitos_propuestas_aplanado.requisito_propuesta = sga_requisitos_propuestas.requisito_propuesta
				JOIN sga_requisitos ON sga_requisitos_propuestas.requisito = sga_requisitos.requisito 
				JOIN sga_planes_versiones ON sga_requisitos_propuestas_aplanado.plan_version = sga_planes_versiones.plan_version
				JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
				WHERE
				  -- Requisitos documentales
				  sga_requisitos.requisito_tipo = '" . requisito::tipo_documentales . "' 
				  -- Requisitos de ingreso
				  AND sga_requisitos.requisito_ingreso = '" . comunes::si . "'
				  -- De propuestas que administran cursos
				  AND sga_planes.administra_cursos = '" . comunes::si . "'
				  AND sga_requisitos_propuestas.requisito_para = '".requisito::requisito_propuesta_para_ingreso."'
				  $where
				ORDER BY
					restrictivo DESC, 
					nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	function get_requisitos_persona_adeudados($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "
				SELECT	
					sga_requisitos.nombre as requisito
				FROM	
					sga_requisitos
				WHERE	
					sga_requisitos.requisito_tipo = ".requisito::tipo_documentales." AND
					sga_requisitos.requisito in (
					SELECT	sga_requisitos_aspirante.requisito
					FROM 	sga_requisitos_aspirante,
							sga_propuestas_aspira
					WHERE 	sga_propuestas_aspira.propuesta_aspira 	= sga_requisitos_aspirante.propuesta_aspira
					AND		sga_propuestas_aspira.persona = $persona

					UNION

					SELECT 	sga_requisitos_presentados.requisito
					FROM	sga_requisitos_presentados
					WHERE  	sga_requisitos_presentados.persona =  $persona)
					AND requisito NOT IN (SELECT 	sga_requisitos_presentados.requisito
								FROM	sga_requisitos_presentados
								WHERE 	sga_requisitos_presentados.persona =  $persona)
				ORDER BY sga_requisitos.requisito";
		return guarani_db::consultar($sql);
	}

	function get_ingresantes_adeudan_requisito($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_propuestas_aspira.plan_version,
							vw_personas.persona,
							vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento,
							vw_periodos_insc_propuesta.anio_academico,
							sga_propuestas.propuesta,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_nombre,
							sga_ubicaciones.nombre as ubicacion_nombre,
							GREATEST(sga_requisitos_aspirante.fecha_prorroga, COALESCE(sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos)) as fecha_tope_presentacion,
							sga_requisitos.regla,
							sga_requisitos_aspirante.parametros
				FROM		sga_propuestas_aspira
							JOIN vw_personas ON sga_propuestas_aspira.persona = vw_personas.persona
							JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
							JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
							JOIN sga_propuestas ON sga_propuestas_aspira.propuesta = sga_propuestas.propuesta
							JOIN sga_ubicaciones ON sga_propuestas_aspira.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_requisitos_aspirante ON sga_propuestas_aspira.propuesta_aspira = sga_requisitos_aspirante.propuesta_aspira
							JOIN sga_requisitos ON sga_requisitos_aspirante.requisito = sga_requisitos.requisito
							JOIN sga_requisitos_tipos ON sga_requisitos.requisito_tipo = sga_requisitos_tipos.requisito_tipo
							LEFT JOIN sga_requisitos_ingreso_excep ON vw_periodos_insc_propuesta.periodo_inscripcion = sga_requisitos_ingreso_excep.periodo_inscripcion AND sga_requisitos.requisito = sga_requisitos_ingreso_excep.requisito 
				WHERE		sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."','".propuesta::inscripcion_pendiente."') AND
							sga_requisitos_aspirante.exceptuado = '".comunes::no."' AND
							CURRENT_DATE > GREATEST(sga_requisitos_aspirante.fecha_prorroga, COALESCE(sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos))
							$where
				ORDER BY	vw_personas.apellido_nombres, propuesta_nombre
		";
		return guarani_db::consultar($sql);
	}

	//--------------------------------------------------------------------------
	//---- Reportes de requisitos para ficha de la persona ---------------------
	//--------------------------------------------------------------------------
	
	function get_requisitos_ingreso_documentales_adeudados_o_exceptuados($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "SELECT		
					sga_propuestas.propuesta,
					sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
					'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_nombre,
					sga_requisitos.nombre as requisito_nombre,
					sga_requisitos.requisito_subordinado_de,
					sga_requisitos_presentados.fecha_presentacion,
					sga_requisitos_presentados.fecha_vencimiento,
					".guarani_sql::get_case_si_no('sga_requisitos_aspirante','exceptuado').",
					sga_requisitos_aspirante.fecha_excepcion,
					sga_req_motivos_excepcion.nombre as motivo_excepcion_nombre,
					GREATEST(sga_requisitos_aspirante.fecha_prorroga, COALESCE(sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos)) as fecha_tope_presentacion,
					sga_requisitos_aspirante.exceptuado as exceptuado_valor,
					sga_requisitos_aspirante.requisito as requisito
				FROM	
					sga_propuestas_aspira
					JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
					JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
					JOIN sga_propuestas ON sga_propuestas_aspira.propuesta = sga_propuestas.propuesta
					JOIN sga_requisitos_aspirante ON sga_propuestas_aspira.propuesta_aspira = sga_requisitos_aspirante.propuesta_aspira
					JOIN sga_requisitos ON sga_requisitos_aspirante.requisito = sga_requisitos.requisito
					LEFT JOIN sga_req_motivos_excepcion ON sga_requisitos_aspirante.motivo_excepcion = sga_req_motivos_excepcion.motivo_excepcion
					LEFT JOIN sga_requisitos_presentados ON sga_requisitos_presentados.persona = sga_propuestas_aspira.persona AND sga_requisitos_presentados.requisito = sga_requisitos.requisito
					LEFT JOIN sga_requisitos_ingreso_excep ON sga_requisitos_ingreso_excep.periodo_inscripcion = vw_periodos_insc_propuesta.periodo_inscripcion AND sga_requisitos_ingreso_excep.requisito = sga_requisitos.requisito
				WHERE
					sga_propuestas_aspira.persona = $persona
					AND sga_requisitos.requisito_tipo = '". requisito::tipo_documentales . "'
					AND sga_situacion_aspirante.resultado_asp IN ('" . propuesta::inscripcion_aceptada . "', '" . propuesta::inscripcion_pendiente . "')
					AND (
						sga_requisitos_presentados.fecha_presentacion IS NULL	-- Pendiente
						OR sga_requisitos_aspirante.fecha_excepcion IS NOT NULL -- Exceptuado
					)
				ORDER BY	
					sga_propuestas.propuesta, 
					requisito_nombre";
		
		return guarani_db::consultar($sql);
	}

	function get_requisitos_presentados_y_vencidos($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT		sga_requisitos_presentados.requisito_presentado,
							sga_requisitos_presentados.persona,
							sga_requisitos_presentados.requisito,
							sga_requisitos.nombre AS requisito_nombre,
							sga_requisitos_presentados.fecha_presentacion,
							sga_requisitos_presentados.fecha_vencimiento,
							sga_requisitos_presentados.fecha_alta,
							sga_requisitos_presentados.requisito_digital,
							sga_requisitos.requisito_tipo,
							CASE WHEN sga_requisitos_presentados.fecha_vencimiento IS NULL OR CURRENT_DATE < sga_requisitos_presentados.fecha_vencimiento
								THEN 'Cumplido'
								ELSE 'Vencido'
							END AS estado_descr,
							sga_requisitos.requisito_subordinado_de
				FROM		sga_requisitos_presentados
							JOIN sga_requisitos ON sga_requisitos_presentados.requisito = sga_requisitos.requisito
				WHERE		sga_requisitos_presentados.persona = $persona AND
							sga_requisitos_presentados.fecha_presentacion IS NOT NULL
				ORDER BY	sga_requisitos_presentados.fecha_presentacion DESC,
							sga_requisitos.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_datos_requisitos_tipo_proceso($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "SELECT	
					sga_requisitos_aspirante.requisito_aspirante,
					sga_requisitos.requisito,
					sga_propuestas_aspira.propuesta_aspira,
					sga_propuestas_aspira.persona,
					sga_propuestas.propuesta,
					sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
					'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_nombre,
					vw_periodos_insc_propuesta.anio_academico as propuesta_anio,
					sga_requisitos.nombre as requisito_nombre,		
					".guarani_sql::get_case_si_no('sga_requisitos_aspirante','exceptuado').",
					sga_requisitos_aspirante.fecha_excepcion,
					sga_req_motivos_excepcion.nombre as motivo_excepcion_nombre,
					GREATEST(sga_requisitos_aspirante.fecha_prorroga, COALESCE(sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos)) as fecha_tope_presentacion,
					sga_requisitos.requisito_tipo,
					sga_requisitos.regla,
					vw_periodos_insc_propuesta.anio_academico,
					sga_requisitos_aspirante.parametros,
					sga_propuestas_aspira.plan_version,
					sga_requisitos_aspirante.restrictivo
				FROM		
					sga_propuestas_aspira
					JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
					JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
					JOIN sga_propuestas ON sga_propuestas_aspira.propuesta = sga_propuestas.propuesta
					JOIN sga_requisitos_aspirante ON sga_propuestas_aspira.propuesta_aspira = sga_requisitos_aspirante.propuesta_aspira
					JOIN sga_requisitos ON sga_requisitos_aspirante.requisito = sga_requisitos.requisito
					LEFT JOIN sga_req_motivos_excepcion ON sga_requisitos_aspirante.motivo_excepcion = sga_req_motivos_excepcion.motivo_excepcion					
					LEFT JOIN sga_requisitos_ingreso_excep ON sga_requisitos_ingreso_excep.periodo_inscripcion = vw_periodos_insc_propuesta.periodo_inscripcion AND sga_requisitos_ingreso_excep.requisito = sga_requisitos.requisito
				WHERE
					sga_propuestas_aspira.persona = $persona
					AND sga_situacion_aspirante.resultado_asp IN ('" . propuesta::inscripcion_aceptada . "','" . propuesta::inscripcion_pendiente . "')
					AND sga_requisitos.requisito_tipo = '". requisito::tipo_procesos . "'
				ORDER BY	
					sga_propuestas.propuesta, 
					requisito_nombre
				";
		
		$datos = guarani_db::consultar($sql);
		
		foreach ($datos as $key => $dato) {
			if (!empty($dato['fecha_excepcion'])) {
				// Si tiene fecha de excepción, se lo considera cumplido
				$datos[$key]['cumplido'] = true;
				$datos[$key]['cumplido_descr'] = 'Excep.';
			} else {		
				$regla = guarani::regla($dato['regla']);
				$regla->set_parametros($dato['parametros']);

				$parametros_contexto['fecha'] = $dato['fecha_tope_presentacion']; 
				$parametros_contexto['persona'] = $dato['persona'];
				$parametros_contexto['propuesta'] = $dato['propuesta'];
				$parametros_contexto['anio_academico'] = $dato['anio_academico'];
				$parametros_contexto['plan_version'] = $dato['plan_version'];
				$regla->set_parametros_contexto($parametros_contexto);
				
				$cumplido = $regla->validar();

				$datos[$key]['cumplido'] = $cumplido;
				$datos[$key]['cumplido_descr'] = ($cumplido) ? 'Sí' : 'No';	
			}
		}
		
		return $datos;
	}

	function get_requisitos_egreso_adeudados_o_exceptuados($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "SELECT		sga_certificados.certificado,
							'(' || sga_certificados.codigo || ') ' || sga_certificados.nombre as certificado_nombre,  
							sga_requisitos.nombre as requisito_nombre,
							sga_requisitos.requisito_subordinado_de,
							".guarani_sql::get_case_si_no('sga_requisitos_certificado', 'exceptuado').",
							sga_requisitos_certificado.fecha_excepcion,
							sga_req_motivos_excepcion.nombre as motivo_excepcion_nombre,
							sga_requisitos_certificado.exceptuado as exceptuado_valor,
							sga_requisitos_certificado.requisito
				FROM		sga_requisitos_certificado
							JOIN sga_certificados_otorg ON sga_requisitos_certificado.nro_solicitud = sga_certificados_otorg.nro_solicitud
							JOIN sga_certificados ON sga_certificados_otorg.certificado = sga_certificados.certificado
							JOIN sga_requisitos ON sga_requisitos_certificado.requisito = sga_requisitos.requisito
							LEFT JOIN sga_req_motivos_excepcion ON sga_requisitos_certificado.motivo_excepcion = sga_req_motivos_excepcion.motivo_excepcion
							LEFT JOIN sga_requisitos_presentados ON sga_requisitos_presentados.persona = sga_certificados_otorg.persona AND sga_requisitos_presentados.requisito = sga_requisitos.requisito
				WHERE		sga_certificados_otorg.persona = $persona AND
							(sga_requisitos_presentados.fecha_presentacion IS NULL OR sga_requisitos_certificado.fecha_excepcion IS NOT NULL)
				ORDER BY	sga_certificados.certificado, 
							sga_requisitos.nombre
		";
		return guarani_db::consultar($sql);
	}
}