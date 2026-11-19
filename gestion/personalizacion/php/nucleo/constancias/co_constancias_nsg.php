<?php

/**
 *
 * 
 * @package 	Constancias y Certificados
 * 
 */
class co_constancias_nsg extends co_constancias
{
	/** Newsol - Se agregan los campos de la tabla extendida de constancias: si es cobrable y el concepto correspondiente para facturar
	 * Obtiene un listado de constancias, seg�n el filtro recibidos
	 */
	function get_listado_constancias($where = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}

		$sql = "SELECT		sga_constancias.constancia,
							sga_constancias.nombre,
							sga_constancias.descripcion,
							sga_constancias.max_cant_solicitudes,
							sga_constancias.max_cant_copias,
							sga_constancias.objeto,
							sga_constancias_formularios.nombre AS formulario_nombre,
							sga_constancias.plantilla,
							mce_plantillas.nombre AS plantilla_nombre,
							CASE sga_constancias.permite_descargar
								WHEN true THEN 'S�'
								WHEN false THEN 'No'
							END AS permite_descargar_descr,
							sga_constancias.permite_descargar,
							sga_constancias.vigencia,
							sga_constancias.activo,
							CASE sga_constancias.activo
								WHEN '" . comunes::si . "' THEN 'Activo'
								WHEN '" . comunes::no . "' THEN 'No Activo'
							END AS activo_descr,
							sga_constancias.exige_aprobacion,
                            CASE sga_constancias.disponible_autogestion
								WHEN '" . comunes::si . "' THEN '" . comunes::si_desc . "'
								WHEN '" . comunes::no . "' THEN '" . comunes::no_desc . "'
							END AS disponible_autogestion_descr,
                            sco.cobrable,
							sco.concepto,
							sco.requiere_documento,
							sco.requiere_documento_desc
				FROM			sga_constancias
							JOIN mce_plantillas ON sga_constancias.plantilla = mce_plantillas.plantilla
							LEFT JOIN sga_constancias_formularios ON sga_constancias.objeto = sga_constancias_formularios.objeto
							LEFT JOIN negocio_nsg.sga_constancias_ext sco ON sga_constancias.constancia = sco.id_constancia
				$where
				ORDER BY	sga_constancias.nombre
		";

