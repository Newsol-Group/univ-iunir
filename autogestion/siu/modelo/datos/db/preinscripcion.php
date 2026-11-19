<?php

namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use siu\modelo\_comun\comunes;
use propuesta;
use siu\modelo\datos\catalogo;
use siu\extension_kernel\formularios\guarani_form_elemento as util_form;
use siu\modelo\_comun\clienteRDI;

class preinscripcion
{
	// Estados de una inscripción a propuesta.
	const insc_pendiente = 'P';
	const insc_procesada = 'I';
	const insc_nueva = 'N';
	const insc_solicitud_informacion = 'S';
        /**
	 * Determina si existe algún período de Preinscripción vigente
	 * 
	 * cache: no
	 * filas: 1
	 */
	function hay_periodo_preinsc_vigente()
	{
		$sql = "SELECT 	
					count(1) as cant
				FROM 	
					sga_periodos_inscripcion,
					sga_periodos_inscripcion_propuesta
				WHERE 	
					sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion 
					AND sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN ('3', '4') -- Períodos de tipo: 'Todos' y 'Preinscripcion'
					AND EXISTS (
						SELECT 	1
						FROM 	sga_periodos_inscripcion_fechas
						WHERE	sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion
						AND CURRENT_DATE BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND sga_periodos_inscripcion_fechas.fecha_fin 
						AND sga_periodos_inscripcion_fechas.habilitado_interfaz IN (0,2)
					)
				";

		$datos = kernel::db()->consultar_fila($sql);
		return $datos['cant'] > 0;
	}

	/**
	 * Determina la fecha de inicio del próximo período de Preinscripción 
	 * 
	 * cache: no
	 * filas: 1
	 */
	function proximo_inicio_preinscripcion()
	{
		$sql = "SELECT 	
					CAST(sga_periodos_inscripcion_fechas.fecha_inicio AS DATE) AS fecha_inicio
				FROM 	
					sga_periodos_inscripcion,
					sga_periodos_inscripcion_propuesta,
					sga_periodos_inscripcion_fechas
				WHERE 	
					sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion 
					AND sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN ('3', '4') -- Períodos de tipo: 'Todos' y 'Preinscripcion'
					AND sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion
					AND sga_periodos_inscripcion_fechas.habilitado_interfaz IN (0,2)
					AND sga_periodos_inscripcion_fechas.fecha_inicio > CURRENT_DATE
				ORDER BY
					fecha_inicio ASC
				LIMIT 1;
				";

		$datos = kernel::db()->consultar_fila($sql);
		return !empty($datos['fecha_inicio']) ? convertir_fecha_formato_frontend($datos['fecha_inicio']) : '';
	}

	/**
	 * Obtiene el período de Preinscripción vigente (periodo_insc) para una propuesta dada.
	 * Da prioridad a los períodos de tipo Preinscripción (por encima de los de tipo "Todos").
	 * En caso de haber más de uno, decide por el que comience primero.
	 * 
	 * parametros: propuesta, ubicacion, modalidad
	 * cache: memoria
	 * filas: 1
	 */
	function get_periodo_preinscripcion($parametros)
	{
		$propuesta = $parametros['propuesta'];
        $ubicacion = $parametros['ubicacion'];
        $modalidad = $parametros['modalidad'];

		$sql = "SELECT DISTINCT
					vw_periodos_insc_propuesta.periodo_insc,
					vw_periodos_insc_propuesta.periodo_inscripcion,
					vw_periodos_insc_propuesta.fecha_inicio,
					vw_periodos_insc_propuesta.periodo_insc_tipo
				FROM vw_periodos_insc_propuesta
				JOIN sga_periodos_inscripcion_aplanado  ON (sga_periodos_inscripcion_aplanado.periodo_insc  = vw_periodos_insc_propuesta.periodo_insc)
				JOIN sga_per_insc_ubicacion             ON (sga_per_insc_ubicacion.periodo_inscripcion      = vw_periodos_insc_propuesta.periodo_inscripcion)
				JOIN sga_per_insc_modalidad             ON (sga_per_insc_modalidad.periodo_inscripcion      = vw_periodos_insc_propuesta.periodo_inscripcion) 
				JOIN sga_planes_versiones               ON (sga_planes_versiones.plan_version               = sga_periodos_inscripcion_aplanado.plan_version)
				JOIN sga_planes                         ON (sga_planes.plan                                 = sga_planes_versiones.plan)
				WHERE 
				-- Períodos de tipo: 'Todos' y 'Preinscripcion'
				vw_periodos_insc_propuesta.periodo_insc_tipo IN ('3', '4') 
				-- De la propuesta especificada
				AND sga_planes.propuesta = $propuesta
				-- De la ubicacion especificada
				AND sga_per_insc_ubicacion.ubicacion = $ubicacion
				-- De la modalidad especificada
				AND sga_per_insc_modalidad.modalidad = $modalidad
				-- Vigente y habilitada
				AND CURRENT_DATE BETWEEN vw_periodos_insc_propuesta.fecha_inicio AND vw_periodos_insc_propuesta.fecha_fin 
				AND vw_periodos_insc_propuesta.habilitado = '" . comunes::si . "'
				AND vw_periodos_insc_propuesta.habilitado_interfaz IN (0,2)
				ORDER BY
					-- Prioridad para los períodos especificos de tipo 'Preinscripción'
					periodo_insc_tipo DESC,
					-- Prioridad para los primeros en comenzar 
					fecha_inicio 
				LIMIT 1 
				";

		$datos = kernel::db()->consultar_fila($sql);
		return $datos['periodo_insc'];
	}

