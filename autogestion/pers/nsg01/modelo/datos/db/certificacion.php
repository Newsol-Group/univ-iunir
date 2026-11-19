<?php

namespace nsg01\modelo\datos\db;

use SIU\Chulupi\kernel;
use siu\modelo\_comun\clienteRDI;
use siu\modelo\datos\db\certificacion as certificacion_nsg;

class certificacion extends certificacion_nsg {
	
	
	
	/**
	 * parametros: alumno
	 * no_quote: alumno
	 * cache: memoria
	 * filas: n
	 */
    function get_certificaciones_tramitadas_por_alumno($parametros)
    {
        $datos = \toba::consulta_php('co_certificados')->get_certificados_por_alumno($parametros['alumno']);
		return array_combine(array_column($datos, 'nro_solicitud'), $datos);
    }

	/**
     * parametros:	alumno, plan_version
	 * no_quote:	alumno, plan_version
     * cache: memoria
     * filas: n
     */
    function get_certificaciones_no_tramitadas_por_alumno($parametros)
    {
        return \toba::consulta_php('co_certificados')->get_certificados_no_tramitados_por_alumno($parametros['plan_version'], $parametros['alumno']);
    }
	
	/**
     * parametros:	plan_version
	 * no_quote:	plan_version
     * cache: memoria
     * filas: n
     */
    function get_certificados_plan_version($parametros)
    {
        return \toba::consulta_php('co_certificados')->get_certificados_plan_version($parametros['plan_version']);
    }
	
	/**
     * parametros: plan_version
     * cache: memoria
     * filas: n
     */
    function get_orientaciones_plan_version($parametros)
    {
		
		$subtipo_orientacion = kernel::db()->quote(\elemento::subtipo_orientacion);
		
        $sql = "
                -- Listado de orientaciones de la version del plan del alumno
				SELECT orie.elemento as orientacion,
						orie.nombre as orientacion_nombre
				  FROM vw_modulos_plan as orie
				WHERE orie.plan_version = {$parametros['plan_version']}
				  AND orie.entidad_subtipo = $subtipo_orientacion 
				ORDER BY orie.nombre
        ";

        return kernel::db()->consultar($sql);
    }
	
	/**
     * parametros: propuesta
     * cache: memoria
     * filas: n
     */
    function get_colaciones_por_propuesta($parametros)
    {
		/*
          Listado de colaciones con fecha igual o posterior a hoy.
		  Si existe fecha de inicio y fin de inscripcion y hora de inicio de inscripcion entonces solo se recuperan colaciones
		  con periodos de inscripciÃ³n vigente.
		*/
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
        
		$sql = "
				SELECT sga_colaciones.colacion, nombre, descripcion, to_char(fecha, $formato_fecha_visual) as fecha_colacion
				  FROM sga_colaciones
					   JOIN sga_colaciones_propuestas ON (sga_colaciones_propuestas.colacion = sga_colaciones.colacion)
				WHERE sga_colaciones.fecha >= CURRENT_DATE
				  AND (sga_colaciones.fecha_inicio_inscripcion IS NULL OR (CURRENT_DATE >= sga_colaciones.fecha_inicio_inscripcion))
				  AND (sga_colaciones.hora_inicio_inscripcion IS NULL OR (CURRENT_TIMESTAMP >= (sga_colaciones.fecha_inicio_inscripcion + sga_colaciones.hora_inicio_inscripcion)))
				  AND (sga_colaciones.fecha_fin_inscripcion IS NULL OR (CURRENT_DATE <= sga_colaciones.fecha_fin_inscripcion))
				  AND sga_colaciones_propuestas.propuesta = {$parametros['propuesta']}
				ORDER BY sga_colaciones.fecha
        ";

        return kernel::db()->consultar($sql);
    }
	
	/**
     * parametros:
     * cache: memoria
     * filas: n
     */
    function get_juramentos($parametros)
    {
        return \toba::consulta_php('co_juramentos')->get_juramentos();
    }
	
	
	/**
     * parametros: persona, cuil
     * cache: no
     * filas: 1
     */
    function insertar_cuil($parametros)
    {
		$cuil = kernel::db()->quote(\persona::doc_CUIL);
		$pais_argentina = kernel::db()->quote(\persona::pais_argentina);
		
        $sql = "
				INSERT INTO mdp_personas_documentos 
				(persona, pais_documento, tipo_documento, nro_documento)
				VALUES
				({$parametros['persona']}, $pais_argentina, $cuil, {$parametros['cuil']})
        ";

        return kernel::db()->ejecutar($sql);
    }
	
