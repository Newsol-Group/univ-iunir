<?php
/**
 *
 * @package Cursadas
 * @subpackage Actas
 */
class co_actas
{
	/**
	 * Obtiene un listado de actas de cursadas/promoción según filtros recibidos
	 *
	 */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' AND '.$where;
		}

		$tipos_actas = $this->get_opciones_tipos_actas();

		$sql = "SELECT 
				   sga_actas.id_acta,
				   sga_actas.nro_acta,
				   sga_instancias.nombre as instancia_nombre,
				   " . guarani_sql::get_case('sga_actas', 'tipo_acta', 'tipo_acta', $tipos_actas) . ",
				   f_libros_acta(sga_actas.id_acta, 2) as libro_nombre,
				   sga_actas.estado,
				   sga_actas_estados.nombre as estado_nombre,
				   sga_comisiones.nombre as comision_nombre,
				   f_modalidad_cursada_comision(sga_comisiones.comision) as modalidad_nombre,
				   sga_elementos.codigo || ' - ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as codigo_y_nombre_actividad,
				   sga_ubicaciones.nombre as ubicacion_nombre,
				   sga_turnos_cursadas.nombre as turno_nombre,				   
				   CASE
					  WHEN sga_actas.estado = '" . acta::estado_abierta . "' THEN
                         CASE sga_actas.origen
                           WHEN '" . acta::origen_regulares . "' THEN -- Acta de regulares abierta
						       (SELECT count(1) FROM sga_eval_detalle_cursadas  
                                 WHERE evaluacion = sga_actas.evaluacion AND id_acta_cursada = sga_actas.id_acta AND acta_cursada_cerrada = 0)
                           WHEN '" . acta::origen_promociones . "' THEN -- Acta de promocion abierta
						       (SELECT count(1) FROM sga_eval_detalle_cursadas  
                                 WHERE evaluacion = sga_actas.evaluacion AND id_acta_promocion = sga_actas.id_acta AND acta_promocion_cerrada = 0)						   
						 END	
					  WHEN sga_actas.estado IN ('" . acta::estado_anulada . "','" . acta::estado_cerrada . "') THEN
						  -- Alumnos en sga_actas_detalle con acta cerrada
						 (SELECT COUNT(1) FROM sga_actas_detalle WHERE id_acta = sga_actas.id_acta)
					END AS cant_alumnos,
					sga_elementos.codigo,
					sga_ubicaciones.ubicacion,
					sga_actas.fecha_generacion,
					CASE
						WHEN (sga_actas.version_impresa <> 0) THEN '".comunes::si_desc."'
						ELSE '".comunes::no_desc."'
					END AS impreso,					
					CASE
					  WHEN (SELECT count(1)
							  FROM sga_actas as a
							 WHERE a.acta_referencia = sga_actas.id_acta 
							   AND a.tipo_acta = '". acta::tipo_rectificativa . "' 
							   AND a.estado = '" . acta::estado_abierta . "') > 0 THEN 1
					  ELSE 0
					END AS tiene_rectificativas_abiertas,
					sga_actas.origen,
					sga_actas.comision,
					sga_actas.llamado_mesa,
					sga_actas.digital,
					CASE
					  WHEN (SELECT COUNT(1) FROM sga_actas_folios WHERE sga_actas_folios.id_acta = sga_actas.id_acta AND sga_actas_folios.folio_fisico IS NULL) > 0 THEN '".comunes::no_desc."'
					  ELSE '".comunes::si_desc."'
					END AS tiene_folio_fisico_asignado
				FROM sga_actas
				JOIN sga_actas_instancias ON sga_actas.id_acta = sga_actas_instancias.id_acta
				JOIN sga_instancias ON sga_actas_instancias.instancia = sga_instancias.instancia
				JOIN sga_comisiones ON sga_actas.comision = sga_comisiones.comision
				JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
				JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
				JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
				JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
				JOIN sga_actas_estados ON sga_actas_estados.estado = sga_actas.estado
				LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
				WHERE true
				$where
				ORDER BY sga_actas.nro_acta
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene un listado de actas según filtros recibidos
	 *
	 */
	function get_listado_actas_rectificativas($where='')
	{
		if ($where) {
			$where = ' AND '.$where;
		}

		$tipos_actas = $this->get_opciones_tipos_actas();

		$sql = "SELECT sga_actas.id_acta,
						sga_actas.nro_acta,
						sga_instancias.nombre as instancia_nombre,
						" . guarani_sql::get_case('sga_actas', 'tipo_acta', 'tipo_acta', $tipos_actas) . ",
						f_libros_acta(sga_actas.id_acta, 2) as libro_nombre,
						sga_actas.estado,
						sga_comisiones.nombre as comision_nombre,
						f_modalidad_cursada_comision(sga_comisiones.comision) as modalidad_nombre,
						sga_elementos.codigo || ' - ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as codigo_y_nombre_actividad,
						sga_ubicaciones.nombre as ubicacion_nombre,
						sga_turnos_cursadas.nombre as turno_nombre,
						CASE
							WHEN (sga_actas.estado = '" . acta::estado_abierta . "')
								THEN
										-- Alumnos en eval_detalles_cursadas con acta no cerrada
										(	SELECT	count(1)
											FROM	sga_eval_detalle_cursadas
											WHERE	id_acta_cursada = sga_actas.id_acta AND
													sga_actas.origen = '" . acta::origen_regulares . "' AND
													sga_eval_detalle_cursadas.acta_cursada_cerrada = 0
										) +
										(	SELECT	count(1)
											FROM	sga_eval_detalle_cursadas
											WHERE 	id_acta_promocion = sga_actas.id_acta AND
													sga_actas.origen = '" . acta::origen_promociones . "' AND
													sga_eval_detalle_cursadas.acta_promocion_cerrada = 0
										)
								WHEN (sga_actas.estado <> '" . acta::estado_abierta  . "')
									THEN
									-- Alumnos en sga_actas_detalle con acta cerrada
									(SELECT	count(1)
									FROM	sga_actas_detalle
									WHERE	sga_actas_detalle.id_acta = sga_actas.id_acta
									)
							END	AS cant_alumnos,
							acta_referencia.nro_acta as rectifica_a
					FROM sga_actas
					JOIN sga_actas as acta_referencia ON sga_actas.acta_referencia = acta_referencia.id_acta
					JOIN sga_actas_instancias ON sga_actas.id_acta = sga_actas_instancias.id_acta
					JOIN sga_instancias ON sga_actas_instancias.instancia = sga_instancias.instancia
					JOIN sga_comisiones ON sga_actas.comision = sga_comisiones.comision
					JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
					JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
					LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
					
					WHERE sga_actas.tipo_acta = '". acta::tipo_rectificativa . "'
					$where
					ORDER BY sga_actas.nro_acta
		";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene un listado de actas asociadas a llamados de mesas, según los filtros recibidos
	 *
	 */
	function get_listado_actas_examen($where='')
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		
		$tipos_actas = $this->get_opciones_tipos_actas();
		
		$sql = "SELECT 	sga_actas.id_acta,
						sga_actas.nro_acta,
						sga_actas.acta_referencia,
						" . guarani_sql::get_case('sga_actas', 'tipo_acta', 'tipo_acta_descr', $tipos_actas) . ",
						sga_actas.tipo_acta,
						f_libros_acta(sga_actas.id_acta, 2) as libro_nombre,
						sga_actas.estado,
						sga_actas.version,
                        sga_actas.version_impresa,
						sga_actas.observaciones,
						sga_actas_estados.nombre	as estado_nombre,
						sga_mesas_examen.nombre		as mesa_nombre,
						CASE
							WHEN (sga_actas.estado = '" . acta::estado_abierta . "')
								THEN
								-- Alumnos en eval_detalles_examenes con acta no cerrada
								(	SELECT 	count(1) 
									FROM	sga_eval_detalle_examenes 
									WHERE 	sga_eval_detalle_examenes.id_acta = sga_actas.id_acta
								) 
							WHEN (sga_actas.estado IN ('" . acta::estado_cerrada . "','" . acta::estado_anulada . "'))
								THEN
								-- Alumnos en sga_actas_detalle con acta cerrada
								(	SELECT 	COUNT(1)  
									FROM 	sga_actas_detalle			
									WHERE 	sga_actas_detalle.id_acta = sga_actas.id_acta
								)
						END as cant_alumnos,
						CASE 
							WHEN (	SELECT 	count(1)
									FROM 	sga_actas as sub_sga_actas
									WHERE	sub_sga_actas.tipo_acta = '" . acta::tipo_rectificativa . "' AND
											sub_sga_actas.acta_referencia = sga_actas.id_acta AND
											sub_sga_actas.estado = '" . acta::estado_abierta . "' ) > 0
									THEN 1
							ELSE 0
						END as tiene_rectificativas_abiertas,
						sga_actas.llamado_mesa,
						sga_actas.digital,
						f_instancias_acta(sga_actas.id_acta) as instancias_acta
					FROM 	sga_actas,
							sga_mesas_examen,
							sga_llamados_mesa,
							sga_actas_estados
					WHERE	sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen AND
							sga_llamados_mesa.llamado_mesa = sga_actas.llamado_mesa AND
							sga_actas_estados.estado = sga_actas.estado
							$where
					ORDER BY 	sga_actas.nro_acta
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene un listado de actas que pueden estar o no asociadas a mesas, según el filtro recibido
	 *
	 */
	function get_listado_actas_examen_con_sin_mesas($where='')
	{
		if ($where) {
			$where = " AND $where ";
		}
		
		$tipos_actas = $this->get_opciones_tipos_actas();
		
		$sql = "SELECT 	sga_actas.id_acta,
						sga_actas.nro_acta,
						sga_actas.origen,
						sga_actas.estado,
						sga_actas_estados.nombre 				AS estado_descr,
						sga_actas.tipo_acta,
						sga_actas.version_impresa,
						sga_actas.renglones_folio,
						sga_actas.observaciones,
						sga_actas.acta_referencia,
						rectificada.nro_acta as rectifica_a,
						sga_actas.version,
						sga_actas.evaluacion,
						sga_actas.llamado_mesa,
						sga_actas.cerrada_por_docente,
						sga_actas.id_documento_digital,
						sga_actas.estado_documento_digital,
						sga_actas.digital,
						vw_mesas_examen.mesa_examen,
						vw_mesas_examen.anio_academico,
						vw_mesas_examen.llamado_mesa_entidad,
						vw_mesas_examen.mesa_examen_fecha		AS fecha,
						vw_mesas_examen.mesa_examen_elemento	AS elemento,
						sga_ubicaciones.nombre					AS ubicacion_nombre,
						sga_elementos.codigo					AS codigo_actividad,
						get_nombre_actividad_mesa_examen(vw_mesas_examen.mesa_examen, 1) as nombre_actividad,
						vw_mesas_examen.mesa_examen_nombre		AS mesa_nombre,
						vw_mesas_examen.turno_examen_nombre 	AS turno_nombre,
						vw_mesas_examen.llamado_nombre			AS llamado_nombre,
						f_libros_acta(sga_actas.id_acta, 2) 	AS libro_nombre,
						'(' || sga_elementos.codigo || ') ' || get_nombre_actividad_mesa_examen(vw_mesas_examen.mesa_examen, 1) AS cod_nombre_actividad,
						".guarani_sql::get_case('sga_actas', 'tipo_acta', 'tipo_acta_descr', $tipos_actas).",
						to_char(vw_mesas_examen.mesa_examen_fecha,'".comunes::formato_fecha_visual."') AS fecha_formato_visual,
						CASE 
							WHEN   (SELECT 	COUNT(1)
									FROM 	sga_actas as sub_sga_actas
									WHERE	sub_sga_actas.tipo_acta	= '".acta::tipo_rectificativa."' AND
											sub_sga_actas.estado 	= '".acta::estado_abierta 	 ."' AND
											sub_sga_actas.acta_referencia = sga_actas.id_acta) > 0
								THEN 1
							ELSE 0
						END AS tiene_rectificativas_abiertas,
						CASE
							WHEN (sga_actas.estado = '" . acta::estado_abierta . "')
								THEN
								-- Alumnos en eval_detalles_examenes con acta no cerrada
									(	SELECT 		count(1) 
										FROM		sga_eval_detalle_examenes 
										WHERE 		sga_eval_detalle_examenes.id_acta = sga_actas.id_acta
									) 
							WHEN (sga_actas.estado IN ('" . acta::estado_cerrada . "','" . acta::estado_anulada . "'))
								THEN
								-- Alumnos en sga_actas_detalle con acta cerrada
								(	SELECT 		COUNT(1)  
									FROM 		sga_actas_detalle			
									WHERE 		sga_actas_detalle.id_acta = sga_actas.id_acta
								)		
						END	AS cant_alumnos,
						sga_actas.cerrada_por_docente AS cerrada_por_docente,
						CASE
                                                    WHEN (SELECT COUNT(1) FROM sga_actas_folios WHERE sga_actas_folios.id_acta = sga_actas.id_acta AND sga_actas_folios.folio_fisico IS NULL) > 0 THEN '".comunes::no_desc."'
                                                    ELSE '".comunes::si_desc."'
						END AS tiene_folio_fisico_asignado
				FROM vw_mesas_examen
				JOIN sga_actas ON sga_actas.llamado_mesa = vw_mesas_examen.llamado_mesa
				JOIN sga_actas_estados ON sga_actas.estado = sga_actas_estados.estado
				JOIN sga_elementos ON vw_mesas_examen.mesa_examen_elemento = sga_elementos.elemento
				JOIN sga_ubicaciones ON vw_mesas_examen.mesa_examen_ubicacion = sga_ubicaciones.ubicacion
				LEFT JOIN sga_actas AS rectificada ON sga_actas.acta_referencia = rectificada.id_acta
							
				WHERE true		
				$where
				ORDER BY 	sga_actas.nro_acta
				";
				
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene un listado de Actas asociadas a llamados mesas y períodos, según los filtros recibidos
	 *
	 */
	function get_listado_rep_imprimir_actas_masivo($where = '')
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		
		$tipos_actas = $this->get_opciones_tipos_actas();
		
		$sql = "SELECT 	sga_actas.id_acta,
						sga_actas.nro_acta,
						sga_actas.tipo_acta,
						sga_actas.digital,
						sga_llamados_mesa.fecha,
						sga_ubicaciones.ubicacion,
						sga_mesas_examen.mesa_examen,
						sga_llamados_turno.llamado,
						sga_llamados_mesa.llamado_mesa,
						'Mesa: ' || sga_mesas_examen.nombre || ' - ' || 'Llamado: ' || sga_periodos.nombre AS llamado_mesa_nombre,
						sga_ubicaciones.nombre 											AS ubicacion_nombre,
						'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre 	AS codigo_nombre_actividad,
						".guarani_sql::get_case('sga_actas', 'tipo_acta', 'tipo_acta_descr', $tipos_actas).",
						f_instancias_mesa_examen(sga_mesas_examen.mesa_examen) AS instancias_nombre,
						CASE
							WHEN (sga_actas.version_impresa <> 0) THEN '".comunes::si_desc."'
							ELSE '".comunes::no_desc."'
						END AS impreso,
						CASE sga_actas.estado  
						WHEN '".acta::estado_abierta."' THEN                                     
							( SELECT  count(1)  
							  FROM    sga_eval_detalle_examenes  
							  WHERE   sga_eval_detalle_examenes.id_acta = sga_actas.id_acta 
							)  
						ELSE  
							( SELECT  count(1)  
							  FROM    sga_actas_detalle 
							  WHERE   sga_actas_detalle.id_acta = sga_actas.id_acta 
							)  
				END     AS cant_alumnos 
				FROM 	sga_actas
				JOIN	sga_llamados_mesa ON sga_llamados_mesa.llamado_mesa = sga_actas.llamado_mesa
				JOIN	sga_mesas_examen ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
				JOIN	sga_actas_estados ON sga_actas_estados.estado = sga_actas.estado
				JOIN	sga_llamados_turno ON sga_llamados_mesa.llamado	= sga_llamados_turno.llamado
				JOIN	sga_periodos ON sga_llamados_turno.periodo = sga_periodos.periodo 
				JOIN	vw_actividades ON sga_mesas_examen.elemento = vw_actividades.elemento
				JOIN	sga_turnos_examen ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
				JOIN	sga_ubicaciones ON sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
				
				WHERE true  	
				$where
				ORDER BY 	sga_actas.nro_acta
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene un listado de ACTAS DE EXAMEN ANULADAS, según los filtros recibidos
	 *
	 */
	function get_listado_actas_examen_anuladas($where='')
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		
		$tipos_actas = $this->get_opciones_tipos_actas();
		
		$sql = "SELECT 
					-- Grupo 1: Ubicación
					sga_ubicaciones.ubicacion,
					sga_ubicaciones.nombre AS ubicacion_nombre,
					
					-- Datos que se exhiben
					sga_actas.id_acta,
					sga_actas.nro_acta,
					'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS codigo_nombre_actividad,
					" . guarani_sql::get_case('sga_actas', 'tipo_acta', 'tipo_acta_descr', $tipos_actas) . ",
					sga_mesas_examen.nombre AS mesa_nombre,
					sga_periodos.nombre AS llamado_nombre,
					f_instancias_mesa_examen(sga_mesas_examen.mesa_examen) as instancias_nombre,
					sga_llamados_mesa.fecha,
					to_char(sga_llamados_mesa.fecha,'".comunes::formato_fecha_visual."') as fecha_formato_visual,
					sga_periodos.anio_academico,
					periodo_turno.nombre as turno_nombre
					
				FROM sga_actas
				JOIN sga_llamados_mesa ON sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa
				JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				JOIN sga_llamados_turno ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
				JOIN sga_periodos ON sga_llamados_turno.periodo = sga_periodos.periodo
				JOIN vw_actividades ON sga_mesas_examen.elemento = vw_actividades.elemento
				JOIN sga_ubicaciones ON sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
				JOIN sga_turnos_examen ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
				JOIN sga_periodos as periodo_turno ON periodo_turno.periodo = sga_turnos_examen.periodo
				
				WHERE sga_actas.estado = '" . acta::estado_anulada . "'
				$where

				ORDER BY
					sga_ubicaciones.nombre,
					sga_actas.nro_acta
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene una lista de detalles de acta
	 *
	 */
	function get_detalles_acta($where='')
	{
		if ($where) {
			$where = ' AND '.$where;
		}

		$sql = "SELECT 
					vw_personas.apellido_nombres AS apellido_y_nombre,
					vw_personas.tipo_nro_documento AS documento_principal,
					sga_propuestas.nombre_abreviado AS propuesta_nombre,
					sga_instancias.nombre AS instancia_nombre,
					sga_actas_detalle.nota,
					COALESCE(sga_instancias_resultado.descripcion, sga_escalas_notas_resultado.descripcion) AS resultado_descripcion,
					sga_actas_detalle.fecha,
					sga_actas_detalle.fecha_vigencia
				
				FROM sga_actas_detalle
				JOIN sga_alumnos ON sga_actas_detalle.alumno = sga_alumnos.alumno
				JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
				JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN sga_instancias ON sga_actas_detalle.instancia = sga_instancias.instancia
				LEFT JOIN sga_instancias_resultado ON (sga_actas_detalle.instancia = sga_instancias_resultado.instancia AND
				                                       sga_actas_detalle.resultado = sga_instancias_resultado.resultado)
				LEFT OUTER JOIN sga_escalas_notas_resultado ON sga_actas_detalle.resultado = sga_escalas_notas_resultado.resultado
					
				WHERE true
				$where
					
				ORDER BY vw_personas.apellido, vw_personas.nombres
				";
		
		return guarani_db::consultar($sql);
	}
	
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
						sga_actas_folios.folio_fisico
						
					FROM sga_actas 
					JOIN sga_comisiones ON sga_actas.comision = sga_comisiones.comision
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


	function get_nro_acta($id_acta)
	{
		if (is_array($id_acta)) {
			$where = guarani_sql::armar_condicion_in($id_acta, 'sga_actas', 'id_acta');
		} else {
			$id_acta = toba::db()->quote($id_acta);
			$where = "sga_actas.id_acta = $id_acta";
		}
		$sql = "SELECT sga_actas.nro_acta FROM sga_actas WHERE $where ";
		
		return guarani_db::consultar($sql);
	}
        
        function get_origen_acta($id_acta)
	{
		if (is_array($id_acta)) {
			$where = guarani_sql::armar_condicion_in($id_acta, 'sga_actas', 'id_acta');
		} else {
			$id_acta = toba::db()->quote($id_acta);
			$where = "sga_actas.id_acta = $id_acta";
		}
		$sql = "SELECT sga_actas.origen FROM sga_actas WHERE $where";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene el nro o nros del id o ids de actas a las que se rectifican para la carga externa (masiva).
	 *
	 */
	function get_nro_acta_rectificada($id_acta)
	{
		if (is_array($id_acta)) {
			$where = guarani_sql::armar_condicion_in($id_acta, 'sga_actas', 'id_acta');
		} else {
			$id_acta = toba::db()->quote($id_acta);
			$where = "sga_actas.id_acta = $id_acta";
		}
		$sql = "SELECT sga_actas.nro_acta as nro_acta_rectificada FROM sga_actas WHERE $where ";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el ID del acta original de un acta rectificativa recibida como parámetro
	 * @param int $id_acta_rectificativa
	 * @return int
	 */
	function get_id_acta_original($id_acta_rectificativa)
	{
		$id_acta_rectificativa = toba::db()->quote($id_acta_rectificativa);
		$sql = "SELECT sga_actas.acta_referencia FROM sga_actas WHERE sga_actas.id_acta = $id_acta_rectificativa";
		$res = guarani_db::consultar_fila($sql);
		return $res['acta_referencia'];
	}
	
	/**
	 * Retorna para un elemento visual de información las fechas de regularidades y
	 * la nota de aprobación de cursada si es que existe. En caso de existir varias
	 * cursadas se toma la última, siempre que el acta esté cerrada y no sea rectificativa
	 *
	 */
	function get_datos_ultima_cursada_alumno($alumno, $evaluacion)
	{
		$alumno = toba::db()->quote($alumno);
		
		$sql = "SELECT	get_ultima_cursada($alumno, sga_mesas_examen.elemento) AS datos_cursada
				FROM	sga_evaluaciones,
						sga_llamados_mesa,
						sga_mesas_examen
				WHERE 	sga_evaluaciones.evaluacion = $evaluacion
				AND		sga_evaluaciones.entidad = sga_llamados_mesa.entidad
				AND		sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				";
		
		$datos = guarani_db::consultar_fila($sql);
		
		//Procesamiento de los datos de cursada separados por '$PARAM$'
		$datos_inscripcion = explode('$PARAM$', $datos['datos_cursada']);
		
		if (!empty($datos_inscripcion[2])) {
			$datos['fecha_regular'] = $datos_inscripcion[2];
		}
		if (!empty($datos_inscripcion[4])) {
			$datos['nota_regularidad'] = $datos_inscripcion[4];
		}
		if (!empty($datos_inscripcion[6])) {
			$datos['fecha_vigencia'] = $datos_inscripcion[6];
		}
		
		return $datos;
	}
	
	/**
	 * Obtiene el nro. de acta a partir del id de acta
	 * Lo retorna con el nombre 'nro_acta_cursada' para la carga externa del datos_tabla eval_detalle_cursadas
	 *
	 */
	function get_nro_acta_cursada($id_acta)
	{
		if (is_array($id_acta)) {
			$filtro = guarani_sql::armar_condicion_in($id_acta, 'sga_actas', 'id_acta');
		} elseif ($id_acta != acta::id_acta_ficticia) {
			$id_acta = toba::db()->quote($id_acta);
			$filtro = "sga_actas.id_acta = $id_acta";
		} else {
			return array('nro_acta_cursada' => '');
		}
		$sql = "SELECT  sga_actas.id_acta as id_acta_cursada,
						sga_actas.nro_acta as nro_acta_cursada
				FROM	sga_actas
				WHERE	$filtro
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene el nro. de acta a partir del id de acta
	 * Lo retorna con el nombre 'nro_acta_promocion' para la carga externa del datos_tabla eval_detalle_cursadas
	 *
	 */
	function get_nro_acta_promocion($id_acta)
	{
		if (is_array($id_acta)) {
			$filtro = guarani_sql::armar_condicion_in($id_acta, 'sga_actas', 'id_acta');
		} elseif ($id_acta != acta::id_acta_ficticia) {
			$id_acta = toba::db()->quote($id_acta);
			$filtro = "sga_actas.id_acta = $id_acta";
		} else {
			return array('nro_acta_promocion' => '');
		}
		$sql = "SELECT  sga_actas.id_acta as id_acta_promocion,
						sga_actas.nro_acta as nro_acta_promocion
				FROM	sga_actas
				WHERE	$filtro
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los datos básicos del acta de exámen dada.
	 *
	 */
	function get_datos_basicos_acta_examen($id_acta)
	{
		$id_acta = toba::db()->quote($id_acta);
		$tipos_actas = $this->get_opciones_tipos_actas();
		$sql = "SELECT  sga_actas.id_acta,
						sga_actas.nro_acta,
						sga_actas.origen,
						sga_actas.estado,
						sga_actas.version,
						sga_actas.version_impresa,
						".guarani_sql::get_case('sga_actas', 'tipo_acta', 'tipo_acta_descr', $tipos_actas).",
						sga_actas.tipo_acta,
						sga_actas.acta_referencia,
						sga_actas.renglones_folio,
						f_libros_acta(sga_actas.id_acta, 2) as libro_nombre,
						sga_actas_estados.nombre as estado_nombre,
						sga_actas.renglones_folio,
						sga_actas.observaciones,
						sga_actas.evaluacion,
						sga_actas.llamado_mesa,
						sga_actas.digital,
						sga_llamados_mesa.fecha,
						sga_llamados_mesa.mesa_examen,
						sga_mesas_examen.mesa_en_turno_examen,
						sga_actas_folios.folio,
						sga_actas_folios.libro_tomo,
						sga_actas_folios.folio_fisico
				FROM	sga_actas
						JOIN sga_actas_estados ON sga_actas.estado = sga_actas_estados.estado
						JOIN sga_llamados_mesa ON sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa
						JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
						LEFT JOIN sga_actas_folios ON (sga_actas.id_acta = sga_actas_folios.id_acta)
				WHERE	sga_actas.id_acta = $id_acta
		";
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Obtiene los datos del Acta y de la Actividad a la cual se encuentra asociada el Acta de Examen dada.
	 *
	 */
	function get_datos_actividad_mesa_acta_examen($id_acta)
	{
		$id_acta = toba::db()->quote($id_acta);
		$tipos_actas = $this->get_opciones_tipos_actas();
		
		$sql = "SELECT  sga_actas.id_acta,
						sga_actas.nro_acta,
						sga_actas.origen,
						sga_actas.estado,
						" . guarani_sql::get_case('sga_actas', 'tipo_acta', 'tipo_acta_descr', $tipos_actas) . ",
						sga_actas.tipo_acta,
						sga_actas.acta_referencia,
						vw_mesas_examen.anio_academico,
						vw_mesas_examen.mesa_examen_ubicacion AS ubicacion,
						sga_ubicaciones.nombre AS ubicacion_nombre,
						vw_mesas_examen.mesa_examen_elemento AS elemento,
						COALESCE('(' || vw_actividades.codigo || ') ', '') || vw_actividades.nombre AS actividad_descr,
						vw_actividades.entidad_subtipo AS tipo_actividad,
						vw_actividades.entidad_subtipo_nombre AS tipo_actividad_nombre,
						sga_elementos_atrib.escala_nota_examen
				FROM	sga_actas
				JOIN	sga_actas_estados ON sga_actas.estado = sga_actas_estados.estado
				JOIN	vw_mesas_examen ON sga_actas.llamado_mesa = vw_mesas_examen.llamado_mesa
				JOIN	sga_ubicaciones ON vw_mesas_examen.mesa_examen_ubicacion = sga_ubicaciones.ubicacion
				JOIN	vw_actividades ON vw_mesas_examen.mesa_examen_elemento = vw_actividades.elemento
				JOIN	sga_elementos_atrib ON vw_actividades.elemento = sga_elementos_atrib.elemento
				
				WHERE	sga_actas.id_acta = $id_acta
						
		";
		
		return guarani_db::consultar_fila($sql);
	}
	
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
							pv_insc.plan as plan_inscripcion

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
						sga_actas_detalle.estado
						
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

	/**
	 * Obtiene las distintas propuestas vinculadas a un
	 * 
	 * @param int		$id_acta
	 * @param string	$estado_acta
	 */
	function get_propuestas_alumnos_acta_cursadas($id_acta, $estado_acta)
	{
		$id_acta = toba::db()->quote($id_acta);
		
		if ($estado_acta == acta::estado_abierta) {
			$sql = "SELECT 
						DISTINCT sga_alumnos.propuesta
					FROM 
						sga_eval_detalle_cursadas,
						sga_alumnos
					WHERE
						sga_eval_detalle_cursadas.id_acta_cursada = $id_acta
						AND sga_eval_detalle_cursadas.alumno = sga_alumnos.alumno
					";
		} elseif ($estado_acta == acta::estado_cerrada) {
			$sql = "SELECT 
						DISTINCT sga_alumnos.propuesta
					FROM 
						sga_actas_detalle,
						sga_alumnos
					WHERE
						sga_actas_detalle.id_acta = $id_acta
						AND sga_actas_detalle.alumno = sga_alumnos.alumno";
		}
		
		$datos = guarani_db::consultar($sql);
		return aplanar_matriz($datos, 'propuesta');
	}
	
	/**
	 * Retorna el conjunto de alumnos que no aprobaron la promoción para un acta dada.
	 * 
	 * @param integer $id_acta
	 * @return array
	 */
	function get_alumnos_no_promocionales_acta($id_acta)
	{
		$id_acta = toba::db()->quote($id_acta);
		$sql = "SELECT	sga_eval_detalle_cursadas.alumno,
						sga_eval_detalle_cursadas.plan_version,
						sga_eval_detalle_cursadas.fecha_promocion,
						sga_eval_detalle_cursadas.escala_nota_promocion,
						sga_eval_detalle_cursadas.nota_promocion,
						sga_eval_detalle_cursadas.resultado_promocion,
						sga_eval_detalle_cursadas.observacion_promocion
				FROM	sga_eval_detalle_cursadas
				WHERE	sga_eval_detalle_cursadas.instancia_promocion = 1 AND
						sga_eval_detalle_cursadas.id_acta_promocion = $id_acta AND
						(sga_eval_detalle_cursadas.resultado_promocion <> '".escalas_notas::resultado_aprobado."' OR sga_eval_detalle_cursadas.resultado_promocion IS NULL)
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene el campo de ordenamiento en función del parámetro 'cur_orden_detalle_actas'
	 * 
	 * @param	int		$param_ordenamiento valor del parámetro cur_orden_detalle_actas
	 * @return	string	campos para usar en la cláusula ORDER BY
	 */
	function get_campo_ordenamiento($param_ordenamiento)
	{
		if ($param_ordenamiento == 1) {
			// Ordena por legajo
			$campo_ordenamiento = 'LOWER(f_limpiar_acentos(sga_alumnos.legajo))';
		} elseif ($param_ordenamiento == 2) {
			// Ordena por apellido y nombre en forma separada
			$campo_ordenamiento = 'LOWER(f_limpiar_acentos(vw_personas.apellido)), LOWER(f_limpiar_acentos(vw_personas.nombres))';
		} elseif ($param_ordenamiento == 3) {
			// Ordena por Número de documento principal del alumno. No importa el tipo de documento
			$campo_ordenamiento = 'LOWER(f_limpiar_acentos(vw_personas.nro_documento))';
		}
		
		return $campo_ordenamiento;
	}

	/**
	 * Agrega al listado de inscriptos los datos correspondientes a folio y renglón
	 *
	 * @param array $datos			listado de inscriptos.
	 * @param int 	$lineas_folio	cantidad de renglones por folio
	 */
	function procesar_listado_alumnos($datos, $lineas_folio)
	{
		$longitud = guarani_arreglos::count($datos);
		$folio = 1;
		$renglon = 1;
		
		for ($i = 0; $i < $longitud; $i++) {
			$datos[$i]['folio'] = $folio;
			$datos[$i]['renglon'] = $renglon;
			$renglon++;			
			if ($renglon > $lineas_folio) {
				$folio++;
				$renglon = 1;
			}
		}
		
		return $datos;
	}

	/**
	 * Retorna los datos de todos los alumnos asociados a un acta,
	 * más aquellos que se hayan agregado al acta en alguna de sus rectificativas cerradas,
	 * más los alumnos de la comisión que no han sido asignados a ningún acta.
	 *
	 * @param integer	$evaluacion			código de evaluación para la búsqueda de alumnos de la comisión.
	 * @param integer	$acta				código de acta a la cual se quieren agregar detalles, puede ser -1 (acta::id_acta_ficticia) cuando todavia no existe o null.
	 * @param integer	$instancia			código de instancia.
	 * @param integer	$id_acta_original	código del acta original.
	 * @param str		$where				filtro sobre los detalles a retornar.
	 */
	function get_alumnos_acta_cursada_rectificada($evaluacion, $acta, $instancia, $id_acta_original, $where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$evaluacion = toba::db()->quote($evaluacion);
		$acta = toba::db()->quote($acta);
		$id_acta_original = toba::db()->quote($id_acta_original);

		$union = '';
		if ($instancia == comision::inst_cursar) {
			$campo_instancia = 'instancia_cursada';
			$campo_acta = 'id_acta_cursada';
			$campo_acta_cerrada = 'acta_cursada_cerrada';
			$origen = acta::origen_regulares;
		} else {
			$campo_instancia = 'instancia_promocion';
			$campo_acta = 'id_acta_promocion';
			$campo_acta_cerrada = 'acta_promocion_cerrada';
			$origen = acta::origen_promociones;
			$union = "	UNION
						SELECT	sga_alumnos.alumno,
								sga_alumnos.legajo,
								vw_personas.apellido_nombres as apellido_y_nombre,
								vw_personas.tipo_nro_documento as documento_principal,
								sga_propuestas.nombre_abreviado	as propuesta_nombre_abreviado,
								'".comunes::no_desc."' as en_acta,
								'".comunes::no_desc."' as invalidado
						FROM	sga_actas_alumnos_no_prom,
								sga_actas,
								sga_comisiones,
								sga_evaluaciones,
								sga_alumnos,
								vw_personas,
								sga_propuestas
						WHERE	sga_actas_alumnos_no_prom.alumno = sga_alumnos.alumno AND
								sga_alumnos.persona = vw_personas.persona AND
								sga_alumnos.propuesta = sga_propuestas.propuesta AND
								sga_actas_alumnos_no_prom.acta = sga_actas.id_acta AND
								sga_actas.comision = sga_comisiones.comision AND
								sga_comisiones.entidad = sga_evaluaciones.entidad AND
								sga_evaluaciones.evaluacion = $evaluacion
								$where
			";
		}

		$origen = toba::db()->quote($origen);

		$sql = "SELECT	sga_alumnos.alumno,
						sga_alumnos.legajo,
						vw_personas.apellido_nombres AS apellido_y_nombre,
						vw_personas.tipo_nro_documento AS documento_principal,
						sga_propuestas.nombre_abreviado	AS propuesta_nombre_abreviado,
						'".comunes::si_desc."' AS en_acta,
						CASE
							WHEN vw_actas.estado = '".acta::detalle_estado_invalidado."' THEN '".comunes::si_desc."'
							ELSE '".comunes::no_desc."'
						END as invalidado
				FROM	vw_actas,
						sga_alumnos,
						vw_personas,
						sga_propuestas
				WHERE	vw_actas.alumno = sga_alumnos.alumno AND
						sga_alumnos.persona = vw_personas.persona AND
						sga_alumnos.propuesta = sga_propuestas.propuesta AND
						vw_actas.id_acta_original = $id_acta_original
						$where
				UNION
				SELECT	sga_alumnos.alumno,
						sga_alumnos.legajo,
						vw_personas.apellido_nombres AS apellido_y_nombre,
						vw_personas.tipo_nro_documento AS documento_principal,
						sga_propuestas.nombre_abreviado	AS propuesta_nombre_abreviado,
						'No' as en_acta,
						'No' as invalidado
				FROM	sga_eval_detalle_cursadas,
						sga_alumnos,
						vw_personas,
						sga_propuestas
				WHERE	sga_eval_detalle_cursadas.alumno = sga_alumnos.alumno AND
						sga_alumnos.persona = vw_personas.persona AND
						sga_alumnos.propuesta = sga_propuestas.propuesta AND
						sga_eval_detalle_cursadas.evaluacion = $evaluacion AND
						sga_eval_detalle_cursadas.$campo_instancia = 1 AND
						(sga_eval_detalle_cursadas.$campo_acta IS NULL OR sga_eval_detalle_cursadas.$campo_acta = $acta OR sga_eval_detalle_cursadas.$campo_acta_cerrada = 1) AND
						sga_eval_detalle_cursadas.alumno NOT IN (	SELECT	sga_actas_detalle.alumno
																	FROM	sga_actas,
																			sga_actas_detalle
																	WHERE	sga_actas.id_acta = sga_actas_detalle.id_acta AND
																			sga_actas.id_acta <> $acta AND
																			sga_actas.evaluacion = $evaluacion AND
																			sga_actas.origen = $origen AND
																			sga_actas.estado = '".acta::estado_cerrada."' AND
																			sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
																			sga_actas_detalle.rectificado = '".comunes::no."')
						$where
				$union
				ORDER BY apellido_y_nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos del detalle del último acta válida para un alumno en una comisión, por origen.
	 *
	 * @param integer	$alumno
	 * @param integer	$comision
	 * @param char		$origen
	 */
	function get_detalle_alumno_comision($alumno, $comision, $origen)
	{
		$union = '';
		if ($origen == acta::origen_promociones) {
			$union = "	UNION
						SELECT	sga_actas_alumnos_no_prom.acta,
								sga_actas.origen,
								sga_actas_alumnos_no_prom.fecha,
								sga_actas_alumnos_no_prom.escala_nota,
								sga_actas_alumnos_no_prom.nota,
								sga_actas_alumnos_no_prom.resultado,
								NULL as cond_regularidad,
								sga_actas_alumnos_no_prom.plan_version
						FROM	sga_actas_alumnos_no_prom,
								sga_actas
						WHERE	sga_actas_alumnos_no_prom.acta = sga_actas.id_acta AND
								sga_actas_alumnos_no_prom.alumno = $alumno AND
								sga_actas.comision = $comision
			";
		}
		$origen = toba::db()->quote($origen);
		$sql = "SELECT	vw_actas.id_acta,
						vw_actas.origen,
						vw_actas.fecha,
						vw_actas.escala_nota,
						vw_actas.nota,
						vw_actas.resultado,
						vw_actas.cond_regularidad,
						vw_actas.plan_version
				FROM	vw_actas
				WHERE	vw_actas.alumno = $alumno AND
						vw_actas.comision = $comision AND
						vw_actas.origen = $origen
				$union
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna los datos de todos los alumnos del siguiente conjunto: los asociados a un acta de exámen, más aquellos 
	 * que se hayan agregado en alguna de las rectificativas cerradas de la misma, màs aquellos alumnos de la mesa que
	 * no están en ningún acta no anulada para la misma. 
	 *
	 * @param integer	$evaluacion			id de la evaluacion correspondiente al llamado mesa para el que se recuperan
	 * 										los datos de los alumnos.
	 * @param integer	$id_acta			código del acta.
	 * @param integer	$id_acta_original	código del acta original.
	 * @param str		$where				filtro sobre los detalles a retornar.
	 */
	function get_alumnos_acta_examen_rectificada($evaluacion, $id_acta, $id_acta_original, $where='')
	{
		$evaluacion = toba::db()->quote($evaluacion);
		$id_acta = toba::db()->quote($id_acta);
		$id_acta_original = toba::db()->quote($id_acta_original);
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "-- Alumnos del Acta y sus Rectificativas cerradas.
				SELECT	sga_alumnos.alumno,
						sga_alumnos.legajo,
						'".comunes::si_desc."' AS en_acta,
						CASE
							WHEN vw_actas.estado = '".acta::detalle_estado_invalidado."' THEN '".comunes::si_desc."'
							ELSE '".comunes::no_desc."'
						END as invalidado,
						vw_personas.apellido_nombres AS apellido_y_nombre,
						vw_personas.tipo_nro_documento AS documento_principal,
						sga_instancias.nombre AS instancia_nombre,
						sga_propuestas.nombre_abreviado	AS propuesta_nombre_abreviado
				FROM	vw_actas
				JOIN	sga_alumnos ON vw_actas.alumno = sga_alumnos.alumno
				JOIN	vw_personas ON sga_alumnos.persona = vw_personas.persona
				JOIN	sga_instancias ON sga_instancias.instancia = vw_actas.instancia
				JOIN	sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				WHERE	vw_actas.id_acta_original = $id_acta_original
				$where
				
				UNION
				
				-- Alumnos de la Mesa, que están en el detalle, pero no están asociados a ningún acta no anulada de la misma.
				SELECT	sga_alumnos.alumno,
						sga_alumnos.legajo,
						'No' AS en_acta,
						'No' AS invalidado,
						vw_personas.apellido_nombres AS apellido_y_nombre,
						vw_personas.tipo_nro_documento AS documento_principal,
						sga_instancias.nombre AS instancia_nombre,
						sga_propuestas.nombre_abreviado	AS propuesta_nombre_abreviado
				FROM	sga_eval_detalle_examenes
				JOIN	sga_alumnos ON sga_eval_detalle_examenes.alumno = sga_alumnos.alumno
				JOIN	vw_personas ON sga_alumnos.persona = vw_personas.persona
				JOIN	sga_instancias ON sga_eval_detalle_examenes.instancia = sga_instancias.instancia
				JOIN	sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				
				WHERE	sga_eval_detalle_examenes.evaluacion = $evaluacion AND
						-- Debe controlar actas abiertas y cerradas porque al rectificar un acta se crea un registro nuevo para el alumno en sga_eval_detalle_examenes.
						(sga_eval_detalle_examenes.id_acta IS NULL OR sga_eval_detalle_examenes.id_acta = $id_acta) AND
						sga_eval_detalle_examenes.alumno NOT IN (	SELECT	sga_actas_detalle.alumno
																	FROM	sga_actas,
																			sga_actas_detalle
																	WHERE	sga_actas.id_acta = sga_actas_detalle.id_acta AND
																			sga_actas.id_acta <> $id_acta AND
																			sga_actas.evaluacion = $evaluacion AND
																			sga_actas.estado = '".acta::estado_cerrada."' AND
																			sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
																			sga_actas_detalle.rectificado = '".comunes::no."')
						$where

				ORDER BY apellido_y_nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos de los detalles del acta original o su la última rectificativa cerrada si existe.
	 * 
	 * @param integer $id_acta_original
	 * @return array
	 */
	function get_detalles_ultima_rectificacion_acta($id_acta_original)
	{
		$id_acta_original = toba::db()->quote($id_acta_original);
		$sql = "SELECT	vw_actas.alumno,
						vw_actas.fecha,
						vw_actas.nota,
						vw_actas.resultado,
						vw_actas.instancia
				FROM	vw_actas
				WHERE	vw_actas.id_acta_original = $id_acta_original
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los datos del detalle del último acta válida para un alumno en un llamado mesa.
	 *
	 * @param integer	$alumno			Alumno para el cual se recuperan los datos del Detalle.
	 * @param integer	$llamado_mesa	Llamado Mesa para el cual se recuperan los alumnos.
	 */
	function get_detalle_alumno_llamado_mesa_examen($alumno, $llamado_mesa)
	{
		$alumno = toba::db()->quote($alumno);
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$sql = "SELECT	vw_actas.alumno,
						vw_actas.id_acta,
						vw_actas.instancia,
						vw_actas.fecha,
						vw_actas.escala_nota,
						vw_actas.nota,
						vw_actas.resultado,
						vw_actas.plan_version
				FROM	vw_actas
				WHERE	vw_actas.alumno = $alumno AND
						vw_actas.llamado_mesa = $llamado_mesa
				;";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna los datos de todos los alumnos asociados al acta (id_acta) , busca en eval_detalle o acta_detalle segun el estado pasado como parametro
	 *
	 * @param $id_acta
	 * @param $estado
	 * @param $params_sistema	+ exa_orden_detalle_actas
	 * @return array
	 */
	function get_alumnos_acta_examen($id_acta=null, $estado=null, $params_sistema = array())
	{
		$param_ordenamiento = $params_sistema['exa_orden_detalle_actas'];
		$apellido_y_nombre=  persona::get_param_nombre_persona_doc_oficiales();
		
		$campo_ordenamiento = $this->get_campo_ordenamiento_examenes($param_ordenamiento);
		$campo_anexo = '';
		switch ($estado) {
			case acta::estado_abierta:
				$tabla 	= 'sga_eval_detalle_examenes';
				$sql_estado = "'".acta::estado_abierta."' AS estado";
				break;
			case acta::estado_cerrada:
			case acta::estado_anulada:
				$tabla = 'sga_actas_detalle';
				$campo_anexo = ',sga_actas_detalle.renglon, sga_actas_detalle.folio';
				$campo_ordenamiento = 'sga_actas_detalle.folio, sga_actas_detalle.renglon';
				$sql_estado = 'sga_actas_detalle.estado';
				break;
		}
		
		$sql = "SELECT  sga_alumnos.persona as id_persona,
						sga_alumnos.propuesta,
						sga_alumnos.legajo,
						$apellido_y_nombre AS persona,
						vw_personas.tipo_nro_documento AS documento,
						sga_propuestas.nombre_abreviado	AS propuesta_nombre,
						sga_instancias.nombre AS instancia_nombre,
						COALESCE(sga_instancias_resultado.descripcion, sga_escalas_notas_resultado.descripcion)	AS resultado_descr,
						get_plan_version_alumno($tabla.alumno, $tabla.fecha::varchar) AS plan_version,
						$tabla.plan_version as detalle_plan_version,
						plan_version_inscripcion.plan as plan_inscripcion,
						sga_actas.renglones_folio,
						sga_actas.evaluacion,
						sga_actas.nro_acta,
						$tabla.alumno,
						$tabla.id_acta,
						$tabla.instancia,
						$tabla.fecha,
						to_char($tabla.fecha, '".comunes::formato_fecha_visual."') as fecha_formateada, 
						$tabla.escala_nota,
						$tabla.nota,
						sga_escalas_notas_det.descripcion AS nota_descr,
						$sql_estado,
						$tabla.observaciones,
						$tabla.resultado
						$campo_anexo

				FROM 	$tabla
						JOIN sga_planes_versiones as plan_version_inscripcion ON $tabla.plan_version = plan_version_inscripcion.plan_version
						LEFT JOIN sga_instancias_resultado ON ($tabla.instancia = sga_instancias_resultado.instancia AND $tabla.resultado = sga_instancias_resultado.resultado)
						LEFT JOIN sga_escalas_notas_det ON ($tabla.escala_nota = sga_escalas_notas_det.escala_nota AND $tabla.nota = sga_escalas_notas_det.nota)
						LEFT JOIN sga_escalas_notas_resultado ON $tabla.resultado = sga_escalas_notas_resultado.resultado
						JOIN sga_alumnos on $tabla.alumno			= sga_alumnos.alumno
						JOIN vw_personas on sga_alumnos.persona 	= vw_personas.persona
						JOIN mdp_personas on sga_alumnos.persona 	= mdp_personas.persona
						JOIN sga_propuestas on sga_alumnos.propuesta 	= sga_propuestas.propuesta
						JOIN sga_instancias on $tabla.instancia		= sga_instancias.instancia
                                                JOIN sga_actas on $tabla.id_acta			= sga_actas.id_acta
				
                                WHERE	$tabla.id_acta			= $id_acta
                                ORDER BY
				$campo_ordenamiento 
				";
		$datos = guarani_db::consultar($sql);
		
		if (!empty($datos) && $estado == acta::estado_abierta) {
			return $this->procesar_listado_alumnos($datos, $datos[0]['renglones_folio']);
		} else {
			return $datos;
		}
	}

	/**
	 * Retorna el detalle para el acta abierta $id_acta, ordenada segun el parametro del sistema exa_orden_detalle_actas
	 * 
	 * @param <type> $id_acta
	 * @return <type>
	 */
	function get_detalle_acta_examen_abierta($id_acta, $params_sistema=array())
	{
		$param_ordenamiento = $params_sistema['exa_orden_detalle_actas'];
		$campo_ordenamiento = $this->get_campo_ordenamiento_examenes($param_ordenamiento);
		
		$campo_anexo = '';

		$sql = "SELECT  vw_personas.apellido_nombres AS persona,
						vw_personas.tipo_nro_documento AS documento,
						sga_propuestas.nombre_abreviado	AS propuesta_nombre,
						sga_instancias.nombre AS instancia_nombre,
						COALESCE(sga_instancias_resultado.descripcion, sga_escalas_notas_resultado.descripcion) AS resultado_descr,

						get_plan_version_alumno(sga_eval_detalle_examenes.alumno, COALESCE (sga_eval_detalle_examenes.fecha::varchar, sga_llamados_mesa.fecha::varchar)) AS plan_version,

						sga_eval_detalle_examenes.alumno,
						sga_eval_detalle_examenes.id_acta,
						sga_eval_detalle_examenes.instancia,
						COALESCE (sga_eval_detalle_examenes.fecha, sga_llamados_mesa.fecha) AS fecha,
						sga_eval_detalle_examenes.escala_nota,
						sga_eval_detalle_examenes.nota,
						sga_eval_detalle_examenes.resultado

				FROM sga_eval_detalle_examenes
				JOIN sga_evaluaciones					ON sga_evaluaciones.evaluacion			= sga_eval_detalle_examenes.evaluacion
				JOIN sga_llamados_mesa					ON sga_llamados_mesa.entidad			= sga_evaluaciones.entidad
				JOIN sga_alumnos						ON sga_alumnos.alumno					= sga_eval_detalle_examenes.alumno
				JOIN vw_personas						ON vw_personas.persona					= sga_alumnos.persona
				JOIN sga_propuestas						ON sga_propuestas.propuesta				= sga_alumnos.propuesta
				JOIN sga_instancias						ON sga_instancias.instancia				= sga_eval_detalle_examenes.instancia
				LEFT JOIN sga_instancias_resultado		ON (sga_eval_detalle_examenes.instancia = sga_instancias_resultado.instancia AND sga_eval_detalle_examenes.resultado = sga_instancias_resultado.resultado)
				LEFT JOIN sga_escalas_notas_resultado	ON sga_eval_detalle_examenes.resultado	= sga_escalas_notas_resultado.resultado

				WHERE	sga_eval_detalle_examenes.id_acta = $id_acta

				ORDER BY $campo_ordenamiento
				";

		$datos = guarani_db::consultar($sql);
		return $datos;
	}

	/**
	 * Retorna el detalle para el acta abierta $id_acta, ordenada segun el parametro del sistema exa_orden_detalle_actas
	 * y le agrega folio y renglon
	 *
	 * @param <type> $id_acta
	 * @return <type>
	 */
	function get_detalle_acta_examen_abierta_renglones($id_acta, $renglones_x_folio, $params_sistema=array())
	{
		$datos = $this->get_detalle_acta_examen_abierta($id_acta, $params_sistema);
		if (!empty($datos)) {
			return $this->procesar_listado_alumnos($datos, $renglones_x_folio);
		} else {
			return $datos;
		}
	}

	/**
	 * Obtiene el/los campos de ordenamiento en función del parámetro 'exa_orden_detalle_actas'
	 *
	 */
	function get_campo_ordenamiento_examenes($param_ordenamiento)
	{
		if ($param_ordenamiento == 1) {
			// Ordena por legajo
			$campo_ordenamiento = 'LOWER(f_limpiar_acentos(sga_alumnos.legajo))';
		} elseif ($param_ordenamiento == 2) {
			// Ordena por apellido y nombre
			$campo_ordenamiento = 'LOWER(f_limpiar_acentos(vw_personas.apellido)), LOWER(f_limpiar_acentos(vw_personas.nombres))';
		} elseif ($param_ordenamiento == 3) {
			// Ordena por tipo y número de documento
			$campo_ordenamiento = 'LOWER(f_limpiar_acentos(vw_personas.desc_tipo_documento)), LOWER(f_limpiar_acentos(vw_personas.nro_documento))';
		}
		
		return $campo_ordenamiento;
	}

	/**
	 * Retorna los datos de todas las instancias asociadas al acta dada como parámetro.
	 *
	 * @param integer	$acta	id del acta para la cual se desean recuperar las instancias.
	 * @param str		$where	filtro sobre los datos a recuperar.
	 */
	function get_instancias_acta($acta=null, $where='')
	{
		$filtro = 'true';
		if (! empty($acta)) {
			$acta = toba::db()->quote($acta);
			$filtro .= " AND sga_actas_instancias.id_acta = $acta";
		}
		if ($where) {
			$filtro .= ' AND ' . $where;
		}

		$sql = "SELECT	sga_actas_instancias.instancia,
						sga_actas_instancias.escala_nota
				FROM	sga_actas_instancias
				WHERE	$filtro
		";
		return guarani_db::consultar($sql);
	}

	function get_nombres_instancias_acta($acta)
	{
		$acta = toba::db()->quote($acta);
		
		$sql = "SELECT	sga_instancias.instancia,
						sga_instancias.nombre
				FROM	sga_actas_instancias,
						sga_instancias
				WHERE	sga_instancias.instancia = sga_actas_instancias.instancia
				AND		sga_actas_instancias.id_acta = $acta
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los datos de todas las Instancias para actas, según el filtro dado. 
	 * 
	 * @param str		$where		filtro sobre las instancias a retornar.
	 */
	function get_datos_instancias($where='')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		$sql = "SELECT		sga_instancias.instancia,
							sga_instancias.nombre as instancia_nombre,
							sga_instancias.orden as instancia_orden
				FROM		sga_instancias
				$where
				ORDER BY	sga_instancias.nombre
				;";
		
		return guarani_db::consultar($sql);
	}

	/**
 	  * Retorna los datos correspondientes a una Instancia en formato RecordSet, en caso de que el parámetro sea un 
 	  * array retorna los datos de las Instancias correspondientes según la condición del array (para la carga masiva).
      *	@param integer/array instancia id o arreglo de ids de la o las instancias para los cuales se consultan los datos.
    */
	function get_nombres_instancias($instancia)
	{
		if (is_array($instancia)) {
			$filtro = guarani_sql::armar_condicion_in($instancia, 'sga_instancias', 'instancia');
			$datos = $this->get_datos_instancias($filtro);
			return $datos;
		} 
		$instancia = toba::db()->quote($instancia);
		$datos = $this->get_datos_instancias("sga_instancias.instancia = $instancia");
		if (!empty($datos)) {
			return current($datos);
		}
	}
	
	
	//Retorna los valores y descripciones de los distintos tipos de actas.
	function get_opciones_tipos_actas() {
		return array(	array('valor' => acta::tipo_normal, 'descr' => acta::tipo_normal_descr),
						array('valor' => acta::tipo_rectificativa, 'descr' => acta::tipo_rectificativa_descr),
					);
	}

	/**
	 * Obtene los posibles valores de origen de actas.
	 */
	function get_origenes_actas()
	{
		$sql = 'SELECT 	sga_actas_origen.origen,
						sga_actas_origen.nombre
				FROM 	sga_actas_origen';
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los detalles de actas donde el alumno dado tiene resultado "Ausente"
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_detalles_ausencia_alumno($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT		sga_actas.id_acta as id_acta,
							vw_mesas_examen.anio_academico as anio_academico,
							vw_mesas_examen.turno_examen_nombre as turno_nombre,
							vw_mesas_examen.mesa_examen_fecha as fecha,
							sga_elementos.codigo as actividad_codigo,
							get_nombre_actividad_mesa_examen(vw_mesas_examen.mesa_examen, 1) as actividad_nombre,
							vw_mesas_examen.llamado_nombre as llamado_nombre,
							vw_mesas_examen.mesa_examen_nombre as mesa_nombre,
							sga_actas.nro_acta as nro_acta,
							sga_motivos_ausencias.nombre as motivo_ausencia_nombre,
							sga_ausencias_examen.estado as ausencia_estado
							
				 FROM       sga_actas_detalle
							JOIN sga_actas ON sga_actas.id_acta = sga_actas_detalle.id_acta  
							JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = sga_actas.llamado_mesa
                            JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
                            LEFT JOIN sga_ausencias_examen ON sga_actas.id_acta = sga_ausencias_examen.id_acta AND sga_ausencias_examen.alumno = sga_actas_detalle.alumno
                            LEFT JOIN sga_motivos_ausencias ON sga_ausencias_examen.motivo_ausencia = sga_motivos_ausencias.motivo_ausencia
							
				WHERE		sga_actas_detalle.alumno = $alumno AND 
							sga_actas.origen = '".acta::origen_examen."' AND
							sga_actas.estado = '".acta::estado_cerrada."' AND
							sga_actas_detalle.rectificado = '".comunes::no."' AND
							sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
							sga_actas_detalle.resultado = '".escalas_notas::resultado_ausente."'
							
				ORDER BY	vw_mesas_examen.mesa_examen_fecha
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene todos los planes de los alumnos involucrados en un acta
	 * 
	 * @param int $id_acta
	 * @return array
	 */
	function get_planes_alumnos_acta($id_acta)
	{
		$id_acta = toba::db()->quote($id_acta);
		
		$sql = "----------------------------------------------------------------
				-- Sólo encontrará datos en uno de los tres bloques siguientes
				----------------------------------------------------------------
				
				-- Actas de regulares abiertas
				SELECT 
					DISTINCT sga_planes_versiones.plan
				FROM 
					sga_eval_detalle_cursadas,
					sga_alumnos,
					sga_planes_versiones
				WHERE
					sga_eval_detalle_cursadas.id_acta_cursada = $id_acta
					AND sga_eval_detalle_cursadas.alumno = sga_alumnos.alumno
					AND sga_alumnos.plan_version = sga_planes_versiones.plan_version
					
				UNION
				
				-- Actas de promocionales abiertas
				SELECT 
					DISTINCT sga_planes_versiones.plan
				FROM 
					sga_eval_detalle_cursadas,
					sga_alumnos,
					sga_planes_versiones
				WHERE
					sga_eval_detalle_cursadas.id_acta_promocion = $id_acta
					AND sga_eval_detalle_cursadas.alumno = sga_alumnos.alumno
					AND sga_alumnos.plan_version = sga_planes_versiones.plan_version
					
				UNION
				
				-- Actas cerradas
				SELECT 
					DISTINCT sga_planes_versiones.plan
				FROM 
					sga_actas_detalle,
					sga_alumnos,
					sga_planes_versiones
				WHERE
					sga_actas_detalle.id_acta = $id_acta
					AND sga_actas_detalle.alumno = sga_alumnos.alumno
					AND sga_alumnos.plan_version = sga_planes_versiones.plan_version
				";
		
		$datos = guarani_db::consultar($sql);
		return aplanar_matriz($datos, 'plan');
	}
	
	function nro_acta_en_uso($nro_acta, $origen)
	{
		$nro_acta = toba::db()->quote($nro_acta);
		$origen	= toba::db()->quote($origen);
		
		$sql = "SELECT	1 AS cant
				FROM	sga_actas
				WHERE	nro_acta = $nro_acta
						AND origen = $origen";
		
		$res = guarani_db::consultar_fila($sql);
		return (isset($res['cant']))? ($res['cant'] > 0) : false;
	}

	//--------------------------------------------------------------------
	//----- REPORTES -----------------------------------------------------
	//--------------------------------------------------------------------
	
	function get_listado_reporte_actas_examen($anio_academico, $filtros_especiales, $where='')
	{
		$anio_academico	= toba::db()->quote($anio_academico);
		
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		//----------------------------------------------------------------------
		//---- Filtros especiales
		
		if (!empty($filtros_especiales['propuesta'])) {
			$propuesta	= toba::db()->quote($filtros_especiales['propuesta']);
			$where .= "
					AND EXISTS (
						SELECT 	1
						FROM 	sga_mesas_examen_propuestas
						WHERE	sga_mesas_examen_propuestas.mesa_examen = sga_mesas_examen.mesa_examen
								AND sga_mesas_examen_propuestas.propuesta = $propuesta
					)";
		}
		
		if (!empty($filtros_especiales['instancia'])) {
			$instancia	= toba::db()->quote($filtros_especiales['instancia']);
			$where .= "
					AND EXISTS (
						SELECT 	1
						FROM 	sga_actas_instancias
						WHERE	sga_actas_instancias.id_acta = sga_actas.id_acta
								AND sga_actas_instancias.instancia = $instancia
					)";
		}
		
		if (!empty($filtros_especiales['rectifica_a'])) {
			$rectifica_a = toba::db()->quote($filtros_especiales['rectifica_a']);
			$where .= "	AND rectificada.nro_acta = $rectifica_a ";
		}
		
		if (!empty($filtros_especiales['acta_vacia'])) {
			// ¿Hay alumnos en el acta?
			if ($filtros_especiales['acta_vacia'] == comunes::si) {
				$operador = '=';
			} else {
				$operador = '<>';
			}
			$where .= "	AND f_acta_cantidad_alumnos(sga_actas.id_acta, 1) $operador 0";
		}
		
		if (!empty($filtros_especiales['version_desactualizada'])) {
			if ($filtros_especiales['version_desactualizada'] == comunes::si) {
				$operador = '<>';
			} else {
				$operador = '=';
			}
			$where .= "	AND sga_actas.version $operador sga_actas.version_impresa";
		}

		//----------------------------------------------------------------------
		
		$tipos_actas = $this->get_opciones_tipos_actas();
		
		$sql = "SELECT 	
					-- Corte 1: Ubicación
					sga_ubicaciones.ubicacion,
					sga_ubicaciones.nombre as ubicacion_nombre,
					-- Corte 2: Turnos
					COALESCE(sga_turnos_examen.turno_examen, -1) AS turno_examen_id,
					COALESCE(periodo_turno.nombre, 'Fuera de calendario') AS turno_examen_descr,
					-- Campos que se muestran en el reporte
					sga_actas.nro_acta,    
					-- Tipo
					" . guarani_sql::get_case('sga_actas', 'tipo_acta', 'tipo_acta', $tipos_actas) . ",
					sga_llamados_mesa.fecha,
					to_char(sga_llamados_mesa.fecha,'".comunes::formato_fecha_visual."') AS fecha_formato_visual,
					-- Libro
					f_libros_acta(sga_actas.id_acta, 2) as libro_nombre,
					sga_mesas_examen.nombre AS mesa_nombre, 
					'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_codigo_y_nombre,
					f_instancias_acta(sga_actas.id_acta) AS instancias_descr,
					sga_actas_estados.nombre AS estado_descr,
					rectificada.nro_acta AS rectifica_a,
					f_docentes_mesa_examen(sga_llamados_mesa.llamado_mesa) AS docentes,
					f_acta_cantidad_alumnos(sga_actas.id_acta, 1) AS cant_alumnos,
					sga_actas.version,
					sga_actas.version_impresa,
                                        sga_actas.digital AS acta_digital,
					sga_actas.id_documento_digital,
					sga_actas.estado_documento_digital,
                                        arai_documentos_estados.autorizado AS arai_autorizado,
                                        arai_documentos_estados.estado_codigo AS arai_estado_codigo,
                                        -- https://www.pgcasts.com/episodes/generating-json-from-sql
                                        (SELECT json_agg(row_to_json(t)) AS arai_firmantes
                                            FROM (

                                                SELECT
                                                COALESCE (mdp_personas.apellido_elegido, mdp_personas.apellido) AS apellido,
                                                COALESCE (mdp_personas.nombres_elegido, mdp_personas.nombres) AS nombres,
                                                arai_documentos_autorizaciones.usuario_sso AS usuario_sso
                                                FROM arai_documentos_autorizaciones
                                                LEFT JOIN mdp_personas ON (arai_documentos_autorizaciones.usuario_sso = mdp_personas.arai_identificador_sso)
                                                WHERE   arai_documentos_autorizaciones.id_documento_digital = sga_actas.id_documento_digital
                                                AND     arai_documentos_autorizaciones.estado = '".arai_documentos::ESTADO_SOLICITUD_AUTORIZACION_ACEPTADA_DESC."'
                                                ORDER BY arai_documentos_autorizaciones.secuencia

                                            ) t),
                                            (SELECT json_agg(row_to_json(t)) AS arai_pendientes_firma
                                            FROM (

                                                SELECT
                                                COALESCE (mdp_personas.apellido_elegido, mdp_personas.apellido) AS apellido,
                                                COALESCE (mdp_personas.nombres_elegido, mdp_personas.nombres) AS nombres,
                                                arai_documentos_autorizaciones.usuario_sso AS usuario_sso
                                                FROM arai_documentos_autorizaciones
                                                LEFT JOIN mdp_personas ON (arai_documentos_autorizaciones.usuario_sso = mdp_personas.arai_identificador_sso)
                                                WHERE   arai_documentos_autorizaciones.id_documento_digital = sga_actas.id_documento_digital
                                                AND     arai_documentos_autorizaciones.estado = '".arai_documentos::ESTADO_SOLICITUD_AUTORIZACION_PENDIENTE_DESC."'
                                                ORDER BY arai_documentos_autorizaciones.secuencia

                                            ) t),
                                            (SELECT json_agg(row_to_json(t)) AS arai_rechazado_por
                                            FROM (

                                                SELECT
                                                COALESCE (mdp_personas.apellido_elegido, mdp_personas.apellido) AS apellido,
                                                COALESCE (mdp_personas.nombres_elegido, mdp_personas.nombres) AS nombres,
                                                arai_documentos_autorizaciones.usuario_sso AS usuario_sso
                                                FROM arai_documentos_autorizaciones
                                                LEFT JOIN mdp_personas ON (arai_documentos_autorizaciones.usuario_sso = mdp_personas.arai_identificador_sso)
                                                WHERE   arai_documentos_autorizaciones.id_documento_digital = sga_actas.id_documento_digital
                                                AND     arai_documentos_autorizaciones.estado = '".arai_documentos::ESTADO_SOLICITUD_AUTORIZACION_RECHAZADA_DESC."'
                                                LIMIT 1

                                            ) t)
	
				FROM sga_actas
				JOIN sga_llamados_mesa ON sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa
				JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				JOIN vw_actividades ON vw_actividades.elemento = sga_mesas_examen.elemento
				JOIN sga_ubicaciones ON sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
				JOIN sga_actas_estados ON sga_actas.estado = sga_actas_estados.estado
				LEFT JOIN sga_actas as rectificada ON sga_actas.acta_referencia = rectificada.id_acta
				LEFT JOIN sga_llamados_turno ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
				LEFT JOIN sga_periodos ON sga_llamados_turno.periodo = sga_periodos.periodo
				LEFT JOIN sga_turnos_examen ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
				LEFT JOIN sga_periodos AS periodo_turno	ON sga_turnos_examen.periodo = periodo_turno.periodo
                                LEFT JOIN arai_documentos_estados ON (sga_actas.id_documento_digital = arai_documentos_estados.id_documento_digital)
				WHERE	
					-- Filtro por año académico
					( -- Actas dentro de calendario, para un año académico específico
					 (sga_llamados_mesa.llamado IS NOT NULL AND sga_periodos.anio_academico = $anio_academico) OR
					 (-- Actas fuera de calendario, para un año académico específico
					  sga_llamados_mesa.llamado IS NULL AND sga_mesas_examen.anio_academico = $anio_academico)
					)
					$where
				ORDER BY	
					sga_ubicaciones.nombre,
					turno_examen_id,
					vw_actividades.nombre,
					sga_llamados_mesa.fecha
				";
		return guarani_db::consultar($sql);
	}
	
	function get_listado_reporte_actas_comisiones($filtros_especiales, $where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		//----------------------------------------------------------------------
		//---- Filtros especiales
		
		if (!empty($filtros_especiales['propuesta'])) {
			$propuesta	= toba::db()->quote($filtros_especiales['propuesta']);
			$where .= "
					AND EXISTS (
						SELECT 	1
						FROM 	sga_comisiones_propuestas
						WHERE	sga_comisiones_propuestas.comision = sga_comisiones.comision
								AND sga_comisiones_propuestas.propuesta = $propuesta
					)";
		}
		
		if (!empty($filtros_especiales['instancia'])) {
			$instancia	= toba::db()->quote($filtros_especiales['instancia']);
			$where .= "
					AND EXISTS (
						SELECT 	1
						FROM 	sga_actas_instancias
						WHERE	sga_actas_instancias.id_acta = sga_actas.id_acta
								AND sga_actas_instancias.instancia = $instancia
					)";
		}
		
		if (!empty($filtros_especiales['rectifica_a'])) {
			$rectifica_a = toba::db()->quote($filtros_especiales['rectifica_a']);
			$where .= "	AND rectificada.nro_acta = $rectifica_a ";
		}
		
		if (!empty($filtros_especiales['acta_vacia'])) {
			// ¿Hay alumnos en el acta?
			if ($filtros_especiales['acta_vacia'] == comunes::si) {
				$operador = '=';
			} else {
				$operador = '<>';
			}
			$where .= "	AND f_acta_cantidad_alumnos(sga_actas.id_acta, 1) $operador 0";
		}
		
		if (!empty($filtros_especiales['version_desactualizada'])) {
			if ($filtros_especiales['version_desactualizada'] == comunes::si) {
				$operador = '<>';
			} else {
				$operador = '=';
			}
			$where .= "	AND sga_actas.version $operador sga_actas.version_impresa";
		}

		//----------------------------------------------------------------------

		$tipos_actas = $this->get_opciones_tipos_actas();
		
		$sql = "SELECT 
					-- Corte 1: Ubicación
					sga_ubicaciones.ubicacion,
					sga_ubicaciones.nombre as ubicacion_nombre,
					-- Corte 2: Períodos lectivos
					sga_periodos.periodo,
					sga_periodos.nombre AS periodo_nombre,
					-- Campos que se muestran en el reporte
					sga_actas.nro_acta,
					" . guarani_sql::get_case('sga_actas', 'tipo_acta', 'tipo_acta', $tipos_actas) . ",
					sga_actas.fecha_generacion,
					to_char(sga_actas.fecha_generacion,'".comunes::formato_fecha_visual."') AS fecha_generacion_formato_visual,
					f_libros_acta(sga_actas.id_acta, 2) as libro_nombre,
					sga_elementos.codigo || ' - ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as codigo_y_nombre_actividad, 
					sga_comisiones.nombre as comision_nombre,
					f_instancias_acta(sga_actas.id_acta) AS instancias_descr,
					sga_actas_estados.nombre AS estado_descr,
					rectificada.nro_acta AS rectifica_a,
					f_docentes_comision(sga_comisiones.comision, false) AS docentes,
					f_acta_cantidad_alumnos(sga_actas.id_acta, 1) AS cant_alumnos,
					sga_actas.version,
					sga_actas.version_impresa,
                                        sga_actas.digital AS acta_digital,
					sga_actas.id_documento_digital,
					sga_actas.estado_documento_digital,
                                        arai_documentos_estados.autorizado AS arai_autorizado,
                                        arai_documentos_estados.estado_codigo AS arai_estado_codigo,
                                        -- https://www.pgcasts.com/episodes/generating-json-from-sql
                                        (SELECT json_agg(row_to_json(t)) AS arai_firmantes
                                            FROM (

                                                SELECT
                                                COALESCE (mdp_personas.apellido_elegido, mdp_personas.apellido) AS apellido,
                                                COALESCE (mdp_personas.nombres_elegido, mdp_personas.nombres) AS nombres,
                                                arai_documentos_autorizaciones.usuario_sso AS usuario_sso
                                                FROM arai_documentos_autorizaciones
                                                LEFT JOIN mdp_personas ON (arai_documentos_autorizaciones.usuario_sso = mdp_personas.arai_identificador_sso)
                                                WHERE   arai_documentos_autorizaciones.id_documento_digital = sga_actas.id_documento_digital
                                                AND     arai_documentos_autorizaciones.estado = '".arai_documentos::ESTADO_SOLICITUD_AUTORIZACION_ACEPTADA_DESC."'
                                                ORDER BY arai_documentos_autorizaciones.secuencia

                                            ) t),
                                            (SELECT json_agg(row_to_json(t)) AS arai_pendientes_firma
                                            FROM (

                                                SELECT
                                                COALESCE (mdp_personas.apellido_elegido, mdp_personas.apellido) AS apellido,
                                                COALESCE (mdp_personas.nombres_elegido, mdp_personas.nombres) AS nombres,
                                                arai_documentos_autorizaciones.usuario_sso AS usuario_sso
                                                FROM arai_documentos_autorizaciones
                                                LEFT JOIN mdp_personas ON (arai_documentos_autorizaciones.usuario_sso = mdp_personas.arai_identificador_sso)
                                                WHERE   arai_documentos_autorizaciones.id_documento_digital = sga_actas.id_documento_digital
                                                AND     arai_documentos_autorizaciones.estado = '".arai_documentos::ESTADO_SOLICITUD_AUTORIZACION_PENDIENTE_DESC."'
                                                ORDER BY arai_documentos_autorizaciones.secuencia

                                            ) t),
                                            (SELECT json_agg(row_to_json(t)) AS arai_rechazado_por
                                            FROM (

                                                SELECT
                                                COALESCE (mdp_personas.apellido_elegido, mdp_personas.apellido) AS apellido,
                                                COALESCE (mdp_personas.nombres_elegido, mdp_personas.nombres) AS nombres,
                                                arai_documentos_autorizaciones.usuario_sso AS usuario_sso
                                                FROM arai_documentos_autorizaciones
                                                LEFT JOIN mdp_personas ON (arai_documentos_autorizaciones.usuario_sso = mdp_personas.arai_identificador_sso)
                                                WHERE   arai_documentos_autorizaciones.id_documento_digital = sga_actas.id_documento_digital
                                                AND     arai_documentos_autorizaciones.estado = '".arai_documentos::ESTADO_SOLICITUD_AUTORIZACION_RECHAZADA_DESC."'
                                                LIMIT 1

                                            ) t)
                                        
				FROM sga_actas
				JOIN sga_comisiones ON sga_actas.comision = sga_comisiones.comision 
				JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento 
				JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo 
				JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo 
				JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion 
				JOIN sga_actas_estados ON sga_actas_estados.estado = sga_actas.estado
				LEFT JOIN sga_actas as rectificada ON sga_actas.acta_referencia = rectificada.id_acta
				LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
                                LEFT JOIN arai_documentos_estados ON (sga_actas.id_documento_digital = arai_documentos_estados.id_documento_digital)
				WHERE true
				$where
				ORDER BY 
					ubicacion,
					periodo,
					fecha_generacion
				";
		
		return guarani_db::consultar($sql);
	}
	
	function get_listado_reporte_actas_cerradas($anio_academico, $where='')
	{
		$anio_academico	= toba::db()->quote($anio_academico);
		
		if ($where) {
			$where = ' AND ' . $where;
		}

		$tipos_actas = $this->get_opciones_tipos_actas();
		
		$sql = "SELECT 	
					-- Grupo 1
					sga_ubicaciones.ubicacion,
					sga_ubicaciones.nombre as ubicacion_nombre,
					-- Campos que se muestran en el reporte
					vw_actividades.codigo AS actividad_codigo,
					vw_actividades.nombre AS actividad_nombre,
					sga_actas.nro_acta,
					-- Tipo
					" . guarani_sql::get_case('sga_actas', 'tipo_acta', 'tipo_acta', $tipos_actas) . ",
					rectificada.nro_acta AS rectifica_a,
					sga_mesas_examen.nombre AS mesa_nombre,
					sga_periodos.nombre AS llamado_nombre,
					sga_llamados_mesa.fecha
					
				FROM sga_actas
				JOIN sga_llamados_mesa ON sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa
				JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				JOIN vw_actividades ON vw_actividades.elemento = sga_mesas_examen.elemento
				JOIN sga_ubicaciones ON sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
				LEFT JOIN sga_actas AS rectificada ON sga_actas.acta_referencia = rectificada.id_acta
				LEFT JOIN sga_llamados_turno ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
				LEFT JOIN sga_periodos ON sga_llamados_turno.periodo = sga_periodos.periodo
				LEFT JOIN sga_turnos_examen	ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
				
				WHERE	
					-- Actas cerradas
					sga_actas.estado = '" . acta::estado_cerrada . "'
					-- Filtro por año académico
					AND 
					(
						( -- Actas dentro de calendario, para un año académico específico
							sga_llamados_mesa.llamado IS NOT NULL AND sga_periodos.anio_academico = $anio_academico)
						OR
						( -- Actas fuera de calendario, para un año académico específico
							sga_llamados_mesa.llamado IS NULL AND sga_mesas_examen.anio_academico = $anio_academico
						)
					)
					$where
				ORDER BY	
					sga_ubicaciones.nombre,
					vw_actividades.nombre,
					sga_llamados_mesa.fecha
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Genera un nuevo número de Acta
	 *
	 * @param Array() Recibe como parametros en el array $parametros
	 * Origen (origen)			: E-Examen/R-Regulares/P-Promociones/Q-Equivalencias (sga_actas_origen)
	 * Tipo de Acta	(tipo_acta)	: N-Normal / R-Rectificativa
	 * Libro de Actas (libro)	: Id del Libro de Actas donde se guardará el examen.
	 *
	 * Según sea de la operación que se llame ya sea del modulo de Exámenes o Cursadas respectivamente)
	 * Llamado (llamado_mesa)	: id de la mesa de examen en un llamado (Examnenes)
	 * Comisión (comision)      : id de la comision (Cursadas)
	 * @return
	 */
	function generar_nro_acta($parametros)
	{
		$origen		= toba::db()->quote($parametros['origen']);
		$tipo_acta	= toba::db()->quote($parametros['tipo_acta']);

		switch ($parametros['origen']) {
			case acta::origen_examen:
				$llamado_mesa = toba::db()->quote($parametros['llamado_mesa']);
			break;
			case acta::origen_regulares:
				$comision = toba::db()->quote($parametros['comision']);
			break;
			case acta::origen_promociones:
				$comision = toba::db()->quote($parametros['comision']);
			break;
		}

		$sql = "SELECT	COALESCE(MAX(CAST(sga_actas.nro_acta AS integer)), 0) + 1 as nro_acta
				FROM	sga_actas
				WHERE	sga_actas.origen = $origen
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['nro_acta'];
	}

	/**
	 * Determina si el Acta de Examen tiene notas cargadas.
	 *
	 * @param int $acta id del Acta.
	 */
	function acta_examen_tiene_notas($acta)
	{
		$sql = "SELECT 	sga_eval_detalle_examenes.alumno
				FROM 	sga_eval_detalle_examenes
				WHERE	sga_eval_detalle_examenes.id_acta = $acta AND
						sga_eval_detalle_examenes.nota <> NULL
				;";
		
		$datos = guarani_db::consultar($sql);
		return guarani_arreglos::count($datos) > 0;
	}
	
	/**
	 * Obtiene la fecha de fin de vigencia de la regularidad, de acuerdo a parámetro que indica la cantidad
	 * de meses que se debe desplazar a la fecha fin dictado del período lectivo definido para la comisión
	 * y la propuesta del alumno (dicho valor se obtiene es obtenido del parámetro 'cur_cant_meses_vigencia_regularidad').
	 * 
	 * @param int $comision
	 * @param int $cant_meses
	 * @return date
	 */
	function get_fin_vigencia_regularidad($comision, $cant_meses)
	{
		$datos = toba::consulta_php('co_comisiones')->get_datos_periodo_comision($comision);
		$fecha = $datos['fecha_fin_dictado'];
		$fecha_desp = guarani_fecha::get_fecha_desplazada($fecha, $cant_meses, 'mes');
		return $fecha_desp;
	}

	/**
	 * Obtiene la fecha de fin de vigencia de la promoción.
	 * El valor por defecto es nulo.
	 */
	function get_fin_vigencia_promocion($comision, $cant_meses)
	{
		return null;
	}

	function get_fin_vigencia_examen($llamado_mesa, $fecha_examen)
	{
		return null;
	}
	

	//--------------------------------------------------------------------------
	//------- CONSULTAS DE LIBROS DE ACTAS -------------------------------------
	//--------------------------------------------------------------------------
	
	//--------------------------------------------------------------------------
	//----- generación de valores automáticos ----------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Retorna el libro_tomo de Actas que cumple con los parametros recibidos
	 * Función para la OBTENCIÓN AUTOMÁTICA DEL LIBRO / TOMO
	 * Si hay más de un libro disponible, se lanza una excepción con el mensaje 800_multiples_libros
	 *
	 * @param Array()   Recibe como parametros en el array $parametros
	 * Origen (origen)  E-Examen/R-Regulares/P-Promociones/Q-Equivalencias (sga_actas_origen)
	 * Tipo de Acta     N-Normal / R-Rectificativa
	 *
	 * Según sea de la operación que se llame ya sea del modulo de Exámenes o Cursadas respectivamente:
	 * 
	 *  + Llamado (llamado_mesa)  id de la mesa de examen en un llamado (Examnenes)
	 *  + Comisión (comision)     id de la comision (Cursadas)
	 *  
	 * @param date    $fecha   Fecha con la cual comparar la vigencia del libro de actas. Si es null, se comparará con CURRENT_DATE
	 * 
	 * @return
	 */
	function get_libro_de_actas($parametros, $fecha = null) 
	{	
		$datos = $this->get_libros_tomos_actas($parametros, $fecha);
		if (empty($datos)) {
			// No hay libro disponible
			return null;
		} elseif (guarani_arreglos::count($datos) > 1) {
			// Hay más de un libro disponible
			$mensaje = guarani_mensajes::get('800_multiples_libros');
			throw guarani::error($mensaje);
		} else {
			$datos = current($datos);
			if (!empty($datos['libro_tomo'])) {
				return $datos['libro_tomo'];
			} else {
				return null;
			}
		}
	}
	
	/**
	 * Retorna la lista de libros / tomos de Actas que cumplen con los parametros recibidos
	 *
	 * @param Array()   Recibe como parametros en el array $parametros
	 * Origen (origen)  E-Examen/R-Regulares/P-Promociones/Q-Equivalencias (sga_actas_origen)
	 * Tipo de Acta     N-Normal / R-Rectificativa
	 *
	 * Según sea de la operación que se llame ya sea del modulo de Exámenes o Cursadas respectivamente:
	 * 
	 *  + Llamado (llamado_mesa)  id de la mesa de examen en un llamado (Examnenes)
	 *  + Comisión (comision)     id de la comision (Cursadas)
	 *  
	 * @param date    $fecha   Fecha con la cual comparar la vigencia del libro de actas. Si es null, se comparará con CURRENT_DATE
	 * 
	 * @return
	 */
	function get_libros_tomos_actas($parametros, $fecha=null)
	{
		$origen = toba::db()->quote($parametros['origen']);
		
		$llamado_mesa = (!empty($parametros['llamado_mesa'])) ? $parametros['llamado_mesa'] : null;
		$comision = (!empty($parametros['comision'])) ? $parametros['comision'] : null;
		
		$sql_libro = $this->get_sql_where_condiciones_libro($parametros['origen'], $llamado_mesa, $comision);

		if (is_null($fecha)) {
			$fecha = 'CURRENT_DATE';
		} else {
			$fecha = toba::db()->quote($fecha);
		}

		$sql = "SELECT
					sga_libros_tomos.libro_tomo,
					sga_libros_actas.nro_libro,
					sga_libros_actas.nombre	|| ' - Tomo: ' || sga_libros_tomos.nro_tomo AS descr
                FROM     
					sga_libros_actas, 
					sga_libros_actas_alcance,
					sga_libros_tomos
                WHERE     
					sga_libros_actas.libro = sga_libros_actas_alcance.libro 
					AND sga_libros_tomos.libro = sga_libros_actas.libro
					AND sga_libros_actas_alcance.origen = $origen
					-- Libros activos
					AND sga_libros_actas.es_libro_activo = '" . comunes::si . "'
					AND ( 
							sga_libros_actas.fecha_activacion IS NULL
							OR sga_libros_actas.fecha_activacion <= $fecha
						)
					AND ( -- Libros vigentes
							sga_libros_actas.fecha_fin_vigencia IS NULL
							OR $fecha <= sga_libros_actas.fecha_fin_vigencia
						)
					AND ( -- Que no tengan límite de folios o que tengan folios disponibles
							sga_libros_tomos.cant_max_folios IS NULL 
							OR sga_libros_tomos.cant_max_folios = 0
							OR sga_libros_tomos.cant_max_folios > 
							(
								SELECT	COUNT(1) AS cant
								FROM	sga_actas_folios
								WHERE	sga_actas_folios.libro_tomo = sga_libros_tomos.libro_tomo
							)
						)
					AND sga_libros_tomos.nro_tomo = (    
						SELECT	MAX (otros_tomos.nro_tomo)
						FROM	sga_libros_tomos AS otros_tomos
						WHERE	otros_tomos.libro = sga_libros_tomos.libro
					)
					$sql_libro
				ORDER BY     
					sga_libros_actas.libro,
					sga_libros_tomos.nro_tomo
        ";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene condiciones particulares para la obtención de libros en función
	 * de los alcances definidos.
	 * 
	 * @param type $param_origen (R)egulares, (P)romociones, (E)xamen
	 * @param type $llamado_mesa
	 * @param type $comision
	 * @return string
	 */
	protected function get_sql_where_condiciones_libro($param_origen, $llamado_mesa=null, $comision=null)
	{
		$sql_libro = '';
		if ($param_origen == acta::origen_examen) {
			$llamado_mesa = toba::db()->quote($llamado_mesa);
			$origen = toba::db()->quote($param_origen);
			$sql_libro = "
				-- ACTAS DE EXAMEN
				AND ($origen = '". acta::origen_examen ."')
				AND	( -- 1. Libros definidos por AÑO ACADEMICO
						sga_libros_actas.anio_academico IS NULL 
						OR (sga_libros_actas.anio_academico = (SELECT anio_academico FROM vw_mesas_examen WHERE llamado_mesa = $llamado_mesa))           
					) 
				AND ( -- 2. Libros definidos por ACTIVIDAD
					EXISTS ( -- 2.1 Que sea un libro de la actividad de la mesa de examen
						SELECT 1 
						FROM sga_llamados_mesa as llm
						JOIN sga_mesas_examen as me ON me.mesa_examen = llm.mesa_examen
						JOIN sga_libros_actas_actividad as la ON  la.libro = sga_libros_actas.libro AND la.elemento = me.elemento
						WHERE llm.llamado_mesa = $llamado_mesa
						)
					OR  -- 2.2 Que no exista definicion del libro por actividad. Vale para todas las actividades
						NOT EXISTS (SELECT 1 FROM sga_libros_actas_actividad as la WHERE la.libro = sga_libros_actas.libro)
					)    
				AND ( -- 3. Libros definidos por PROPUESTAS de la mesa de examen
					EXISTS ( -- 3.1 Que sea un libro de algunas de las propuestas definidas en la mesa de examen
						SELECT 1 
						FROM sga_llamados_mesa as llm 
						JOIN sga_mesas_examen_propuestas as mep ON mep.mesa_examen = llm.mesa_examen
						JOIN sga_libros_actas_propuesta as lp ON lp.libro = sga_libros_actas.libro AND lp.propuesta = mep.propuesta 
						WHERE llm.llamado_mesa = $llamado_mesa
						)
					OR  -- 3.2 Que no exista definicion del libro por propuesta. Es valido para cualquier propuesta.
						NOT EXISTS (SELECT 1 FROM sga_libros_actas_propuesta as lp WHERE lp.libro = sga_libros_actas.libro)
					) 
					
				AND ( -- 4. Libros definidos por UBICACION de la mesa de examen
					EXISTS ( -- 4.1 Que sea un libro de la ubicacion de la mesa de examen
						SELECT 1 
						FROM sga_llamados_mesa as llm
						JOIN sga_mesas_examen as me ON me.mesa_examen = llm.mesa_examen
						JOIN sga_libros_actas_ubicacion as lu ON lu.libro = sga_libros_actas.libro AND lu.ubicacion = me.ubicacion
						WHERE llm.llamado_mesa = $llamado_mesa
						)
					 OR -- 4.2 Que no exista definicion de alguna ubicacion para el libro. En ese caso es para cualquier ubicacion
						NOT EXISTS (SELECT 1 FROM sga_libros_actas_ubicacion as lu WHERE lu.libro = sga_libros_actas.libro)
					)
				
				AND ( -- 5. Libros definidos por INSTANCIA de la mesa de examen. Las instancias son solo para mesas de examen.
					 -- 5.1 Que sea un libro de la instancia de la mesa de examen
					 EXISTS (SELECT 1 
							FROM sga_llamados_mesa as llm
							JOIN sga_mesas_examen_instancias as mei ON mei.mesa_examen = llm.mesa_examen
							JOIN sga_libros_actas_instancias as li ON li.libro = sga_libros_actas.libro AND li.instancia = mei.instancia
							WHERE llm.llamado_mesa = $llamado_mesa
							)
					 -- 5.2 Que no exista definicion por instancia de mesa de examen para el libro (quiere decir que es para cualquier instancia)	
					 OR NOT EXISTS (SELECT 1 FROM sga_libros_actas_instancias as li WHERE li.libro = sga_libros_actas.libro)
					)
			";
		} elseif (($param_origen == acta::origen_regulares) || ($param_origen == acta::origen_promociones)) {
			$comision = toba::db()->quote($comision);
			$origen = toba::db()->quote($param_origen);
			$sql_libro = "
				-- ACTAS DE CURSADAS / PROMOCION ---------------------------
				AND (
					($origen = '" . acta::origen_regulares . "' OR $origen = '" . acta::origen_promociones . "') 
					AND 
					( -- 1. Libros definidos por AÑO ACADEMICO
						sga_libros_actas.anio_academico IS NULL 
						OR (sga_libros_actas.anio_academico = (
							SELECT 
								p.anio_academico 
							FROM 
								sga_comisiones as com, 
								sga_periodos_lectivos as pl, 
								sga_periodos as p
							WHERE 
								com.comision = $comision
								AND pl.periodo_lectivo = com.periodo_lectivo
								AND p.periodo = pl.periodo
							)
						)           
					) 
					AND 
					( -- 2. Libros definidos por ACTIVIDAD
						-- 2.1 Que sea un libro de la actividad de la comision
						(EXISTS (
							SELECT	
								1 
							FROM	
								sga_libros_actas_actividad as la, 
								sga_comisiones as com
							WHERE 
								la.libro = sga_libros_actas.libro
								AND com.comision = $comision
								AND la.elemento = com.elemento
							)
						) OR (
						-- 2.2 Que no exista definicion del libro para la actividad y que no sea libro de otra actividad
							NOT EXISTS ( 
								SELECT 
									1 
								FROM 
									sga_libros_actas_actividad as la, 
									sga_comisiones as com
								WHERE 
									la.libro = sga_libros_actas.libro
									AND com.comision = $comision
									AND la.elemento = com.elemento
							) AND NOT EXISTS (
								SELECT 
									1 
								FROM 
									sga_libros_actas_actividad as la, 
									sga_comisiones as com
								WHERE 
									la.libro = sga_libros_actas.libro
									AND com.comision = $comision
									AND la.elemento <> com.elemento
							)
						)    
					) 
					AND 
					( -- 3. Libros definidos por PROPUESTAS de la comision
						-- 3.1 Que sea un libro de algunas de las propuestas definidas en la comision
						(EXISTS (
							SELECT 
								1 
							FROM 
								sga_libros_actas_propuesta as lp, 
								sga_comisiones_propuestas as com
							WHERE 
								lp.libro = sga_libros_actas.libro
								AND com.comision = $comision
								AND lp.propuesta = com.propuesta
							)
						) OR (
						-- 3.2 Que no exista definicion del libro para la actividad y que no sea libro de otra actividad
							NOT EXISTS (
								SELECT 
									1 
								FROM 
									sga_libros_actas_propuesta as lp, 
									sga_comisiones_propuestas as com
								WHERE 
									lp.libro = sga_libros_actas.libro
									AND com.comision = $comision
									AND lp.propuesta = com.propuesta
							) AND NOT EXISTS (
								SELECT 
									1 
								FROM	
									sga_libros_actas_propuesta as lp
								WHERE 
									lp.libro = sga_libros_actas.libro
									AND lp.propuesta NOT IN (
										SELECT com.propuesta 
										FROM sga_comisiones_propuestas as com
										WHERE com.comision = $comision
									)
							)
						)    
					) 
					AND 
					( -- 4. Libros definidos por UBICACION de la comision
						-- 4.1 Que sea un libro de la actividad de la comision
						(EXISTS (
							SELECT 
								1 
							FROM 
								sga_libros_actas_ubicacion as lu, 
								sga_comisiones as com
							WHERE 
								lu.libro = sga_libros_actas.libro
								AND com.comision = $comision
								AND lu.ubicacion = com.ubicacion
							)
						) OR (
						-- 4.2 Que no exista definicion del libro para la actividad y que no sea libro de otra actividad
						NOT EXISTS (
							SELECT 
								1 
							FROM 
								sga_libros_actas_ubicacion as lu, 
								sga_comisiones as com
							WHERE 
								lu.libro = sga_libros_actas.libro
								AND com.comision = $comision
								AND lu.ubicacion = com.ubicacion
							)
						AND NOT EXISTS (
							SELECT 
								1
							FROM 
								sga_libros_actas_ubicacion as lu, 
								sga_comisiones as com
							WHERE 
								lu.libro = sga_libros_actas.libro
								AND com.comision = $comision
								AND lu.ubicacion <> com.ubicacion
							)
						)
					) 
				)
			";
		}
		
		return $sql_libro;
	}

	/**
	 * Obtiene el listado de libros de actas
	 *
	 */
	function get_listado_libros_actas($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}

		$estados = $this->get_estado_activo_inactivo_libros_actas();

		$sql = "SELECT 	sga_libros_actas.libro,
						sga_libros_actas.nro_libro,
						sga_libros_actas.nombre,
						sga_libros_actas.descripcion,
						sga_libros_actas.fecha_activacion,
						sga_libros_actas.fecha_fin_vigencia,
						sga_libros_actas.es_libro_activo,
						sga_libros_actas.anio_academico,
						" . guarani_sql::get_case('sga_libros_actas', 'es_libro_activo', 'estado', $estados) . ",
						f_get_max_nro_tomo(sga_libros_actas.libro) as nro_tomo
				FROM 	sga_libros_actas
						$where
				ORDER BY sga_libros_actas.nro_libro";
		
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene una lista con los estados posibles para un libro de acta.
	 */
	function get_estado_activo_inactivo_libros_actas()
	{
		return array(array('valor' => 'S','descr' => 'Activo'),
		             array('valor' => 'N','descr' => 'Inactivo')
		            );
	}

	/**
	 * Determina si el libro tiene actas asentadas en el mismo.
	 *
	 * @param int $libro id de libro
	 */
	function libro_tiene_actas($libro)
	{
		$libro = toba::db()->quote($libro);
		$sql = "SELECT EXISTS
				(
					SELECT 	1
					FROM 	sga_actas_folios,
							sga_libros_tomos
					WHERE 	sga_libros_tomos.libro_tomo = sga_actas_folios.libro_tomo
							AND sga_libros_tomos.libro = $libro
				) AS tiene_actas;
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['tiene_actas'];
	}

	/**
	 * Determina si el acta tiene libro asignado.
	 *
	 * @param int $acta id de acta
	 */
	function acta_tiene_libro_asignado($acta)
	{
		$acta = toba::db()->quote($acta);
		$sql = "SELECT EXISTS
				(
					SELECT 	1
					FROM 	sga_actas_folios,
							sga_libros_tomos
					WHERE 	sga_libros_tomos.libro_tomo = sga_actas_folios.libro_tomo
							AND sga_actas_folios.id_acta = $acta
				) AS tiene_libro;
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['tiene_libro'];
	}

	/**
	 * Obtiene la máxima cantidad de folios que admite un libro/tomo de actas (podría ser nula).
	 *
	 * @param integer $libro_tomo
	 * @return integer
	 */
	function get_cant_max_folios_libro($libro_tomo)
	{
		$libro_tomo = toba::db()->quote($libro_tomo);

		$sql = "SELECT 	sga_libros_tomos.cant_max_folios
				FROM 	sga_libros_tomos
				WHERE	sga_libros_tomos.libro_tomo = '1'
		";

		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant_max_folios'];
	}

	/**
	 * Retorna la concatenación de nombres de libro para un acta dada.
	 *
	 * @param integer $id_acta
	 * @return string
	 */
	function get_nombres_libros_acta($id_acta)
	{
		$id_acta = toba::db()->quote($id_acta);
		$sql = "SELECT f_libros_acta($id_acta, 2) as libros_nombre";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['libros_nombre'];
	}

	/**
	 * Retorna la concatenación de tomos de libros para un acta dada.
	 *
	 * @param integer $id_acta
	 * @return string
	 */
	function get_tomos_libros_acta($id_acta)
	{
		$id_acta = toba::db()->quote($id_acta);
		$sql = "SELECT f_tomos_libros_acta($id_acta) as tomos_nombre";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['tomos_nombre'];
	}
	
	//--------------------------------------------------------------------------------------------------
	//------- Envío de Alertas
	//--------------------------------------------------------------------------------------------------
	
	function get_detalles_para_envio_mail($id_acta)
	{
		$id_acta = toba::db()->quote($id_acta);
		
		$sql = "SELECT 	vw_personas.persona,
						vw_personas.apellido_nombres as persona_nombre,
						get_nombre_actividad_mesa_examen(sga_mesas_examen.mesa_examen, 1) as actividad,
						to_char(sga_llamados_mesa.fecha,'".comunes::formato_fecha_visual."') AS fecha_examen,
						sga_actas_detalle.nota || ' (' || sga_escalas_notas_det.descripcion || ')' as nota_descr,
						sga_actas.nro_acta,
						sga_instancias_resultado.descripcion AS resultado_descripcion,
						CASE 
							WHEN sga_actas.tipo_acta = 'R' THEN 1
							ELSE 0
						END AS rectificado
				
				FROM sga_actas
				JOIN sga_actas_detalle ON sga_actas.id_acta = sga_actas_detalle.id_acta
				JOIN sga_alumnos ON sga_actas_detalle.alumno = sga_alumnos.alumno
				JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
				JOIN sga_llamados_mesa ON sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa
				JOIN sga_mesas_examen ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
				LEFT JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = sga_actas_detalle.instancia AND 
				                                       sga_instancias_resultado.resultado = sga_actas_detalle.resultado)
				LEFT JOIN sga_escalas_notas_det ON (sga_escalas_notas_det.escala_nota = sga_actas_detalle.escala_nota AND
				                                    sga_escalas_notas_det.nota = sga_actas_detalle.nota)

				WHERE	sga_actas.id_acta = $id_acta
				";
		
		return guarani_db::consultar($sql);
	}
	
	//--------------------------------------------------------------------------------------------------
	//------- Folio Físico
	//--------------------------------------------------------------------------------------------------

	/**
	 * Obtiene el último folio físico asignado de un libro, o 0 (cero) en caso que no
	 * haya folios físicos asignados.
	 *
	 * @param integer $libro_tomo
	 * @return integer
	 */
	function get_ultimo_folio_fisico($libro_tomo)
	{
		$libro_tomo = toba::db()->quote($libro_tomo);

		$sql = "SELECT 	COALESCE(MAX(sga_actas_folios.folio_fisico), 0) AS ultimo_folio_fisico
				FROM 	sga_actas_folios
				WHERE	sga_actas_folios.libro_tomo = 1
		";

		$rs = guarani_db::consultar_fila($sql);
		return $rs['ultimo_folio_fisico'];
	}

	/**
	 * Obtiene la cantidad de folios físicos asignados a un acta dada.
	 *
	 * @param integer $id_acta
	 * @return integer
	 */
	function get_cantidad_folios_fisicos_acta($id_acta)
	{
		$id_acta = toba::db()->quote($id_acta);

		$sql = "SELECT 	COUNT(1) as cantidad
				FROM 	sga_actas_folios
				WHERE	sga_actas_folios.id_acta = $id_acta
		";

		$rs = guarani_db::consultar_fila($sql);
		return $rs['cantidad'];
	}

	/**
	 * Obtiene los folios para un acta dada.
	 *
	 * @param integer $id_acta
	 * @return array
	 */
	function get_folios_acta($id_acta)
	{
		$id_acta = toba::db()->quote($id_acta);

		$sql = "SELECT		sga_actas_folios.id_acta,
							sga_actas_folios.folio,
							sga_actas_folios.libro_tomo
				FROM		sga_actas_folios
				WHERE		sga_actas_folios.id_acta = $id_acta
				ORDER BY	sga_actas_folios.folio
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos de los folios logicos de un acta dada.
	 *
	 * @param integer $id_acta
	 * @param integer $folio
	 * @return array
	 */
	function get_datos_folio_acta($id_acta, $folio)
	{
		$id_acta = toba::db()->quote($id_acta);
		$folio = toba::db()->quote($folio);

		$sql = "SELECT	sga_actas_folios.id_acta,
						sga_actas_folios.folio,
						sga_actas_folios.libro_tomo,
						sga_actas_folios.folio_fisico,
						sga_libros_tomos.nro_tomo,
						sga_libros_actas.libro,
						sga_libros_actas.nombre as nombre_libro
				FROM	sga_actas_folios,
						sga_libros_tomos,
						sga_libros_actas
				WHERE	sga_actas_folios.id_acta = $id_acta
						AND sga_actas_folios.folio = $folio
						AND sga_libros_tomos.libro_tomo = sga_actas_folios.libro_tomo
						AND sga_libros_tomos.libro = sga_libros_actas.libro
		";

		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Determina si alguno de los folios físicos del libro está en uso para otro acta.
	 *
	 * @param integer $id_acta
	 * @param integer $libro_tomo
	 * @param array $folios_fisicos
	 * @return boolean
	 */
	function hay_folios_fisicos_en_uso($id_acta, $libro_tomo, $folios_fisicos)
	{
		$id_acta = toba::db()->quote($id_acta);
		$libro_tomo = toba::db()->quote($libro_tomo);
		$filtro_folios_fisicos = guarani_sql::armar_condicion_in($folios_fisicos, 'sga_actas_folios', 'folio_fisico'); 
		$sql = "SELECT	COUNT(sga_actas_folios.id_acta) as cant_en_uso
				FROM	sga_actas_folios
				WHERE	sga_actas_folios.id_acta <> $id_acta 
						AND	sga_actas_folios.libro_tomo = $libro_tomo
						AND $filtro_folios_fisicos
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant_en_uso'] > 0);
	}
	
	//--------------------------------------------------------------------------------------------------
	//------- Tomos
	//--------------------------------------------------------------------------------------------------
	
	function get_cantidad_folios_usados_tomo($libro_tomo=null)
	{
		if (is_null($libro_tomo)) return 0;
		
		$libro_tomo = toba::db()->quote($libro_tomo);
		
		$sql = "SELECT 	COUNT(1) AS cant
				FROM 	sga_actas_folios 
				WHERE 	sga_actas_folios.libro_tomo = $libro_tomo";
		
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}
	
	function hay_otros_tomos_disponibles($libro_tomo, $fecha=null)
	{
		$libro_tomo	= toba::db()->quote($libro_tomo);
		
		if (is_null($fecha)) {
			$fecha = 'CURRENT_DATE';
		} else {
			$fecha = toba::db()->quote($fecha);
		}
		
		$sql = "SELECT	otros_libros_tomos.libro_tomo
				FROM	sga_libros_tomos as libro_tomo_param,
						sga_libros_tomos as otros_libros_tomos,
						sga_libros_actas
				WHERE	otros_libros_tomos.libro = libro_tomo_param.libro
						AND otros_libros_tomos.libro = sga_libros_actas.libro
						AND sga_libros_actas.es_libro_activo = '".comunes::si."'
						AND ( -- Libros vigentes
								sga_libros_actas.fecha_fin_vigencia IS NULL
								OR $fecha <= sga_libros_actas.fecha_fin_vigencia
							)
						AND ( -- Que no tengan límite de folios o que tengan folios disponibles
								otros_libros_tomos.cant_max_folios IS NULL 
								OR otros_libros_tomos.cant_max_folios = 0
								OR otros_libros_tomos.cant_max_folios > 
								(
									SELECT 	COUNT(1) AS cant
									FROM 	sga_actas_folios
									WHERE	sga_actas_folios.libro_tomo = otros_libros_tomos.libro_tomo
								)
							)
						AND libro_tomo_param.libro_tomo = $libro_tomo
						AND otros_libros_tomos.libro_tomo <> $libro_tomo
				ORDER BY otros_libros_tomos.nro_tomo
				";
		
		$rs = guarani_db::consultar($sql);
		return (guarani_arreglos::count($rs) > 0);
	}
	
	function get_opciones_estados_actas()
	{
		$sql = "SELECT 
					sga_actas_estados.estado AS valor,
					sga_actas_estados.nombre AS descr
				FROM 
					sga_actas_estados";
		
		return guarani_db::consultar($sql);
	}
	
	
	/*
	  Listado de mesas de examen que tienen alumnos (inscriptos o agregados a la mesa) que no se encuentran
	  en ningun acta de examen abierta o cerrada de esa mesa de examen.
	  Lista las mesas por cada llamado en el turno seleccionado.
	  Solo mesas de examen de turnos de examen.
	*/
	function get_reporte_mesas_examen_con_alumnos_no_estan_en_acta($anio_academico, $where = '')
	{	
		$anio_academico	= toba::db()->quote($anio_academico);
		$formato_fecha_visual	= toba::db()->quote(comunes::formato_fecha_visual);
		
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT         
					vw_actividades.nombre || ' (' || vw_actividades.codigo || ')' as codigo_nombre_actividad,					
					vw_actividades.codigo as codigo_actividad,
					cast(vw_mesas_examen.anio_academico as text)|| ' - ' || vw_mesas_examen.turno_examen_nombre as turno_anio_nombre,
					vw_mesas_examen.anio_academico,
					vw_mesas_examen.turno_examen_fecha_inicio as turno_examen_fecha_inicio,
					vw_mesas_examen.turno_examen as turno,
					vw_mesas_examen.turno_examen_nombre as turno_nombre,
					vw_mesas_examen.llamado_nombre as llamado_nombre,
					vw_mesas_examen.llamado_mesa,
					vw_mesas_examen.mesa_examen,
					vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
					to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha_examen,
					vw_mesas_examen.mesa_examen_fecha as fecha_examen_orden,
					sga_ubicaciones.nombre as ubicacion_nombre,
					(SELECT COUNT(1) FROM sga_actas WHERE llamado_mesa = vw_mesas_examen.llamado_mesa AND estado IN ('A','C')) as cant_actas,
					(SELECT COUNT(1) 
					   FROM sga_evaluaciones as e,
					        sga_eval_detalle_examenes as d  
					  WHERE e.entidad = vw_mesas_examen.llamado_mesa_entidad
					    AND d.evaluacion = e.evaluacion
						AND d.id_acta IS NULL
						AND d.alumno NOT IN ( -- Alumnos en actas cerradas
											SELECT sga_actas_detalle.alumno
						                      FROM sga_actas, sga_actas_detalle
											 WHERE sga_actas.llamado_mesa = vw_mesas_examen.llamado_mesa
											   AND sga_actas.estado = 'C'
											   AND sga_actas_detalle.id_acta = sga_actas.id_acta
											   AND sga_actas_detalle.estado = 'A'
											)
					) as cant_alumnos_sin_acta						
				FROM
					vw_mesas_examen,
					vw_actividades,
					sga_ubicaciones
				WHERE   vw_mesas_examen.anio_academico = $anio_academico
					AND vw_actividades.elemento        = vw_mesas_examen.mesa_examen_elemento
					AND sga_ubicaciones.ubicacion      = vw_mesas_examen.mesa_examen_ubicacion
					AND EXISTS (SELECT 1 
								FROM sga_evaluaciones as e,
									sga_eval_detalle_examenes as d  
								WHERE e.entidad = vw_mesas_examen.llamado_mesa_entidad
								AND d.evaluacion = e.evaluacion
								AND d.id_acta IS NULL
								AND d.alumno NOT IN ( -- Alumnos en actas cerradas
													SELECT sga_actas_detalle.alumno
													FROM sga_actas, sga_actas_detalle
													WHERE sga_actas.llamado_mesa = vw_mesas_examen.llamado_mesa
													AND sga_actas.estado = 'C'
													AND sga_actas_detalle.id_acta = sga_actas.id_acta
													AND sga_actas_detalle.estado = 'A'
													)
								)						
					$where
				ORDER BY vw_mesas_examen.turno_examen_fecha_inicio,
						 1,
				         vw_mesas_examen.mesa_examen_fecha
				";
		return guarani_db::consultar($sql);
	}
	
	/*
	Listado de alumnos de una mesa de examen en un llamado que no estan asignados a ningun acta de examen (abierta/cerrada)
	*/
	function get_alumnos_una_mesa_examen_no_estan_en_acta($llamado_mesa)
	{
		$sql = " SELECT
					vw_personas.apellido_nombres as apellido_nombres,
					sga_alumnos.alumno as alumno,
					sga_alumnos.legajo as legajo,
					sga_alumnos_calidad.nombre as calidad,
					sga_instancias.nombre as instancia_nombre,
					CASE sga_eval_detalle_examenes.inscripto
						WHEN 'S' THEN 'SI'
						WHEN 'N' THEN 'NO'
					END as inscripto_al_examen
				FROM vw_mesas_examen
				JOIN sga_evaluaciones ON sga_evaluaciones.entidad = vw_mesas_examen.llamado_mesa_entidad
				JOIN sga_eval_detalle_examenes ON sga_eval_detalle_examenes.evaluacion = sga_evaluaciones.evaluacion
				JOIN sga_alumnos ON sga_alumnos.alumno = sga_eval_detalle_examenes.alumno
				JOIN sga_alumnos_calidad ON sga_alumnos_calidad.calidad = sga_alumnos.calidad
				JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
				JOIN sga_instancias ON sga_instancias.instancia = sga_eval_detalle_examenes.instancia
				
				WHERE vw_mesas_examen.llamado_mesa = $llamado_mesa
				  AND sga_eval_detalle_examenes.id_acta IS NULL
				  AND sga_eval_detalle_examenes.alumno NOT IN 
												( -- Alumnos en actas cerradas
													SELECT sga_actas_detalle.alumno
													FROM sga_actas, sga_actas_detalle
													WHERE sga_actas.llamado_mesa = vw_mesas_examen.llamado_mesa
													AND sga_actas.estado = 'C'
													AND sga_actas_detalle.id_acta = sga_actas.id_acta
													AND sga_actas_detalle.estado = 'A'
												)
				ORDER BY 1									
				";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el código de todas las propuestas asociadas al acta dada como parámetro.
	 *
	 * @param integer $idActa id del acta para la cual se desean recuperar las propuestas.
	 *
	 * @return int[] Array de propuestas asociadas al acta.
	 */
	function get_propuestas_acta($idActa)
	{
		$idActa = toba::db()->quote($idActa);
		$propuestas = array();
		
		$sql = "SELECT origen, comision, mesa_examen
				FROM sga_actas
				LEFT JOIN sga_llamados_mesa ON (sga_llamados_mesa.llamado_mesa = sga_actas.llamado_mesa)
				WHERE id_acta = $idActa";

		$datosActa = guarani_db::consultar_fila($sql);

		if(in_array($datosActa['origen'], array(acta::origen_regulares, acta::origen_promociones))){
			$propuestas = toba::consulta_php('co_comisiones')->get_propuestas_comision($datosActa['comision']);
		}
		else if ($datosActa['origen'] == acta::origen_examen){
			$propuestas = toba::consulta_php('co_mesas_de_examen')->get_propuestas_mesa_examen($datosActa['mesa_examen']);
		}

		return $propuestas;
	}
	
	function get_cant_alumnos_sin_acta_cursada($comision, $instancia)
	{
		if ($instancia == comision::inst_cursar) {		
			$where_instancia = "AND sga_eval_detalle_cursadas.id_acta_cursada IS NULL";
		} else {
			$where_instancia = "AND sga_eval_detalle_cursadas.id_acta_promocion IS NULL";
		}

		$sql = "SELECT count(1) as cant
				FROM	sga_comisiones 
				JOIN	sga_evaluaciones			ON (sga_evaluaciones.entidad = sga_comisiones.entidad)
				JOIN	sga_eval_detalle_cursadas 	ON (sga_eval_detalle_cursadas.evaluacion = sga_evaluaciones.evaluacion)
				WHERE	sga_comisiones.comision = $comision
				$where_instancia
				";
		$rs = guarani_db::consultar_fila($sql); 
		return $rs['cant'];
	}

	/**
	 * Retorna el listado de alumnos que aprobó alguna materia por acta de examen.
	 * Considera que el acta no haya sido tenido en cuenta aún por la operación "Procesar Matrices de Equivalencia".
	 * 
	 * @param int $anio_academico
	 * @param int $turno_examen
	 * @param array $ubicaciones
	 * @param array $planes
	 * @return array
	 */
	function get_alumnos_acta_examen_aplicar_matrices($anio_academico, $turno_examen, $ubicaciones, $planes)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$turno_examen = toba::db()->quote($turno_examen);
		$filtro_ubicaciones = guarani_sql::armar_condicion_in($ubicaciones, 'vw_mesas_examen', 'mesa_examen_ubicacion');
		$filtro_planes = guarani_sql::armar_condicion_in($planes, 'sga_mesas_examen_propuestas', 'plan');

		$sql = "SELECT		sga_actas.id_acta,
							'CURSADA_SIMULTANEA' as accion,
							otro_alumno.alumno,
							sga_alumnos.propuesta as propuesta_origen,
							sga_planes_versiones.plan as plan_origen,
							sga_planes_versiones.plan_version as plan_version_origen,
							vw_mesas_examen.mesa_examen_elemento as elemento
				FROM		vw_mesas_examen
							JOIN sga_mesas_examen_propuestas ON vw_mesas_examen.mesa_examen = sga_mesas_examen_propuestas.mesa_examen
							JOIN sga_actas ON vw_mesas_examen.llamado_mesa = sga_actas.llamado_mesa
							JOIN sga_actas_detalle ON sga_actas.id_acta = sga_actas_detalle.id_acta
							JOIN sga_alumnos ON sga_actas_detalle.alumno = sga_alumnos.alumno
							JOIN sga_planes_versiones ON sga_actas_detalle.plan_version = sga_planes_versiones.plan_version
							JOIN sga_alumnos as otro_alumno ON (sga_alumnos.persona = otro_alumno.persona AND sga_alumnos.alumno <> otro_alumno.alumno AND otro_alumno.calidad = '".alumno::calidad_activo."')
				WHERE		vw_mesas_examen.anio_academico = $anio_academico AND
							vw_mesas_examen.turno_examen = $turno_examen AND
							$filtro_ubicaciones AND
							$filtro_planes AND
							sga_actas.origen = '".acta::origen_examen."' AND
							sga_actas.estado = '".acta::estado_cerrada."' AND
							sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
							sga_actas_detalle.rectificado = '".comunes::no."' AND
							sga_actas_detalle.instancia IN (".mesa_de_examen::inst_exam_regular.",".mesa_de_examen::inst_exam_libre.",".mesa_de_examen::inst_exam_complementario.") AND
							sga_actas_detalle.resultado = '".escalas_notas::resultado_aprobado."' AND
							NOT EXISTS (SELECT	sga_equiv_actas_procesadas.nro_proceso
										FROM	sga_equiv_actas_procesadas
										WHERE	sga_equiv_actas_procesadas.id_acta = sga_actas.id_acta)
				UNION
				SELECT		sga_actas.id_acta,
							CASE
								WHEN sga_planes_versiones.plan <> plan_version_acta.plan THEN 'CAMBIO_DE_PLAN'
								ELSE 'CAMBIO_DE_VERSION'
							END as accion,
							sga_alumnos.alumno,
							sga_alumnos.propuesta as propuesta_origen,
							plan_version_acta.plan as plan_origen,
							plan_version_acta.plan_version as plan_version_origen,
							vw_mesas_examen.mesa_examen_elemento as elemento
				FROM		vw_mesas_examen
							JOIN sga_mesas_examen_propuestas ON vw_mesas_examen.mesa_examen = sga_mesas_examen_propuestas.mesa_examen
							JOIN sga_actas ON vw_mesas_examen.llamado_mesa = sga_actas.llamado_mesa
							JOIN sga_actas_detalle ON sga_actas.id_acta = sga_actas_detalle.id_acta
							JOIN sga_alumnos ON sga_actas_detalle.alumno = sga_alumnos.alumno
							JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version -- Versión de plan actual del alumno.
							JOIN sga_planes_versiones as plan_version_acta ON sga_actas_detalle.plan_version = plan_version_acta.plan_version -- Versión del plan por la que rindió el examen (podria ser un plan anterior).
				WHERE		vw_mesas_examen.anio_academico = $anio_academico AND
							vw_mesas_examen.turno_examen = $turno_examen AND
							$filtro_ubicaciones AND
							$filtro_planes AND
							sga_actas.origen = '".acta::origen_examen."' AND
							sga_actas.estado = '".acta::estado_cerrada."' AND
							sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
							sga_actas_detalle.rectificado = '".comunes::no."' AND
							sga_actas_detalle.instancia IN (".mesa_de_examen::inst_exam_regular.",".mesa_de_examen::inst_exam_libre.",".mesa_de_examen::inst_exam_complementario.") AND
							sga_actas_detalle.resultado = '".escalas_notas::resultado_aprobado."' AND
							(sga_planes_versiones.plan <> plan_version_acta.plan OR sga_planes_versiones.plan_version <> plan_version_acta.plan_version) AND
							NOT EXISTS (SELECT	sga_equiv_actas_procesadas.nro_proceso
										FROM	sga_equiv_actas_procesadas
										WHERE	sga_equiv_actas_procesadas.id_acta = sga_actas.id_acta)
				ORDER BY	1
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de alumnos que aprobó alguna materia por acta de regularidad/promoción.
	 * Considera que el acta no haya sido tenido en cuenta aún por la operación "Procesar Matrices de Equivalencia".
	 * 
	 * @param int $anio_academico
	 * @param int $periodo_lectivo
	 * @param array $ubicaciones
	 * @param array $planes
	 * @return array
	 */
	function get_alumnos_acta_cursada_aplicar_matrices($anio_academico, $periodo_lectivo, $ubicaciones, $planes)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$periodo_lectivo = toba::db()->quote($periodo_lectivo);
		$filtro_ubicaciones = guarani_sql::armar_condicion_in($ubicaciones, 'vw_comisiones', 'ubicacion');
		$filtro_planes = guarani_sql::armar_condicion_in($planes, 'sga_comisiones_propuestas', 'plan');

		$sql = "SELECT		sga_actas.id_acta,
							'CURSADA_SIMULTANEA' as accion,
							otro_alumno.alumno,
							sga_alumnos.propuesta as propuesta_origen,
							sga_planes_versiones.plan as plan_origen,
							sga_planes_versiones.plan_version as plan_version_origen,
							vw_comisiones.elemento
				FROM		vw_comisiones
							JOIN sga_comisiones_propuestas ON vw_comisiones.comision = sga_comisiones_propuestas.comision
							JOIN sga_actas ON vw_comisiones.comision = sga_actas.comision
							JOIN sga_actas_detalle ON sga_actas.id_acta = sga_actas_detalle.id_acta
							JOIN sga_alumnos ON sga_actas_detalle.alumno = sga_alumnos.alumno
							JOIN sga_planes_versiones ON sga_actas_detalle.plan_version = sga_planes_versiones.plan_version
							JOIN sga_alumnos as otro_alumno ON (sga_alumnos.persona = otro_alumno.persona AND sga_alumnos.alumno <> otro_alumno.alumno AND otro_alumno.calidad = '".alumno::calidad_activo."')
				WHERE		vw_comisiones.anio_academico = $anio_academico AND
							vw_comisiones.periodo_lectivo = $periodo_lectivo AND
							$filtro_ubicaciones AND
							$filtro_planes AND
							(sga_actas.origen = '".acta::origen_regulares."' OR sga_actas.origen = '".acta::origen_promociones."') AND
							sga_actas.estado = '".acta::estado_cerrada."' AND
							sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
							sga_actas_detalle.rectificado = '".comunes::no."' AND
							sga_actas_detalle.resultado = '".escalas_notas::resultado_aprobado."' AND
							NOT EXISTS (SELECT	sga_equiv_actas_procesadas.nro_proceso
										FROM	sga_equiv_actas_procesadas
										WHERE	sga_equiv_actas_procesadas.id_acta = sga_actas.id_acta)
				UNION
				SELECT		sga_actas.id_acta,
							CASE
								WHEN sga_planes_versiones.plan <> plan_version_acta.plan THEN 'CAMBIO_DE_PLAN'
								ELSE 'CAMBIO_DE_VERSION'
							END as accion,
							sga_alumnos.alumno,
							sga_alumnos.propuesta,
							plan_version_acta.plan,
							plan_version_acta.plan_version,
							vw_comisiones.elemento
				FROM		vw_comisiones
							JOIN sga_comisiones_propuestas ON vw_comisiones.comision = sga_comisiones_propuestas.comision
							JOIN sga_actas ON vw_comisiones.comision = sga_actas.comision
							JOIN sga_actas_detalle ON sga_actas.id_acta = sga_actas_detalle.id_acta
							JOIN sga_alumnos ON sga_actas_detalle.alumno = sga_alumnos.alumno
							JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
							JOIN sga_planes_versiones as plan_version_acta ON sga_actas_detalle.plan_version = plan_version_acta.plan_version
				WHERE		vw_comisiones.anio_academico = $anio_academico AND
							vw_comisiones.periodo_lectivo = $periodo_lectivo AND
							$filtro_ubicaciones AND
							$filtro_planes AND
							(sga_actas.origen = '".acta::origen_regulares."' OR sga_actas.origen = '".acta::origen_promociones."') AND
							sga_actas.estado = '".acta::estado_cerrada."' AND
							sga_actas_detalle.estado = '".acta::detalle_estado_activo."' AND
							sga_actas_detalle.rectificado = '".comunes::no."' AND
							sga_actas_detalle.resultado = '".escalas_notas::resultado_aprobado."' AND
							(sga_planes_versiones.plan <> plan_version_acta.plan OR sga_planes_versiones.plan_version <> plan_version_acta.plan_version) AND
							NOT EXISTS (SELECT	sga_equiv_actas_procesadas.nro_proceso
										FROM	sga_equiv_actas_procesadas
										WHERE	sga_equiv_actas_procesadas.id_acta = sga_actas.id_acta)
				ORDER BY	1
		";

		return guarani_db::consultar($sql);
	}
        
        /**
	 * Obtiene el encabezado de un acta dada para enviar a Araí-Documentos.
	 * @see https://redmine.siu.edu.ar/redmine/issues/30921 Ticket #30921
	 */
	function get_encabezado_acta_examen_arai_documentos($id_acta)
	{
		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);
		$texto_acta_examen = acta::texto_acta_examen;
		$texto_mesa_dia = acta::texto_mesa_dia;
                
		$sql = "SELECT 
                        '$texto_acta_examen ' || ' ' || nro_acta AS titulo,
                        sga_elementos.nombre || ' - $texto_mesa_dia ' || to_char(sga_llamados_mesa.fecha, $formato_fecha_visual) AS descripcion
                        FROM sga_actas
                        JOIN sga_llamados_mesa  ON (sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa)
                        JOIN sga_mesas_examen   ON (sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen)
                        JOIN sga_elementos      ON (sga_mesas_examen.elemento = sga_elementos.elemento)
                        WHERE sga_actas.id_acta = $id_acta";
                
		return guarani_db::consultar_fila($sql);
	}


        //===========================================
        //     Actas digitales - Araí Documentos     
        //===========================================

        /**
         * Obtiene los docentes que pueden firma un acta de regularidad o promoción.
         * @see https://redmine.siu.edu.ar/redmine/issues/30935 Ticket #30935
         * @see https://redmine.siu.edu.ar/redmine/issues/30959 Ticket #30959
         *
         * @param integer $id_acta Id del acta.
         *
         * @return array Docentes autorizantes.
         */
	function get_docentes_autorizantes_acta_comision($id_acta)
	{
            $sql = "SELECT
                    mdp_personas.persona,
                    mdp_personas.arai_identificador_sso
                    FROM sga_actas
                    JOIN sga_docentes_comision ON (sga_actas.comision = sga_docentes_comision.comision)
                    JOIN sga_docentes_responsabilidades ON (sga_docentes_comision.responsabilidad = sga_docentes_responsabilidades.responsabilidad)
                    JOIN sga_docentes ON (sga_docentes.docente = sga_docentes_comision.docente)
                    JOIN mdp_personas ON (sga_docentes.persona = mdp_personas.persona)
                    WHERE sga_docentes_comision.firma = '".comunes::si."'
                    AND sga_actas.id_acta = $id_acta
                    ORDER BY sga_docentes_responsabilidades.orden_acta,  COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
                            COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)
                    ";

            return guarani_db::consultar($sql);
	}

        /**
	 * Obtiene los docentes autorizantes de un acta de examen.
	 * @see https://redmine.siu.edu.ar/redmine/issues/30921 Ticket #30921
         *
         * @param integer $id_acta Id del acta.
         *
         * @return array Docentes autorizantes.
	 */
	function get_docentes_autorizantes_acta_examen($id_acta)
	{
            $sql = "SELECT 
                    mdp_personas.persona,
                    mdp_personas.arai_identificador_sso
                    FROM sga_actas
                    JOIN sga_llamados_mesa ON (sga_llamados_mesa.llamado_mesa = sga_actas.llamado_mesa)
                    JOIN sga_docentes_mesa_llamado ON (sga_docentes_mesa_llamado.llamado_mesa = sga_llamados_mesa.llamado_mesa)
                    JOIN sga_docentes ON (sga_docentes.docente = sga_docentes_mesa_llamado.docente)
                    JOIN mdp_personas ON (sga_docentes.persona = mdp_personas.persona)
                    JOIN sga_tribunal_roles ON (sga_docentes_mesa_llamado.rol = sga_tribunal_roles.rol)
                    WHERE sga_docentes_mesa_llamado.firma = '".comunes::si."'
                    AND sga_actas.id_acta = $id_acta
                    ORDER BY sga_tribunal_roles.orden_acta,  COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
                            COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)
                    ";

            return guarani_db::consultar($sql);
	}
    
    /**
	 * Obtiene el encabezado de un acta dada para enviar a Araí-Documentos.
	 */
	function get_encabezado_acta_comision_arai_documentos($id_acta, $origen)
	{
		$texto_acta = ($origen == acta::origen_regulares) ? acta::texto_acta_regulares : acta::texto_acta_promocion;
		$texto_comision = acta::texto_comision;
                
		$sql = "
                SELECT 
                '$texto_acta' || ' ' || nro_acta as titulo, 
                sga_elementos.nombre || ' - $texto_comision ' || sga_comisiones.nombre AS descripcion
                FROM sga_actas
                JOIN sga_comisiones ON (sga_comisiones.comision = sga_actas.comision)
                JOIN sga_elementos ON (sga_comisiones.elemento = sga_elementos.elemento)
                WHERE sga_actas.id_acta = $id_acta";
                
		return guarani_db::consultar_fila($sql);
	}

    /**
	 * Obtiene las actas cerradas que fallaron a la hora de crear el documento en Araí.
	 * @see https://redmine.siu.edu.ar/redmine/issues/32582 Ticket #32582
	 */
	function get_actas_cerradas_falla_documentos()
	{
			$estado_cerrada = toba::db()->quote(acta::estado_cerrada);
			$estado_documento_digital_fallo = toba::db()->quote(arai_documentos::ESTADO_DOCUMENTO_DIGITAL_FALLO);

			$origen_examen = toba::db()->quote(acta::origen_examen);
			$origen_promociones = toba::db()->quote(acta::origen_promociones);
			$origen_regulares = toba::db()->quote(acta::origen_regulares);

			$texto_acta_examen = toba::db()->quote(acta::texto_acta_examen);
			$texto_acta_promocion = toba::db()->quote(acta::texto_acta_promocion);
			$texto_acta_regulares = toba::db()->quote(acta::texto_acta_regulares);


            $sql = "
				SELECT 
					sga_actas.id_acta,
					sga_actas.nro_acta,
					sga_actas.origen as origen_acta,
					$texto_acta_examen as origen_acta_texto,
					sga_actas.parametros_documento_digital,
					'(' || sga_elementos.codigo || ')' || ' ' || sga_elementos.nombre as nombre_actividad
				FROM sga_actas
				JOIN sga_llamados_mesa ON (sga_llamados_mesa.llamado_mesa = sga_actas.llamado_mesa)
				JOIN sga_mesas_examen ON (sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen)
				JOIN sga_elementos ON (sga_elementos.elemento = sga_mesas_examen.elemento)
				WHERE sga_actas.estado = $estado_cerrada
				AND sga_actas.estado_documento_digital = $estado_documento_digital_fallo

				UNION 

				SELECT 
					sga_actas.id_acta,
					sga_actas.nro_acta,
					sga_actas.origen as origen_acta,
					CASE
					   WHEN origen = $origen_promociones	THEN $texto_acta_promocion
					   WHEN origen = $origen_regulares		THEN $texto_acta_regulares
					END origen_acta_texto,
					sga_actas.parametros_documento_digital,
					'(' || sga_elementos.codigo || ')' || ' ' || sga_elementos.nombre as nombre_actividad
				FROM sga_actas
				JOIN sga_comisiones ON (sga_comisiones.comision = sga_actas.comision)
				JOIN sga_elementos ON (sga_elementos.elemento = sga_comisiones.elemento)
				WHERE sga_actas.estado = $estado_cerrada
				AND sga_actas.estado_documento_digital = $estado_documento_digital_fallo
				
				ORDER BY id_acta";

            return guarani_db::consultar($sql);
	}

   /**
	* Si existen actas que fallaron en Araí.
	* @see https://redmine.siu.edu.ar/redmine/issues/32755 Ticket #32755
	* @return bool true si existen actas que fallaron en Araí, false en caso contrario.
	*/
	function existen_actas_fallaron_arai()
	{
		$estado_cerrada = toba::db()->quote(acta::estado_cerrada);
		$estado_documento_digital_fallo = toba::db()->quote(arai_documentos::ESTADO_DOCUMENTO_DIGITAL_FALLO);

		$sql = "
				SELECT COUNT(*) AS cant_actas_fallaron_arai
				FROM sga_actas
				WHERE sga_actas.estado = $estado_cerrada
				AND sga_actas.estado_documento_digital = $estado_documento_digital_fallo;
				";
		$row = guarani_db::consultar_fila($sql);
		return ($row['cant_actas_fallaron_arai'] > 0);
	}

	function get_cant_actas_cerradas_subcomision($subcomision, $where='')
	{
		$subcomision = toba::db()->quote($subcomision);
		if ($where) {
			$where .= " AND $where";
		}
		$sql = "SELECT 	count(sga_actas.id_acta) as cant_actas
				FROM 	sga_actas
						JOIN sga_subcomisiones ON sga_actas.comision = sga_subcomisiones.comision
				WHERE	sga_subcomisiones.subcomision = $subcomision AND
						sga_actas.estado = '".acta::estado_cerrada."'
						$where
		";
		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['cant_actas'];
	}

	/**
	 * Retorna verdadero si existe un acta no anulada para el llamado recibido como parámetro.
	 * 
	 * @param int $llamado_mesa
	 * @return bool
	 */
	function existe_acta_no_anulada_llamado_mesa($llamado_mesa)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$sql = "SELECT	COUNT(sga_actas.id_acta) as cant
				FROM	sga_actas
				WHERE	sga_actas.llamado_mesa = $llamado_mesa AND
						sga_actas.estado <> '".acta::estado_anulada."'
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}
}