	//--------------------------------------------------------------------------
	//---- Manejo de Preinscripciones ------------------------------------------
	//--------------------------------------------------------------------------
	/**
	 * 
	 * parametros: persona
	 * cache: no
	 * filas: n
	 */
	function get_propuestas_preinscripcion($parametros)
	{
		$persona = $parametros['persona'];
		/*
		 Recupera preinscripciones a propuestas vigentes de una persona que aun no fueron procesadas.
		*/
		$sql = "SELECT 
					sga_preinscripcion_propuesta.preinscripcion,
					sga_preinscripcion_propuesta.persona,
					sga_preinscripcion_propuesta.responsable_academica,
					sga_responsables_academicas.nombre AS responsable_academica_nombre,
					sga_preinscripcion_propuesta.propuesta,
					sga_propuestas.nombre AS propuesta_nombre,
					sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
					sga_preinscripcion_propuesta.ubicacion,
					sga_ubicaciones.nombre AS ubicacion_nombre,
					sga_preinscripcion_propuesta.modalidad,
					sga_modalidad_cursada.nombre AS modalidad_nombre,
					sga_preinscripcion_propuesta.periodo_insc,
					sga_preinscripcion_propuesta.estado,
					sga_preinscripcion_propuesta.fecha_preinscripcion,
					sga_preinscripcion_propuesta.fecha_fin_vigencia,
					CASE WHEN CURRENT_DATE > sga_preinscripcion_propuesta.fecha_fin_vigencia 
						THEN true
						ELSE false
					END AS vencido
				FROM sga_preinscripcion_propuesta
				JOIN sga_propuestas ON sga_preinscripcion_propuesta.propuesta = sga_propuestas.propuesta
				JOIN sga_responsables_academicas ON sga_preinscripcion_propuesta.responsable_academica = sga_responsables_academicas.responsable_academica
				JOIN sga_ubicaciones ON sga_preinscripcion_propuesta.ubicacion = sga_ubicaciones.ubicacion
				JOIN sga_modalidad_cursada ON sga_preinscripcion_propuesta.modalidad = sga_modalidad_cursada.modalidad
				WHERE sga_preinscripcion_propuesta.persona = $persona
				  AND sga_preinscripcion_propuesta.fecha_fin_vigencia >= CURRENT_DATE
				  AND sga_preinscripcion_propuesta.estado <> '".\preinscripcion::insc_procesada."'";

		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		foreach ($datos as $dato) {
			$id = catalogo::generar_id($dato['preinscripcion']);
			$dato['fecha_fin_vigencia_visual'] = '--';
			$dato['vigente'] = false;
			//$estado = kernel::traductor()->trans('preinscripcion.estado_pendiente');
			$dato['fecha_vencimiento_formato_visual'] = convertir_fecha_formato_frontend($dato['fecha_fin_vigencia']);
			$dato['vigente'] = true;

			$nuevo[$id] = $dato;
			$nuevo[$id][catalogo::id] = $id;
		}
		return $nuevo;
	}

	//--------------------------------------------------------------------------
	//---- Consultas para selector de Propuestas -------------------------------
	//--------------------------------------------------------------------------
	/**
	 * 
	 * cache: memoria
	 * filas: n
	 */
	function get_responsables_academicas()
	{

		$pi_tipo_todos = kernel::db()->quote(\periodo_de_inscripcion::tipo_todos);
		$pi_tipo_preinscripcion = kernel::db()->quote(\periodo_de_inscripcion::tipo_preinscripcion);
		$si = kernel::db()->quote(\comunes::si);

		$sql = "SELECT 
						sga_responsables_academicas.responsable_academica,
						sga_responsables_academicas.nombre,
						sga_responsables_academicas.codigo
				FROM	sga_propuestas_ra					
				JOIN	sga_responsables_academicas			ON (sga_responsables_academicas.responsable_academica		= sga_propuestas_ra.responsable_academica)
				JOIN 	sga_propuestas						ON (sga_propuestas.propuesta								= sga_propuestas_ra.propuesta)
				JOIN	sga_planes							ON (sga_planes.propuesta									= sga_propuestas.propuesta)
				JOIN	sga_planes_versiones				ON (sga_planes_versiones.plan								= sga_planes.plan)
				JOIN	sga_periodos_inscripcion_aplanado 	ON (sga_periodos_inscripcion_aplanado.plan_version			= sga_planes_versiones.plan_version)
				JOIN	sga_periodos_inscripcion_fechas	  	ON (sga_periodos_inscripcion_fechas.periodo_insc			= sga_periodos_inscripcion_aplanado.periodo_insc)
				JOIN	sga_periodos_inscripcion			ON (sga_periodos_inscripcion.periodo_inscripcion			= sga_periodos_inscripcion_fechas.periodo_inscripcion)
				JOIN	sga_periodos_inscripcion_propuesta	ON (sga_periodos_inscripcion_propuesta.periodo_inscripcion	= sga_periodos_inscripcion.periodo_inscripcion)
				WHERE   sga_periodos_inscripcion_fechas.habilitado = $si
						AND CURRENT_DATE BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND sga_periodos_inscripcion_fechas.fecha_fin
						AND sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN ($pi_tipo_todos, $pi_tipo_preinscripcion) -- Períodos de tipo: 'Todos' y 'Preinscripcion'
				";

		$datos = kernel::db()->consultar($sql);
		foreach ($datos as $key => $dato) {
			$datos[$key]['ra_hash'] = catalogo::generar_id($dato['responsable_academica']);
		}
		return $datos;
	}