	/**
     * parametros: persona, cuil
     * cache: no
     * filas: 1
     */
    function actualizar_cuil($parametros)
    {
		$cuil = kernel::db()->quote(\persona::doc_CUIL);
		
        $sql = "
               UPDATE mdp_personas_documentos SET nro_documento = {$parametros['cuil']} 
			   WHERE persona = {$parametros['persona']} 
			   AND tipo_documento = $cuil
        ";

        return kernel::db()->ejecutar($sql);
    }
	
	/**
     * parametros: persona
     * cache: no
     * filas: 1
     */
    function borrar_cuil($parametros)
    {
		$cuil = kernel::db()->quote(\persona::doc_CUIL);
		
        $sql = "
               DELETE FROM mdp_personas_documentos 
			   WHERE persona = {$parametros['persona']} 
			   AND tipo_documento = $cuil
        ";

        return kernel::db()->ejecutar($sql);
    }
	
	
	/**
     * parametros: persona, certificado, alumno, plan_version, juramento, colacion
	 * param_null: juramento, colacion
	 * no_quote: alumno, certificado
     * cache: no
     * filas: 1
     */
    function registrar_solicitud($parametros)
    {

		$interfaz_autogestion = kernel::db()->quote(\comunes::interfaz_autogestion);
		
		// Se completan la fecha de egreso y los promedios.
		$fecha_egreso = \toba::consulta_php('co_alumnos')->get_fecha_egreso($parametros['alumno'], null, $parametros['certificado']);
		$promedio = \toba::consulta_php('co_alumnos')->get_promedio($parametros['alumno'], null, $parametros['certificado']);
		$promedio_sin_aplazos = \toba::consulta_php('co_alumnos')->get_promedio($parametros['alumno'], null, $parametros['certificado'], \comunes::no);
		
		$fecha_egreso			=	($fecha_egreso)?			kernel::db()->quote($fecha_egreso)			: 'null';
		$promedio				=	($promedio)?				kernel::db()->quote($promedio)				: 'null';
		$promedio_sin_aplazos	=	($promedio_sin_aplazos)?	kernel::db()->quote($promedio_sin_aplazos)	: 'null';
		
		$datos_certificado = \toba::consulta_php('co_certificados')->get_certificado($parametros['certificado']);
		$circuito_certificado = ($datos_certificado['circuito'])? $datos_certificado['circuito'] : 'null';
		
		$parametros['alumno'] = kernel::db()->quote($parametros['alumno']);
		$parametros['certificado'] = kernel::db()->quote($parametros['certificado']);
		
		$juramento = 'null';
		if(!is_null($parametros['juramento'])) $juramento = $parametros['juramento'];
		
		$colacion = 'null';
		if(!is_null($parametros['colacion'])) $colacion = $parametros['colacion'];

        $sql = "
                INSERT INTO sga_certificados_otorg (
							persona,
							certificado,
							alumno,
							plan_version,
							circuito,
							fecha_inicio_tramite,
							fecha_egreso,
							promedio,
							promedio_sin_aplazos,
							juramento,
							colacion,
							interfaz
							)
						VALUES(
							{$parametros['persona']},
							{$parametros['certificado']},
							{$parametros['alumno']},
							{$parametros['plan_version']},
							{$circuito_certificado},
							now(),
							{$fecha_egreso},
							{$promedio},
							{$promedio_sin_aplazos},
							{$juramento},
							{$colacion},
							{$interfaz_autogestion}
							)
        ";
							
		$datos = kernel::db()->ejecutar($sql);
        	$id_solicitud = kernel::db()->ultimo_insert_id('sga_certificados_otorg_seq');
 
		/*if ($datos > 0) {
	
			$sql = "INSERT INTO negocio_nsg.sga_certificados_otorg_ext (
					id_solicitud, pago) VALUES ($id_solicitud, 'N')";

			kernel::db()->ejecutar($sql);
		} */

        return $id_solicitud;
    }
	
