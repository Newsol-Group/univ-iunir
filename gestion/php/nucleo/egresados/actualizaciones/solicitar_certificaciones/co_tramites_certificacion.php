<?php
/**
 *
 * 
 * @package Egresados
 */
class co_tramites_certificacion
{
	/**
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
					 ".guarani_sql::get_case('sga_certificados_otorg', 'estado_requisitos', 'estado_requisitos_descr', $estados_requisitos)."

				FROM sga_certificados_otorg
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

	function get_tramites_por_estado($where)
	{
		if ($where) {
			$where = " WHERE $where";
		}
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
                                        acc_interfaces.nombre AS descripcion_interfaz					
				FROM sga_certificados_otorg
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

	/**
	 * Obtiene un listado de cambios de estado para un trámite de solicitud de certificado.
	 *
	 */
	function get_cambios_estado($nro_solicitud)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		// La solicitud de certificado puede no tener aun cambios de estado. por eso el LEFT JOIN con mce_estados.
		$sql = "-- 1. Estado inicial, por si no tiene registro de cambios de estados.
				SELECT 
					'Inicio Trámite'  as accion_nombre,
				    CASE 
					   WHEN sga_certificados_otorg.estado IS NULL THEN 'Iniciado' 
					   ELSE mce_estados.nombre
					END as estado_nombre,   
					to_char(sga_certificados_otorg.fecha_alta, '".comunes::formato_fecha_visual."') as fecha_visual,
					sga_certificados_otorg.fecha_alta as fecha,
					0 as orden,
                    sga_certificados_otorg.fecha_alta as fecha_orden
				FROM sga_certificados_otorg
				LEFT JOIN mce_estados ON mce_estados.estado = sga_certificados_otorg.estado
				WHERE sga_certificados_otorg.nro_solicitud = $nro_solicitud
				  AND NOT EXISTS (SELECT 1 FROM sga_certificados_otorg_cmb as a WHERE a.nro_solicitud = $nro_solicitud)
				UNION 
				-- 2. Cambios de estados del trámite.
				SELECT 
					mce_acciones.nombre as accion_nombre,
				    mce_estados.nombre as estado_nombre,
					to_char(sga_certificados_otorg_cmb.fecha, '".comunes::formato_fecha_visual."') as fecha_visual,
					cast(sga_certificados_otorg_cmb.fecha as DATE) as fecha,
					1 as orden,
                    sga_certificados_otorg_cmb.fecha as fecha_orden
				FROM sga_certificados_otorg_cmb
				JOIN mce_acciones ON mce_acciones.accion = sga_certificados_otorg_cmb.accion
				JOIN mce_estados ON mce_estados.estado = sga_certificados_otorg_cmb.estado_nuevo
				WHERE sga_certificados_otorg_cmb.nro_solicitud = $nro_solicitud
				UNION 
				-- 3. Muestro anulación del trámite.
				SELECT 
					'Anulación' as accion_nombre,
					'Anulado' as estado_nombre,
					to_char(sga_certificados_otorg.fecha_anulacion, '".comunes::formato_fecha_visual."') as fecha_visual,
					sga_certificados_otorg.fecha_anulacion as fecha,
					2 as orden,
                    sga_certificados_otorg.fecha_anulacion as fecha_orden
				FROM sga_certificados_otorg
				WHERE sga_certificados_otorg.nro_solicitud = $nro_solicitud
				  AND sga_certificados_otorg.anulado = 1
				ORDER BY orden, fecha, fecha_orden 
				";
		$datos_query = guarani_db::consultar($sql);
		foreach ($datos_query as $dato) {
			unset($dato['fecha']);
			unset($dato['orden']);
			unset($dato['fecha_orden']);
			$datos[] = $dato;
		}
		return $datos;
	}

	/**
	 * Retorna el listado de alumnos de un plan-versión (con inscripciones aceptadas en un rango de años académicos) que no solicitaron un certificado dado.
	 *
	 * @param integer $plan_version
	 * @param integer $anio_desde
	 * @param integer $anio_hasta
	 * @param integer $certificado
	 * @return array
	 */
	function get_alumnos_plan_version_anios_certificado($plan_version, $anio_desde, $anio_hasta, $certificado)
	{
		$plan_version = toba::db()->quote($plan_version);
		$anio_desde = toba::db()->quote($anio_desde);
		$anio_hasta = toba::db()->quote($anio_hasta);
		$certificado = toba::db()->quote($certificado);
		$sql = "SELECT		sga_alumnos.alumno,
							sga_alumnos.persona,
							sga_alumnos.propuesta,
							vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento
				FROM		sga_alumnos,
							vw_personas,
							sga_propuestas_aspira,
							sga_situacion_aspirante
				WHERE		sga_alumnos.persona = vw_personas.persona AND
							sga_alumnos.persona = sga_propuestas_aspira.persona AND
							sga_alumnos.propuesta = sga_propuestas_aspira.propuesta AND
							sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp AND
							sga_alumnos.plan_version = $plan_version AND
							sga_alumnos.calidad = '" . alumno::calidad_activo . "' AND
							sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_aceptada . "' AND
							sga_propuestas_aspira.anio_academico BETWEEN $anio_desde AND $anio_hasta AND
							sga_alumnos.alumno NOT IN (	SELECT	sga_certificados_otorg.alumno
														FROM	sga_certificados_otorg
														WHERE	sga_certificados_otorg.certificado = $certificado AND
																sga_certificados_otorg.anulado = 0)
				ORDER BY	vw_personas.apellido,
							vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	function get_hist_acad_excluida($nro_solicitud)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);

		$sql = "SELECT	sga_certificados_ha_no_elementos.elemento
				FROM	sga_certificados_configurar_ha,
						sga_certificados_ha_no_elementos
				WHERE	sga_certificados_ha_no_elementos.certificado_ha = sga_certificados_configurar_ha.certificado_ha
						AND sga_certificados_configurar_ha.nro_solicitud = $nro_solicitud";

		$datos = guarani_db::consultar($sql);
		return aplanar_matriz($datos, 'elemento');
	}

	function get_datos_tramite($nro_solicitud)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$sql = "SELECT	sga_certificados_otorg.circuito,
						sga_certificados_otorg.estado,
						sga_certificados_otorg.estado_requisitos
				FROM	sga_certificados_otorg
				WHERE	sga_certificados_otorg.nro_solicitud = $nro_solicitud
		";
		return guarani_db::consultar_fila($sql);
	}
	
	function get_datos_tramite_todos($nro_solicitud)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$sql = "SELECT	*
				FROM	sga_certificados_otorg
				WHERE	sga_certificados_otorg.nro_solicitud = $nro_solicitud
		";
		return guarani_db::consultar_fila($sql);
	}
	
	function get_documentos_adjuntados_expediente_egreso($nro_solicitud)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$sql = "SELECT	mce_formularios.nombre AS documento,
						id_documento_digital,
						fecha_envio,
						sga_certificados_otorg_form.formulario
				FROM sga_certificados_otorg_form
				JOIN mce_formularios ON (sga_certificados_otorg_form.formulario = mce_formularios.formulario)
				WHERE sga_certificados_otorg_form.nro_solicitud = $nro_solicitud
				AND sga_certificados_otorg_form.id_documento_digital IS NOT NULL
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de trámites de egreso activos.
	 *
	 */
	function get_tramites_egreso_activos($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "
			SELECT
				sga_certificados_otorg.nro_solicitud,
				sga_alumnos.alumno,
				vw_personas.apellido_nombres			AS apellido_y_nombres,
				vw_personas.tipo_nro_documento			AS documento_principal,
				sga_propuestas.nombre					AS propuesta_nombre,
				CASE vw_personas.sexo
					WHEN '".persona::sexo_femenino."' THEN COALESCE(sga_certificados.nombre_femenino, sga_certificados.nombre)
					WHEN '".persona::sexo_no_binario."' THEN COALESCE(sga_certificados.nombre_no_binario, sga_certificados.nombre)
					ELSE sga_certificados.nombre
				END as certificado_nombre,
				sga_certificados_otorg.nro_expediente,
				mce_estados.nombre						AS estado_nombre,
				sga_certificados_otorg.fecha_cambio_estado,
				sga_planes_versiones.nombre				AS plan_nombre,
				(
					SELECT	count(anexo)
					FROM	sga_certificados_otorg_anexo
					WHERE	sga_certificados_otorg_anexo.nro_solicitud = sga_certificados_otorg.nro_solicitud
				)										AS cant_anexos
			FROM
				sga_certificados_otorg
				JOIN sga_alumnos			ON sga_alumnos.alumno = sga_certificados_otorg.alumno
				JOIN sga_certificados		ON sga_certificados.certificado = sga_certificados_otorg.certificado
				JOIN vw_personas			ON vw_personas.persona = sga_certificados_otorg.persona
				JOIN sga_planes_versiones	ON sga_planes_versiones.plan_version = sga_certificados_otorg.plan_version
				JOIN sga_planes				ON sga_planes.plan = sga_planes_versiones.plan
				JOIN sga_propuestas			ON sga_propuestas.propuesta = sga_planes.propuesta
				LEFT JOIN mce_estados		ON mce_estados.estado = sga_certificados_otorg.estado
			WHERE
				sga_certificados_otorg.anulado = 0
				$where
			ORDER BY
				fecha_cambio_estado DESC
			";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los tipos de suplementos al título
	 */
	function get_suplementos_tipos($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		
		$sql = "
			SELECT 	
				sga_suplemento_tipos.tipo_suplemento,
				sga_suplemento_tipos.descripcion		AS tipo_suplemento_nombre,
				sga_suplemento_tipos.metodo_php,
				sga_suplemento_tipos.tipo_suplemento	AS valor,
				sga_suplemento_tipos.descripcion		AS descr
  			FROM
				sga_suplemento_tipos
			$where
			ORDER BY tipo_suplemento;
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * 
	 * Retorna los datos correspondientes a un tipo de suplemento en formato RecordSet.
	 * Tiene en cuenta el caso de la carga masiva cuando el parámetro es un arreglo.
	 * 
	 * @param integer/array código de tipo de suplemento o arreglo de tipo/s de suplemento/es.
	 */
	function get_nombre_tipo_suplemento($tipo_suplemento)
	{
		if (is_array($tipo_suplemento)) {
			$filtro = guarani_sql::armar_condicion_in($tipo_suplemento, 'sga_suplemento_tipos', 'tipo_suplemento');
		} else {
			$tipo_suplemento = toba::db()->quote($tipo_suplemento);
			$filtro = "sga_suplemento_tipos.tipo_suplemento = $tipo_suplemento";
		}
		
		return $this->get_suplementos_tipos($filtro);
	}
	
	/**
	 * Retorna registros de la tabla sga_certificados_otorg_documentos.
	 */
	function get_documentos_asociados_tramite_certificacion($where = '')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		
		$sql = "
			SELECT 	
				sga_certificados_otorg_documentos.nro_solicitud,
				sga_certificados_otorg_documentos.tipo_documento,
				sga_certificados_otorg_documentos.id_documento_digital,
				sga_certificados_otorg_documentos.nro_tramite_sicer,
				sga_certificados_otorg_documentos.enviado_a_sicer
  			FROM
				sga_certificados_otorg_documentos
			$where;
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el Certificado Analítico o Diploma con sus identificadores del documento digital de SIU-Araí.
	 */
	function get_documento_digital_arai($nro_solicitud, $tipo_documento)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$tipo_documento = toba::db()->quote($tipo_documento);
		$where = "sga_certificados_otorg_documentos.nro_solicitud = $nro_solicitud";
		$where .= " AND sga_certificados_otorg_documentos.tipo_documento = $tipo_documento";
		
		$documentos_asociados = $this->get_documentos_asociados_tramite_certificacion($where);
		// Devuelvo el primer elemento del array
		return reset($documentos_asociados);
	}

	function solicitud_tiene_requisitos_egreso($nro_solicitud)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$sql = "SELECT	COUNT(sga_requisitos_certificado.requisito) as cant
				FROM	sga_requisitos_certificado
				WHERE	sga_requisitos_certificadO.nro_solicitud = $nro_solicitud
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}

	function cumple_requisitos_egreso($nro_solicitud, $where='')
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT	sga_requisitos_certificado.requisito
				FROM	sga_certificados_otorg
						JOIN sga_requisitos_certificado ON sga_certificados_otorg.nro_solicitud = sga_requisitos_certificado.nro_solicitud
				WHERE	sga_certificados_otorg.nro_solicitud = $nro_solicitud AND
						NOT f_requisito_egreso_presentado_exceptuado(sga_certificados_otorg.persona, sga_requisitos_certificado.requisito, CURRENT_DATE, sga_certificados_otorg.nro_solicitud)
						$where
		";
		$rs = guarani_db::consultar($sql);
		return empty($rs);
	}

	function get_opciones_estados_requisitos()
	{
		return array(	array('valor' => tramite_certificacion::estado_requisitos_abierto, 'descr' =>  tramite_certificacion::estado_requisitos_abierto_descr),
						array('valor' => tramite_certificacion::estado_requisitos_aceptado, 'descr' =>  tramite_certificacion::estado_requisitos_aceptado_descr),
						array('valor' => tramite_certificacion::estado_requisitos_pendiente, 'descr' =>  tramite_certificacion::estado_requisitos_pendiente_descr)
					);
	}

	function get_requisitos_egreso_adeudados($nro_solicitud)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);
		$sql = "SELECT	sga_requisitos_certificado.requisito,
						sga_requisitos.nombre as requisito_nombre
				FROM	sga_certificados_otorg
						JOIN sga_requisitos_certificado ON sga_certificados_otorg.nro_solicitud = sga_requisitos_certificado.nro_solicitud
						JOIN sga_requisitos ON sga_requisitos_certificado.requisito = sga_requisitos.requisito
				WHERE	sga_certificados_otorg.nro_solicitud = $nro_solicitud AND
						sga_certificados_otorg.anulado = 0 AND
						sga_requisitos_certificado.exceptuado = '".comunes::no."' AND
						NOT f_requisito_egreso_presentado_exceptuado(sga_certificados_otorg.persona, sga_requisitos_certificado.requisito, CURRENT_DATE, sga_certificados_otorg.nro_solicitud)
		";
		return guarani_db::consultar($sql);
	}
}