	/**
	 * Obtiene todas las propuestas disponibles para Preinscripción, a partir de
	 * una Responsable Académica dada, excluyendo las propuestas con preinscripciones vigentes o con inscripciones no rechazadas
	 * 
	 * parametros: ra, persona
	 * cache: no
	 * filas: n
	 */
	function get_propuestas_disponibles_preinscripcion($parametros)
	{
		$ra = $parametros['ra'];
		$persona = $parametros['persona'];
                $excluir = catalogo::consultar('preinscripcion', 'get_propuestas_a_excluir', ['persona' => $persona]);

		$pi_tipo_todos = kernel::db()->quote(\periodo_de_inscripcion::tipo_todos);
		$pi_tipo_preinscripcion = kernel::db()->quote(\periodo_de_inscripcion::tipo_preinscripcion);
		$si = kernel::db()->quote(\comunes::si);
		$propuesta_tipo_curso = kernel::db()->quote(propuesta::tipo_curso);

		if (!empty($excluir)) {
			$propuestas = aplanar_matriz($excluir, 'propuesta');
			$where_excluir = ' AND sga_propuestas.propuesta NOT IN (' . implode(',', $propuestas) . ')';
		} else {
			$where_excluir = '';
		}
				$sql = "SELECT	DISTINCT 
						sga_planes.propuesta,
						sga_propuestas.nombre
				FROM 	sga_propuestas_ra
				JOIN 	sga_propuestas						ON (sga_propuestas.propuesta								= sga_propuestas_ra.propuesta)
				JOIN	sga_planes							ON (sga_planes.propuesta									= sga_propuestas.propuesta)
				JOIN	sga_planes_versiones				ON (sga_planes_versiones.plan								= sga_planes.plan)
				JOIN	sga_periodos_inscripcion_aplanado 	ON (sga_periodos_inscripcion_aplanado.plan_version			= sga_planes_versiones.plan_version)
				JOIN	sga_periodos_inscripcion_fechas	  	ON (sga_periodos_inscripcion_fechas.periodo_insc			= sga_periodos_inscripcion_aplanado.periodo_insc)
				JOIN	sga_periodos_inscripcion			ON (sga_periodos_inscripcion.periodo_inscripcion			= sga_periodos_inscripcion_fechas.periodo_inscripcion)
				JOIN	sga_periodos_inscripcion_propuesta	ON (sga_periodos_inscripcion_propuesta.periodo_inscripcion	= sga_periodos_inscripcion.periodo_inscripcion)
				WHERE	sga_propuestas_ra.responsable_academica = $ra
						AND sga_periodos_inscripcion_fechas.habilitado = $si
						AND CURRENT_DATE BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND sga_periodos_inscripcion_fechas.fecha_fin
						AND sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN ($pi_tipo_todos, $pi_tipo_preinscripcion) -- Períodos de tipo: 'Todos' y 'Preinscripcion'
						AND sga_propuestas.propuesta_tipo <> $propuesta_tipo_curso
						AND sga_planes.inscripcion_habilitada = $si

						$where_excluir
				";
		
		return kernel::db()->consultar($sql);
	}

	/**
	 * Obtiene las ubicaciones donde se ofrece una propuesta
	 * 
	 * parametros: propuesta
	 * cache: memoria
	 * cache_expiracion: 30
	 * filas: n
	 */
	function get_ubicaciones_propuesta($parametros)
	{
		$propuesta = $parametros['propuesta'];
		$plan_estado_activo = kernel::db()->quote(\planes::estado_activo);
		$plan_estado_vigente = kernel::db()->quote(\planes::estado_vigente);
		$pi_tipo_todos = kernel::db()->quote(\periodo_de_inscripcion::tipo_todos);
		$pi_tipo_preinscripcion = kernel::db()->quote(\periodo_de_inscripcion::tipo_preinscripcion);
		$si = kernel::db()->quote(\comunes::si);

		$sql = "SELECT DISTINCT
					sga_ubicaciones.ubicacion,
					sga_ubicaciones.nombre
				FROM sga_planes
				JOIN sga_planes_versiones				ON (sga_planes_versiones.plan = sga_planes.plan)
				JOIN sga_propuestas_oferta				ON (sga_propuestas_oferta.propuesta = sga_planes.propuesta)
				JOIN sga_ubicaciones					ON (sga_ubicaciones.ubicacion = sga_propuestas_oferta.ubicacion)
				JOIN sga_per_insc_ubicacion				ON (sga_per_insc_ubicacion.ubicacion = sga_propuestas_oferta.ubicacion)
				JOIN vw_periodos_insc_propuesta			ON (vw_periodos_insc_propuesta.periodo_inscripcion = sga_per_insc_ubicacion.periodo_inscripcion)
				JOIN sga_periodos_inscripcion_aplanado	ON (sga_periodos_inscripcion_aplanado.periodo_insc  = vw_periodos_insc_propuesta.periodo_insc AND
															sga_periodos_inscripcion_aplanado.plan_version = sga_planes.version_actual)

				WHERE sga_planes.propuesta = $propuesta
				AND sga_planes_versiones.estado IN ($plan_estado_activo, $plan_estado_vigente)
				AND vw_periodos_insc_propuesta.periodo_insc_tipo IN ($pi_tipo_todos, $pi_tipo_preinscripcion)
				AND CURRENT_DATE BETWEEN vw_periodos_insc_propuesta.fecha_inicio AND vw_periodos_insc_propuesta.fecha_fin
				AND vw_periodos_insc_propuesta.habilitado = $si
				AND sga_planes.inscripcion_habilitada = $si
		";

		return kernel::db()->consultar($sql);
	}