		return guarani_db::consultar($sql);
	}

	/**Newsol - Se agregan los campos de la tabla extendida de constancias y de solicitudes de constancias: si es cobrable la constancia y/o la solicitud
	 * Obtiene un listado de constancias solicitadas seg�n filtros recibidos
	 */
	function get_listado_const_solicitadas($where = '', $order = '', $params_sistema = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		if (!$order) {
			$order = 'sga_constancias.nombre';
		}

		// toba::consulta_php('co_facturacion')->actualizar_estado_pago_constancias();

		$apellido_y_nombre = persona::get_param_nombre_persona_doc_oficiales();

		$sql = "SELECT		sga_constancias_solicitud.solicitud,
							sga_constancias_solicitud.constancia,
							sga_constancias.nombre AS constancia_nombre,
							$apellido_y_nombre	AS apellido_y_nombre,
							vw_personas.apellido_elegido 	AS apellido_elegido,
							vw_personas.nombres_elegido		AS nombres_elegido,
							vw_personas.nro_documento,
							vw_personas.tipo_nro_documento AS documento_principal,
							vw_personas.persona,
							sga_alumnos.propuesta,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS propuesta_nombre,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_planes_versiones.plan,
							sga_planes.nombre AS plan_nombre,
							sga_alumnos.plan_version,
							sga_planes_versiones.nombre AS plan_version_nombre,
							sga_alumnos.ubicacion,
							sga_ubicaciones.nombre AS ubicacion_nombre,
							sga_constancias_solicitud.fecha_solicitud,
							to_char(sga_constancias_solicitud.fecha_solicitud, '" . comunes::formato_fecha_visual . "') as fecha_solicitud_formateada,
							sga_constancias_solicitud.cant_copias_solicitadas,
							CASE 
								WHEN sga_constancias_solicitud.estado = '" . constancias::estado_solicitud_impreso . "' THEN sga_constancias_solicitud.fecha_proceso
							END AS fecha_impresion,
							CASE
								WHEN sga_constancias_solicitud.estado = '" . constancias::estado_solicitud_rechazado . "' THEN sga_constancias_solicitud.fecha_proceso
							END AS fecha_rechazo,
							sga_constancias_solicitud.fecha_entrega,
							sga_constancias_solicitud.presentar_a,
							sga_constancias_solicitud.observaciones,
							sga_constancias_solicitud.estado,
							sga_constancias_solicitud.alumno,
							sga_constancias_solicitud.fecha_fin_vigencia,
							sga_constancias_solicitud.nro_transaccion,
							sga_constancias_solicitud.codigo_verificacion,
							acc_interfaces.interfaz,
							acc_interfaces.nombre as interfaz_nombre,
							sga_constancias.permite_descargar,
							negocio_nsg.sga_constancias_solicitud_ext.cobrable as solic_cobrable,
							negocio_nsg.sga_constancias_ext.cobrable as const_cobrable,
							negocio_nsg.sga_inscripciones_motivos_baja.nombre as motivo_baja_desc,
							negocio_nsg.nsg_tipo_beneficio.descripcion as tipo_beneficio_desc,
							(select a.nombre || ' ('|| a.codigo || ')' from sga_elementos a, sga_comisiones b where a.elemento = b.elemento and b.comision = negocio_nsg.sga_constancias_solicitud_ext.comision) as nombre_elemento_baja,
							negocio_nsg.sga_constancias_solicitud_ext.pago	
					FROM		sga_constancias_solicitud
							LEFT JOIN negocio_nsg.sga_constancias_solicitud_ext ON sga_constancias_solicitud.solicitud = negocio_nsg.sga_constancias_solicitud_ext.id_solicitud
							JOIN sga_constancias ON sga_constancias.constancia = sga_constancias_solicitud.constancia
							LEFT JOIN negocio_nsg.sga_constancias_ext ON sga_constancias.constancia = negocio_nsg.sga_constancias_ext.id_constancia 
							LEFT JOIN negocio_nsg.sga_inscripciones_motivos_baja ON negocio_nsg.sga_inscripciones_motivos_baja.motivo_baja = negocio_nsg.sga_constancias_solicitud_ext.motivo_baja
							LEFT JOIN negocio_nsg.nsg_tipo_beneficio ON negocio_nsg.nsg_tipo_beneficio.tipo_beneficio = negocio_nsg.sga_constancias_solicitud_ext.tipo_beneficio
							JOIN sga_alumnos ON sga_alumnos.alumno = sga_constancias_solicitud.alumno
							JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
							JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
							JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
							JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
							JOIN sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
							JOIN acc_interfaces ON sga_constancias_solicitud.interfaz = acc_interfaces.interfaz
							JOIN mdp_personas ON mdp_personas.persona = sga_alumnos.persona
			    $where
				ORDER BY	$order
		";
		return guarani_db::consultar($sql);
	}

	function get_listado_const_solicitadas2($where = '', $order = '', $where_base = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		if (!$order) {
			$order = 'sga_constancias.nombre';
		}

		//toba::consulta_php('co_facturacion')->actualizar_estado_pago_constancias2($where_base);

		$apellido_y_nombre = persona::get_param_nombre_persona_doc_oficiales();

		$sql = "SELECT		sga_constancias_solicitud.solicitud,
							sga_constancias_solicitud.constancia,
							sga_constancias.nombre AS constancia_nombre,
							$apellido_y_nombre	AS apellido_y_nombre,
							vw_personas.apellido_elegido 	AS apellido_elegido,
							vw_personas.nombres_elegido		AS nombres_elegido,
							vw_personas.nro_documento,
							vw_personas.tipo_nro_documento AS documento_principal,
							vw_personas.persona,
							sga_alumnos.propuesta,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS propuesta_nombre,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_planes_versiones.plan,
							sga_planes.nombre AS plan_nombre,
							sga_alumnos.plan_version,
							sga_planes_versiones.nombre AS plan_version_nombre,
							sga_alumnos.ubicacion,
							sga_ubicaciones.nombre AS ubicacion_nombre,
							sga_constancias_solicitud.fecha_solicitud,
							to_char(sga_constancias_solicitud.fecha_solicitud, '" . comunes::formato_fecha_visual . "') as fecha_solicitud_formateada,
							sga_constancias_solicitud.cant_copias_solicitadas,
							CASE 
								WHEN sga_constancias_solicitud.estado = '" . constancias::estado_solicitud_impreso . "' THEN sga_constancias_solicitud.fecha_proceso
							END AS fecha_impresion,
							CASE
								WHEN sga_constancias_solicitud.estado = '" . constancias::estado_solicitud_rechazado . "' THEN sga_constancias_solicitud.fecha_proceso
							END AS fecha_rechazo,
							sga_constancias_solicitud.fecha_entrega,
							sga_constancias_solicitud.presentar_a,
							sga_constancias_solicitud.observaciones,
							sga_constancias_solicitud.estado,
							sga_constancias_solicitud.alumno,
							sga_constancias_solicitud.fecha_fin_vigencia,
							sga_constancias_solicitud.nro_transaccion,
							sga_constancias_solicitud.codigo_verificacion,
							acc_interfaces.interfaz,
							acc_interfaces.nombre as interfaz_nombre,
							sga_constancias.permite_descargar,
							negocio_nsg.sga_constancias_solicitud_ext.cobrable as solic_cobrable,
							negocio_nsg.sga_constancias_ext.cobrable as const_cobrable,
							negocio_nsg.sga_inscripciones_motivos_baja.nombre as motivo_baja_desc,
							negocio_nsg.nsg_tipo_beneficio.descripcion as tipo_beneficio_desc,
							(select a.nombre || ' ('|| a.codigo || ')' from sga_elementos a, sga_comisiones b where a.elemento = b.elemento and b.comision = negocio_nsg.sga_constancias_solicitud_ext.comision) as nombre_elemento_baja,
							case when fpc.estado_pago = 3 then 'S' else 'N' end as pago
							--negocio_nsg.sga_constancias_solicitud_ext.pago	
					FROM		sga_constancias_solicitud
							LEFT JOIN negocio_nsg.sga_constancias_solicitud_ext ON sga_constancias_solicitud.solicitud = negocio_nsg.sga_constancias_solicitud_ext.id_solicitud
							JOIN sga_constancias ON sga_constancias.constancia = sga_constancias_solicitud.constancia
							LEFT JOIN negocio_nsg.sga_constancias_ext ON sga_constancias.constancia = negocio_nsg.sga_constancias_ext.id_constancia 
							LEFT JOIN negocio_nsg.sga_inscripciones_motivos_baja ON negocio_nsg.sga_inscripciones_motivos_baja.motivo_baja = negocio_nsg.sga_constancias_solicitud_ext.motivo_baja
							LEFT JOIN negocio_nsg.nsg_tipo_beneficio ON negocio_nsg.nsg_tipo_beneficio.tipo_beneficio = negocio_nsg.sga_constancias_solicitud_ext.tipo_beneficio
							left join negocio_nsg.nsg_facturacion_pagos_conceptos fpc on fpc.alumno = sga_constancias_solicitud.alumno and sga_constancias_solicitud.solicitud = cast(fpc.id_referencia as integer)
							JOIN sga_alumnos ON sga_alumnos.alumno = sga_constancias_solicitud.alumno
							JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
							JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
							JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
							JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
							JOIN sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
							JOIN acc_interfaces ON sga_constancias_solicitud.interfaz = acc_interfaces.interfaz
							JOIN mdp_personas ON mdp_personas.persona = sga_alumnos.persona
			    $where
				ORDER BY	$order
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * 23/12/2025
	 * Newsol - Joaquin
	 * get_constancia_requiere_documento_info	
	 * Obtiene si una constancia requiere documento adjunto y la descripci��n del mismo
	 */
	function get_constancia_requiere_documento_info($parametros)
	{
		$sql = "SELECT ce.requiere_documento,
						ce.requiere_documento_desc
				FROM negocio_nsg.sga_constancias_ext ce
				WHERE ce.id_constancia = {$parametros['constancia']}";
		return guarani_db::consultar_fila($sql);
	}

	
	/**
	 * 23/12/2025
	 * Newsol - Joaquin
	 * get_constancia_documento_seq	
	 * Obtiene el valor actual de la secuencia de constancia_documento
	 */
	function get_constancia_documento_seq()
	{
		$sql = "SELECT last_value as constancia_documento from negocio_nsg.sga_constancias_documentos_constancia_documento_seq;";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * 23/12/2025
	 * Newsol - Joaquin
	 * get_documento_constancia_x_solicitud	
	 * Obtiene el id del documento asociado a la constancia de una solicitud
	 */
	function get_documento_constancia_x_solicitud($solicitud)
	{
		$sql = "SELECT cd.id_documento
				from negocio_nsg.sga_constancias_documentos_solicitudes cds
				join negocio_nsg.sga_constancias_documentos cd on cds.id_constancia_documento = cd.constancia_documento
				WHERE cds.solicitud = $solicitud
				ORDER BY cd.fecha_subida DESC
				LIMIT 1;";
		$result = guarani_db::consultar_fila($sql);
		return $result['id_documento'];
	}

	/**
	 * 29/12/2025
	 * Newsol - Joaquin
	 * Obtiene un listado de constancias solicitadas seg�n filtros recibidos
	 */

	function get_constancias_activas_autogestion($propuesta = null)
	{   
		// Listado de constancias disponibles para solicitud por el alumno en autogestion.
		$constancia_alumno_regular = \constancias::constancia_de_alumno_regular;
		//$si = kernel::db()->quote(\comunes::si);
		$where_ra = '';
		if (!is_null($propuesta)) {
			//$propuesta = kernel::db()->quote($parametros['propuesta']);
			$where_ra = "AND c.constancia IN (SELECT DISTINCT sga_constancias_ra.constancia
											FROM sga_constancias_ra
											JOIN sga_propuestas_ra ON (sga_constancias_ra.responsable_academica = sga_propuestas_ra.responsable_academica)
											WHERE sga_propuestas_ra.propuesta = {$propuesta})";
		}

		$sql = "SELECT c.constancia, 
						c.nombre,
						c.objeto,
						case when ce.requiere_documento is not null then 'S' else 'N' end as requiere_documento,
						case when ce.requiere_documento_desc is not null then ce.requiere_documento_desc else '' end as requiere_documento_desc
				FROM sga_constancias c
				LEFT JOIN negocio_nsg.sga_constancias_ext ce ON c.constancia = ce.id_constancia AND ce.requiere_documento = 'S'
		        WHERE c.activo = 'S'
					--AND (objeto IS NULL OR constancia = $constancia_alumno_regular)  -- Esta condicion hay que sacarla cuando se implemente los formularios de entrada de datos
					AND c.disponible_autogestion = 'S'
					$where_ra
		        ORDER BY nombre
				";
		return guarani_db::consultar($sql);
	}
}
