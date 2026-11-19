<?php

namespace nsg01\modelo\datos\db;

use SIU\Chulupi\kernel;
use siu\modelo\_comun\comunes;
use propuesta;
use siu\modelo\datos\catalogo;
use siu\extension_kernel\formularios\guarani_form_elemento as util_form;
use siu\modelo\_comun\clienteRDI;
use siu\modelo\datos\db\preinscripcion as preinscripcion_nsg;

class preinscripcion extends preinscripcion_nsg
{

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
						(case sga_propuestas.propuesta when 2 then '1° Ciclo Lic. en Instrumentación Quirúrgica' 
					when 32 then '1° Ciclo Lic. en Enfermería' 
					when 1 then '2° Ciclo Lic. en Enfermería' 
					when 35 then '2° Ciclo Lic. en Instrumentación Quirúrgica' else sga_propuestas.nombre end) as nombre
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
					END AS vencido,
					sga_propuestas.propuesta_tipo
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


}