	/**
	 * Obtiene las modalidades disponibles para una propuesta
	 * 
	 * parametros: propuesta, ubicacion
	 * cache: memoria
	 * cache_expiracion: 30
	 * filas: n
	 */
	function get_modalidades_propuesta($parametros)
	{
		$propuesta = $parametros['propuesta'];
		$ubicacion = $parametros['ubicacion'];
		$plan_estado_activo = kernel::db()->quote(\planes::estado_activo);
		$plan_estado_vigente = kernel::db()->quote(\planes::estado_vigente);
		$pi_tipo_todos = kernel::db()->quote(\periodo_de_inscripcion::tipo_todos);
		$pi_tipo_preinscripcion = kernel::db()->quote(\periodo_de_inscripcion::tipo_preinscripcion);
		$si = kernel::db()->quote(\comunes::si);

		$sql = "SELECT DISTINCT
					sga_planes_modalidad.modalidad,
					sga_modalidad_cursada.nombre
				FROM sga_planes
				JOIN sga_planes_versiones				ON (sga_planes_versiones.plan = sga_planes.plan)
				JOIN sga_planes_modalidad				ON (sga_planes_modalidad.plan = sga_planes.plan)
				JOIN sga_modalidad_cursada				ON (sga_modalidad_cursada.modalidad = sga_planes_modalidad.modalidad)
				JOIN sga_per_insc_modalidad				ON (sga_per_insc_modalidad.modalidad = sga_planes_modalidad.modalidad)
				JOIN vw_periodos_insc_propuesta			ON (vw_periodos_insc_propuesta.periodo_inscripcion = sga_per_insc_modalidad.periodo_inscripcion)
				JOIN sga_per_insc_ubicacion				ON (sga_per_insc_ubicacion.periodo_inscripcion = vw_periodos_insc_propuesta.periodo_inscripcion AND sga_per_insc_ubicacion.ubicacion = $ubicacion)
				JOIN sga_periodos_inscripcion_aplanado	ON (sga_periodos_inscripcion_aplanado.periodo_insc  = vw_periodos_insc_propuesta.periodo_insc AND
															sga_periodos_inscripcion_aplanado.plan_version = sga_planes.version_actual)

				WHERE sga_planes.propuesta = $propuesta
				AND sga_planes_versiones.estado IN ($plan_estado_activo, $plan_estado_vigente)
				AND vw_periodos_insc_propuesta.periodo_insc_tipo IN ($pi_tipo_todos, $pi_tipo_preinscripcion)
				AND CURRENT_DATE BETWEEN vw_periodos_insc_propuesta.fecha_inicio AND vw_periodos_insc_propuesta.fecha_fin
				AND vw_periodos_insc_propuesta.habilitado = $si
				AND sga_planes.inscripcion_habilitada = $si
		";

		return kernel::db()->consultar($sql);
	}

