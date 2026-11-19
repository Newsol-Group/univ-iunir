<?php
/**
 *
 * 
 * @package Egresados
 */
class co_tramites_certificacion_nsg extends co_tramites_certificacion
{

	/** Newsol (07/2025) Se agregó el campo pago para saber si el alumno abonó el trámite del título (Analítico + Diploma)
	 * Obtiene un listado de trámites iniciados para solicitar la obtención de una certificacón.
	 *
	 */
	function get_listado($where = '', $order = '')
	{
		$estados_requisitos = $this->get_opciones_estados_requisitos();

		if ($where) {
			$where = " WHERE $where";
		}

		if ($order) {
			$order = "ORDER BY $order";
		} else {
			$order = "ORDER BY vw_personas.apellido, vw_personas.nombres";
		}

		toba::consulta_php('co_facturacion')->actualizar_estado_pago_certificacion_egreso();

		$orden_tipo_email = persona::orden_tipo_email();
		$orden_tipo_telefono = persona::orden_tipo_telefono();
		$param_nombre_persona_doc_oficiales = persona::get_param_nombre_persona_doc_oficiales();
		// Listado de alumnos que obtuvieron una certificación	
		$sql = "SELECT
					sga_certificados_otorg.nro_solicitud,
					sga_certificados_otorg.certificado,
					vw_personas.persona,
					$param_nombre_persona_doc_oficiales		AS apellido_y_nombres,
					vw_personas.desc_tipo_documento,
					vw_personas.nro_documento,
					vw_personas.tipo_nro_documento 	AS documento_principal,
					CASE vw_personas.sexo
					   WHEN '".persona::sexo_femenino."' THEN COALESCE(sga_certificados.nombre_femenino, sga_certificados.nombre)
					   WHEN '".persona::sexo_no_binario."' THEN COALESCE(sga_certificados.nombre_no_binario, sga_certificados.nombre)
					   ELSE sga_certificados.nombre
					END as certificado_nombre,
					sga_planes.nombre 			AS plan_nombre,
					sga_juramentos.descripcion_abreviada 	AS juramento_descripcion,
					sga_propuestas.nombre 			AS propuesta_nombre,
					sga_propuestas.nombre_abreviado 	AS propuesta_nombre_abrv,
					sga_certificados_otorg.nro_expediente,
					sga_certificados_otorg.nro_expediente_descr,
					sga_certificados_otorg.fecha_inicio_tramite,
					sga_certificados_otorg.promedio,
					sga_certificados_otorg.promedio_sin_aplazos,
					sga_certificados_otorg.estado,
					CASE 
					   WHEN sga_certificados_otorg.anulado = 1 THEN 'Anulado' 
					   ELSE mce_estados.nombre 
					END AS estado_nombre,
					CASE 
					   WHEN sga_certificados_otorg.anulado = 1 THEN sga_certificados_otorg.fecha_anulacion
					   ELSE sga_certificados_otorg.fecha_cambio_estado
					END as fecha_cambio_estado,
					sga_certificados_otorg.fecha_egreso,
					sga_certificados_otorg.fecha_anulacion,
					sga_propuestas.propuesta,
					get_fecha_ingreso_alumno(sga_certificados_otorg.alumno,NULL) as fecha_ingreso,
					(SELECT mdp_personas_contactos.email
					   FROM mdp_personas_contactos
					  WHERE mdp_personas_contactos.persona = vw_personas.persona 
					    AND mdp_personas_contactos.contacto_tipo IN ('" . persona::mail_primario . "','" . persona::mail_secundario . "','" . persona::mail_institucional . "') 
					    AND mdp_personas_contactos.email IS NOT NULL
					  ORDER BY $orden_tipo_email
					  LIMIT 1
					 ) as email,
					(SELECT mdp_personas_contactos.telefono_codigo_area || '|' || mdp_personas_contactos.telefono_numero
					   FROM mdp_personas_contactos
					  WHERE mdp_personas_contactos.persona = vw_personas.persona 
					    AND mdp_personas_contactos.contacto_tipo IN ('" . persona::tel_celular . "','" . persona::tel_familiar . "','" . persona::tel_fijo . "','" . persona::tel_laboral . "') 
					    AND mdp_personas_contactos.telefono_codigo_area IS NOT NULL 
					    AND mdp_personas_contactos.telefono_numero IS NOT NULL
					 ORDER BY $orden_tipo_telefono
					 LIMIT 1
					 ) as telefono,
                                        COALESCE(mce_estados.nombre,'') AS estado_nombre,
                                        sga_certificados_otorg.interfaz,
                                        acc_interfaces.nombre AS descripcion_interfaz,
					 sga_certificados_otorg.estado_requisitos,
					 ".guarani_sql::get_case('sga_certificados_otorg', 'estado_requisitos', 'estado_requisitos_descr', $estados_requisitos).",
					negocio_nsg.sga_certificados_otorg_ext.pago

				FROM sga_certificados_otorg
				JOIN negocio_nsg.sga_certificados_otorg_ext ON sga_certificados_otorg.nro_solicitud = negocio_nsg.sga_certificados_otorg_ext.id_solicitud
				INNER JOIN sga_certificados		ON sga_certificados_otorg.certificado = sga_certificados.certificado
				INNER JOIN vw_personas			ON sga_certificados_otorg.persona = vw_personas.persona
				INNER JOIN mdp_personas			ON sga_certificados_otorg.persona = mdp_personas.persona
				INNER JOIN sga_planes_versiones	ON sga_planes_versiones.plan_version = sga_certificados_otorg.plan_version
				INNER JOIN sga_planes			ON sga_planes.plan = sga_planes_versiones.plan
				INNER JOIN sga_propuestas		ON sga_propuestas.propuesta = sga_planes.propuesta
				LEFT JOIN sga_juramentos		ON sga_juramentos.juramento = sga_certificados_otorg.juramento
				LEFT JOIN sga_colaciones		ON sga_certificados_otorg.colacion = sga_colaciones.colacion
				LEFT JOIN mce_estados			ON mce_estados.estado = sga_certificados_otorg.estado
				LEFT JOIN acc_interfaces ON sga_certificados_otorg.interfaz = acc_interfaces.interfaz
                                $where
				$order
				";
		return guarani_db::consultar($sql);
	}

