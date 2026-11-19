<?php
/**
 * *
 * @package Cursadas
 * @subpackage Reportes
 */
class co_inscripciones_cursadas
{
	/**
	 * Retorna el listado de inscripciones a cursadas según el filtro especificado
	 */
	function get_listado($filtro = '', $solo_vigentes = false, $order = '')
	{
		$where = 'WHERE true';
		if ($filtro) {
			$where .= " AND $filtro";
		}
        
        
		if ($order) {
			$order = " ORDER BY $order";
		} else {
			$order = 'ORDER BY ubicacion, actividad, comision, apellido_y_nombre';
		}

		$where_vigentes = '';
		if ($solo_vigentes) {
			$where_vigentes = " AND sga_periodos.fecha_fin >= '" . guarani_fecha::get_hoy() . "'";
		}

		$where_gatillo = ' AND true';
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (!empty($perfil_datos)) {
			$where_gatillo = ' AND ' . toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_elementos', 'sga_elementos');
		}

		$sql = "SELECT		sga_ubicaciones.nombre			AS ubicacion,
					'(' || sga_elementos.codigo || ') ' || sga_elementos_plan.nombre as actividades,
      					vw_personas.apellido_nombres 	AS apellido_y_nombre,
					vw_personas.tipo_nro_documento 	AS documento_principal,
        				sga_insc_cursada.inscripcion,
        				sga_insc_cursada.nro_transaccion,
        				sga_alumnos.alumno,
        				sga_alumnos.legajo,
        				sga_alumnos.persona,
					sga_alumnos.propuesta,
					sga_elementos.elemento,
					sga_periodos.periodo,
					sga_insc_cursada.plan_version,
					sga_insc_cursada.comision			AS id_comision,
        				sga_comisiones.nombre				AS comision,
					sga_turnos_cursadas.nombre			AS turno,
            				sga_catedras.nombre					AS catedra,
					sga_insc_cursada.estado,
        				sga_inscripciones_estados.nombre	AS estado_inscr,
					sga_insc_cursada.fecha_inscripcion	AS fecha,
					to_char(sga_insc_cursada.fecha_inscripcion,'" . comunes::formato_fecha_hora_visual . "') AS fecha_inscripcion,
					to_char(sga_insc_cursada.fecha_inscripcion,'" . comunes::formato_fecha_visual . "') 	 AS fecha_inscripcion_visual,
        				sga_periodos.nombre 				AS periodo_nombre,
        				sga_periodos.anio_academico,
					sga_periodos.anio_academico || ' - ' || sga_periodos.nombre	AS anio_periodo_nombre,
        				sga_elementos.codigo as cod_actividad,
        				sga_elementos_plan.nombre as actividad,
        				sga_propuestas.nombre_abreviado		AS propuesta_nombre_abreviado,
        				f_instancias_insc_cursada(sga_insc_cursada.inscripcion) 			AS instancias,
        				f_comision_cant_clases(sga_alumnos.alumno, sga_comisiones.comision) AS cant_clases,
					sga_insc_cursada.sq_token,
					sga_elementos_plan.nombre || ' (' || sga_comisiones.nombre || ')' AS actividad_comision
				FROM sga_insc_cursada
				JOIN sga_inscripciones_estados ON sga_insc_cursada.estado = sga_inscripciones_estados.estado
				JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
				JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
				JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN sga_comisiones ON sga_insc_cursada.comision = sga_comisiones.comision
				JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento $where_gatillo
				JOIN sga_elementos_revision ON sga_elementos.elemento = sga_elementos_revision.elemento	
				JOIN sga_elementos_plan ON (sga_elementos_plan.plan_version      = sga_insc_cursada.plan_version AND
				                            sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision)
				JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
				JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
				JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
				LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
				LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				$where
				$where_vigentes
				$order
		";

		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Retorna el listado de inscripciones a cursadas obtenidas de la vista según el filtro especificado
	 * @param string $filtro
	 * @param boolean $solo_vigentes
	 * @param string $filtro_requisito_no_cumplido
	 * @param boolean $query_aceptadas_pendientes
	 * @param boolean $query_rechazadas_bajas
	 */
	function get_listado_vista($filtro = '', $solo_vigentes = false,  $filtro_requisito_no_cumplido = '', $query_aceptadas_pendientes = true, $query_rechazadas_bajas = true)
	{
		// Si los dos parametros vienen en false, retorno un array vacio.
		if(!$query_aceptadas_pendientes && !$query_rechazadas_bajas){
			return [];
		}	

		$formato_fecha_hora_visual = toba::db()->quote(comunes::formato_fecha_hora_visual);
		$operacion_baja = toba::db()->quote(inscripcion_cursada::operacion_baja);
		$operacion_rechazo = toba::db()->quote(inscripcion_cursada::operacion_rechazo);
		$operacion_cambio_comision = toba::db()->quote(inscripcion_cursada::operacion_cambio_comision);
		$operacion_baja_desc = toba::db()->quote(inscripcion_cursada::operacion_baja_desc);
		$operacion_rechazo_desc = toba::db()->quote(inscripcion_cursada::operacion_rechazo_desc);
		$operacion_cambio_comision_desc = toba::db()->quote(inscripcion_cursada::operacion_cambio_comision_desc);

        $sql = '';
        
		$where = 'WHERE true';
		if ($filtro) {
			$where .= " AND $filtro";
		}

		$where_vigentes = '';
		if ($solo_vigentes) {
			$where_vigentes = " AND sga_periodos.fecha_fin >= CURRENT_DATE ";
		}
        
        $orden_tipo_email = persona::orden_tipo_email();
		$orden_tipo_telefono = persona::orden_tipo_telefono();

        
        $aceptadas_pendientes='';
        if($query_aceptadas_pendientes){
            $aceptadas_pendientes = "
			SELECT		sga_ubicaciones.ubicacion AS id_ubicacion,
					sga_ubicaciones.nombre AS ubicacion,
					sga_elementos.elemento AS id_actividad,							
					'(' || sga_elementos.codigo || ') ' || sga_elementos_plan.nombre AS actividades,
					COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as apellido_y_nombre,
					mdp_tipo_documento.desc_abreviada || ' ' || mdp_personas_documentos.nro_documento as documento_principal,
					COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) as apellido,
					COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as nombres,
					mdp_personas.sexo as genero,
					mdp_personas_documentos.nro_documento,
					mdp_personas_documentos.tipo_documento,
					mdp_personas_documentos.pais_documento,
					(SELECT mdp_personas_contactos.email
					   FROM mdp_personas_contactos
					  WHERE mdp_personas_contactos.persona = mdp_personas.persona 
					    AND mdp_personas_contactos.contacto_tipo IN ('" . persona::mail_primario . "','" . persona::mail_secundario . "','" . persona::mail_institucional . "') 
					    AND mdp_personas_contactos.email IS NOT NULL
					ORDER BY $orden_tipo_email
					LIMIT 1) as email,
					(SELECT mdp_personas_contactos.telefono_codigo_area || '|' || mdp_personas_contactos.telefono_numero
					   FROM mdp_personas_contactos
					  WHERE mdp_personas_contactos.persona = mdp_personas.persona 
					    AND mdp_personas_contactos.contacto_tipo IN ('" . persona::tel_celular . "','" . persona::tel_familiar . "','" . persona::tel_fijo . "','" . persona::tel_laboral . "') 
					    AND mdp_personas_contactos.telefono_codigo_area IS NOT NULL 
					    AND mdp_personas_contactos.telefono_numero IS NOT NULL
					ORDER BY $orden_tipo_telefono
					LIMIT 1) as telefono,
					vw_insc_cursada.inscripcion,
					sga_alumnos.alumno,
					sga_alumnos.legajo,
					sga_alumnos.persona,
					sga_alumnos.propuesta,
					sga_elementos.elemento,
					sga_periodos.periodo,
					vw_insc_cursada.plan_version,
					vw_insc_cursada.comision 			AS id_comision,							
					sga_comisiones.nombre				AS comision,
					vw_insc_cursada.estado				AS estado,
					vw_insc_cursada.estado				AS estado_real,
					vw_insc_cursada.nro_transaccion		AS nro_transaccion,
					sga_turnos_cursadas.nombre			AS turno,
					sga_catedras.nombre					AS catedra,
					sga_inscripciones_estados.nombre	AS estado_inscr,
					to_char(vw_insc_cursada.fecha_inscripcion, $formato_fecha_hora_visual) AS fecha_inscripcion,
					sga_periodos.nombre 				AS periodo_nombre,
					sga_periodos.anio_academico,
					sga_periodos.anio_academico || ' - ' || sga_periodos.nombre	AS anio_periodo_nombre,
					sga_elementos.codigo 		AS cod_actividad,
					sga_elementos_plan.nombre		AS actividad,
					sga_propuestas.nombre_abreviado	AS propuesta_nombre_abreviado,
					f_instancias_insc_cursada(vw_insc_cursada.inscripcion) AS instancia,
					vw_insc_cursada.sq_token
					
				FROM vw_insc_cursada
				JOIN sga_comisiones ON vw_insc_cursada.comision = sga_comisiones.comision
				JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
				JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
				
				JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento 
				JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_comisiones.elemento
				JOIN sga_elementos_plan ON sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision 
				                       AND sga_elementos_plan.plan_version = vw_insc_cursada.plan_version

				JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
				JOIN sga_alumnos ON vw_insc_cursada.alumno = sga_alumnos.alumno
				JOIN mdp_personas ON sga_alumnos.persona = mdp_personas.persona
				JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN sga_inscripciones_estados ON vw_insc_cursada.estado = sga_inscripciones_estados.estado
				JOIN mdp_personas_documentos ON mdp_personas.documento_principal = mdp_personas_documentos.documento
				JOIN mdp_tipo_documento ON mdp_personas_documentos.tipo_documento = mdp_tipo_documento.tipo_documento
				LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
				LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				$where
				$where_vigentes
				";
            
        }
        
        
        $rechazadas_bajas = '';
        if($query_rechazadas_bajas){
            $rechazadas_bajas = "
                -- Rechazadas y Dadas de Baja
				SELECT		sga_ubicaciones.ubicacion AS id_ubicacion,
							sga_ubicaciones.nombre AS ubicacion,
							sga_elementos.elemento AS id_actividad,
							'(' || sga_elementos.codigo || ') ' || sga_elementos_plan.nombre AS actividades,
							COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as apellido_y_nombre,
							mdp_tipo_documento.desc_abreviada || ' ' || mdp_personas_documentos.nro_documento as documento_principal,
							COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) as apellido,
							COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as nombres,
							mdp_personas.sexo as genero,
							mdp_personas_documentos.nro_documento,
							mdp_personas_documentos.tipo_documento,
							mdp_personas_documentos.pais_documento,
							(SELECT mdp_personas_contactos.email
							   FROM mdp_personas_contactos
							  WHERE mdp_personas_contactos.persona = mdp_personas.persona 
							    AND mdp_personas_contactos.contacto_tipo IN ('" . persona::mail_primario . "','" . persona::mail_secundario . "','" . persona::mail_institucional . "') 
							    AND mdp_personas_contactos.email IS NOT NULL
							ORDER BY $orden_tipo_email
							LIMIT 1) as email,
							(SELECT mdp_personas_contactos.telefono_codigo_area || '|' || mdp_personas_contactos.telefono_numero
							   FROM mdp_personas_contactos
							  WHERE mdp_personas_contactos.persona = mdp_personas.persona
							    AND mdp_personas_contactos.contacto_tipo IN ('" . persona::tel_celular . "','" . persona::tel_familiar . "','" . persona::tel_fijo . "','" . persona::tel_laboral . "') 
							    AND mdp_personas_contactos.telefono_codigo_area IS NOT NULL 
							    AND mdp_personas_contactos.telefono_numero IS NOT NULL
							ORDER BY $orden_tipo_telefono
							LIMIT 1) as telefono,
							vw_insc_cursada.inscripcion,
							sga_alumnos.alumno,
							sga_alumnos.legajo,
							sga_alumnos.persona,
							sga_alumnos.propuesta,
							sga_elementos.elemento,
							sga_periodos.periodo,
							vw_insc_cursada.plan_version,
							vw_insc_cursada.comision 		AS id_comision,
							sga_comisiones.nombre				AS comision,
							vw_insc_cursada.estado				AS estado,
							vw_insc_cursada.operacion			AS estado_real,
							vw_insc_cursada.nro_transaccion		AS nro_transaccion,
							sga_turnos_cursadas.nombre			AS turno,
							sga_catedras.nombre					AS catedra,
							Case vw_insc_cursada.operacion
							  WHEN $operacion_baja THEN $operacion_baja_desc
							  WHEN $operacion_rechazo THEN $operacion_rechazo_desc
							  WHEN $operacion_cambio_comision THEN $operacion_cambio_comision_desc
							END	AS estado_inscr,
							to_char(vw_insc_cursada.fecha_inscripcion, $formato_fecha_hora_visual) AS fecha_inscripcion,
							sga_periodos.nombre 				AS periodo_nombre,
							sga_periodos.anio_academico,
							sga_periodos.anio_academico || ' - ' || sga_periodos.nombre	AS anio_periodo_nombre,
							sga_elementos.codigo 		AS cod_actividad,
							sga_elementos_plan.nombre		AS actividad,
							sga_propuestas.nombre_abreviado	AS propuesta_nombre_abreviado,
							f_instancias_insc_cursada_log(vw_insc_cursada.inscripcion) AS instancia,
							vw_insc_cursada.sq_token
					
				FROM vw_insc_cursada_log as vw_insc_cursada
				JOIN sga_comisiones ON vw_insc_cursada.comision = sga_comisiones.comision
				JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
				JOIN sga_periodos ON sga_periodos_lectivos.periodo  = sga_periodos.periodo
				
				JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento 
				JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_comisiones.elemento
				JOIN sga_elementos_plan ON sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision 
				                       AND sga_elementos_plan.plan_version = vw_insc_cursada.plan_version
				                       
				JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion 
				JOIN sga_alumnos ON vw_insc_cursada.alumno = sga_alumnos.alumno
				JOIN mdp_personas ON sga_alumnos.persona = mdp_personas.persona
				JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN mdp_personas_documentos ON mdp_personas.documento_principal = mdp_personas_documentos.documento
				JOIN mdp_tipo_documento ON mdp_personas_documentos.tipo_documento = mdp_tipo_documento.tipo_documento
				LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
				LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				$where
				$where_vigentes
				$filtro_requisito_no_cumplido
				 ";
        }
        