	/**
	 * Obtiene las propuestas a excluir del selector de propuestas para preinscribirse
	 *  + Propuestas con preinscripciones vigentes sin procesar o preinscripciones a propuesta procesadas
	 *  + Propuestas donde se tiene una inscripción pendiente o aceptada.
	 * 
	 * parametros: persona
         * no_quote: persona
	 * cache: no
	 * filas: n
	 */
	function get_propuestas_a_excluir($parametros)
	{
		$persona = $parametros['persona'];
		$estado_pre_inscripto = kernel::db()->quote(\preinscripcion::insc_procesada);
		$estado_pre_pendiente = kernel::db()->quote(\preinscripcion::insc_pendiente);
		$inscripcion_aceptada = kernel::db()->quote(\propuesta::inscripcion_aceptada);
		$inscripcion_pendiente = kernel::db()->quote(\propuesta::inscripcion_pendiente);

		$sql = "SELECT DISTINCT 
					sga_preinscripcion_propuesta.propuesta
				FROM 
					sga_preinscripcion_propuesta 
				WHERE
					sga_preinscripcion_propuesta.persona = $persona
					AND (--1. Esta inscripto en la propuesta. La preinscripción fue procesada desde Gestion.
					     ( sga_preinscripcion_propuesta.estado = $estado_pre_inscripto
					      -- Que tenga la inscripcion aceptada o pendiente en la propuesta  (que no se haya rechazado)
					      AND EXISTS (SELECT 1
					                  FROM sga_propuestas_aspira
					                  JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
					                  WHERE sga_propuestas_aspira.persona = sga_preinscripcion_propuesta.persona
					                    AND sga_propuestas_aspira.propuesta = sga_preinscripcion_propuesta.propuesta
					                    AND sga_situacion_aspirante.resultado_asp IN ($inscripcion_pendiente, $inscripcion_aceptada)
					                 )
					     ) OR 
					     (--2. La preinscripción está vigente para ser procesada desde Gestion 
					      sga_preinscripcion_propuesta.estado = $estado_pre_pendiente AND
					      sga_preinscripcion_propuesta.fecha_fin_vigencia >= CURRENT_DATE
					     ) 
					)

				UNION 

				SELECT DISTINCT
					sga_propuestas_aspira.propuesta
				FROM 
					sga_propuestas_aspira
					JOIN sga_situacion_aspirante ON (sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp)
				WHERE
					sga_propuestas_aspira.persona = $persona
					AND sga_situacion_aspirante.resultado_asp IN ($inscripcion_pendiente, $inscripcion_aceptada) 
				";

		return kernel::db()->consultar($sql);
	}

	/**
	 * Cantidad de preinscripciones vigentes de una persona
	 * 
	 * parametros: persona
	 * cache: no
	 * filas: 1
	 * 	 
	 */
	function get_cantidad_preinscripciones_vigentes($parametros)
	{
		$persona = $parametros['persona'];

		$sql = "SELECT COUNT(*) as cantidad_preinscripciones
				FROM sga_preinscripcion_propuesta 
				WHERE sga_preinscripcion_propuesta.persona = $persona
				AND sga_preinscripcion_propuesta.fecha_fin_vigencia >= CURRENT_DATE";

		$datos = kernel::db()->consultar_fila($sql);

		return $datos['cantidad_preinscripciones'];
	}

	/**
	 * Obtiene los requisitos de ingreso de una persona en una propuesta
	 * 
	 * parametros: persona, propuesta, estado_req_digital
	 * no_quote: persona, propuesta, estado_req_digital
	 * param_null: estado_req_digital
	 * cache: memoria
	 * filas: n
	 */
	function get_requisitos_persona_propuesta($parametros)
	{
		
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$case_estado_req_digital = clienteRDI::case_estado_req_digital();
		$case_observaciones = clienteRDI::case_observaciones();

		$where_estado_req_digital = "";
		// Si se filtra por estado_req_digital
		if (is_array($parametros['estado_req_digital'])) {
			$estado_req_digital = implode("', '", $parametros['estado_req_digital']);
			$where_estado_req_digital = "AND {$case_estado_req_digital} IN ('{$estado_req_digital}')";
		}

		$sql = "SELECT DISTINCT 
					sga_requisitos.requisito as requisito,
					sga_requisitos.nombre as requisito_nombre,
					sga_requisitos_digitales_draft.requisito_digital,
					COALESCE(sga_requisitos_digitales_draft.id_documento, sga_requisitos_digitales.id_documento) AS id_documento,
					$case_estado_req_digital AS estado_req_digital,
					$case_observaciones AS observaciones,
					CASE 
						WHEN sga_requisitos_presentados.requisito_presentado IS NOT NULL
						THEN true
						ELSE false
					END AS presentado,
					to_char(sga_requisitos_presentados.fecha_vencimiento, $formato_fecha_visual) AS fecha_vencimiento_formato_visual,
					sga_requisitos_presentados.fecha_vencimiento,
					to_char( sga_requisitos_presentados.fecha_presentacion, $formato_fecha_visual) as f_presentacion, 
					
					sga_requisitos.descripcion as descripcion,
					sga_requisitos.tipo_documento_digital,
					sga_requisitos_propuestas.doc_digital_obligatorio_preinscripcion
					
				FROM 
					sga_requisitos_propuestas_aplanado
					JOIN sga_requisitos_propuestas ON (sga_requisitos_propuestas_aplanado.requisito_propuesta = sga_requisitos_propuestas.requisito_propuesta)
					JOIN sga_planes_versiones ON (sga_requisitos_propuestas_aplanado.plan_version = sga_planes_versiones.plan_version)
					JOIN sga_planes ON (sga_planes_versiones.plan = sga_planes.plan)
					JOIN sga_requisitos ON (sga_requisitos_propuestas.requisito = sga_requisitos.requisito)
					LEFT JOIN sga_requisitos_digitales_draft ON (
						sga_requisitos_digitales_draft.requisito =  sga_requisitos_propuestas.requisito
						AND sga_requisitos_digitales_draft.persona = {$parametros['persona']}
					)
					LEFT JOIN sga_requisitos_presentados ON (
						sga_requisitos_presentados.requisito_presentado =
						(
							SELECT MAX(rp_aux.requisito_presentado)
							FROM sga_requisitos_presentados AS rp_aux
							WHERE rp_aux.requisito = sga_requisitos_propuestas.requisito
							AND rp_aux.persona = {$parametros['persona']}
						)
					)
					LEFT JOIN sga_requisitos_digitales ON (sga_requisitos_presentados.requisito_digital = sga_requisitos_digitales.requisito_digital)
					LEFT JOIN sga_requisitos_digitales_draft_detalle ON (
						sga_requisitos_digitales_draft_detalle.persona = {$parametros['persona']}
						AND sga_requisitos_propuestas.requisito = sga_requisitos_digitales_draft_detalle.requisito
					)
				WHERE
					sga_planes.propuesta = {$parametros['propuesta']}
					AND sga_requisitos.requisito_tipo = '" . \requisito::tipo_documentales . "'
					AND sga_requisitos_propuestas.requisito_para = '".\requisito::requisito_propuesta_para_ingreso."'
					$where_estado_req_digital
				ORDER BY requisito_nombre";


		$salida = kernel::db()->consultar($sql);

		foreach ($salida as $key => $requisito) {
			$salida[$key][catalogo::id] = catalogo::generar_id($requisito['requisito']);
		}
		return $salida;
	}

