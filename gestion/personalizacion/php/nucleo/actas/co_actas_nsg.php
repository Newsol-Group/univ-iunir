<?php
 
class co_actas_nsg extends co_actas
 {
   	// Newsol - 06/2025 - Se agrega el porcentaje de asistencia a las actas de cursada
	/**
	 * Obtiene detalles de un acta dada.
	 *
	 */
	function get_datos_acta($id_acta)
	{ 
	
		$param_nombre_persona_doc_oficiales = pg_escape_string(persona::get_param_nombre_persona_doc_oficiales());
        	$sql_docentes = "f_docentes_acta_comision(sga_actas.id_acta, '{$param_nombre_persona_doc_oficiales}') as docentes,";
		$id_acta = toba::db()->quote($id_acta);
		$tipos_actas = $this->get_opciones_tipos_actas();
		$sql = "SELECT  sga_actas.nro_acta,
						sga_actas.origen,
						sga_actas.estado,
						sga_actas.version,
						sga_actas.version_impresa,
						sga_actas.tipo_acta,
						sga_actas.acta_referencia,
						sga_actas.renglones_folio,
						sga_actas.observaciones,  
						sga_actas.id_acta,
						sga_actas.evaluacion,
						sga_actas.comision,
						sga_actas.llamado_mesa,
						sga_actas.fecha_generacion,
						sga_actas.fecha_cierre,
						sga_actas.fecha_anulacion,
						sga_actas.nua,
						sga_actas.documento,
						sga_actas.nro_ultima_copia,
                        sga_actas.id_documento_digital,
                        sga_actas.estado_documento_digital,
						sga_actas.digital,
						sga_periodos.anio_academico								AS anio_academico_periodo,
						sga_comisiones.nombre									AS comision_nombre,
						sga_comisiones.entidad									AS comision_entidad,
						sga_comisiones.elemento,
						f_libros_acta(sga_actas.id_acta, 2)						AS libro_nombre,
						sga_ubicaciones.nombre 									AS nombre_ubicacion,
						sga_elementos.codigo									AS actividad_codigo,
						get_nombre_actividad_comision(sga_comisiones.comision, 1) as actividad_nombre,
						sga_elementos.codigo || ' - ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as codigo_y_nombre_actividad,
						sga_periodos_genericos.nombre							AS periodo_nombre,
						sga_instancias.nombre									AS instancia_nombre,
						sga_actas_estados.nombre								AS estado_nombre,
						sga_escalas_notas.escala_nota,
						sga_escalas_notas.nombre								AS escala_nota_nombre,
						sga_escalas_notas.tipo									AS escala_nota_tipo,
						sga_periodos.nombre										AS periodo_lectivo_nombre,
						sga_periodos_lectivos.fecha_inicio_dictado,
						to_char(sga_periodos_lectivos.fecha_inicio_dictado, '" . comunes::formato_fecha_visual . "') as fecha_inicio_visual,
						sga_periodos_lectivos.fecha_fin_dictado,
						to_char(sga_periodos_lectivos.fecha_fin_dictado, '" . comunes::formato_fecha_visual . "') as fecha_fin_visual,
						sga_periodos.fecha_inicio,
						to_char(sga_periodos.fecha_inicio, '" . comunes::formato_fecha_visual . "') as fecha_inicio_periodo_visual,
						sga_periodos.fecha_fin,
						to_char(sga_periodos.fecha_fin, '" . comunes::formato_fecha_visual . "') as fecha_fin_periodo_visual,
						f_modalidad_cursada_comision(sga_comisiones.comision) as modalidad_nombre,
						f_instancias_comision_escala_notas(sga_actas.comision, true) as instancias_escalas,
						sga_turnos_cursadas.nombre  AS nombre_turno,
						" . guarani_sql::get_case('sga_actas', 'tipo_acta', 'tipo_acta_descr', $tipos_actas) . ",
						CASE
							WHEN sga_actas.estado = '" . acta::estado_abierta . "' THEN
									-- Alumnos en eval_detalles_cursadas con acta no cerrada
									(SELECT count(1) 
									   FROM sga_eval_detalle_cursadas 
									  WHERE id_acta_cursada = sga_actas.id_acta 
									    AND sga_actas.origen = '" . acta::origen_regulares . "'
									    AND sga_eval_detalle_cursadas.acta_cursada_cerrada = 0
									) +
									(SELECT count(1) 
									   FROM sga_eval_detalle_cursadas 
									   WHERE id_acta_promocion = sga_actas.id_acta 
									     AND sga_actas.origen = '" . acta::origen_promociones ."'
									     AND sga_eval_detalle_cursadas.acta_promocion_cerrada = 0
									)	
							WHEN sga_actas.estado <> '" . acta::estado_abierta  . "' THEN
								-- Alumnos en sga_actas_detalle con acta cerrada
								(SELECT COUNT(1) FROM sga_actas_detalle WHERE sga_actas_detalle.id_acta = sga_actas.id_acta)
						END AS cant_alumnos,
						cerrada_por_docente AS cerrada_por_docente,
						$sql_docentes
						sga_actas_folios.folio,
						sga_actas_folios.libro_tomo,
						sga_actas_folios.folio_fisico,
						sga_comisiones_instancias.porc_asistencia

					FROM sga_actas 
					JOIN sga_comisiones ON sga_actas.comision = sga_comisiones.comision
					JOIN sga_comisiones_instancias ON sga_comisiones.comision = sga_comisiones_instancias.comision
					JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo 
					JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo 
					JOIN sga_periodos_genericos ON sga_periodos_genericos.periodo_generico = sga_periodos.periodo_generico
					JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento 
					JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion 
					JOIN sga_actas_instancias ON sga_actas.id_acta = sga_actas_instancias.id_acta
					JOIN sga_actas_estados ON sga_actas.estado = sga_actas_estados.estado
					JOIN sga_instancias ON sga_actas_instancias.instancia = sga_instancias.instancia
					JOIN sga_escalas_notas ON sga_actas_instancias.escala_nota = sga_escalas_notas.escala_nota
					LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
					LEFT JOIN sga_actas_folios ON (sga_actas.id_acta = sga_actas_folios.id_acta)
					WHERE sga_actas.id_acta = $id_acta
					ORDER BY sga_comisiones.nombre
				";
		return guarani_db::consultar_fila($sql);
	}

	// Newsol - 06/2025 - Se agrega el porcentaje de asistencia de la comisión
	/**
	 * Obtiene los alumnos de un acta de cursadas.
	 *
	 * @param integer	$id_acta					código de acta a la cual se quieren agregar detalles, puede ser -1 (acta::id_acta_ficticia) cuando todavia no existe.
	 * @param integer	$acta_abierta_instancia		código de instancia del acta abierta a la cual está asociado el acta.
	 * @param str		$where						filtro sobre los detalles a retornar.
	 * @param			$limit
	 * @param			$offset
	 * @param boolean	$para_anular				indica si el listado de alumnos se está obteniendo 
	 * para la Anulación del acta, en cuyo caso no debe considerarse el parámetro cur_inserta_alumnos_no_promocionales_en_acta
	 * ya que deben desasociarse todos los detalles de alumnos del acta al anular el acta de promoción.
	 * 
	 * @param array		$params_sistema				+ cur_inserta_alumnos_no_promocionales_en_acta
	 *								+ cur_orden_detalle_actas
	 */
	function get_alumnos_acta_cursadas($id_acta, $acta_abierta_instancia=null, $where='', $limit='', $offset='', $para_anular=false, $params_sistema = array())
	{
		$id_acta = toba::db()->quote($id_acta);
		if ($where) {
			$where = ' AND '.$where;
		}
                
		$apellido_y_nombre= persona::get_param_nombre_persona_doc_oficiales();
		// Chequea si $acta_abierta_instancia es null entonces no se buscan actas abiertas
		if (!is_null($acta_abierta_instancia)) {
			$param_ordenamiento = $params_sistema['cur_orden_detalle_actas'];
			$campo_ordenamiento = $this->get_campo_ordenamiento($param_ordenamiento);
	
			if ($acta_abierta_instancia == comision::inst_cursar) {
				$campo_instancia = 'instancia_cursada';
				$campo_acta = 'id_acta_cursada';
				$campo_nota = 'nota_cursada';
				$campo_escala_nota = 'escala_nota_cursada';
				$campo_resultado = 'resultado_cursada';
				$campo_fecha = 'fecha_regular';
				$campo_observaciones = 'observacion_cursada';
			} else {
				$campo_instancia = 'instancia_promocion';
				$campo_acta = 'id_acta_promocion';
				$campo_nota = 'nota_promocion';
				$campo_escala_nota = 'escala_nota_promocion';
				$campo_resultado = 'resultado_promocion';
				$campo_fecha = 'fecha_promocion';
				$campo_observaciones = 'observacion_promocion';
	
				$inserta_alumnos_no_promocionales_en_acta = $params_sistema['cur_inserta_alumnos_no_promocionales_en_acta'];
				
				if ($inserta_alumnos_no_promocionales_en_acta == comunes::no && !$para_anular) {
					$where .= " AND (sga_actas.tipo_acta = '".acta::tipo_normal."' AND sga_eval_detalle_cursadas.resultado_promocion = '".escalas_notas::resultado_aprobado."' OR sga_actas.tipo_acta = '".acta::tipo_rectificativa."')";
				}
			}

            $sql = "
					SELECT	sga_alumnos.persona,
							sga_alumnos.propuesta,
							sga_alumnos.legajo		AS legajo,
							vw_personas.apellido,
							vw_personas.nombres,
							$apellido_y_nombre AS apellido_y_nombre,
							vw_personas.tipo_nro_documento AS documento_principal,
							vw_personas.id_imagen,
							sga_eval_detalle_cursadas.evaluacion,
							sga_eval_detalle_cursadas.$campo_fecha as fecha,
							to_char(sga_eval_detalle_cursadas.$campo_fecha, '".comunes::formato_fecha_visual."') as fecha_display,
							COALESCE(sga_clases_asistencia_acum.porc_asistencia, 0) as pct_asistencia,
							sga_cond_regularidad.nombre AS cond_regularidad_nombre,
							sga_escalas_notas_det.nota,
							sga_escalas_notas_det.nota || ' (' || sga_escalas_notas_det.descripcion || ')' AS nota_nombre,
							sga_eval_detalle_cursadas.$campo_resultado AS resultado,
							COALESCE(sga_instancias_resultado.descripcion, sga_escalas_notas_resultado.descripcion) AS resultado_nombre,
							sga_actas.id_acta,
							sga_actas.comision,
							sga_eval_detalle_cursadas.alumno,
							sga_propuestas.nombre_abreviado AS propuesta_nombre,
							sga_eval_detalle_cursadas.$campo_escala_nota AS escala_nota,
							sga_alumnos.plan_version,
							sga_actas.renglones_folio,
							sga_eval_detalle_cursadas.cond_regularidad,
							sga_eval_detalle_cursadas.$campo_observaciones as observaciones,
							sga_eval_detalle_cursadas.inscripto,
							$acta_abierta_instancia AS instancia,
							sga_eval_detalle_cursadas.acta_cursada_cerrada,
							sga_eval_detalle_cursadas.instancia_cursada,
							sga_eval_detalle_cursadas.instancia_promocion,
							sga_eval_detalle_cursadas.acta_promocion_cerrada,
							sga_eval_detalle_cursadas.plan_version as plan_version_inscripcion,
							pv_insc.plan as plan_inscripcion,
							sga_comisiones_instancias.porc_asistencia

					FROM	sga_actas 
					JOIN	sga_eval_detalle_cursadas ON (sga_eval_detalle_cursadas.$campo_instancia = 1 AND
					                                      sga_eval_detalle_cursadas.$campo_acta = sga_actas.id_acta)
					JOIN	sga_alumnos ON sga_eval_detalle_cursadas.alumno = sga_alumnos.alumno
					JOIN	vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN	mdp_personas ON sga_alumnos.persona = mdp_personas.persona
					JOIN	sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
					JOIN	sga_planes_versiones as pv_insc ON pv_insc.plan_version = sga_eval_detalle_cursadas.plan_version    
					LEFT JOIN sga_cond_regularidad ON sga_cond_regularidad.cond_regularidad = sga_eval_detalle_cursadas.cond_regularidad
					LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.nota = sga_eval_detalle_cursadas.$campo_nota AND sga_escalas_notas_det.escala_nota = sga_eval_detalle_cursadas.$campo_escala_nota)
					LEFT JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = $acta_abierta_instancia AND sga_instancias_resultado.resultado = sga_eval_detalle_cursadas.$campo_resultado)
					LEFT JOIN sga_escalas_notas_resultado ON sga_escalas_notas_resultado.resultado = sga_eval_detalle_cursadas.$campo_resultado
					LEFT JOIN sga_clases_asistencia_acum ON (sga_clases_asistencia_acum.comision = sga_actas.comision AND sga_clases_asistencia_acum.alumno = sga_alumnos.alumno)
					LEFT JOIN sga_comisiones_instancias ON sga_actas.comision = sga_comisiones_instancias.comision
					
					WHERE sga_actas.id_acta = $id_acta 
					  AND sga_actas.estado = '" . acta::estado_abierta ."'
					$where
					
					ORDER BY $campo_ordenamiento
					$limit
					$offset
			";
	
			$datos = guarani_db::consultar($sql);
			if (!empty($datos)) {
				$detalle_acta = $this->procesar_listado_alumnos($datos, $datos[0]['renglones_folio']);
			} else {
				$detalle_acta = $datos;
			}
			
		} else {
			
			$sql = "SELECT 	
						vw_personas.persona,
						$apellido_y_nombre AS apellido_y_nombre,
						vw_personas.tipo_nro_documento AS documento_principal,
						sga_actas_detalle.fecha,
						to_char(sga_actas_detalle.fecha, '".comunes::formato_fecha_visual."') 			AS fecha_display,
						sga_actas_detalle.pct_asistencia,
						sga_cond_regularidad.nombre 													AS cond_regularidad_nombre,
						sga_escalas_notas_det.nota || ' (' || sga_escalas_notas_det.descripcion || ')'  AS nota_nombre,
						sga_actas_detalle.resultado,
						COALESCE(sga_instancias_resultado.descripcion, sga_escalas_notas_resultado.descripcion) AS resultado_nombre,
						sga_actas.id_acta,
						sga_actas_detalle.alumno,
						sga_alumnos.legajo		AS legajo,
						sga_propuestas.nombre_abreviado													AS propuesta_nombre,
						sga_actas_detalle.escala_nota,
						sga_actas_detalle.folio,
						sga_actas_detalle.renglon,
						sga_actas_detalle.observaciones,
						sga_actas_detalle.plan_version,
						sga_actas_detalle.estado,
						sga_comisiones_instancias.porc_asistencia

					FROM sga_actas
					JOIN sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta
					JOIN sga_alumnos ON sga_actas_detalle.alumno = sga_alumnos.alumno
					JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
					JOIN mdp_personas ON mdp_personas.persona = vw_personas.persona
					JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
					LEFT JOIN sga_cond_regularidad ON (sga_cond_regularidad.cond_regularidad = sga_actas_detalle.cond_regularidad)
					LEFT JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = sga_actas_detalle.instancia AND sga_instancias_resultado.resultado = sga_actas_detalle.resultado)
					LEFT JOIN sga_escalas_notas_resultado ON (sga_escalas_notas_resultado.resultado = sga_actas_detalle.resultado)
					LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.nota = sga_actas_detalle.nota AND sga_escalas_notas_det.escala_nota = sga_actas_detalle.escala_nota)
					LEFT JOIN sga_comisiones_instancias ON sga_actas.comision = sga_comisiones_instancias.comision

					WHERE sga_actas.id_acta = $id_acta
					  AND sga_actas.estado IN ('" . acta::estado_cerrada . "','" . acta::estado_anulada  . "') 
					$where
					
					ORDER BY 
						sga_actas_detalle.folio, 
						sga_actas_detalle.renglon;
			";
			
			$detalle_acta = guarani_db::consultar($sql);
		}
		
		return $detalle_acta;
	}

	// Newsol - 06/2025 - Calculo el % de asistencia del alumno en la comisión
	/**
	 * Obtiene los alumnos de un acta de cursadas.
	 *
	 * @param integer	$id_acta		código de acta a la cual se quieren agregar detalles, puede ser -1 (acta::id_acta_ficticia) cuando todavia no existe.
	 * @param integer	$id_alumno		identificador del alumno.
	 */
	function get_pct_asistencia_alumno_acta_cursada($id_acta, $id_alumno)
	{

		$id_acta = toba::db()->quote($id_acta);
		$id_alumno = toba::db()->quote($id_alumno);

		if ($where) {
			$where = ' AND '.$where;
		}

		$sql = "SELECT 	COALESCE(sga_clases_asistencia_acum.porc_asistencia, 0) as pct_asistencia

				FROM sga_actas
				JOIN sga_eval_detalle_cursadas ON (sga_eval_detalle_cursadas.instancia_cursada = 1 AND
					                                      sga_eval_detalle_cursadas.id_acta_cursada= sga_actas.id_acta)
				LEFT JOIN sga_clases_asistencia_acum ON (sga_clases_asistencia_acum.comision = sga_actas.comision AND sga_clases_asistencia_acum.alumno = $id_alumno)
				WHERE sga_actas.id_acta = $id_acta  
				AND sga_eval_detalle_cursadas.alumno = $id_alumno				
			";
	
		$pct_asistencia = guarani_db::consultar_fila($sql);
		return $pct_asistencia;
	}
}


?>