	// Newsol (07/2025) Se agregó el campo pago para saber si el alumno abonó el trámite del título (Analítico + Diploma)
	function get_tramites_por_estado($where)
	{
		if ($where) {
			$where = " WHERE $where";
		}

		toba::consulta_php('co_facturacion')->actualizar_estado_pago_certificacion_egreso();

		$sql = "SELECT	
					sga_certificados_otorg.nro_solicitud,
					vw_personas.apellido_nombres AS apellido_y_nombres,
					vw_personas.tipo_nro_documento AS documento_principal,
					sga_propuestas.nombre_abreviado AS propuesta_nombre_abrv,
					sga_certificados.nombre AS certificado_nombre,
					sga_certificados_otorg.nro_expediente,
					sga_certificados_otorg.fecha_inicio_tramite,
					sga_certificados_otorg.fecha_cambio_estado,
				    COALESCE(sga_certificados_otorg.estado,0) AS estado,
				    COALESCE(mce_estados.nombre,'') AS estado_nombre,
                                        sga_certificados_otorg.interfaz,
                                        acc_interfaces.nombre AS descripcion_interfaz,
					negocio_nsg.sga_certificados_otorg_ext.pago
				FROM sga_certificados_otorg
				JOIN negocio_nsg.sga_certificados_otorg_ext ON sga_certificados_otorg.nro_solicitud = negocio_nsg.sga_certificados_otorg_ext.id_solicitud
				JOIN vw_personas ON sga_certificados_otorg.persona = vw_personas.persona
				JOIN sga_certificados ON sga_certificados_otorg.certificado = sga_certificados.certificado
				JOIN sga_planes_versiones ON sga_certificados_otorg.plan_version = sga_planes_versiones.plan_version
				JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
				JOIN sga_propuestas ON sga_planes.propuesta = sga_propuestas.propuesta
				LEFT JOIN mce_estados ON mce_estados.estado = sga_certificados_otorg.estado
				LEFT JOIN acc_interfaces ON sga_certificados_otorg.interfaz = acc_interfaces.interfaz
				
				$where
				
				ORDER BY	mce_estados.nombre,
							vw_personas.apellido,
							vw_personas.nombres
		";
		
             
                return guarani_db::consultar($sql);
	}