	//--------------------------------------------------------------------------
	//---- Turnos de presentación de documentación -----------------------------
	//--------------------------------------------------------------------------
	/**
	 * Asigna un turno según la solicitud (fecha, rango horario, RA)
	 * 
	 * parametros: persona
         * no_quote: persona
	 * cache: no
	 * filas: n
	 */
	function get_listado_turnos($parametros)
	{
                // Recupero las preinscripciones a propuestas
                $preinscripcion_propuestas = catalogo::consultar('preinscripcion', 'get_propuestas_preinscripcion', $parametros);

		$salida = [];
                // Recorro las preinscripciones a propuestas
		foreach ($preinscripcion_propuestas as $preinscripcion_propuesta) {

                    $responsable_academica = $preinscripcion_propuesta['responsable_academica'];
                    $responsable_academica_nombre = $preinscripcion_propuesta['responsable_academica_nombre'];
                    
                    $parametros_turnos['responsable_academica'] = $responsable_academica;
                    $parametros_turnos['ubicacion'] = $preinscripcion_propuesta['ubicacion'];
                    $parametros_turnos['modalidad'] = $preinscripcion_propuesta['modalidad'];
                    // Hay turnos de presentación de documentación disponibles
                    $hay_turnos = catalogo::consultar('preinscripcion', 'hay_turnos_preinscripcion_propuesta', $parametros_turnos);

                    $parametros_turno['persona'] = $parametros['persona'];
                    $parametros_turno['ra'] = $responsable_academica;
                    // Turno asignado
                    $turno_asignado = catalogo::consultar('preinscripcion', 'get_turno_presentacion', $parametros_turno);

                    // Hay turno asignado
                    $hay_turno_asignado = !empty($turno_asignado);

                    // Si no hay turnos de presentación de documentación disponibles
                    // ni tampoco turno asignado
                    // salteo a la siguiente preinscripción a propuesta
                    if (!$hay_turnos && !$hay_turno_asignado) {
                        continue;
                    }

                    // Si ya existe la RA en $salida salteo a la siguiente preinscripción a propuesta
                    if (isset($salida[$responsable_academica])) {
                        continue;
                    }

                    // Si hay turno asignado
                    if ($hay_turno_asignado) {
                            $pre_turno = $turno_asignado['pre_turno'];
                            $turno_fecha = convertir_fecha_formato_frontend($turno_asignado['fecha']);
                            $turno_hora = convertir_hora_formato_frontend($turno_asignado['hora']);
                            $turno_operador = $turno_asignado['operador'];
                            $asignado = true;
                    } else {// Si no hay turno asignado
                            $msg_no_asignado = kernel::traductor()->trans('turno.no_asignado');
                            $pre_turno = null;
                            $turno_fecha = $msg_no_asignado;
                            $turno_hora = $msg_no_asignado;
                            $turno_operador = $msg_no_asignado;
                            $asignado = false;
                    }
                    
                    $salida[$responsable_academica] = [
                            'pre_turno' => $pre_turno,
                            'responsable_academica_plana' => $responsable_academica,
                            'responsable_academica' => catalogo::generar_id($responsable_academica),
                            'responsable_academica_nombre' => $responsable_academica_nombre,
                            'fecha' => $turno_fecha,
                            'hora' => $turno_hora,
                            'operador' => $turno_operador,
                            'asignado' => $asignado,
                    ];
		}

		return $salida;
	}

	/**
	 * Determina si existen turnos disponibles (no ocupados, vigentes) para una
	 * Responsable Académica (RA) dada
	 * 
	 * parametros: ra
	 * cache: no
	 * filas: 1
	 */
	function hay_turnos_disponibles($parametros)
	{
		$ra = $parametros['ra'];
		$fechas = $this->get_fechas_turnos_disponibles($ra);
		return \guarani_arreglos::count($fechas) > 0;
	}