	/**
     * parametros:	persona, propuesta
	 * no_quote:	persona, propuesta
     * cache: memoria
     * filas: n
     */
    function get_tramites_certificacion_by_persona_propuesta($parametros)
    {
		// Sólo solicitudes no anuladas.
		$where = "sga_certificados_otorg.anulado = 0";
		// De la persona y propuesta dada
		$where .= " AND vw_personas.persona = {$parametros['persona']}";
		$where .= " AND sga_propuestas.propuesta = {$parametros['propuesta']}";
		
		// Ordenados en forma descendente por fecha
		$order = 'sga_certificados_otorg.fecha_inicio_tramite DESC';
		
		return \toba::consulta_php('co_tramites_certificacion')->get_listado($where, $order);
    }
	
	/**
	 * parametros: nro_solicitud
	 * no_quote: nro_solicitud
	 * cache: memoria
	 * filas: n
	 */
    function get_documentos_asociados_tramite_certificacion($parametros)
    {
		$where = "sga_certificados_otorg_documentos.nro_solicitud = {$parametros['nro_solicitud']}";
		return \toba::consulta_php('co_tramites_certificacion')->get_documentos_asociados_tramite_certificacion($where);
    }

	/**
	 * Obtiene los requisitos de egreso de una persona en una solicitud
	 *
	 * parametros: nro_solicitud, estado_req_digital
	 * no_quote: nro_solicitud, estado_req_digital
	 * param_null: estado_req_digital
	 * cache: memoria
	 * filas: n
	 */
	function get_requisitos_by_solicitud($parametros)
	{

		$case_estado_req_digital = clienteRDI::case_estado_req_digital();
		$case_observaciones = clienteRDI::case_observaciones();

		$where_estado_req_digital = "";
		// Si se filtra por estado_req_digital
		if (is_array($parametros['estado_req_digital'])) {
			$estado_req_digital = implode("', '", $parametros['estado_req_digital']);
			$where_estado_req_digital = "AND {$case_estado_req_digital} IN ('{$estado_req_digital}')";
		}

		$sql = "
			SELECT
			sga_requisitos.requisito,
			sga_requisitos.nombre AS requisito_nombre,
			sga_requisitos.descripcion AS requisito_descripcion,
			COALESCE(sga_requisitos_digitales_draft.id_documento, sga_requisitos_digitales.id_documento) AS id_documento,
			sga_requisitos_digitales_draft.requisito_digital,
			$case_estado_req_digital AS estado_req_digital,
			sga_requisitos.tipo_documento_digital,
			$case_observaciones AS observaciones
			FROM
				sga_requisitos_certificado
				JOIN sga_certificados_otorg ON (sga_requisitos_certificado.nro_solicitud = sga_certificados_otorg.nro_solicitud)
				JOIN sga_requisitos ON (sga_requisitos_certificado.requisito = sga_requisitos.requisito)
				LEFT JOIN sga_requisitos_digitales_draft ON (
					sga_requisitos_digitales_draft.requisito = sga_requisitos_certificado.requisito
					AND sga_requisitos_digitales_draft.persona = sga_certificados_otorg.persona
				)
				LEFT JOIN sga_requisitos_presentados ON (
					sga_requisitos_presentados.requisito_presentado =
					(
						SELECT MAX(rp_aux.requisito_presentado)
						FROM sga_requisitos_presentados AS rp_aux
						WHERE rp_aux.requisito = sga_requisitos_certificado.requisito
						AND rp_aux.persona = sga_certificados_otorg.persona
					)
				)
				LEFT JOIN sga_requisitos_digitales ON (sga_requisitos_presentados.requisito_digital = sga_requisitos_digitales.requisito_digital)
				LEFT JOIN sga_requisitos_digitales_draft_detalle ON (
					sga_certificados_otorg.persona = sga_requisitos_digitales_draft_detalle.persona
					AND sga_requisitos.requisito = sga_requisitos_digitales_draft_detalle.requisito
				)
			WHERE sga_requisitos_certificado.nro_solicitud = {$parametros['nro_solicitud']}
			AND sga_requisitos.requisito_tipo = '".\requisito::tipo_documentales."'
			$where_estado_req_digital
			ORDER BY requisito_nombre
		";


		$datos = kernel::db()->consultar($sql);
		return array_combine(array_column($datos, 'requisito'), $datos);
	}
	
}