        // Armo la query
        if($query_aceptadas_pendientes && $query_rechazadas_bajas){
            $sql = $aceptadas_pendientes . " UNION ALL " . $rechazadas_bajas;
        } elseif ($aceptadas_pendientes) {
            $sql = $aceptadas_pendientes;
        } elseif ($rechazadas_bajas) {
            $sql = $rechazadas_bajas;
		} 
			
        $sql .= " ORDER BY ubicacion, actividad, apellido_y_nombre";
        
		return guarani_db::consultar($sql);
	}

	function get_listado_inscripciones_a_comision($where = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}

		$sql = "SELECT		vw_insc_cursada.comision,
					sga_ubicaciones.nombre 	AS ubicacion_nombre,
					sga_comisiones.nombre		AS comision_nombre,
					sga_turnos_cursadas.nombre	AS turno_nombre,
					vw_actividades_plan.codigo 	AS codigo_actividad,
					vw_actividades_plan.nombre	AS actividad_nombre,
					sga_periodos.nombre 		AS periodo_nombre,
					sga_periodos.anio_academico
				FROM vw_insc_cursada
				JOIN sga_alumnos ON vw_insc_cursada.alumno = sga_alumnos.alumno
				JOIN sga_comisiones ON vw_insc_cursada.comision = sga_comisiones.comision
				JOIN vw_actividades_plan ON (vw_actividades_plan.elemento = sga_comisiones.elemento AND vw_actividades_plan.plan_version = vw_insc_cursada.plan_version)
				JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
				JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
				JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
				LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				$where
				ORDER BY	sga_periodos.anio_academico
	   ";

		return guarani_db::consultar($sql);
	}

	function get_listado_alumnos_inscripciones_a_comisiones($where = '', $limit ='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$tipo_inscripcion = toba::db()->quote(inscripcion_cursada::tipo_inscripcion);
		$tipo_preinscripcion = toba::db()->quote(inscripcion_cursada::tipo_preinscripcion);
		$preinsc_aceptada = toba::db()->quote(inscripcion_cursada::preinsc_aceptada);

		$sql = "SELECT
					vw_insc_cursada.inscripcion,
					vw_insc_cursada.alumno,
					vw_insc_cursada.estado,
					vw_insc_cursada.fecha_inscripcion,
					vw_insc_cursada.nro_transaccion,
					sga_comisiones.elemento,
					vw_actividades_plan.codigo 	AS codigo_actividad,
					vw_actividades_plan.nombre	AS actividad_nombre,
					sga_comisiones.comision,
					sga_comisiones_cupo.cupo,
					sga_comisiones_cupo.cant_inscriptos,
					f_docentes_comision(sga_comisiones.comision, true) AS comision_docentes,
					(SELECT COUNT(sga_subcomisiones.subcomision)
					 FROM	sga_subcomisiones
					 WHERE	sga_subcomisiones.comision = sga_comisiones.comision
					) AS cant_subcomisiones,
					sga_alumnos.persona,
					sga_alumnos.plan_version,
					sga_alumnos.propuesta,
					sga_comisiones.nombre			AS comision_nombre,
					sga_ubicaciones.nombre			AS ubicacion_nombre,
					sga_turnos_cursadas.nombre		AS turno_nombre,
					vw_personas.apellido_nombres	AS apellido_y_nombres,
					vw_personas.apellido,
					vw_personas.nombres,
					vw_personas.documento,
					vw_personas.tipo_documento,
					vw_personas.tipo_nro_documento	AS alumno_identificacion,
					sga_alumnos.legajo,
					sga_propuestas.nombre			AS propuesta_nombre
				    
				FROM vw_insc_cursada
				JOIN sga_alumnos ON vw_insc_cursada.alumno = sga_alumnos.alumno
				JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
				JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN sga_comisiones ON vw_insc_cursada.comision = sga_comisiones.comision
				JOIN vw_actividades_plan ON (vw_actividades_plan.elemento = sga_comisiones.elemento AND vw_actividades_plan.plan_version = vw_insc_cursada.plan_version)
				JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
				JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
				JOIN sga_comisiones_cupo ON sga_comisiones.comision = sga_comisiones_cupo.comision
				LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra

				WHERE
					-- Sólo inscripciones normales o inscripciones por prioridad que están aceptadas (fueron procesadas y quedo una sola aceptada).
					(vw_insc_cursada.tipo = $tipo_inscripcion OR 
					 (vw_insc_cursada.tipo = $tipo_preinscripcion AND vw_insc_cursada.estado_preinscripcion = $preinsc_aceptada)
					) 
					AND vw_periodos_lectivos.fecha_tope_movimientos >= " . guarani_sql::fecha_actual . "
				   	$where

				ORDER BY
					sga_comisiones.comision,
					vw_personas.apellido,
				   	vw_personas.nombres
					   $limit
		";

		return guarani_db::consultar($sql);
	}

	function get_listado_alumnos_inscripciones_sin_subcomisiones($where)
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		// Usado en la operacion de distribuir alumnos entre comisiones.
		$sql = "
                SELECT
                    vw_insc_cursada.inscripcion,
                    'Alumnos sin Comisión Asignada' as subcomision_desc,
                    0 as insc_subcomision,
                    vw_insc_cursada.alumno,
                    vw_insc_cursada.estado,
                    sga_comisiones.elemento,
                    vw_actividades_plan.codigo 	AS codigo_actividad,
                    vw_actividades_plan.nombre	AS actividad_nombre,
                    sga_comisiones.comision,
                    sga_comisiones_cupo.cupo as cupo_comision,
                    0 as subcomision,
                    sga_comisiones_cupo.cant_inscriptos as inscriptos_comision,
                    0 as cupo_subcomision,
                    0 as inscriptos_subcomision,
                    f_docentes_comision(sga_comisiones.comision, true) AS comision_docentes,	
                    sga_alumnos.persona,
                    sga_alumnos.plan_version,
                    sga_alumnos.propuesta,
                    sga_comisiones.nombre			AS comision_nombre,
                    sga_ubicaciones.nombre			AS ubicacion_nombre,
                    sga_turnos_cursadas.nombre		AS turno_nombre,
                    vw_personas.apellido_nombres	AS apellido_y_nombres,
                    vw_personas.tipo_nro_documento	AS alumno_identificacion,
                    sga_alumnos.legajo,
                    sga_propuestas.nombre			AS propuesta_nombre,
                    '' as tipo_clase
                FROM
					vw_insc_cursada
					JOIN sga_alumnos ON vw_insc_cursada.alumno = sga_alumnos.alumno
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
					JOIN sga_comisiones ON vw_insc_cursada.comision = sga_comisiones.comision
					JOIN vw_actividades_plan ON (sga_comisiones.elemento = vw_actividades_plan.elemento AND vw_insc_cursada.plan_version = vw_actividades_plan.plan_version)
					JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
					JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
					JOIN sga_comisiones_cupo ON sga_comisiones.comision = sga_comisiones_cupo.comision
					LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
					LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
                WHERE	
					-- Sólo inscripciones normales o inscripciones por prioridad que están aceptadas (fueron procesadas y quedo una sola aceptada).
					(vw_insc_cursada.tipo = '" . inscripcion_cursada::tipo_inscripcion . "' OR 
					 (vw_insc_cursada.tipo = '" . inscripcion_cursada::tipo_preinscripcion . "' AND vw_insc_cursada.estado_preinscripcion = '" . inscripcion_cursada::preinsc_aceptada . "')
					) 
				  AND vw_periodos_lectivos.fecha_tope_movimientos >= " . guarani_sql::fecha_actual . "
				  AND EXISTS (SELECT	sga_subcomisiones.subcomision 
								FROM	sga_subcomisiones 
								WHERE	sga_subcomisiones.comision = sga_comisiones.comision)
				  AND NOT EXISTS (SELECT	vw_insc_subcomision.inscripcion
									FROM	vw_insc_subcomision
									WHERE	vw_insc_subcomision.inscripcion = vw_insc_cursada.inscripcion)
				  $where
                ORDER BY
					sga_comisiones.comision,
					vw_personas.apellido,
					vw_personas.nombres
		";

		return guarani_db::consultar($sql);
	}

	function get_listado_alumnos_inscripciones_a_subcomisiones($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		// Usado en distribuir alumnos entre comisiones.
		$sql = "SELECT
                    vw_insc_cursada.inscripcion,
                    sga_subcomisiones.nombre as subcomision_desc,
                    vw_insc_subcomision.inscripcion insc_subcomision,
                    vw_insc_cursada.alumno,
                    vw_insc_cursada.estado,
                    sga_comisiones.elemento,
                    vw_actividades_plan.codigo 	AS codigo_actividad,
                    vw_actividades_plan.nombre	AS actividad_nombre,
                    sga_comisiones.comision,                    
                    sga_comisiones_cupo.cupo as cupo_comision,
                    sga_comisiones_cupo.cant_inscriptos as inscriptos_comision,
                    sga_subcomisiones.subcomision as subcomision,
                    sga_subcomisiones_cupo.cupo as cupo_subcomision,
                    sga_subcomisiones_cupo.cant_inscriptos as inscriptos_subcomision,
                    f_docentes_comision(sga_comisiones.comision, true) AS comision_docentes,	
                    sga_alumnos.persona,
                    sga_alumnos.plan_version,
                    sga_alumnos.propuesta,
                    sga_comisiones.nombre			AS comision_nombre,
                    sga_ubicaciones.nombre			AS ubicacion_nombre,
                    sga_turnos_cursadas.nombre		AS turno_nombre,
                    vw_personas.apellido_nombres	AS apellido_y_nombres,
                    vw_personas.tipo_nro_documento	AS alumno_identificacion,
                    sga_alumnos.legajo,
                    sga_propuestas.nombre			AS propuesta_nombre,
                    sga_clases_tipos.nombre as tipo_clase
                FROM
					vw_insc_cursada
					JOIN sga_alumnos ON vw_insc_cursada.alumno = sga_alumnos.alumno
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
					JOIN sga_comisiones ON vw_insc_cursada.comision = sga_comisiones.comision
					JOIN vw_actividades_plan ON (sga_comisiones.elemento = vw_actividades_plan.elemento AND vw_insc_cursada.plan_version = vw_actividades_plan.plan_version)
					JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
					JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
					JOIN sga_comisiones_cupo ON sga_comisiones.comision = sga_comisiones_cupo.comision
					JOIN sga_subcomisiones ON sga_comisiones.comision = sga_subcomisiones.comision
					JOIN vw_insc_subcomision ON (sga_subcomisiones.subcomision = vw_insc_subcomision.subcomision AND vw_insc_cursada.inscripcion = vw_insc_subcomision.inscripcion)
					JOIN sga_subcomisiones_cupo ON sga_subcomisiones.subcomision = sga_subcomisiones_cupo.subcomision
					JOIN sga_clases_tipos ON sga_subcomisiones.tipo_clase = sga_clases_tipos.tipo_clase
					LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
					LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
                WHERE
					-- Sólo inscripciones normales o inscripciones por prioridad que están aceptadas (fueron procesadas y quedo una sola aceptada).
					(vw_insc_cursada.tipo = '" . inscripcion_cursada::tipo_inscripcion . "' OR 
					 (vw_insc_cursada.tipo = '" . inscripcion_cursada::tipo_preinscripcion . "' AND vw_insc_cursada.estado_preinscripcion = '" . inscripcion_cursada::preinsc_aceptada . "')
					) 
					AND vw_periodos_lectivos.fecha_tope_movimientos >= " . guarani_sql::fecha_actual . "
					$where
				ORDER BY
					sga_comisiones.comision,
					vw_personas.apellido,
					vw_personas.nombres
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la cantidad de inscripciones a cursadas (en estado Pendiente o Aceptado) registradas para la 
	 * comisión dada, según el filtro dado. 
	 */
	function get_cant_inscripciones_para_comision($comision = null, $where = '')
	{
		$filtro = '';
		if (!is_null($comision)) {
			$comision = toba::db()->quote($comision);
			$filtro = " WHERE sga_insc_cursada.comision = $comision ";
		}
		if ($where) {
			$filtro .= ' AND ' . $where;
		}

		$sql = "SELECT	count(sga_insc_cursada.inscripcion) as cant_inscripciones
				FROM	sga_insc_cursada
						JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
				$filtro
				;";

		$datos = guarani_db::consultar_fila($sql);
		return $datos['cant_inscripciones'];
	}

	/**
	 * Retorna la cantidad de inscripciones a cursadas registradas para la comisión dada en estado de Baja o Rechazadas, 
	 * según el filtro dado. Para esto se consulta la tabla sga_insc_cursada_log en lugar de sga_insc_cursada.
	 */
	function get_cant_inscripciones_bajas_para_comision($comision = null, $where = '')
	{
		$filtro = '';
		if (!is_null($comision)) {
			$comision = toba::db()->quote($comision);
			$filtro = " WHERE sga_insc_cursada_log.comision = $comision ";
		}
		if ($where) {
			$filtro .= ' AND ' . $where;
		}

		$sql = "SELECT	count(sga_insc_cursada_log.inscripcion) as cant_inscripciones
				FROM	sga_insc_cursada_log
				$filtro
				;";

		$datos = guarani_db::consultar_fila($sql);
		return $datos['cant_inscripciones'];
	}

	/**
	 * Retorna la cantidad de inscripciones a cursadas registradas para la subcomisión dada. En este caso no es necesario 
	 * considerar inscripciones en estado de baja, ya que no hay bajas a la sucomisión sólo a la comisión asociada.
	 */
	function get_cant_inscripciones_para_subcomision($subcomision = null, $where = '')
	{
		$filtro = '';
		if (!is_null($subcomision)) {
			$subcomision = toba::db()->quote($subcomision);
			$filtro = " WHERE sga_insc_subcomision.subcomision = $subcomision ";
		}
		if ($where) {
			$filtro .= ' AND ' . $where;
		}

		$sql = "SELECT	count(sga_insc_subcomision.inscripcion) as cant_inscripciones
				FROM	sga_insc_subcomision
				$filtro
				;";

		$datos = guarani_db::consultar_fila($sql);
		return $datos['cant_inscripciones'];
	}

	function get_cant_inscripciones($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	count(sga_insc_cursada.inscripcion) as cantidad
				FROM	sga_insc_cursada
				WHERE	sga_insc_cursada.alumno = $alumno
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cantidad'];
	}

	/**
	 * Retorna la cantidad de inscripciones para un alumno en una actividad y período lectivo dados.
	 * 
	 * @param integer $alumno
	 * @param integer $actividad
	 * @param integer $periodo_lectivo
	 * @return integer
	 */
	function get_cant_inscripciones_actividad_periodo_lectivo($alumno, $actividad, $periodo_lectivo)
	{
		$alumno = toba::db()->quote($alumno);
		$actividad = toba::db()->quote($actividad);
		$periodo_lectivo = toba::db()->quote($periodo_lectivo);
		$sql = "SELECT	count(sga_insc_cursada.inscripcion) as cantidad
				FROM	sga_insc_cursada,
						sga_comisiones
				WHERE	sga_insc_cursada.comision = sga_comisiones.comision AND
						sga_insc_cursada.alumno = $alumno AND
						sga_comisiones.elemento = $actividad AND
						sga_comisiones.periodo_lectivo = $periodo_lectivo
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cantidad'];
	}

	/**
	 * Retorna el listado de inscripciones a cursadas FUERA DE TÉRMINO según el filtro especificado
	 */
	function get_listado_fuera_de_termino($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT		sga_ubicaciones.ubicacion			AS ubicacion_id,
							sga_ubicaciones.nombre				AS ubicacion,
							sga_elementos.elemento				AS actividad_id,
        					'(' || sga_elementos.codigo || ') '
        					|| sga_elementos.nombre				AS actividades,
        					vw_personas.apellido_nombres 		AS alumno,
							vw_personas.tipo_nro_documento 		AS nro_identificacion,
        					sga_comisiones.nombre				AS comision,
        					sga_inscripciones_estados.nombre	AS estado_inscr,
        					sga_alumnos.legajo
        
				FROM		vw_insc_cursada,
							sga_comisiones,
							sga_elementos,
							sga_ubicaciones,
							sga_alumnos,
							vw_personas,
							sga_periodos_lectivos,
							sga_periodos,
							sga_inscripciones_estados

				WHERE    	vw_insc_cursada.comision = sga_comisiones.comision
        					AND vw_insc_cursada.alumno = sga_alumnos.alumno
        					AND vw_insc_cursada.estado = sga_inscripciones_estados.estado    -- Estado de la Inscripcion
    						AND sga_comisiones.elemento = sga_elementos.elemento
        					AND sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
        					AND sga_alumnos.persona = vw_personas.persona
        					AND sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
        					AND sga_periodos_lectivos.periodo  = sga_periodos.periodo
							AND vw_insc_cursada.fuera_de_termino = 'S'
							$where
		
				ORDER BY	ubicacion,
							actividades,
							alumno
  			   ";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado del total de inscripciones a cursadas según el filtro especificado
	 */
	function get_listado_total_inscripciones($where = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}

		$sql = "SELECT  	sga_comisiones.ubicacion	as ubicacion,
					        sga_ubicaciones.nombre		as nombre_ubicacion,
					        vw_actividades.elemento		as actividad,
					        vw_actividades.codigo		as codigo_actividad,
					        vw_actividades.nombre		as nombre_actividad,
					        sga_comisiones.comision		as comision,
					        sga_comisiones.nombre		as nombre_comision,
							sga_comisiones.cupo			as cupo_comision,
							sga_comisiones.turno		as turno_comision,
							sga_turnos_cursadas.nombre	as turno_nombre,
        					(SELECT COUNT (*) 
        					 FROM vw_insc_cursada 
        					 WHERE vw_insc_cursada.comision = sga_comisiones.comision)  as cant_inscriptos
    			FROM		sga_comisiones
							JOIN vw_actividades ON sga_comisiones.elemento = vw_actividades.elemento
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				$where
				ORDER BY	sga_ubicaciones.nombre, 
							vw_actividades.nombre, 
							sga_comisiones.nombre
		";

		return guarani_db::consultar($sql);
	}

	function get_listado_inscriptos_y_encuestas_pendientes($where)
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT  	sga_comisiones.ubicacion as ubicacion,
					        sga_ubicaciones.nombre   as nombre_ubicacion,
					        vw_actividades.elemento  as actividad,
					        vw_actividades.codigo    as codigo_actividad,
					        vw_actividades.nombre    as nombre_actividad,
					        sga_comisiones.comision  as comision,
					        sga_comisiones.nombre    as nombre_comision,
        					(SELECT COUNT (*) 
        					 FROM	vw_insc_cursada 
        					 WHERE	vw_insc_cursada.comision = sga_comisiones.comision) AS cant_inscriptos,
        					(SELECT COUNT (*) 
        					 FROM	vw_insc_cursada 
        					 WHERE	vw_insc_cursada.comision = sga_comisiones.comision
        					 AND	vw_insc_cursada.estado = '" . inscripcion_cursada::estado_pendiente . "')  AS cant_inscriptos_pendientes,
							(SELECT COUNT(*) 
                             FROM	gde_encuestas_pendientes,
									gde_formularios
                             WHERE	gde_encuestas_pendientes.formulario = gde_formularios.formulario
							 AND	gde_formularios.comision = sga_comisiones.comision 
                             AND	gde_encuestas_pendientes.fecha_respuesta IS NULL) AS cant_encuestas_pendientes
    
    			FROM  		sga_comisiones, 
					        vw_actividades,
					        sga_ubicaciones,
					        vw_periodos_lectivos
    
    			WHERE 		sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
						    AND vw_actividades.elemento = sga_comisiones.elemento
							AND sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
							$where
							
    			ORDER BY  	sga_ubicaciones.nombre, 
    						vw_actividades.nombre, 
    						sga_comisiones.nombre
  			   ";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de alumnos inscriptos a propuestas según el filtro especificado
	 */
	function get_listado_alumnos_propuestas($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT 		sga_alumnos.alumno,
							vw_personas.persona,
							vw_personas.apellido_nombres	AS persona_nombre,
							vw_personas.tipo_nro_documento	AS nro_identificacion,
							sga_alumnos.legajo				AS nro_legajo,
				            sga_propuestas.codigo			AS propuesta_codigo,
				            sga_propuestas.nombre_abreviado	AS propuesta_nombre
				FROM 		sga_alumnos
							JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
							JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				WHERE		(	SELECT	COUNT(*)
								FROM 	sga_insc_cursada
								WHERE	sga_insc_cursada.alumno = sga_alumnos.alumno) > 0
							$where
				ORDER BY	persona_nombre
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de inscripciones del alumno agrupado por propuesta
	 */
	function get_listado_inscripciones_alumno_propuestas($persona, $filtro = '')
	{
		$where = 'WHERE true';
		if ($persona) {
			$persona = toba::db()->quote($persona);
			$where .= " AND sga_alumnos.persona = $persona";
		}
		if ($filtro) {
			$where .= " AND $filtro";
		}

		$sql = "SELECT		vw_actividades.codigo					AS actividad_codigo,
							vw_actividades.nombre					AS actividad_nombre,
							vw_insc_cursada.fecha_inscripcion,
							to_char(vw_insc_cursada.fecha_inscripcion, '" . comunes::formato_fecha_hora_visual . "') AS fecha_inscripcion_formateada,
							cast(null as varchar(19))				AS fecha_baja_rechazo_formateada,
							sga_periodos_genericos.nombre			AS periodo_lectivo_nombre,
							sga_comisiones.nombre					AS comision_nombre,
							sga_catedras.nombre						AS catedra_nombre,
							sga_turnos_cursadas.nombre				AS turno_nombre,
							f_modalidad_cursada_comision(sga_comisiones.comision) AS modalidad_nombre,
							sga_ubicaciones.nombre					AS ubicacion_nombre,
						    sga_inscripciones_estados.nombre		AS inscripcion_estado,
						    sga_propuestas.propuesta,
						    sga_propuestas.nombre					AS propuesta_nombre,
						    vw_insc_cursada.prioridad				AS prioridad_inscripcion,
						    sga_periodos.anio_academico,
						    'Año Académico: ' || sga_periodos.anio_academico AS anio_academico_descr
				FROM 		vw_insc_cursada
							JOIN sga_inscripciones_estados ON vw_insc_cursada.estado = sga_inscripciones_estados.estado
							JOIN sga_alumnos ON vw_insc_cursada.alumno = sga_alumnos.alumno
							JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
							JOIN sga_comisiones ON vw_insc_cursada.comision = sga_comisiones.comision
							JOIN vw_actividades ON sga_comisiones.elemento = vw_actividades.elemento
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
						    JOIN sga_periodos_genericos ON sga_periodos.periodo_generico = sga_periodos_genericos.periodo_generico
							LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
						    LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				$where

				UNION ALL

				SELECT		vw_actividades.codigo					AS actividad_codigo,
							vw_actividades.nombre					AS actividad_nombre,
							vw_insc_cursada.fecha_inscripcion,
							to_char(vw_insc_cursada.fecha_inscripcion, '" . comunes::formato_fecha_hora_visual . "') AS fecha_inscripcion_formateada,
							to_char(vw_insc_cursada.fecha_operacion, '" . comunes::formato_fecha_hora_visual . "') AS fecha_baja_rechazo_formateada,
							sga_periodos_genericos.nombre			AS periodo_lectivo_nombre,
							sga_comisiones.nombre					AS comision_nombre,
							sga_catedras.nombre						AS catedra_nombre,
							sga_turnos_cursadas.nombre				AS turno_nombre,
							f_modalidad_cursada_comision(sga_comisiones.comision) AS modalidad_nombre,
							sga_ubicaciones.nombre					AS ubicacion_nombre,
						    Case vw_insc_cursada.operacion
						      WHEN 'R' THEN 'Rechazada'
						      WHEN 'B' THEN 'Baja'
						    END 									AS inscripcion_estado,
						    sga_propuestas.propuesta,
						    sga_propuestas.nombre					AS propuesta_nombre,
						    vw_insc_cursada.prioridad			AS prioridad_inscripcion,
						    sga_periodos.anio_academico,
						    'Año Académico: ' || sga_periodos.anio_academico AS anio_academico_descr
				FROM		vw_insc_cursada_log as vw_insc_cursada
							JOIN sga_alumnos ON vw_insc_cursada.alumno = sga_alumnos.alumno
							JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
							JOIN sga_comisiones ON vw_insc_cursada.comision = sga_comisiones.comision
							JOIN vw_actividades ON sga_comisiones.elemento = vw_actividades.elemento
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							JOIN sga_periodos_genericos ON sga_periodos.periodo_generico = sga_periodos_genericos.periodo_generico
							LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				$where
				ORDER BY	anio_academico DESC,
							propuesta_nombre,
							actividad_nombre,
							prioridad_inscripcion
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos de una inscripción a cursadas por id.
	 *
	 */
	function get_insc_cursada_por_id($inscripcion)
	{
		$inscripcion = toba::db()->quote($inscripcion);
		$where = "sga_insc_cursada.inscripcion = $inscripcion";
		$datos = $this->get_insc_cursada($where);
		return $datos[0];
	}

	/**
	 * Obtiene los datos de una inscripción a cursadas por nro_transaccion.
	 *
	 */
	function get_insc_cursada_por_nro_transaccion($nro_transaccion)
	{
		$nro_transaccion = toba::db()->quote($nro_transaccion);
		$where = "sga_insc_cursada.nro_transaccion = $nro_transaccion";
		return $this->get_insc_cursada($where);
	}

	/**
	 * Obtiene los datos de una inscripción a cursadas para un alumno en una 
	 * comisión dada
	 *
	 */
	function get_insc_cursada_por_alumno_comision($alumno, $comision)
	{
		$alumno = toba::db()->quote($alumno);
		$comision = toba::db()->quote($comision);

		$where = "	sga_insc_cursada.alumno = $alumno
					AND sga_insc_cursada.comision = $comision";

		return current($this->get_insc_cursada($where));
	}

	/**
	 * Retorna los registros de la tabla sga_insc_cursada
	 */
	function get_insc_cursada($where = '')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}

		$sql = "SELECT 
					sga_insc_cursada.inscripcion,
					sga_insc_cursada.comision,
					sga_insc_cursada.alumno,
					sga_insc_cursada.tipo,
					sga_insc_cursada.prioridad,
					sga_insc_cursada.estado_preinscripcion,
					sga_insc_cursada.plan_version,
					sga_insc_cursada.fecha_inscripcion,
					sga_insc_cursada.fuera_de_termino,
					sga_insc_cursada.autorizado_por,
					sga_insc_cursada.nro_transaccion,
					sga_insc_cursada.motivo_excepcion,
					sga_insc_cursada.exceptuado,
					sga_insc_cursada.interfaz,
					sga_insc_cursada.estado,
					sga_insc_cursada.sq_token
				FROM 
					sga_insc_cursada
				$where
  			   ";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los registros de la tabla sga_insc_cursadas_log
	 */
	function get_insc_cursadas_log($where = '')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}

		$sql = "SELECT 
				    sga_insc_cursada_log.inscripcion,
				    sga_insc_cursada_log.alumno,
				    sga_insc_cursada_log.nro_transaccion_log,
				    sga_insc_cursada_log.comision,
				    sga_insc_cursada_log.prioridad,
				    sga_insc_cursada_log.plan_version,
				    sga_insc_cursada_log.fecha_inscripcion,
				    sga_insc_cursada_log.fuera_de_termino,
				    sga_insc_cursada_log.nro_transaccion,
				    sga_insc_cursada_log.estado,
				    sga_insc_cursada_log.operacion,
                                    sga_insc_cursada_log.fecha_operacion
				FROM 
				    sga_insc_cursada_log
				    $where
  			   ";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos de las inscripciones a cursadas que fueron rechazadas o dadas de baja, según el filtro 
	 * especificado (registros de la tabla sga_insc_cursadas_log).
	 * @param str $where filtro sobre las inscripciones para las que se recuperan los datos.
	 * @param boolean $solo_vigentes recupera sólo inscripciones cuyo período lectivo sea vigente.
	 */
	function get_listado_insc_cursadas_rechazadas_bajas($where = '', $solo_vigentes = false, $revertir_rechazo = false)
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$where_vigentes = '';
		if ($solo_vigentes) {
			$where_vigentes = " AND periodo_pl.fecha_fin >= CURRENT_DATE AND sga_periodos_lectivos.fecha_tope_movimientos >= CURRENT_DATE ";
		}
		
		$where_preinsc_rechazada = '';
		if ($revertir_rechazo){
			// Este metodo se llama desde la operacion de Revertir Rechazo de Inscripción a Comision.
			// En el caso de inscripcion por prioridades solo visualizo la de prioridad 1, y solo aquellas donde al alumno se le rechazaron todas las preinscripciones
			$where_preinsc_rechazada = " AND (sga_insc_cursada_log.tipo = '" . inscripcion_cursada::tipo_inscripcion . "' OR 
			                                  (sga_insc_cursada_log.tipo = '" . inscripcion_cursada::tipo_preinscripcion  . "' AND sga_insc_cursada_log.prioridad = 1 AND 
			                                  -- No tiene una inscripcion confirmada en la misma actividad (misma transaccion)
			                                  NOT EXISTS (SELECT 1 FROM sga_insc_cursada as i WHERE i.alumno = sga_insc_cursada_log.alumno AND i.nro_transaccion = sga_insc_cursada_log.nro_transaccion AND i.tipo = '" . inscripcion_cursada::tipo_preinscripcion  . "'))
			                                 )";
		}
		

		$sql = "SELECT	sga_insc_cursada_log.inscripcion,
						sga_insc_cursada_log.nro_transaccion_log,
						sga_insc_cursada_log.alumno,
						sga_alumnos.legajo				AS alumno_legajo,
						vw_personas.persona,
						vw_personas.apellido_nombres 	AS apellido_y_nombre,
						vw_personas.tipo_nro_documento 	AS documento_principal,
						sga_insc_cursada_log.comision,
						sga_comisiones.nombre			AS comision_nombre,
						f_subcomisiones_insc_cursada_log(sga_insc_cursada_log.inscripcion, true, false)	AS subcomisiones_nombres,
						sga_comisiones.ubicacion,
						sga_ubicaciones.nombre			AS ubicacion_nombre,
						sga_insc_cursada_log.plan_version,
						vw_actividades_plan.codigo 		AS actividad_codigo,
						vw_actividades_plan.nombre		AS actividad_nombre,
						COALESCE('(' || vw_actividades_plan.codigo || ') ', '') || vw_actividades_plan.nombre AS actividad_descr,
						sga_insc_cursada_log.fecha_inscripcion,
						sga_insc_cursada_log.estado,
						sga_insc_cursada_log.operacion,
						CASE sga_insc_cursada_log.operacion
							WHEN '" . inscripcion_cursada::operacion_baja . "' THEN '" . inscripcion_cursada::operacion_baja_desc . "'
							WHEN '" . inscripcion_cursada::operacion_rechazo . "' THEN '" . inscripcion_cursada::operacion_rechazo_desc . "'
							WHEN '" . inscripcion_cursada::operacion_cambio_comision . "' THEN '" . inscripcion_cursada::operacion_cambio_comision_desc . "'
						END	AS operacion_descr,
						sga_insc_cursada_log.fecha_operacion,
						to_char(sga_insc_cursada_log.fecha_operacion,'" . comunes::formato_fecha_hora_visual . "') AS fecha_operacion_visual
						
				FROM	sga_insc_cursada_log
				JOIN	sga_alumnos ON sga_alumnos.alumno = sga_insc_cursada_log.alumno
				JOIN	vw_personas ON vw_personas.persona = sga_alumnos.persona
				JOIN	sga_comisiones ON sga_comisiones.comision = sga_insc_cursada_log.comision
				JOIN	sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
				JOIN	sga_periodos as periodo_pl ON periodo_pl.periodo = sga_periodos_lectivos.periodo
				JOIN	vw_actividades_plan ON vw_actividades_plan.plan_version = sga_insc_cursada_log.plan_version 
				                           AND vw_actividades_plan.elemento		= sga_comisiones.elemento
				JOIN	sga_ubicaciones  ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
						
				WHERE	TRUE
						$where
						$where_vigentes
						$where_preinsc_rechazada
  			   ";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de inscripciones a cursada que pueden ser dadas de baja de acuerdo al filtro especificado.
	 * - No recupera la inscripción si el alumno ya existe en un acta cerrada de cursada o promoción (en estado abierta o cerrada) o en una evaluacion parcial.
	 * - Para actividades de propuestas de tipo curso, solo devuelve inscripciones de períodos lectivos con fecha de finalizacion de dictado mayor o igual a hoy.
	 * - Para actividades de propuestas de que no sean de tipo curso, devuelve inscripciones mientras la fecha tope de movimientos sea mayor o igual a hoy
	 * - NO se considera la fecha tope de bajas del período de inscripción. Esto es solo para la interfaz de autogestión. 
	 * - Si la inscripción es por priodidad, solo devuelve las inscripciones con prioridad = 1. Al darse de baja se eliminan las inscripciones relacionadas con otras prioridades.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_listado_baja($where = '')
	{
		if ($where) {
			$where = " AND $where";
		}

		$tipo_curso = toba::db()->quote(propuesta::tipo_curso);

		$sql = "SELECT		sga_insc_cursada.inscripcion,
							sga_insc_cursada.nro_transaccion,
							sga_insc_cursada.fecha_inscripcion,
							'(' || sga_elementos.codigo || ') ' || sga_elementos_plan.nombre AS actividad_descripcion,
							sga_comisiones.comision,
							sga_comisiones.nombre AS comision_nombre,
							sga_periodos.nombre AS periodo_nombre,
							sga_inscripciones_estados.nombre AS inscripcion_estado,
							sga_catedras.nombre AS catedra_nombre,
							sga_ubicaciones.nombre AS ubicacion_nombre,
							sga_periodos.anio_academico,
							vw_personas.persona,
							vw_personas.apellido_nombres AS apellido_y_nombres,
							vw_personas.tipo_nro_documento AS nro_identificacion,
							sga_alumnos.alumno,
							sga_alumnos.legajo,
							sga_alumnos.propuesta,
							sga_alumnos.plan_version,
							sga_propuestas.nombre_abreviado AS propuesta_nombre,
							sga_turnos_cursadas.nombre AS turno_nombre,
							sga_elementos.elemento,
							sga_elementos.codigo AS actividad_codigo,
							sga_elementos_plan.nombre AS actividad_nombre,
							sga_comisiones.cobrable
				FROM		sga_insc_cursada
							JOIN sga_inscripciones_estados ON sga_insc_cursada.estado = sga_inscripciones_estados.estado
							JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
							JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
							JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
							JOIN sga_comisiones ON sga_insc_cursada.comision = sga_comisiones.comision
							JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento 
							JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_comisiones.elemento 
							JOIN sga_elementos_plan ON (sga_elementos_plan.plan_version = sga_insc_cursada.plan_version AND sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision)
							JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_insc_cursada.plan_version
							JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
							LEFT JOIN sga_comisiones_excep_perlect ON sga_comisiones_excep_perlect.comision = sga_comisiones.comision
				WHERE		sga_periodos.fecha_fin >= CURRENT_DATE AND
							(
								-- Que la fecha actual sea menor o igual a la fecha tope de movimientos del período lectivo.
								(sga_planes.administra_cursos = '" . comunes::no . "' 
								 AND CURRENT_DATE <= sga_periodos_lectivos.fecha_tope_movimientos
								) 
								OR
								-- Si se trata de un plan que administra cursos, recuperar las inscripciones 
								-- a comisiones cuyo dictado no haya finalizado.
								(sga_planes.administra_cursos = '" . comunes::si . "' 
								 AND CURRENT_DATE <= COALESCE(sga_comisiones_excep_perlect.fecha_fin_dictado, sga_periodos_lectivos.fecha_fin_dictado) 
								) 
							) AND 
							
							-- Inscripciones sin prioridad o 
							-- inscripciones con prioridad ya procesadas y aceptada la preinscripcion.
							-- inscripciones por prioridad sin procesar y solo muestra la de prioridad 1 (al borrar se borran las otras inscripciones a la actividad)
							(sga_insc_cursada.tipo = '" . inscripcion_cursada::tipo_inscripcion . "' OR 
							 (sga_insc_cursada.tipo = '" . inscripcion_cursada::tipo_preinscripcion . "' AND sga_insc_cursada.estado_preinscripcion = '" . inscripcion_cursada::preinsc_aceptada . "') OR
							 (sga_insc_cursada.tipo = '" . inscripcion_cursada::tipo_preinscripcion . "' AND sga_insc_cursada.estado_preinscripcion = '" . inscripcion_cursada::preinsc_pendiente . "' AND sga_insc_cursada.prioridad = 1)
							) AND

							NOT EXISTS (-- Alumno en acta de Regulares y/o Promocion abierta.
										SELECT	1
										FROM	sga_evaluaciones,
												sga_evaluaciones_tipos,
												sga_eval_detalle_cursadas
										WHERE	sga_evaluaciones.entidad = sga_comisiones.entidad AND
												sga_eval_detalle_cursadas.evaluacion = sga_evaluaciones.evaluacion AND
												sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo AND
												sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND
												sga_eval_detalle_cursadas.alumno = sga_insc_cursada.alumno AND
												(sga_eval_detalle_cursadas.id_acta_cursada IS NOT NULL OR sga_eval_detalle_cursadas.id_acta_promocion IS NOT NULL)
										UNION
										-- Alumno en acta de Regulares y/o Promocion cerrada
										SELECT	1
										FROM	sga_actas,
												sga_actas_detalle
										WHERE	sga_actas.comision = sga_comisiones.comision AND
												sga_actas_detalle.id_acta = sga_actas.id_acta AND
												sga_actas.estado = '" . acta::estado_cerrada . "' AND
												sga_actas_detalle.alumno = sga_insc_cursada.alumno) AND

							-- Alumno en evaluaciones de la comisión.
							NOT EXISTS (SELECT	1
										FROM	sga_eval_detalle,
												sga_evaluaciones
										WHERE	sga_evaluaciones.entidad = sga_comisiones.entidad AND
												sga_evaluaciones.evaluacion = sga_eval_detalle.evaluacion AND
												sga_eval_detalle.alumno = sga_insc_cursada.alumno
										)
					$where
				ORDER BY	ubicacion_nombre,
							actividad_nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene la lista de inscripciones para una dada comisión
	 *
	 */
	function get_inscripciones_por_comision($comision, $where = '')
	{
		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT 	sga_insc_cursada.inscripcion,
						sga_insc_cursada.comision,
						sga_insc_cursada.alumno,
				        sga_insc_cursada_instancias.instancia
				FROM 	sga_insc_cursada
						LEFT JOIN sga_insc_cursada_instancias ON sga_insc_cursada_instancias.inscripcion = sga_insc_cursada.inscripcion
				WHERE	sga_insc_cursada.comision = $comision
						$where
			;";

		return guarani_db::consultar($sql);
	}

	function get_datos_inscripcion($nro_transaccion)
	{
		$nro_transaccion = toba::db()->quote($nro_transaccion);

		$sql = "SELECT 	sga_insc_cursada.comision, 
						sga_insc_cursada.alumno, 
						sga_insc_cursada.tipo, 
						sga_insc_cursada.prioridad, 
						sga_insc_cursada.estado_preinscripcion, 
						sga_insc_cursada.plan_version, 
						sga_insc_cursada.fecha_inscripcion, 
						to_char(sga_insc_cursada.fecha_inscripcion, '" . comunes::formato_fecha_hora_visual . "') as fecha_inscripcion_visual,
						sga_insc_cursada.fuera_de_termino, 
						sga_insc_cursada.autorizado_por, 
						sga_insc_cursada.nro_transaccion, 
						sga_insc_cursada.estado,
						sga_inscripciones_estados.nombre as estado_nombre,
						sga_insc_cursada.sq_token
				FROM	sga_insc_cursada,
						sga_inscripciones_estados
				WHERE	sga_insc_cursada.nro_transaccion = $nro_transaccion
				AND		sga_inscripciones_estados.estado = sga_insc_cursada.estado
						;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Recupera los datos del alumno, nombre de comisión y, en caso de existir, subcomisión, asociados a las
	 * inscripciones a cursadas.
	 * @param str $where filtro sobre las inscripciones para las que se desea averiguar los datos.
	 */
	function get_datos_basicos_inscripciones($where)
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = "SELECT 	vw_insc_cursada.inscripcion,
						vw_insc_cursada.comision,
						sga_comisiones.elemento,
						sga_comisiones.nombre as comision_nombre,
						vw_periodos_lectivos.nombre as periodo_nombre,
						vw_insc_cursada.alumno, 
						sga_alumnos.propuesta,
						vw_personas.persona,
						vw_personas.apellido_nombres,
						vw_personas.tipo_nro_documento,
						sga_alumnos.legajo,
						sga_alumnos.division as alumno_division,
						sga_divisiones.nombre as division_nombre,
						sga_propuestas.nombre as propuesta_nombre,
						vw_insc_cursada.tipo, 
						vw_insc_cursada.prioridad, 
						vw_insc_cursada.plan_version, 
						vw_insc_cursada.fecha_inscripcion, 
						vw_insc_cursada.nro_transaccion, 
						vw_insc_cursada.estado,
						vw_insc_subcomision.subcomision,
						sga_subcomisiones.nombre as subcomision_nombre
				FROM	vw_insc_cursada
						JOIN sga_alumnos ON vw_insc_cursada.alumno = sga_alumnos.alumno
						JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
						JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
						JOIN sga_comisiones ON vw_insc_cursada.comision = sga_comisiones.comision
						JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
						LEFT JOIN sga_divisiones ON sga_alumnos.division = sga_divisiones.division
						LEFT JOIN vw_insc_subcomision ON vw_insc_cursada.inscripcion = vw_insc_subcomision.inscripcion
						LEFT JOIN sga_subcomisiones ON vw_insc_subcomision.subcomision = sga_subcomisiones.subcomision
				$where
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Recupera los datos de los alumnos asociados a un conjunto de inscripciones o id de inscripción dado.
	 * No se incluyen los datos de la comisión, si no únicamente de los alumnos (utilizado para obtener datos del Alumno
	 * asociado a una Inscripción Origen y datos de Comisión Destino a la cual se distribuirá).
	 * @param type $where
	 * @return type
	 */
	function get_datos_alumnos_inscripciones($inscripcion, $filtro = '')
	{
		$where = "WHERE true";
		if (is_array($inscripcion)) {
			$cond_in_inscripciones = guarani_sql::armar_condicion_in($inscripcion, 'vw_insc_cursada', 'inscripcion', 'inscripcion');
			empty($cond_in_inscripciones) ? $filtro_inscripciones = ' ' : $filtro_inscripciones = ' AND ' . $cond_in_inscripciones;
		} else {
			$inscripcion = toba::db()->quote($inscripcion);
			$filtro_inscripciones = " AND vw_insc_cursada.inscripcion = $inscripcion";
		}
		if ($filtro) {
			$where .= " AND $filtro";
		}

		$sql = "SELECT
					vw_insc_cursada.inscripcion,
					vw_insc_cursada.alumno,
					sga_alumnos.persona,
					vw_personas.apellido_nombres	AS apellido_y_nombres,
					vw_personas.tipo_nro_documento	AS alumno_identificacion,
					sga_alumnos.legajo,
					sga_alumnos.propuesta,
					sga_propuestas.nombre			AS propuesta_nombre

				FROM
					vw_insc_cursada
					JOIN sga_alumnos ON vw_insc_cursada.alumno = sga_alumnos.alumno
				    JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta	

				$where
					$filtro_inscripciones
					
				ORDER BY
					vw_personas.apellido,
				   	vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	function get_nuevo_nro_transaccion()
	{
		return comunes::get_nuevo_nro_transaccion();
	}

	/**
	 * Retorna las actividades disponibles para inscribirse a cursar de un alumno
	 * @param str		$alumno							Alumno en cuestión.
	 * @param str		$plan_version					Plan versión del alumno.
	 * @param str		$where							Filtro a utilizar.
	 * @param boolean	$fuera_de_termino				Indica si filtra por períodos vigentes (false) o vencidos (true).
	 */
	function get_actividades_disponibles_inscripcion($param_ingresantes_oferta_actividades,
			$param_filtro_actividades_con_comision,
			$param_cursa_en_cualquier_ubicacion,
			$param_recursa_regularizadas,
			$alumno, $propuesta, $plan, $plan_version, $ubicacion,
			$where = '', $fuera_de_termino = false)
	{
		/**
		 * 	Si el parámetro del sistema cur_ingresantes_oferta_actividades = PRIMER_AÑO:
		 *  Sólo traer las actividades correspondan al primer año del plan de estudios.
		 */
		$filtro_solo_primer_año = '';
		if ($param_ingresantes_oferta_actividades == 'PRIMER_AÑO') {
			if (guarani::co('co_alumnos')->es_ingresante_periodos_insc_cursada($alumno)) {
				$filtro_solo_primer_año = 'AND sga_elementos_plan.anio_de_cursada = 1';
			}
		}

		if ($param_cursa_en_cualquier_ubicacion == comunes::si) {
			$ubicacion = 'null';
		}

		$filtro_solo_con_comision_vigente = '';
		//Si el parámetro cur_filtro_actividades_con_comision = S
		//solo recuperar las actividades con comisión definida y con períodos de inscripción vigentes
		if ($param_filtro_actividades_con_comision == comunes::si) {
			$interfaz = comunes::interfaz_gestion; // Gestion
			$en_periodo_inscripcion = (!$fuera_de_termino) ? 'true' : 'false';
			$filtro_solo_con_comision_vigente = " AND vw_actividades_plan.elemento IN (SELECT * FROM f_actividades_con_comisiones_vigentes($propuesta, $plan, $plan_version, $en_periodo_inscripcion, $ubicacion, $interfaz, $alumno)) ";
		}

		// Si el parámetro cur_recursar_regularizadas = 'S', agregar la columna 'regularizada'.
		$select_regularizada = '';
		$filtro_no_regularizadas = '';
		if ($param_recursa_regularizadas == comunes::si) {
			$select_regularizada = ",	CASE
											WHEN (	SELECT	COUNT(1)
													FROM	vw_regularidades_basica
													WHERE	vw_regularidades_basica.elemento = vw_actividades_plan.elemento AND
															vw_regularidades_basica.alumno = $alumno AND
															vw_regularidades_basica.resultado = '" . comunes::resultado_nota_aprobado . "' AND
															(vw_regularidades_basica.fecha_vigencia IS NULL OR	vw_regularidades_basica.fecha_vigencia >= CURRENT_DATE)) > 0 THEN 1
											ELSE 0
										END AS regularizada";
		} else { // Si el parámetro cur_recursar_regularizadas = 'N', excluir actividades regularizadas vigentes.
			$filtro_no_regularizadas = " AND vw_actividades_plan.elemento NOT IN (	SELECT	vw_regularidades_basica.elemento
																					FROM	vw_regularidades_basica
																					WHERE	vw_regularidades_basica.alumno = $alumno AND
																							vw_regularidades_basica.resultado = '" . comunes::resultado_nota_aprobado . "' AND
																							(vw_regularidades_basica.fecha_vigencia IS NULL OR	vw_regularidades_basica.fecha_vigencia >= CURRENT_DATE))";
		}

		$alumno = toba::db()->quote($alumno);
		$plan_version = toba::db()->quote($plan_version);

		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT	vw_actividades_plan.elemento as id,
						vw_actividades_plan.codigo,
						vw_actividades_plan.nombre,
						'(' || vw_actividades_plan.codigo || ') ' || vw_actividades_plan.nombre as descr,
						vw_actividades_plan.entidad_subtipo_nombre,
						sga_elementos_plan.creditos
						$select_regularizada
				FROM	vw_actividades_plan
				JOIN	sga_elementos_plan ON sga_elementos_plan.elemento_plan = vw_actividades_plan.elemento_plan
				WHERE	vw_actividades_plan.plan_version = $plan_version
						AND	vw_actividades_plan.estado = '" . comunes::estado_activo . "'
						$filtro_solo_primer_año
						-- Se obvian las actividades aprobadas
						AND	vw_actividades_plan.elemento
							NOT IN (SELECT	vw_hist_academica_basica.elemento
									FROM	vw_hist_academica_basica
									WHERE	vw_hist_academica_basica.alumno = $alumno
									AND		vw_hist_academica_basica.resultado = '" . comunes::resultado_nota_aprobado . "')
						$filtro_solo_con_comision_vigente
						$filtro_no_regularizadas
						$where
				 ORDER BY	
				 		vw_actividades_plan.codigo
				;";

		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si existen actividades con comisiones vigentes para una propuesta, plan, plan-versión y ubicación dados.
	 * 
	 * @param integer $propuesta
	 * @param integer $plan
	 * @param integer $plan_version
	 * @param integer $ubicacion
	 * @param string  $param_cursa_en_cualquier_ubicacion
	 * @param integer $interfaz
	 * @return boolean
	 */
	function hay_actividades_con_comisiones_vigentes($propuesta, $plan, $plan_version, $ubicacion, $alumno, $param_cursa_en_cualquier_ubicacion, $interfaz)
	{
		if ($param_cursa_en_cualquier_ubicacion == comunes::si) {
			$ubicacion = 'null';
		}
                
		$sql = "SELECT COUNT(1) as cant FROM f_actividades_con_comisiones_vigentes($propuesta, $plan, $plan_version, true, $ubicacion, $interfaz, $alumno)";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}

	/**
	 * Obtiene un listado de actividades factibles de aplicar cambios de prioridad
	 *
	 * @param	 $alumno	ID de Alumno inscripto a las actividades
	 */
	function get_actividades_disp_cambio_prioridad($alumno)
	{
		$alumno = toba::db()->quote($alumno);

		$sql = "SELECT
					sga_insc_cursada.nro_transaccion,
					sga_comisiones.elemento,
					vw_actividades_plan.codigo,
					vw_actividades_plan.nombre,
					sga_elementos_plan.creditos,
					vw_actividades_plan.entidad_subtipo_nombre,
					count(*) as cant_comisiones
				
				FROM
					sga_alumnos
					JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
					JOIN sga_insc_cursada ON sga_alumnos.alumno = sga_insc_cursada.alumno
					JOIN sga_comisiones ON sga_insc_cursada.comision = sga_comisiones.comision
					JOIN vw_actividades_plan ON (sga_comisiones.elemento = vw_actividades_plan.elemento AND sga_alumnos.plan_version = vw_actividades_plan.plan_version)
					JOIN sga_elementos_plan ON (vw_actividades_plan.plan_version = sga_elementos_plan.plan_version AND vw_actividades_plan.elemento_revision = sga_elementos_plan.elemento_revision)

				WHERE
					sga_alumnos.alumno = $alumno

					-- Que correspondan a períodos de inscripción vigentes
					AND sga_comisiones.comision IN (SELECT * FROM f_comisiones_vigentes_una_actividad(sga_alumnos.propuesta, sga_planes_versiones.plan, sga_alumnos.plan_version, sga_comisiones.elemento, true, sga_comisiones.ubicacion, " . comunes::interfaz_gestion . ", sga_alumnos.alumno))
					AND sga_insc_cursada.fuera_de_termino = '" . comunes::no . "'

					-- Que este preinscripto en las comisiones
					AND sga_insc_cursada.tipo = '" . inscripcion_cursada::tipo_preinscripcion . "'

					-- Que la prioridad de la inscripción en la comision no haya sido procesada
					AND sga_insc_cursada.estado_preinscripcion = '" . inscripcion_cursada::estado_pendiente . "'

				GROUP BY                 
					sga_insc_cursada.nro_transaccion,
					sga_comisiones.elemento,
					vw_actividades_plan.codigo,
					vw_actividades_plan.nombre,
					sga_elementos_plan.creditos,
					vw_actividades_plan.entidad_subtipo_nombre
				
				HAVING                
					count(*) > 1
				
				ORDER BY
					vw_actividades_plan.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos de las comisiones (sin acta de cursada/promoción cerrada)
	 * en las que se encuentra inscripto un alumno, según el filtro dado.
	 *
	 * @param	$alumno	Id del alumno para el cual se desean recuperar las comisiones.
	 * @param	$where	Filtro sobre las comisiones a recuperar.
	 */
	function get_datos_comisiones_insc_alumno($alumno, $where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT		sga_insc_cursada.inscripcion,
							sga_insc_cursada.alumno,
							sga_comisiones.comision,
							sga_comisiones.nombre AS comision_nombre,
							vw_periodos_lectivos.anio_academico,
							vw_periodos_lectivos.nombre AS periodo_lectivo_nombre,
							sga_turnos_cursadas.nombre AS turno_nombre,
							sga_ubicaciones.nombre AS ubicacion_nombre,
							sga_elementos.codigo AS actividad_codigo,
							sga_elementos.nombre AS actividad_nombre
				FROM		sga_insc_cursada
							JOIN sga_comisiones ON sga_insc_cursada.comision = sga_comisiones.comision
							JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
							JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
							JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				WHERE		sga_insc_cursada.alumno = $alumno AND
							NOT EXISTS (SELECT	sga_actas.id_acta
										FROM	sga_actas
										WHERE	sga_actas.comision = sga_insc_cursada.comision AND
												sga_actas.estado = '" . acta::estado_cerrada . "')
				ORDER BY	sga_comisiones.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_subcomisiones_por_inscripcion($inscripcion)
	{
		$inscripcion = toba::db()->quote($inscripcion);

		$sql = "SELECT 
					sga_insc_subcomision.inscripcion,
					sga_insc_subcomision.subcomision
				FROM 
					sga_insc_subcomision
				WHERE
					sga_insc_subcomision.inscripcion = $inscripcion";

		return guarani_db::consultar($sql);
	}

	function get_listado_inscripciones($where = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = "SELECT
					sga_insc_cursada.inscripcion,
					sga_insc_cursada.alumno,
					sga_comisiones.elemento,
					sga_comisiones.comision,
					sga_alumnos.persona,
					sga_alumnos.propuesta,
					sga_alumnos.plan_version,
					vw_personas.apellido_nombres AS apellido_y_nombres,
					vw_personas.tipo_nro_documento AS nro_identificacion,
					sga_alumnos.legajo,
					'(' || sga_elementos.codigo || ') ' ||	sga_elementos.nombre AS actividad_codigo_y_nombre,
					sga_comisiones.nombre AS comision_nombre,
					sga_propuestas.nombre AS propuesta_nombre
				FROM
					sga_insc_cursada
					JOIN sga_inscripciones_estados ON sga_insc_cursada.estado = sga_inscripciones_estados.estado
					JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
					JOIN sga_comisiones ON sga_insc_cursada.comision = sga_comisiones.comision
					JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
					JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
					JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
				$where
				ORDER BY
					vw_personas.apellido,
				   	vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	function get_listado_motivos_rechazo_inscripcion_cursada_activos()
	{
		$where = "(sga_inscripciones_motivos_rechazo.alcance = 'Cursadas' OR sga_inscripciones_motivos_rechazo.alcance = 'Todos')";
		return guarani::co('co_inscripciones')->get_listado_motivos_rechazo_inscripcion_activos($where);
	}

	function get_instancias_inscripcion($inscripcion)
	{
		$inscripcion = toba::db()->quote($inscripcion);

		$sql = "SELECT 	sga_insc_cursada_instancias.instancia
				FROM 	sga_insc_cursada_instancias
				WHERE	sga_insc_cursada_instancias.inscripcion = $inscripcion";

		return guarani_db::consultar($sql);
	}

	function get_instancias_nombre_inscripcion($inscripcion)
	{
		$inscripcion = toba::db()->quote($inscripcion);
		$sql = "SELECT f_instancias_insc_cursada($inscripcion) as instancias";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['instancias'];
	}

	function existe_instancia_para_inscripcion($instancia, $inscripcion)
	{
		$instancia = toba::db()->quote($instancia);
		$inscripcion = toba::db()->quote($inscripcion);
		$sql = "SELECT		1
				FROM		sga_insc_cursada_instancias
				WHERE		sga_insc_cursada_instancias.instancia = $instancia	AND
							sga_insc_cursada_instancias.inscripcion = $inscripcion
		";
		$datos = guarani_db::consultar($sql);
		return (!empty($datos));
	}

	/**
	 * Retorna las opciones Aceptada / Pendiente / Rechazada / Baja.
	 */
	function get_inscripciones_estados()
	{
		return array(array('valor' => 'A', 'descr' => 'Aceptada'),
			array('valor' => 'P', 'descr' => 'Pendiente'),
			array('valor' => 'B', 'descr' => 'Baja'),
			array('valor' => 'R', 'descr' => 'Rechazada')
		);
	}

	/**
	 * Retorna el listado de turnos de cursada de una inscripción.
	 */
	function get_turnos_cursadas()
	{
		$sql = "SELECT 	sga_turnos_cursadas.turno	AS valor,
						sga_turnos_cursadas.nombre	AS descr
				FROM	sga_turnos_cursadas
				ORDER BY 2";

		return guarani_db::consultar($sql);
	}

	function get_actividades_insc_prioridad($periodo_lectivo, $actividades, $criterios_prioridad = '', $cont_criterios = 0)
	{
		$periodo_lectivo = toba::db()->quote($periodo_lectivo);
		$elementos = '(' . implode(', ', $actividades) . ')';

		$order_by = '';
		for ($i = 0; $i < $cont_criterios; $i++) {
			$order_by .= $i + 1 . ', ';
		}

		$sql = "SELECT		$criterios_prioridad
							sga_insc_cursada.inscripcion,
							sga_insc_cursada.alumno,
							sga_alumnos.persona,
							sga_alumnos.propuesta,
							sga_comisiones.elemento,
							sga_insc_cursada.comision,
							sga_insc_cursada.prioridad,
							sga_comisiones.cupo,
							sga_insc_cursada.tipo,
							sga_insc_cursada.estado_preinscripcion,
							sga_insc_cursada.plan_version,
							sga_insc_cursada.fecha_inscripcion,
							CAST(sga_insc_cursada.fecha_inscripcion AS DATE) AS fecha_inscripcion_formato_date,
							sga_insc_cursada.fuera_de_termino,
							sga_insc_cursada.autorizado_por,
							sga_insc_cursada.nro_transaccion,
							sga_insc_cursada.motivo_excepcion,
							sga_insc_cursada.exceptuado,
							sga_insc_cursada.interfaz,
							sga_insc_cursada.estado,
							-- Campos a mostrar en reportes --------------------
							sga_elementos.nombre || ' (' || sga_elementos.codigo || ')' AS actividad_descr,
							'Comision: ' || sga_comisiones.nombre || COALESCE(' - Turno: ' || sga_turnos_cursadas.nombre, '') || ' - Cupo: ' || COALESCE(CAST(sga_comisiones.cupo AS VARCHAR), 'Sin cupo definido') AS comision_descr,
							COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) as apellido,
							COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombres,
							COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ' ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) AS apellido_y_nombre,
							mdp_tipo_documento.desc_abreviada || ' ' || mdp_personas_documentos.nro_documento AS identificacion,
							'(' || sga_elementos.codigo || ') ' || sga_elementos.nombre AS actividad_codigo_y_nombre,
							sga_comisiones.nombre AS comision_nombre,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_periodos.anio_academico,
							sga_periodos_genericos.nombre AS periodo_generico_nombre,
							f_instancias_insc_cursada(sga_insc_cursada.inscripcion) AS instancias,
							sga_periodos.nombre AS periodo_nombre
				FROM		sga_insc_cursada
							JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
							JOIN mdp_personas ON sga_alumnos.persona = mdp_personas.persona
							JOIN mdp_personas_documentos ON mdp_personas.documento_principal = mdp_personas_documentos.documento
							JOIN mdp_tipo_documento ON mdp_personas_documentos.tipo_documento = mdp_tipo_documento.tipo_documento
							JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
							JOIN sga_comisiones ON sga_insc_cursada.comision = sga_comisiones.comision
							JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
							JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
							JOIN sga_periodos_genericos ON sga_periodos.periodo_generico = sga_periodos_genericos.periodo_generico
							LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				WHERE		sga_comisiones.periodo_lectivo = $periodo_lectivo
							AND sga_comisiones.elemento IN $elementos
							AND sga_insc_cursada.tipo = '" . inscripcion_cursada::tipo_preinscripcion . "'
							AND sga_insc_cursada.estado_preinscripcion = '" . inscripcion_cursada::preinsc_pendiente . "'
				ORDER BY	sga_comisiones.elemento,
							$order_by
							sga_insc_cursada.prioridad
		";

		return guarani_db::consultar($sql);
	}

	function get_cant_insc_aceptadas($comision)
	{
		$comision = toba::db()->quote($comision);
		$tipo_inscripcion = toba::db()->quote(inscripcion_cursada::tipo_inscripcion);
		$tipo_preinscripcion = toba::db()->quote(inscripcion_cursada::tipo_preinscripcion);
		$preinsc_aceptada = toba::db()->quote(inscripcion_cursada::preinsc_aceptada);
		$estado_aceptada = toba::db()->quote(inscripcion_cursada::estado_aceptada);

		$sql = "SELECT 	COUNT(1) as cant
				FROM  	sga_insc_cursada
				WHERE  (sga_insc_cursada.tipo = $tipo_inscripcion OR
						(sga_insc_cursada.tipo = $tipo_preinscripcion AND sga_insc_cursada.estado_preinscripcion = $preinsc_aceptada)
						)	
				  AND estado = $estado_aceptada
				  AND comision = $comision;
				";

		$res = guarani_db::consultar_fila($sql);
		return $res['cant'];
	}

	function get_cant_insc_aceptadas_bh($comision)
	{
		$comision = toba::db()->quote($comision);
		$tipo_inscripcion = toba::db()->quote(inscripcion_cursada::tipo_inscripcion);
		$tipo_preinscripcion = toba::db()->quote(inscripcion_cursada::tipo_preinscripcion);
		$preinsc_aceptada = toba::db()->quote(inscripcion_cursada::preinsc_aceptada);
		$estado_aceptada = toba::db()->quote(inscripcion_cursada::estado_aceptada);

		$sql = "SELECT 	COUNT(1) AS cant
				FROM  	sga_insc_cursada 
				WHERE  (sga_insc_cursada.tipo = $tipo_inscripcion OR
						(sga_insc_cursada.tipo = $tipo_preinscripcion AND  sga_insc_cursada.estado_preinscripcion = $preinsc_aceptada )
						)	
				  AND estado = $estado_aceptada
				  AND comision IN (
							SELECT 	DISTINCT bh2.comision 
							  FROM  sga_comisiones_bh,
							  JOIN sga_comisiones_bh as bh2 ON bh2.asignacion = sga_comisiones_bh.asignacion
							 WHERE sga_comisiones_bh.comision = $comision
							);
				";

		$res = guarani_db::consultar_fila($sql);
		return $res['cant'];
	}

	function get_criterios_prioridad_insc_cursada($solo_activos = true, $todos_seleccionados = true)
	{
		if ($solo_activos) {
			$where = ' WHERE activo = 1';
		} else {
			$where = '';
		}

		if ($todos_seleccionados) {
			$select = ', 1 as seleccion';
		} else {
			$select = '';
		}

		$sql = "SELECT		criterio,
							nombre,
							descripcion,
							orden,
							activo,
							criterio_base
							$select
				FROM		sga_criterios_prioridad_cursada
				$where
				ORDER BY	orden
				";

		return guarani_db::consultar($sql);
	}

	/**
	 * 	Listado de inscripciones a cursadas de un alumno de actividades de planes que administran "cursos"
	 * 	Si se pasa el filtro de solo aprobadas (solo_aprobadas = true) entonces solo recupera los cursos
	 * 	que el alumno aprobo por examen final/promocion/equivalencia/aprob. x resolucion...
	 * @param $alumno Id del alumno para el cual se desean recuperar las comisiones.
	 * @param $where Filtro sobre las comisiones a recuperar.
	 * @return array
	 */
	function get_cursos_inscripto_alumno($alumno, $where = '', $solo_aprobadas = false)
	{
		$where_aprobadas = '';
		if ($solo_aprobadas) {
			$where_aprobadas = " AND vw_comisiones.elemento IN (SELECT elemento FROM vw_hist_academica WHERE alumno = $alumno AND resultado = '" . escalas_notas::resultado_aprobado . "')";
		}

		$sql = "
				SELECT  	
					vw_comisiones.comision,
					vw_comisiones.comision_nombre as nombre,
					vw_comisiones.anio_academico as anio_academico,
					vw_comisiones.periodo_lectivo as periodo_lectivo,
					vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
					vw_comisiones.elemento as actividad,
					vw_comisiones.elemento_nombre as nombre_actividad,
					vw_comisiones.elemento_codigo as actividad_codigo,
					'(' || vw_comisiones.elemento_codigo || ') ' || vw_comisiones.elemento_nombre as actividad_codigo_nombre,					
					f_docentes_comision(vw_comisiones.comision, false) as docentes,
					vw_comisiones.fecha_inicio_dictado as fecha_inicio_dictado,	
					vw_comisiones.fecha_fin_dictado as fecha_fin_dictado	
				FROM
					vw_insc_cursada
					JOIN vw_comisiones ON vw_insc_cursada.comision = vw_comisiones.comision
					JOIN sga_planes_versiones ON vw_insc_cursada.plan_version = sga_planes_versiones.plan_version
					JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
				WHERE		
					vw_insc_cursada.alumno = $alumno 
					AND vw_insc_cursada.estado = '" . comunes::estado_activo . "'
					AND sga_planes.administra_cursos = " . toba::db()->quote(comunes::si) . "
					$where
					$where_aprobadas 
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el estado de una inscripción según el número de transacción.
	 */
	function get_estado_inscripcion_by_transaccion($transaccion)
	{
		$transaccion = toba::db()->quote($transaccion);

		$sql = "SELECT estado AS estado FROM sga_insc_cursada WHERE nro_transaccion = $transaccion
                UNION
                SELECT operacion AS estado FROM sga_insc_cursada_log WHERE nro_transaccion = $transaccion
                ";

		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Obtiene las propuestas asociadas a un conjunto de inscripciones
	 * 
	 * @param array $inscripciones
	 * @return array
	 */
	function get_propuestas_inscripciones($inscripciones)
	{
		$where = ' AND ' . guarani_sql::armar_condicion_in($inscripciones, 'sga_insc_cursada', 'inscripcion', 'inscripcion');

		$sql = "SELECT 
					DISTINCT sga_alumnos.propuesta
				FROM 
					sga_insc_cursada,
					sga_alumnos
				WHERE
					sga_insc_cursada.alumno = sga_alumnos.alumno
					$where";

		$datos = guarani_db::consultar($sql);
		return aplanar_matriz($datos, 'propuesta');
	}

	/**
	 * Retorna la lista de planes asociados a un conjunto de inscripciones.
	 * 
	 * @param array $inscripciones
	 * @return array
	 */
	function get_planes_inscripciones($inscripciones)
	{
		$where = guarani_sql::armar_condicion_in($inscripciones, 'sga_insc_cursada', 'inscripcion', 'inscripcion');
		$sql = "SELECT	DISTINCT sga_planes_versiones.plan
				FROM	sga_insc_cursada
						JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
						JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
				WHERE	$where
		";
		$datos = guarani_db::consultar($sql);
		return aplanar_matriz($datos, 'plan');
	}

	/**
	 * Obtiene las inscripciones a comision de un alumno
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_inscripciones_actividad_alumno($alumno, $where = '')
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT
					vw_comisiones.comision,
					sga_elementos_plan.nombre,
					vw_comisiones.elemento as valor,
					sga_elementos_plan.nombre || ' (' || sga_elementos.codigo || ') ' as descr
				FROM sga_alumnos
				JOIN sga_insc_cursada ON sga_alumnos.alumno = sga_insc_cursada.alumno
				JOIN vw_comisiones ON vw_comisiones.comision = sga_insc_cursada.comision
				JOIN sga_elementos ON sga_elementos.elemento = vw_comisiones.elemento	
				JOIN sga_elementos_revision ON sga_elementos_revision.elemento = sga_elementos.elemento	
				JOIN sga_elementos_plan ON (sga_elementos_plan.plan_version      = sga_insc_cursada.plan_version AND
				                            sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision)
				WHERE sga_alumnos.alumno = $alumno
			    $where
				ORDER BY sga_elementos_plan.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna las actividades disponibles para la inscripción masiva a cursadas
	 * 
	 * @param string $param_filtro_actividades_con_comision
	 * @param string $param_cursa_en_cualquier_ubicacion
	 * @param int	 $propuesta
	 * @param int	 $plan
	 * @param int	 $plan_version
	 * @param int	 $ubicacion
	 * @param string $where
	 * @return array
	 */
	function get_actividades_disponibles_inscripcion_masiva($param_filtro_actividades_con_comision, $param_cursa_en_cualquier_ubicacion,
			$propuesta, $plan, $plan_version, $ubicacion, $where = '')
	{
		if ($param_cursa_en_cualquier_ubicacion == comunes::si) {
			$ubicacion = 'null';
		}

		$filtro_solo_con_comision_vigente = '';
		// Si el parámetro cur_filtro_actividades_con_comision = S, sólo recuperar las 
		// actividades con comisión definida y con períodos de inscripción vigentes
		if ($param_filtro_actividades_con_comision == comunes::si) {
			$en_periodo_inscripcion = 'true';
			$interfaz = comunes::interfaz_gestion; // Gestion
			$alumno = 'null';

			$filtro_solo_con_comision_vigente = " AND vw_actividades_plan.elemento IN (
					SELECT * FROM f_actividades_con_comisiones_vigentes($propuesta, $plan, $plan_version, $en_periodo_inscripcion, $ubicacion, $interfaz, $alumno)
				) ";
		}

		$plan_version = toba::db()->quote($plan_version);

		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT	vw_actividades_plan.elemento as id,
						vw_actividades_plan.codigo,
						vw_actividades_plan.nombre,
						'(' || vw_actividades_plan.codigo || ') ' || vw_actividades_plan.nombre as descr,
						vw_actividades_plan.entidad_subtipo_nombre,
						sga_elementos_plan.creditos,
						sga_elementos_atrib.insc_cursada_por_prioridad,
						sga_elementos_plan.anio_de_cursada
				FROM sga_elementos_plan
				JOIN vw_actividades_plan ON (vw_actividades_plan.plan_version = sga_elementos_plan.plan_version	AND	
				                             vw_actividades_plan.elemento_revision = sga_elementos_plan.elemento_revision)
				LEFT JOIN sga_elementos_atrib ON sga_elementos_atrib.elemento = vw_actividades_plan.elemento
				WHERE sga_elementos_plan.plan_version = $plan_version
				AND	vw_actividades_plan.estado = '" . comunes::estado_activo . "'
				$filtro_solo_con_comision_vigente
				$where
				ORDER BY vw_actividades_plan.codigo
				;";

		return guarani_db::consultar($sql);
	}
    
    
}