	/**
	 * Obtiene las fechas en las que exiten turnos disponibles
	 * 
	 * parametros: ra
	 * cache: no
	 * filas: n
	 */
	function get_fechas_turnos_disponibles($parametros)
	{
		$ra = $parametros['ra'];
		$where_ra = $this->get_where_ra($ra);

		$sql = "SELECT
					distinct(fecha) AS fecha
				FROM 
					pre_turnos
				WHERE		
					id_preinscripcion IS NULL 
					AND persona IS NULL
					AND (
						fecha > CURRENT_DATE
						OR	(
							fecha = CURRENT_DATE
							AND hora >= CURRENT_TIME
						)
					)
					$where_ra
				ORDER BY 
					fecha
				";

		$res = kernel::db()->consultar($sql);
		$salida = array();
		foreach ($res as $fecha) {
			$salida[] = convertir_fecha_formato_frontend($fecha['fecha']);
		}
		return $salida;
	}

	/**
	 * Obtiene la primera fecha en la que hay un turno disponible, o el string 
	 * vacío en caso de no existir turnos disponibles
	 * 
	 * parametros: ra
	 * cache: no
	 * filas: 1
	 */
	function get_fecha_minima($parametros)
	{
		$ra = $parametros['ra'];
		$where_ra = $this->get_where_ra($ra);

		$sql = "SELECT 	
					MIN(fecha) as fecha
				FROM 	
					pre_turnos
				WHERE	
					id_preinscripcion IS NULL
					AND persona IS NULL
					AND fecha >= CURRENT_DATE
					$where_ra
				";
		$res = kernel::db()->consultar_fila($sql);
		if (!empty($res['fecha'])) {
			$salida = convertir_fecha_formato_frontend($res['fecha']);
		} else {
			$salida = '';
		}
		return $salida;
	}

	/**
	 * Obtiene la última fecha en la que hay un turno disponible, o el string 
	 * vacío en caso de no existir turnos disponibles
	 * 
	 * parametros: ra
	 * cache: no
	 * filas: 1
	 */
	function get_fecha_maxima($parametros)
	{
		$ra = $parametros['ra'];
		$where_ra = $this->get_where_ra($ra);

		$sql = "SELECT 	
					MAX(fecha) as fecha
				FROM 	
					pre_turnos
				WHERE 
					id_preinscripcion IS NULL
					AND persona IS NULL
					$where_ra
				";

		$res = kernel::db()->consultar_fila($sql);
		if (!empty($res['fecha'])) {
			$salida = convertir_fecha_formato_frontend($res['fecha']);
		} else {
			$salida = '';
		}
		return $salida;
	}

	/**
	 * Obtiene las franjas horarias en las que hay turnos disponibles para una 
	 * RA dada en una determinada fecha
	 * 
	 * parametros: fecha, ra
	 * no_quote: fecha
	 * cache: no
	 * filas: n
	 */
	function get_franjas_horarias_disponibles($parametros)
	{
		$fecha = kernel::db()->quote(convertir_fecha_formato_base($parametros['fecha']));
		$ra = $parametros['ra'];
		$where_ra = $this->get_where_ra($ra);

		$sql = "SELECT 	
					DISTINCT EXTRACT(HOUR FROM hora) AS hora
				FROM 	
					pre_turnos
				WHERE 	
					fecha = $fecha
					AND id_preinscripcion IS NULL
					AND persona IS NULL
					AND (
						fecha > CURRENT_DATE
						OR (
							fecha = CURRENT_DATE
							AND hora >= CURRENT_TIME
						)
					)
					$where_ra
				ORDER BY hora
				";

		$datos = kernel::db()->consultar($sql);
		foreach ($datos as $key => $dato) {
			$hora = $dato['hora'];
			$hora_sig = $hora + 1;

			$hora_str = ($hora < 10) ? '0' . $hora : $hora;
			$hora_sig_str = ($hora_sig < 10) ? '0' . $hora_sig : $hora_sig;

			$datos[$key]['descr'] = "De $hora_str:00 a $hora_sig_str:00";
		}

		return util_form::armar_combo_opciones($datos, 'hora', 'descr', true, false);
	}

	/**
	 * Asigna un turno según la solicitud (fecha, rango horario, RA)
	 * 
	 * parametros: persona, fecha, ra, franja
	 * no_quote: fecha, franja
	 * cache: no
	 */
	function reservar_turno($parametros)
	{
		$persona = $parametros['persona'];
		$ra = $parametros['ra'];
		$fecha = $parametros['fecha'];
		$franja = $parametros['franja'];
		
		$pre_turno = $this->asignar_turno($fecha, $franja, $ra);

		if (empty($pre_turno)) {
			$mensaje = kernel::traductor()->trans('sel_turno.error_asignacion_turno');
			klog2("-- TURNOS ERROR --", $mensaje);
			return false;
		}

		$sql = "UPDATE 	pre_turnos  
				SET 	persona = $persona,
						responsable_academica = $ra
				WHERE 	pre_turno = $pre_turno;
				";

		kernel::db()->ejecutar($sql);
		return true;
	}

	/**
	 * Deja nuevamente disponible un turno solicitado por un aspirante, para una
	 * RA dada.
	 * 
	 * parametros: persona, ra
	 * cache: no
	 */
	function devolver_turno($parametros)
	{

		$sql = "UPDATE 	pre_turnos  
                        SET     id_preinscripcion = NULL,
                                persona = NULL,
                                responsable_academica = NULL
                        WHERE 	persona = {$parametros['persona']}
                        AND     responsable_academica = {$parametros['ra']};
				";

		kernel::db()->ejecutar($sql);
	}