	//Newsol (NUEVO) - Obtiene un listado de los alumnos egresados.
	function get_listado_egresados($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT
					sga_certificados_otorg.nro_solicitud,
					sga_certificados_otorg.certificado,
					vw_personas.apellido_nombres 			AS apellido_y_nombres,
					vw_personas.tipo_nro_documento 			AS documento_principal,
					CASE WHEN vw_personas.sexo = '".persona::sexo_femenino."' AND sga_certificados.nombre_femenino is not null
						THEN 
							sga_certificados.nombre_femenino
						ELSE
							sga_certificados.nombre
					END AS certificado_nombre,
					sga_planes.nombre 						AS plan_nombre,
					sga_juramentos.descripcion_abreviada 	AS juramento_descripcion,
					sga_propuestas.nombre_abreviado 		AS propuesta_nombre_abrv,
				    mce_estados.nombre 						AS estado_nombre,
				    sga_certificados_otorg.nro_expediente,
				    sga_certificados_otorg.fecha_inicio_tramite,
					sga_certificados_otorg.promedio,
				    sga_certificados_otorg.estado,
					sga_certificados_otorg.fecha_cambio_estado,
					sga_certificados_otorg.fecha_egreso,
					date_part('year', sga_certificados_otorg.fecha_egreso) as anio_academico,
					date(sga_alumnos_hist_calidad.fecha) as fecha_ingreso,
					coalesce((select nombre from sga_periodos where date(sga_alumnos_hist_calidad.fecha) between fecha_inicio and fecha_fin and periodo_generico in (2,3) limit 1),'PRIMER CUATRIMESTRE 2013') as periodo_ingreso,
					extract('day' from (sga_certificados_otorg.fecha_egreso-sga_alumnos_hist_calidad.fecha)/180)+1 as cant_cuatrimestres,				
					sga_propuestas.propuesta
				FROM
					sga_certificados_otorg
					LEFT JOIN sga_juramentos ON sga_juramentos.juramento = sga_certificados_otorg.juramento
					LEFT JOIN sga_colaciones ON sga_certificados_otorg.colacion = sga_colaciones.colacion
				    LEFT JOIN mce_estados ON mce_estados.estado = sga_certificados_otorg.estado,
					sga_certificados,
					vw_personas,
				    sga_planes_versiones,
				    sga_planes,
				    sga_alumnos_hist_calidad,
				    sga_propuestas

				WHERE
					sga_certificados_otorg.certificado = sga_certificados.certificado
				    AND	sga_certificados_otorg.persona = vw_personas.persona
				    AND sga_planes_versiones.plan_version = sga_certificados_otorg.plan_version
				    AND sga_planes.plan = sga_planes_versiones.plan
				    AND sga_propuestas.propuesta = sga_planes.propuesta
				    AND sga_alumnos_hist_calidad.alumno = sga_certificados_otorg.alumno
				    AND sga_alumnos_hist_calidad.calidad = 'A'
				    AND sga_alumnos_hist_calidad.motivo_calidad = 1

					$where

				ORDER BY
					vw_personas.apellido,
					vw_personas.nombres
				";
		return guarani_db::consultar($sql);
	}
}
?>