	/**
	 * Determina qué turno de presentación(en caso que haya sido solicitado) de
	 * un aspirante en una RA dada.
	 * 
	 * parametros: persona, ra
	 * cache: no
	 * filas: 1
	 */
	function get_turno_presentacion($parametros)
	{
            $sql = "SELECT
                                    pre_turnos.pre_turno,
                                    pre_turnos.turno_config,
                                    pre_turnos.id_preinscripcion,
                                    pre_turnos.persona,
                                    pre_turnos.responsable_academica,
                                    pre_turnos.operador,
                                    pre_turnos.fecha,
                                    pre_turnos.hora,
                                    pre_turnos.recordatorio_enviado
                            FROM
                                    pre_turnos
                            WHERE
                                    pre_turnos.persona = {$parametros['persona']}
                                    AND pre_turnos.responsable_academica = {$parametros['ra']}
                                    AND pre_turnos.fecha >= CURRENT_DATE
                            ";

		return kernel::db()->consultar_fila($sql);
	}

	protected function get_where_ra($ra)
	{
		return " AND EXISTS (
						SELECT	1
						FROM	pre_turnos_config_ra 
						WHERE	pre_turnos_config_ra.turno_config = pre_turnos.turno_config
								AND pre_turnos_config_ra.responsable_academica = $ra
					)";
	}

	protected function asignar_turno($fecha, $franja, $ra)
	{

		try{
			
			kernel::db()->abrir_transaccion();
			$fecha = kernel::db()->quote(convertir_fecha_formato_base($fecha));
			$where_ra = $this->get_where_ra($ra);
			
			$sql = "SELECT 	
						pre_turno
					FROM
						pre_turnos
					WHERE 		
						id_preinscripcion IS NULL
						AND persona IS NULL
						AND fecha = $fecha
						AND EXTRACT(HOUR FROM hora) = $franja
						AND (
							fecha > CURRENT_DATE
							OR (
								fecha = CURRENT_DATE
								AND hora >= CURRENT_TIME
							)
						)
						$where_ra
					ORDER BY 	
						hora,
						operador
					LIMIT 1
					";

			$res = kernel::db()->consultar_fila($sql);
			if (!empty($res)) {
				kernel::db()->cerrar_transaccion();
				return $res['pre_turno'];
			}
			kernel::db()->abortar_transaccion();
			return false;
			
		} catch (\Exception $e) {
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
		}
		
	}

        /**
	 * Determina si existen turnos vigentes para una preinscripción dada
	 *
	 * parametros: responsable_academica, ubicacion, modalidad
         * no_quote: responsable_academica, ubicacion
	 * cache: no
	 * filas: 1
	 */
	function hay_turnos_preinscripcion_propuesta($parametros)
	{
            $sql = "SELECT COUNT(pre_turnos.pre_turno) AS cant_turnos
                    FROM pre_turnos_config
                    JOIN pre_turnos_config_ra           ON (pre_turnos_config.turno_config = pre_turnos_config_ra.turno_config)
                    JOIN pre_turnos_config_ubicacion    ON (pre_turnos_config.turno_config = pre_turnos_config_ubicacion.turno_config)
                    JOIN pre_turnos_config_modalidad    ON (pre_turnos_config.turno_config = pre_turnos_config_modalidad.turno_config)
                    JOIN pre_turnos                     ON (pre_turnos_config.turno_config = pre_turnos.turno_config)
                    WHERE pre_turnos_config_ra.responsable_academica = {$parametros['responsable_academica']}
                    AND pre_turnos_config_ubicacion.ubicacion = {$parametros['ubicacion']}
                    AND pre_turnos_config_modalidad.modalidad = {$parametros['modalidad']}
                    -- Turnos de presentación de documentación vigentes
                    AND ((pre_turnos.fecha > CURRENT_DATE) OR (pre_turnos.fecha = CURRENT_DATE AND pre_turnos.hora >= CURRENT_TIME))
                    -- Turnos no tomados desde Preinscripción
                    AND pre_turnos.id_preinscripcion IS NULL
                    -- Turnos no tomados desde Autogestión
                    AND pre_turnos.persona IS NULL
                    ";

            $fila = kernel::db()->consultar_fila($sql);
            return $fila['cant_turnos'] > 0;
	}
	/**
	 * campiar estado de preinscripcion para visualizar en gestion cambia a pendiente 
	 * 
	 * parametros: preinscripcion
         * no_quote: estado
         * cache: no
	 */
    function cambiar_estado_preinscripcion_3w($parametros) {
        $preinscripcion = $parametros['preinscripcion'];
        $estado = $parametros['estado'];
        if ($estado == preinscripcion::insc_pendiente) {
            $estado = preinscripcion::insc_nueva;
        } elseif ($estado == preinscripcion::insc_nueva) {
            $estado = preinscripcion::insc_pendiente;
        }else{
            $estado = preinscripcion::insc_nueva;
        }
        $sql = "UPDATE 	sga_preinscripcion_propuesta  
				SET 	estado = '" . $estado . "'
				WHERE 	preinscripcion = $preinscripcion;
				";

        kernel::db()->ejecutar($sql);

        return $estado;
    }
}
