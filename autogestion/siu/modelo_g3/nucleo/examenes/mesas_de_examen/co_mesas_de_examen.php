<?php

class co_mesas_de_examen
{

	function get_listado($where='', $select='')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		if ($select) {
			$select = ', '.$select;
		}
		$sql = "SELECT	
					sga_elementos.elemento,
					sga_elementos.codigo,
					get_nombre_actividad_mesa_examen(sga_mesas_examen.mesa_examen, 1) as nombre_actividad,
					'(' || COALESCE(sga_elementos.codigo, 'Sin Código') || ') ' || get_nombre_actividad_mesa_examen(sga_mesas_examen.mesa_examen, 1) AS actividad_descr,
					sga_mesas_examen.mesa_examen,
					sga_mesas_examen.nombre,
					sga_mesas_examen.ubicacion,
					COALESCE(sga_ubicaciones.nombre, 'No hay ubicación asociada') as nombre_ubicacion,
					sga_mesas_examen.catedra,
					COALESCE(sga_catedras.nombre, ' ') as catedra_nombre,
					sga_llamados_mesa.llamado_mesa,
					sga_periodos.nombre as nombre_llamado,
					f_docentes_mesa_examen(sga_llamados_mesa.llamado_mesa) AS docentes_llamado,
					sga_llamados_mesa.fecha,
					to_char(sga_llamados_mesa.fecha,'".comunes::formato_fecha_visual."') as fecha_formato_visual,
					to_char(sga_llamados_mesa.fecha,'".comunes::formato_fecha_visual."') || ' ' || to_char(cast(sga_llamados_mesa.hora_inicio as time), '".comunes::formato_hora_visual."') as fecha_formato_visual_sin_segundos,
					sga_llamados_mesa.entidad,
					sga_periodos.anio_academico,
					sga_llamados_turno.turno_examen,
					periodos_turnos_examen.nombre as nombre_turno_examen,
					'Año Académico: ' || sga_periodos.anio_academico || ' - Turno: ' || periodos_turnos_examen.nombre as anio_y_turno,
					'(' || sga_elementos.codigo || ') ' || get_nombre_actividad_mesa_examen(sga_mesas_examen.mesa_examen, 1) as codigo_nombre_actividad,
					f_instancias_mesa_examen(sga_mesas_examen.mesa_examen) as instancias_nombre,
					COALESCE(f_modalidad_cursada_examen(sga_mesas_examen.mesa_examen), ' ') AS modalidades_nombre,
					sga_turnos_examen.turno_tipo,
					 CONCAT_WS(' a ',  to_char(cast(sga_llamados_mesa.hora_inicio as time), '".comunes::formato_hora_visual."') ,
					 to_char(cast(sga_llamados_mesa.hora_finalizacion as time), '".comunes::formato_hora_visual."') ) as horario_examen
					$select
						
				FROM sga_mesas_examen
				JOIN sga_llamados_mesa ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
				JOIN sga_llamados_turno ON sga_llamados_turno.llamado = sga_llamados_mesa.llamado
				JOIN sga_periodos ON sga_periodos.periodo = sga_llamados_turno.periodo
				JOIN sga_elementos ON sga_elementos.elemento = sga_mesas_examen.elemento
				JOIN sga_turnos_examen ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
				JOIN sga_periodos as periodos_turnos_examen ON periodos_turnos_examen.periodo = sga_turnos_examen.periodo
				JOIN vw_actividades ON vw_actividades.elemento = sga_mesas_examen.elemento
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_mesas_examen.ubicacion
				LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_mesas_examen.catedra

				$where
				
				ORDER BY 
					sga_ubicaciones.nombre,
					nombre_actividad,
					fecha DESC
  				;";
		
		return guarani_db::consultar($sql);

	}

	/**
	 * Retorna el listado de llamados-mesa que cumplen alguna de las condiciones para ser modificados:
	 * - tienen acta generada (abierta o cerrada)
	 * - tienen alumnos inscriptos
	 * - tienen inscripciones dadas de baja
	 * 
	 * @param type $where
	 * @return type
	 */
	function get_llamados_editables($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT        
                    sga_elementos.codigo as codigo,
                    get_nombre_actividad_mesa_examen(vw_mesas_examen.mesa_examen, 1) as nombre_actividad,
                    vw_mesas_examen.mesa_examen_nombre as nombre,
                    vw_mesas_examen.anio_academico as anio_academico,
                    vw_mesas_examen.turno_examen_nombre as nombre_turno_examen,
                    vw_mesas_examen.llamado_mesa as llamado_mesa,
                    vw_mesas_examen.llamado_nombre as nombre_llamado,
                    vw_mesas_examen.mesa_examen_fecha as fecha,
                    sga_ubicaciones.nombre as nombre_ubicacion
                
                FROM vw_mesas_examen
                JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion
                JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
                
                WHERE
                    (
                    -- Llamados que tienen acta generada (abierta o cerrada).
                    EXISTS (SELECT     sga_actas.id_acta 
                            FROM     sga_actas 
                            WHERE     sga_actas.llamado_mesa = vw_mesas_examen.llamado_mesa AND
                                    sga_actas.estado <> '".acta::estado_anulada."')
                    OR
                    -- Llamados que tienen alumnos inscriptos.
                    EXISTS (SELECT    sga_insc_examen.inscripcion
                            FROM    sga_insc_examen
                            WHERE    sga_insc_examen.llamado_mesa = vw_mesas_examen.llamado_mesa)
                    OR
                    -- Llamados que tienen inscripciones dadas de baja.
                    EXISTS (SELECT    sga_insc_examen_log.inscripcion
                            FROM    sga_insc_examen_log
                            WHERE    sga_insc_examen_log.llamado_mesa = vw_mesas_examen.llamado_mesa)
                    )
                    -- No recupera mesas que tengan documento digital.
                    AND NOT EXISTS (SELECT  sga_actas.id_acta
                                    FROM    sga_actas
                                    WHERE   sga_actas.llamado_mesa = vw_mesas_examen.llamado_mesa AND
                                            sga_actas.id_documento_digital IS NOT NULL
											AND sga_actas.estado <> '".acta::estado_anulada."'
								   	)
                    $where
                ORDER BY    
                    vw_mesas_examen.mesa_examen_fecha
  		";
		return guarani_db::consultar($sql);
	}

	/**
 	  * Determina si una Ubicación y un Turno de Examen dados tienen definidas Mesas.
 	  * @param  integer $ubicacion	id de la ubicación de la que se desea conocer si tiene o no mesas
 	  * @param  integer $turno		id del turno del que se desea conocer si tiene o no mesas
 	  * @return boolean	true si el turno en la ubicación dados tiene mesas definidas y false en caso contrario
    */
	function existen_mesas_turno_ubicacion($ubicacion, $turno)
	{
		$ubicacion = toba::db()->quote($ubicacion);
		$turno = toba::db()->quote($turno);
		
		$sql = "SELECT 		count(sga_mesas_examen.mesa_examen)	as cant
				FROM 		sga_mesas_examen,
							sga_llamados_mesa,
							sga_llamados_turno
				WHERE		sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen AND
							sga_llamados_mesa.llamado = sga_llamados_turno.llamado AND
							sga_mesas_examen.ubicacion= $ubicacion AND
							sga_llamados_turno.turno_examen = $turno
				;";
		$res = guarani_db::consultar_fila($sql);
		$cant_mesas = $res['cant'];
		if ($cant_mesas > 0) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
 	  * Determina si una Actividad en un Turno de Examen dado tiene definidas Mesas.
 	  * @param  integer $actividad	id de la actividad de la que se desea conocer si tiene o no mesas
 	  * @param  integer $turno		id del turno del que se desea conocer si tiene o no mesas
 	  * @return boolean	true si la actividad en el turno dados tiene mesas definidas y false en caso contrario
    */
	function existe_mesa_en_turno_actividad($actividad, $turno)
	{
		$actividad = toba::db()->quote($actividad);
		$turno = toba::db()->quote($turno);
		
		$sql = "SELECT 		count(sga_mesas_examen.mesa_examen)	as cant
				FROM 		sga_mesas_examen,
							sga_llamados_mesa,
							sga_llamados_turno
				WHERE		sga_mesas_examen.mesa_examen 	= sga_llamados_mesa.mesa_examen
				AND			sga_llamados_mesa.llamado 		= sga_llamados_turno.llamado
				AND			sga_llamados_turno.turno_examen = $turno
				AND			sga_mesas_examen.elemento		= $actividad
				";
		
		$res = guarani_db::consultar_fila($sql);
		$cant_mesas = $res['cant'];
		
		return $cant_mesas > 0;
	}

	function existe_mesa_en_turno($turno_examen)
	{
		$turno_examen = toba::db()->quote($turno_examen);
		$sql = "SELECT	COUNT(sga_llamados_mesa.llamado_mesa) as cant
				FROM	sga_llamados_mesa
						JOIN sga_llamados_turno ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
				WHERE	sga_llamados_turno.turno_examen = $turno_examen
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}

	/**
 	  * Retorna la cantidad de Mesas de Examen en una Actividad y Turno de Examen dados.
 	  * @param  integer $actividad	id de la actividad de la que se desea obtener las mesas
 	  * @param  integer $turno		id del turno del que se desea obtener las mesas
 	  * @return integer				cantidad de mesas que existen en la actividad y turno
    */
	function get_cant_mesas_de_actividad_en_turno_examen($actividad, $turno)
	{
		$actividad = toba::db()->quote($actividad);
		$turno = toba::db()->quote($turno);
		
		$sql = "SELECT 	COUNT(DISTINCT(vw_mesas_examen.mesa_examen)) AS cant
				FROM 	vw_mesas_examen
				WHERE	vw_mesas_examen.turno_examen			= $turno
				AND		vw_mesas_examen.mesa_examen_elemento	= $actividad
				;";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['cant'];
	}
	
	/**
	 * Obtiene un listado de Mesas de Examen para exhibir en reporte según filtro especificado.
	 *
	 */
	function get_listado_reporte_mesas_examen($where='')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		
		$sql = "SELECT 		-- Grupo 1
							sga_ubicaciones.ubicacion,
							sga_ubicaciones.nombre as ubicacion_nombre,
							-- Grupo 2
							vw_actividades.elemento,
							'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_codigo_y_nombre,
                            -- Grupo 3
                            'Mesa: ' || sga_mesas_examen.nombre ||
                            ' - Cátedra: ' || COALESCE(sga_catedras.nombre, '   ') ||
                            ' - Instancias: ' || f_instancias_mesa_examen(sga_mesas_examen.mesa_examen) AS mesa_catedra_instancias,
                            
                            sga_mesas_examen.nombre AS mesa_nombre,
                            COALESCE(sga_catedras.nombre, '   ') AS catedra_nombre,
                            f_instancias_mesa_examen(sga_mesas_examen.mesa_examen) AS instancia_nombre,
				            
				            -- Campos que se muestran en el reporte
				            sga_periodos.nombre AS llamado_nombre,
				            sga_llamados_mesa.fecha,
				            to_char(cast(sga_llamados_mesa.hora_inicio as time), '".comunes::formato_hora_visual."') as hora_inicio,
				            f_docentes_mesa_examen(sga_llamados_mesa.llamado_mesa) AS docentes
				                        
				FROM sga_mesas_examen
				JOIN sga_llamados_mesa ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				JOIN vw_actividades ON vw_actividades.elemento = sga_mesas_examen.elemento
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion= sga_mesas_examen.ubicacion
				JOIN sga_llamados_turno ON sga_llamados_turno.llamado = sga_llamados_mesa.llamado 
				JOIN sga_periodos ON sga_periodos.periodo = sga_llamados_turno.periodo
				JOIN sga_turnos_examen ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
                		LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_mesas_examen.catedra
				            
				$where
				            
				ORDER BY 	
					sga_ubicaciones.nombre, 
					vw_actividades.nombre, 
					sga_periodos.nombre,
					sga_llamados_mesa.fecha,
					sga_mesas_examen.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene un listado de Mesas de Examen sin acta generada para exhibir en reporte según filtro especificado.
	 *
	 */
	function get_listado_reporte_mesas_examen_sin_acta_generada($where='', $order='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		if ($order) {
			$order_by = ' ORDER BY ' . $order;
		} else {
			$order_by = " ORDER BY	sga_ubicaciones.nombre, actividad_codigo_y_nombre, sga_mesas_examen.nombre;";
		}
		
		$sql = "SELECT -- Grupo 1
				sga_ubicaciones.ubicacion,
				sga_ubicaciones.nombre as ubicacion_nombre,
				-- Grupo 2
				vw_actividades.elemento,
				'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_codigo_y_nombre,
				            
				-- Campos que se muestran en el reporte
				sga_mesas_examen.mesa_examen,
				sga_mesas_examen.nombre AS nombre_mesa,
				sga_periodos.nombre AS llamado_nombre,
				sga_periodos.anio_academico,
				periodos_turnos_examen.nombre as nombre_turno,
				sga_llamados_mesa.fecha,
				f_instancias_mesa_examen(sga_mesas_examen.mesa_examen) AS instancias,
				f_docentes_mesa_examen(sga_llamados_mesa.llamado_mesa) AS docentes,
				sga_llamados_mesa.llamado_mesa,
				(SELECT count(1)
				   FROM vw_insc_examen
				  WHERE vw_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
				) as cant_inscriptos
				                        
				FROM sga_mesas_examen
				JOIN sga_llamados_mesa ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				JOIN vw_actividades ON vw_actividades.elemento = sga_mesas_examen.elemento
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_mesas_examen.ubicacion
				JOIN sga_llamados_turno ON sga_llamados_turno.llamado = sga_llamados_mesa.llamado
				JOIN sga_periodos ON sga_periodos.periodo = sga_llamados_turno.periodo
				JOIN sga_turnos_examen ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
				JOIN sga_periodos as periodos_turnos_examen ON periodos_turnos_examen.periodo = sga_turnos_examen.periodo
				            
				WHERE NOT EXISTS (SELECT 1 
				            	FROM sga_actas 
				               WHERE sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa
				                 AND sga_actas.estado <> '" . acta::estado_anulada . "'
				               )
				$where
				$order_by
				";
	
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene un listado de Mesas de Examen sin actas generadas y que tengan alumnos inscriptos según filtro especificado.
	 *
	 */
	function get_listado_mesas_con_inscriptos_sin_actas_gen($where = '')
	{
		$datos = $this->get_listado_reporte_mesas_examen_sin_acta_generada($where);
		$mesas_con_inscriptos = array();
		
		foreach ($datos as $mesa) {
			if ($mesa['cant_inscriptos'] > 0) {
				array_push($mesas_con_inscriptos, $mesa);
			}
		}
		
		return $mesas_con_inscriptos;
	}
	
	/**
	 * Listado de cantidad de inscriptos y resultados de los examenes por cada mesa de examen según filtros especificados.
	 * 
	 */
	function get_listado_reporte_resultados_por_mesa_examen($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT 	
					-- Grupo 1
					sga_ubicaciones.ubicacion,
					sga_ubicaciones.nombre as ubicacion_nombre,
					-- Grupo 2
					vw_actividades.elemento,
					'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_codigo_y_nombre,
				            
					-- Campos que se muestran en el reporte
					sga_mesas_examen.nombre AS mesa_nombre,
					sga_periodos.nombre AS llamado_nombre,
					sga_llamados_mesa.fecha,
					to_char(cast(sga_llamados_mesa.hora_inicio as time), '".comunes::formato_hora_visual."') as hora_inicio,
					(SELECT 		count(1)
								FROM 		vw_insc_examen
								WHERE		vw_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
					) as cant_inscriptos,
					(SELECT 		count(1)
								FROM 		sga_actas_detalle
								WHERE		sga_actas_detalle.id_acta = sga_actas.id_acta
				                			AND sga_actas.estado = 'C'
				                            AND sga_actas_detalle.estado = 'A'
				                            AND sga_actas_detalle.rectificado = 'N'
				                            -- aprobados
				                            AND sga_actas_detalle.resultado = 'A'
					) as cant_aprobados,
					(SELECT 		count(1)
								FROM 		sga_actas_detalle
								WHERE		sga_actas_detalle.id_acta = sga_actas.id_acta
				                			AND sga_actas.estado = 'C'
				                            AND sga_actas_detalle.estado = 'A'
				                            AND sga_actas_detalle.rectificado = 'N'
				                            -- desaprobados
				                            AND sga_actas_detalle.resultado = 'R'
					) as cant_desaprobados,
					(SELECT 		count(1)
								FROM 		sga_actas_detalle
								WHERE		sga_actas_detalle.id_acta = sga_actas.id_acta
				                			AND sga_actas.estado = 'C'
				                            AND sga_actas_detalle.estado = 'A'
				                            AND sga_actas_detalle.rectificado = 'N'
				                            -- ausentes
				                            AND sga_actas_detalle.resultado = 'U'
					) as cant_ausentes
				
				FROM sga_actas
				JOIN sga_llamados_mesa ON sga_llamados_mesa.llamado_mesa = sga_actas.llamado_mesa
				JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				JOIN vw_actividades ON vw_actividades.elemento = sga_mesas_examen.elemento
				JOIN sga_llamados_turno ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
				JOIN sga_periodos ON sga_llamados_turno.periodo = sga_periodos.periodo            
				JOIN sga_turnos_examen ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_mesas_examen.ubicacion
							
				WHERE sga_actas.estado = 'C'	-- Actas cerradas
				$where
				
				ORDER BY 
					sga_ubicaciones.nombre,
					vw_actividades.nombre,
					sga_periodos.nombre,
					sga_mesas_examen.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Listado de Notas de Evaluaciones en Mesa de Examen.
	 * 
	 */
	function get_listado_reporte_notas_eval_en_mesa_examen($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT
				   -- Grupo 1
				   vw_actividades.elemento,
				   '(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_codigo_y_nombre,
				   
				   -- Grupo 2
				   sga_mesas_examen.nombre || ' - ' || llamado.nombre AS mesa_llamado,
				   sga_mesas_examen.nombre AS mesa_nombre,
				   llamado.nombre AS llamado_nombre,
				   
				   -- Campos que se muestran en el reporte
				   vw_personas.apellido_nombres AS apellido_y_nombre,
				   vw_personas.tipo_nro_documento AS identificacion,
				   sga_evaluaciones_tipos.nombre AS tipo_nombre,
				   sga_eval_detalle.nota || ' (' || sga_escalas_notas_det.descripcion || ')' AS nota_descr,
				   sga_escalas_notas_resultado.descripcion as resultado_nombre
				
				FROM sga_llamados_mesa
				JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				JOIN vw_actividades ON vw_actividades.elemento = sga_mesas_examen.elemento 
				JOIN sga_llamados_turno ON sga_llamados_turno.llamado = sga_llamados_mesa.llamado
				JOIN sga_periodos as llamado ON llamado.periodo = sga_llamados_turno.periodo
				JOIN sga_turnos_examen ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen 
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_mesas_examen.ubicacion
				JOIN sga_evaluaciones ON sga_evaluaciones.entidad = sga_llamados_mesa.entidad 
				JOIN sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				JOIN sga_eval_detalle  ON sga_eval_detalle.evaluacion = sga_evaluaciones.evaluacion 
				JOIN sga_alumnos ON sga_alumnos.alumno = sga_eval_detalle.alumno
				JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
				LEFT JOIN sga_escalas_notas_det ON sga_escalas_notas_det.escala_nota = sga_eval_detalle.escala_nota
				                               AND sga_escalas_notas_det.nota = sga_eval_detalle.nota
				LEFT JOIN sga_escalas_notas_resultado ON sga_escalas_notas_resultado.resultado = sga_eval_detalle.resultado
				   
				WHERE sga_evaluaciones_tipos.automatica = '".comunes::no."' -- Generación Manual
				$where
				
				ORDER BY
					vw_actividades.nombre,
				    sga_mesas_examen.nombre,
				    llamado.fecha_inicio,
				    vw_personas.apellido,
				    vw_personas.nombres
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Listado de Total de Inscripciones a Exámenes.
	 *
	 */
	function get_listado_reporte_total_insc_examen($where='')
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		
		$sql = "SELECT 		-- Grupo 1
							sga_ubicaciones.ubicacion,
							sga_ubicaciones.nombre as ubicacion_nombre,
							-- Grupo 2
							turno.anio_academico,
							sga_turnos_examen.turno_examen,
							'Año Académico: ' || turno.anio_academico ||
							' - Turno de Examen: ' || turno.nombre AS anio_y_turno,
							' - Turno de Examen: ' || turno.nombre AS anio_y_turno_desc,
							-- Grupo 3
							vw_actividades.elemento,
							'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_codigo_y_nombre,
				            
				            -- Campos que se muestran en el reporte
				            sga_mesas_examen.nombre AS nombre_mesa,
				            sga_periodos.nombre AS llamado_nombre,
				            sga_llamados_mesa.fecha,
				            to_char(cast(sga_llamados_mesa.hora_inicio as time), '".comunes::formato_hora_visual."') as hora_inicio,
				            (	SELECT 		count(1)
								FROM 		vw_insc_examen
								WHERE		vw_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
				                			AND vw_insc_examen.estado = 'P'
				            ) as insc_pendientes,
				            (	SELECT 		count(1)
								FROM 		vw_insc_examen
								WHERE		vw_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
				                			AND vw_insc_examen.estado = '".inscripcion_examen::estado_aceptada."'
				            ) as insc_aceptadas,
							(	SELECT 		count(1)
								FROM 		vw_insc_examen
								WHERE		vw_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa
				            ) as total_inscripciones  
				                        
				FROM sga_mesas_examen
				JOIN sga_llamados_mesa ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				JOIN vw_actividades ON vw_actividades.elemento = sga_mesas_examen.elemento 
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_mesas_examen.ubicacion
				JOIN sga_llamados_turno ON sga_llamados_turno.llamado = sga_llamados_mesa.llamado
				JOIN sga_periodos ON sga_periodos.periodo = sga_llamados_turno.periodo
				JOIN sga_turnos_examen ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
				JOIN sga_periodos as turno ON turno.periodo = sga_turnos_examen.periodo
				            
				$where
				            
				ORDER BY 	
					sga_ubicaciones.nombre, 
					actividad_codigo_y_nombre, 
					sga_periodos.nombre,
					sga_llamados_mesa.fecha,
					sga_mesas_examen.nombre
				";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene el listado de mesas de examen para el llenado de combos
	 *
	 */
	function get_mesas_examen()
	{
		$sql = "SELECT 		sga_mesas_examen.mesa_examen	AS valor,
							sga_mesas_examen.nombre			AS descr
				FROM 		sga_mesas_examen
				ORDER BY	sga_mesas_examen.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Se recuperan las Mesas de Examen definidas para la Actividad dada, en el Turno de Examen y Ubicación pasados como
	 * parámetro.
	 *
	 * @param integer	$actividad		Id del Elemento para el cual se desean recuperar las Mesas de Examen definidas.
	 * @param integer	$turno_examen	Id del Turno de Exámen para el cual deben estar definidas las Mesas a recuperar.
	 * @param integer 	$ubicacion		Id de la Ubicación asociada a la que deben estar asociadas las Mesas de Examen a recuperar.
	 */
	function get_mesas_actividad_en_turno_ubicacion($actividad, $turno_examen, $ubicacion, $where='')
	{
		$actividad = toba::db()->quote($actividad);
		$turno_examen = toba::db()->quote($turno_examen);
		$ubicacion = toba::db()->quote($ubicacion);
		if ($where) {
			$where = ' AND ' . $where;
		}
		
		$sql = "SELECT 		sga_mesas_examen.mesa_examen,
							sga_mesas_examen.nombre,
							sga_mesas_examen.entidad,
							sga_mesas_examen.observaciones,
							f_instancias_mesa_examen(sga_mesas_examen.mesa_examen) as instancias_nombres,
							sga_mesas_examen.catedra,
							COALESCE(sga_catedras.nombre, 'Sin cátedra asociada') as catedra_nombre,
							sga_mesas_examen.ubicacion,
							sga_ubicaciones.nombre as ubicacion_nombre
				FROM 		sga_mesas_examen
							LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_mesas_examen.catedra,
							sga_ubicaciones
				WHERE		sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion AND
							sga_mesas_examen.mesa_examen IN (SELECT sga_llamados_mesa.mesa_examen
															 FROM	sga_llamados_mesa,
																	sga_llamados_turno
															 WHERE	sga_llamados_mesa.llamado = sga_llamados_turno.llamado AND
																	sga_llamados_turno.turno_examen = $turno_examen) AND							
							sga_mesas_examen.elemento = $actividad AND
							sga_mesas_examen.ubicacion = $ubicacion 
							$where
				ORDER BY	sga_mesas_examen.nombre
				;";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna las mesas de examen definidas en el turno dado.
	 * 
	 * @param integer $turno_examen
	 * @return array
	 */
	function get_mesas_x_turno_examen($turno_examen)
	{
		$turno_examen = toba::db()->quote($turno_examen);
		$sql = "SELECT		DISTINCT sga_mesas_examen.mesa_examen,
							sga_mesas_examen.nombre
				FROM		sga_llamados_turno,
							sga_llamados_mesa,
							sga_mesas_examen
				WHERE		sga_llamados_turno.turno_examen = $turno_examen AND
							sga_llamados_mesa.llamado = sga_llamados_turno.llamado AND
							sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
				ORDER BY	sga_mesas_examen.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Se recuperan las Actividades que correspondan a los Planes de estudio dados y tengan Mesas de Examen en el Turno 
	 * de Exámen dado, con la Ubicación especificada.
	 *
	 * @param array		$planes			Conjunto de Planes correspondientes a las Actividades que se desea recuperar.
	 * @param integer	$turno_examen	Turno para el cual deben estar definidos los Llamados de las Mesas de las Actividades a recuperar.
	 * @param integer 	$ubicacion		Ubicación asociada a las Mesas de Examen definidas para las Actividades a recuperar.
	 * @return array	Conjunto de Actividades que cumplen con las condiciones pedidas.
	 */
	function get_actividades_planes_con_mesas_turno_ubicacion($responsables_academicas, $planes, $turno_examen, $ubicacion)
	{
		$cond_in_ras = guarani_sql::armar_condicion_in($responsables_academicas, 'sga_elementos_ra', 'responsable_academica', 'responsable_academica');
		$condicion_in_ras = "sga_elementos.elemento IN (SELECT sga_elementos_ra.elemento FROM sga_elementos_ra WHERE $cond_in_ras)";

		$cond_in_planes = guarani_sql::armar_condicion_in($planes, 'sga_planes_versiones', 'plan', 'plan');
		$condicion_in_planes	= "sga_elementos_plan.plan_version IN ( SELECT	sga_planes_versiones.plan_version 
																		FROM	sga_planes_versiones 
																		WHERE	sga_planes_versiones.estado IN ('" . planes::estado_activo . "','" . planes::estado_activo_vigente . "') 
																		AND		$cond_in_planes)";
		
		$filtro_elementos = "sga_elementos.estado = '" . elemento::estado_activo . "' 
							AND sga_elementos.disponible_para IN ('" . elemento::solo_para_creacion_mesas_de_examen . "', '" . elemento::para_mesas_de_examen_y_comisiones . "')";

		$turno_examen = toba::db()->quote($turno_examen);
		$ubicacion = toba::db()->quote($ubicacion);

		$sql = "SELECT 		DISTINCT
							sga_elementos.elemento,
							sga_elementos.nombre,	
							sga_elementos.codigo
				FROM 		sga_mesas_examen,
							sga_llamados_mesa,
							sga_llamados_turno,
							sga_elementos_plan,
							sga_elementos_revision,
							sga_elementos
				WHERE		sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen AND
							sga_llamados_mesa.llamado = sga_llamados_turno.llamado AND
							sga_llamados_turno.turno_examen = $turno_examen AND
							sga_elementos_plan.elemento_revision	= sga_elementos_revision.elemento_revision AND
							sga_elementos_revision.elemento			= sga_elementos.elemento AND
							sga_mesas_examen.elemento = sga_elementos.elemento AND
							sga_mesas_examen.ubicacion = $ubicacion AND
							$condicion_in_ras AND
							$condicion_in_planes AND
							$filtro_elementos
				ORDER BY	sga_elementos.codigo, sga_elementos.nombre
				;";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Se recuperan las Actividades de los Planes especificados.
	 *
	 * @param  array	$planes				Conjunto de Planes a cuyos planes versiones activos corresponden las Actividades a las que se desea generarles Mesas.
	 * @param  array	$modalidades 		Conjunto de Modalidades correspondiente a las Actividades a las que se desea generarles Mesas.
	 * @return array						Conjunto de Actividades a retornar que cumplen con las condiciones.
	 */
	function get_actividades_de_planes_versiones($responsables_academicas, $modalidades, $planes, $versiones_no_vigentes)
	{
		$cond_in_ras = guarani_sql::armar_condicion_in($responsables_academicas, 'sga_elementos_ra', 'responsable_academica', 'responsable_academica');
		$cond_in_ras = "sga_elementos.elemento IN (SELECT sga_elementos_ra.elemento FROM sga_elementos_ra WHERE $cond_in_ras)";

		$cond_in_planes = guarani_sql::armar_condicion_in($planes, 'sga_planes_versiones', 'plan', 'plan');
		$cond_versiones_estado = $versiones_no_vigentes ? "sga_planes_versiones.estado IN ('".planes::estado_activo ."', '".planes::estado_activo_vigente."')" : "sga_planes_versiones.estado =  '".planes::estado_activo_vigente."'"; 
		$cond_in_planes	= "sga_elementos_plan.plan_version IN ( SELECT	sga_planes_versiones.plan_version 
																FROM	sga_planes_versiones 
																WHERE	$cond_versiones_estado AND 
																		$cond_in_planes)";

		$cond_in_activ_planes = guarani_sql::armar_condicion_in($planes, 'vw_actividades_plan', 'plan', 'plan');

		$cond_in_modalidades = guarani_sql::armar_condicion_in($modalidades, 'sga_elementos_plan_modalidad', 'modalidad', 'modalidad');
		
		$cond_elementos = "sga_elementos.estado = '" . elemento::estado_activo . "' AND sga_elementos.disponible_para IN ('". elemento::para_mesas_de_examen_y_comisiones . "', '" . elemento::solo_para_creacion_mesas_de_examen . "')";
		
		$sql = "SELECT 	DISTINCT
						sga_elementos.elemento,
						sga_elementos.nombre,
						sga_elementos.codigo,
						sga_elementos_atrib.escala_nota_examen
		
				FROM 	sga_elementos,
						sga_elementos_revision,
						sga_elementos_plan,
						sga_elementos_atrib,
						sga_planes_versiones,
						sga_planes
				
				WHERE	$cond_in_ras
				AND		$cond_in_planes
				AND		$cond_elementos
				AND		sga_planes_versiones.plan_version		= sga_elementos_plan.plan_version
				AND		sga_elementos_plan.elemento_revision	= sga_elementos_revision.elemento_revision
				AND		sga_elementos_revision.elemento			= sga_elementos.elemento
				AND		sga_elementos.elemento					= sga_elementos_atrib.elemento
				AND		sga_elementos_atrib.requiere_examen		= '". comunes::si . "'
				AND		sga_elementos.elemento IN (	
													SELECT	vw_actividades_plan.elemento
													FROM 	vw_actividades_plan,
															sga_elementos_plan_modalidad
													WHERE 	$cond_in_activ_planes
													AND		$cond_in_modalidades
													AND  	vw_actividades_plan.elemento_plan = sga_elementos_plan_modalidad.elemento_plan
												)
				";
		return guarani_db::consultar($sql);
	}
	
	function get_listado_con_cant_actas_sin_anular($where='')
	{
		$select = "(SELECT count(sga_actas.id_acta)
					FROM 		sga_actas
					WHERE		sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa
					AND			sga_actas.estado <> '".acta::estado_anulada."') as cant_actas_sin_anular
					";
		return $this->get_listado($where, $select);
	}

	function get_listado_sin_actas_generadas($where='')
	{
		$where .= ($where != '') ? ' AND ' : '';
		$where .= 'NOT EXISTS ( 
								SELECT 	1 
	            				FROM 	sga_actas 
	                            WHERE 	sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa
	                          )';
		
		return $this->get_listado_con_cant_actas($where);
	}
	
	/**
	 * Obtiene una lista de los nombres de las Mesas existentes, con sus Llamados asociados, según el filtro dado.
	 * Una Mesa de Exámen siempre tiene al menos un Llamado asociado.
	 * @param str $where filtro sobre las mesas o llamados de las mismas a recuperar, y sobre el turno de exámen o 
	 * período asociado a estos llamados.
	 */
	function get_mesas_con_llamados($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT  	sga_mesas_examen.mesa_examen,
							sga_mesas_examen.nombre as mesa_nombre,
							sga_llamados_mesa.llamado_mesa,
							sga_periodos.nombre as llamado_nombre,
							sga_llamados_turno.turno_examen
				FROM		sga_mesas_examen,
							sga_llamados_mesa,
							sga_llamados_turno,
							sga_periodos
				WHERE		sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen AND
							sga_llamados_mesa.llamado = sga_llamados_turno.llamado AND
							sga_llamados_turno.periodo = sga_periodos.periodo
				$where
				ORDER BY	sga_mesas_examen.nombre, sga_periodos.nombre
  				;";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los datos completos relacionados a la Mesa de Exámen dada como parámetro y sus Llamados.
	 *
	 * @param integer $mesa	id de la Mesa de Exámen para la que se desea recuperar los datos.
	 */
	function get_datos_mesa_llamados($llamado_mesa, $select='')
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$datos = $this->get_listado("sga_llamados_mesa.llamado_mesa = $llamado_mesa", $select);
		if (!empty($datos)) {
			return current($datos);
		}
	}
	
	/**
	 * Obtiene todos los datos relacionados a la Mesa de Exámen dada como parámetro.
	 *
	 * @param integer $mesa	id de la Mesa de Exámen para la que se desea recuperar los datos.
	 */
	function get_datos_mesa($mesa)
	{
		$mesa = toba::db()->quote($mesa);
		$sql = "SELECT  vw_mesas_examen.mesa_examen,
						vw_mesas_examen.mesa_examen_nombre as nombre,
						vw_mesas_examen.mesa_examen_elemento as elemento,
						vw_mesas_examen.mesa_examen_entidad as entidad,
						vw_mesas_examen.mesa_examen_catedra as catedra,
						vw_mesas_examen.mesa_examen_ubicacion as ubicacion,
						vw_mesas_examen.anio_academico
				FROM	vw_mesas_examen
				WHERE	vw_mesas_examen.mesa_examen = $mesa
  		";
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Obtiene los datos básicos del llamado mesa dado.
	 *
	 */
	function get_datos_llamado_mesa($llamado_mesa)
	{
		$mesa = toba::db()->quote($llamado_mesa);
		$formato_hora_visual = toba::db()->quote(comunes::formato_hora_visual);
		
		$sql = "SELECT  vw_mesas_examen.anio_academico,	
						vw_mesas_examen.turno_examen,
						vw_mesas_examen.turno_examen_nombre,
						vw_mesas_examen.turno_examen_tipo,
						vw_mesas_examen.mesa_examen,
						vw_mesas_examen.mesa_examen_entidad,
						vw_mesas_examen.mesa_examen_nombre,
						vw_mesas_examen.llamado_mesa,
						vw_mesas_examen.llamado_mesa_entidad,
						vw_mesas_examen.llamado_nombre,
						vw_mesas_examen.mesa_examen_fecha as fecha,
						to_char(vw_mesas_examen.mesa_examen_fecha,'".comunes::formato_fecha_visual."') as fecha_formato_visual,
						vw_mesas_examen.mesa_examen_elemento as actividad,
						sga_elementos.codigo as actividad_codigo,
						get_nombre_actividad_mesa_examen(vw_mesas_examen.mesa_examen, 1) as actividad_nombre,
						'(' || COALESCE(sga_elementos.codigo, 'Sin Código') || ') ' || get_nombre_actividad_mesa_examen(vw_mesas_examen.mesa_examen, 1) as actividad_descripcion,
						vw_mesas_examen.mesa_examen_ubicacion as ubicacion,
						sga_ubicaciones.nombre as ubicacion_nombre,
						vw_mesas_examen.llamado_fecha_inicio,
						vw_mesas_examen.llamado_periodo,
						vw_mesas_examen.llamado,
						vw_mesas_examen.mesa_examen_catedra,
						vw_mesas_examen.mesa_examen_hora_inicio,
						to_char(current_date + vw_mesas_examen.mesa_examen_hora_inicio, $formato_hora_visual) AS mesa_examen_hora_inicio_visual,
						vw_mesas_examen.mesa_examen_hora_finalizacion,
						to_char(current_date + vw_mesas_examen.mesa_examen_hora_finalizacion, $formato_hora_visual) AS mesa_examen_hora_finalizacion_visual
				FROM	vw_mesas_examen,
						sga_ubicaciones,
						sga_elementos
				WHERE	vw_mesas_examen.mesa_examen_ubicacion = sga_ubicaciones.ubicacion AND
						vw_mesas_examen.mesa_examen_elemento = sga_elementos.elemento AND
						vw_mesas_examen.llamado_mesa = $llamado_mesa
  				;";
		
		return guarani_db::consultar_fila($sql);
	}

	// Tipos de cambio que pueden efectuarse en la modificación masiva de mesas de examen.
	function get_tipos_modificacion_masiva_mesas()
	{
		return array(array('valor' => mesa_de_examen::cambio_suspension, 'descr' => mesa_de_examen::cambio_suspension_desc),
					 array('valor' => mesa_de_examen::cambio_fecha, 'descr' => mesa_de_examen::cambio_fecha_desc),
					 array('valor' => mesa_de_examen::cambio_horario, 'descr' => mesa_de_examen::cambio_horario_desc)
					);
	}
	
	/**
	 * Retorna la catedra del llamado mesa dado.
	 *
	 * @param 	integer $llamado_mesa
	 * @return 	boolean
	 */
	function get_catedra_llamado_mesa($llamado_mesa)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$datos = $this->get_datos_llamado_mesa($llamado_mesa);
		return !empty($datos) ? $datos['mesa_examen_catedra'] : null;
	}
	
	/**
	 * Obtiene todos los datos relacionados a la Mesa de Exámen dada como parámetro la entidad del llamado
	 *
	 * @param integer $llamado_mesa_entidad	Entidad del Llamado Mesa para la que se desea recuperar los datos.
	 */
	function get_datos_llamado_mesa_entidad($llamado_mesa_entidad)
	{
		$llamado_mesa_entidad = toba::db()->quote($llamado_mesa_entidad);
		$sql = "SELECT	sga_llamados_mesa.mesa_examen,
						sga_llamados_mesa.llamado_mesa,
						sga_llamados_mesa.fecha
				FROM	sga_llamados_mesa, 
						sga_mesas_examen
				WHERE	sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				AND		sga_llamados_mesa.entidad = $llamado_mesa_entidad
				
  				;";
		
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Obtiene los datos de todas las modalidades asociadas a la Mesa de Exámen dada como parámetro, según el filtro dado
	 *
	 * @param integer	$mesa	id de la Mesa de Exámen para la que se desea recuperar las modalidades.
	 * @param str		$where	filtro sobre las modalidades de la mesa a recuperar.
	 */
	function get_modalidades_mesa($mesa, $where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$mesa = toba::db()->quote($mesa);
		$sql = "SELECT  	sga_mesas_examen_modalidad.mesa_examen,
							sga_mesas_examen_modalidad.modalidad,
							sga_modalidad_cursada.nombre,
							sga_modalidad_cursada.descripcion
				FROM		sga_mesas_examen_modalidad,
							sga_modalidad_cursada
				WHERE		sga_mesas_examen_modalidad.modalidad = sga_modalidad_cursada.modalidad AND
							sga_mesas_examen_modalidad.mesa_examen = $mesa
							$where
				ORDER BY	sga_modalidad_cursada.nombre
  				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los datos de todas las propuestas-planes asociadas a la Mesa de Exámen dada como parámetro, según el 
	 * filtro dado
	 *
	 * @param integer	$mesa	id de la Mesa de Exámen para la que se desea recuperar las propuestas-planes.
	 * @param str		$where	filtro sobre las propuestas-planes de la mesa a recuperar.
	 */
	function get_propuestas_planes_mesa($mesa, $where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$mesa = toba::db()->quote($mesa);
		$sql = "SELECT  	sga_mesas_examen_propuestas.mesa_examen,
							sga_mesas_examen_propuestas.propuesta,
							sga_mesas_examen_propuestas.plan,
							sga_propuestas.nombre_abreviado as propuesta_nombre,
							sga_propuestas.codigo as propuesta_codigo,
							sga_planes.nombre as plan_nombre,
							sga_planes.codigo as plan_codigo
				FROM		sga_mesas_examen_propuestas,
							sga_propuestas,
							sga_planes
				WHERE		sga_mesas_examen_propuestas.propuesta = sga_propuestas.propuesta AND
							sga_mesas_examen_propuestas.plan = sga_planes.plan AND
							sga_planes.propuesta = sga_propuestas.propuesta AND
							sga_mesas_examen_propuestas.mesa_examen = $mesa
							$where
				ORDER BY	sga_planes.codigo, sga_propuestas.nombre
  				;";
		
		return guarani_db::consultar($sql);
	}

	function get_propuestas_mesa_examen($mesa_examen=null)
	{
		$filtro = 'true';
		if (! empty($mesa_examen)) {
			$mesa_examen = toba::db()->quote($mesa_examen);
			$filtro .= " AND sga_mesas_examen_propuestas.mesa_examen = $mesa_examen";
		}
		$sql = "SELECT	DISTINCT sga_mesas_examen_propuestas.propuesta
				FROM	sga_mesas_examen_propuestas
				WHERE	$filtro
		";
		return guarani_db::consultar($sql);
	}
        
        function get_ras_mesa_examen($mesa_examen)
	{
		$sql = "SELECT  DISTINCT sga_propuestas_ra.responsable_academica
                                FROM    sga_mesas_examen_propuestas
                                JOIN    sga_propuestas_ra ON (sga_mesas_examen_propuestas.propuesta = sga_propuestas_ra.propuesta)
                                WHERE sga_mesas_examen_propuestas.mesa_examen = $mesa_examen
		";
		$rs = guarani_db::consultar($sql);
                return array_column($rs, 'responsable_academica');
	}
	
	/**
 	  * Retorna los datos correspondientes a una instancia en formato RecordSet, en caso de que el parámetro sea un 
 	  * array retorna los datos de las instancias correspondientes.
      *	@param integer/array	instancia	id o arreglo de ids de la o las instancias para los cuales se consultan los datos.
    */
	function get_instancias_datos($instancia)
	{
		if (is_array($instancia)) {
			$filtro = guarani_sql::armar_condicion_in($instancia, 'sga_instancias', 'instancia');
			$datos = $this->get_listado_instancias($filtro);
			return $datos;
		} 
		
		$instancia = toba::db()->quote($instancia);
		$datos = $this->get_listado_instancias("sga_instancias.instancia = $instancia");
		if (!empty($datos)) {
			return $datos[0];
		}
	}
	
	function get_listado_instancias($where="")
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		
		$sql = "SELECT 		sga_instancias.instancia,
							sga_instancias.nombre
				FROM 		sga_instancias
							$where
				ORDER BY 	sga_instancias.nombre
				";
	
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los datos de todas las Instancias aplicables a mesas, según el filtro dado. 
	 * 
	 * @param str		$where		filtro sobre las instancias a retornar.
	 */
	function get_instancias_aplicables_a_mesa($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT		sga_instancias.instancia,
							sga_instancias.nombre as instancia_nombre,
							sga_instancias.orden as instancia_orden
				FROM		sga_instancias
				WHERE		sga_instancias.aplica_a = '" . mesa_de_examen::aplica_mesa . "'
							$where
				ORDER BY	sga_instancias.nombre
				;";
		
		return guarani_db::consultar($sql);
	}

	function get_instancias_mesas_examen($where = '')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$sql = "SELECT  	sga_mesas_examen.mesa_examen,
							sga_mesas_examen.nombre as mesa_examen_nombre,
							sga_instancias.instancia,
							sga_instancias.nombre as instancia_nombre,
							sga_instancias.orden as instancia_orden,
							sga_mesas_examen_instancias.escala_nota,
							sga_escalas_notas.nombre as escala_nota_descr
				FROM		sga_mesas_examen,
							sga_mesas_examen_instancias,
							sga_escalas_notas,
							sga_instancias
				WHERE		sga_mesas_examen_instancias.mesa_examen = sga_mesas_examen.mesa_examen
				AND			sga_escalas_notas.escala_nota = sga_mesas_examen_instancias.escala_nota
				AND			sga_instancias.instancia = sga_mesas_examen_instancias.instancia
							$where
				ORDER BY	sga_instancias.nombre
				;";
		return guarani_db::consultar($sql);
	}

	/**
	 *  Retorna la escala de notas de una instancia de la mesa.
	 *
	 */
	function get_escala_nota_instancia($mesa_examen, $instancia)
	{
		$mesa_examen = toba::db()->quote($mesa_examen);
		$instancia = toba::db()->quote($instancia);

		$sql = "SELECT	sga_mesas_examen_instancias.escala_nota
				FROM	sga_mesas_examen_instancias
				WHERE	sga_mesas_examen_instancias.mesa_examen = $mesa_examen
				AND		instancia = $instancia
			    ";

	 return guarani_db::consultar_fila($sql);
	}
	
	/**
	 *  Retorna las escalas de notas de la mesa.de examen
	 *
	 */
	function get_escala_notas_mesa($mesa_examen)
	{
		$mesa_examen = toba::db()->quote($mesa_examen);

		$sql = "SELECT	sga_mesas_examen_instancias.escala_nota
				FROM	sga_mesas_examen_instancias
				WHERE	sga_mesas_examen_instancias.mesa_examen = $mesa_examen
			    ";

	 return guarani_db::consultar($sql);
	}


	function get_instancias_mesa_examen($mesa_examen)
	{
		$mesa_examen = toba::db()->quote($mesa_examen);
		$datos = $this->get_instancias_mesas_examen("sga_mesas_examen.mesa_examen = $mesa_examen");
		return $datos;	
	}
	
	/*
	 * Devuelve los docentes de un llamado mesa
	 */
	function get_docentes_mesa($llamado_mesa, $documento_oficial= null)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		if($documento_oficial){
			$docente_nombre = persona::get_param_nombre_persona_doc_oficiales();
		}else {
			$docente_nombre =' vw_personas.apellido_nombres ';
		}
		
		$sql = "SELECT 
				sga_docentes_mesa_llamado.docente,
				$docente_nombre AS docente_nombre,
				sga_docentes_mesa_llamado.rol,
				sga_tribunal_roles.nombre
			FROM	sga_docentes_mesa_llamado
			JOIN	sga_docentes ON sga_docentes.docente = sga_docentes_mesa_llamado.docente
			JOIN	vw_personas ON vw_personas.persona = sga_docentes.persona
			JOIN	mdp_personas ON  mdp_personas.persona = sga_docentes.persona
			JOIN	sga_tribunal_roles ON sga_tribunal_roles.rol = sga_docentes_mesa_llamado.rol 
				
			WHERE	sga_docentes_mesa_llamado.llamado_mesa = $llamado_mesa
			
			ORDER BY 
				sga_tribunal_roles.orden_acta,
				vw_personas.apellido,
				vw_personas.nombres
				
						;";
		
		return guarani_db::consultar($sql);
	}


	
	/**
	 * Obtiene los datos de todas las Instancias asociadas a la Mesa de Exámen correspondiente al Llamado dado, según el 
	 * filtro dado. 
	 * @param integer	$llamado		id del llamado de la mesa de examen para el cual se traen las instancias asociadas.
	 * @param str		$where			filtro sobre las instancias a retornar.
	 */
	function get_instancias_asociadas_a_mesa_llamado($llamado, $where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}
		$llamado = toba::db()->quote($llamado);
		
		$sql = "SELECT 		sga_mesas_examen_instancias.instancia,
							sga_instancias.nombre,
							sga_mesas_examen_instancias.escala_nota
				FROM 		sga_mesas_examen_instancias,
							sga_instancias,
							sga_llamados_mesa
				WHERE		sga_mesas_examen_instancias.mesa_examen = sga_llamados_mesa.mesa_examen AND
							sga_mesas_examen_instancias.instancia = sga_instancias.instancia AND
							sga_llamados_mesa.llamado_mesa = $llamado
							$where
				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
 	  * Retorna los datos correspondientes a una Instancia en formato RecordSet, en caso de que el parámetro sea un 
 	  * array retorna los datos de las Instancias correspondientes según la condición del array (para la carga masiva).
      *	@param integer/array instancia id o arreglo de ids de la o las instancias para los cuales se consultan los datos.
    */
	function get_datos_instancia($instancia)
	{
		if (is_array($instancia)) {
			$filtro = guarani_sql::armar_condicion_in($instancia, 'sga_instancias', 'instancia');
			$datos = $this->get_instancias_aplicables_a_mesa($filtro);
			return $datos;
		} 
		$instancia = toba::db()->quote($instancia);
		$datos = $this->get_instancias_aplicables_a_mesa("sga_instancias.instancia = $instancia");
		if (!empty($datos)) {
			return current($datos);
		}
	}
	
	/**
	 * Obtiene los datos de todos los Períodos de Inscripción Fechas definidos para el Llamado de Mesa de Exámen dado, 
	 * según los Planes (planes-versiones asociados a los mismos), la Ubicación y las Modalidades dadas como parámetro
	 * (datos asociados a la Mesa a la que corresponde el Llamado).
	 *
	 * @param integer	$llamado_mesa	id del llamado mesa para el cual se quieren recuperar los períodos de inscripción fechas asociados.
	 * @param array		$planes 		arreglo que contiene los planes para los cuales se quieren recuperar los períodos de inscripción definidos. 
	 * @param integer	$ubicacion		id de la ubicación para la cual se quieren recuperar los períodos de inscripción definidos.
	 * @param arrat		$modalidades	arreglo que contiene las modalidades para las cuales se quieren recuperar los períodos de inscripción definidos.
	 */
	function get_datos_per_insc_fechas_para_llamado_mesa($llamado_mesa, $planes=array(), $ubicacion=null, $modalidades=array())
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$where = " WHERE sga_llamados_mesa.llamado_mesa = $llamado_mesa ";

		if (!is_null($ubicacion)) {
			$ubicacion = toba::db()->quote($ubicacion);
			$where .= " AND sga_per_insc_ubicacion.ubicacion = $ubicacion ";
		}
		if (!empty($modalidades)) {
			$where .= ' AND ' . guarani_sql::armar_condicion_in($modalidades, 'sga_per_insc_modalidad', 'modalidad', 'modalidad');
		}
		if (!empty($planes)) {
			$where .= ' AND ' . guarani_sql::armar_condicion_in($planes, 'sga_planes_versiones', 'plan', 'plan');
		}
		/**
		 * Para los períodos de inscripción fechas relacionados a exámenes, pueden estar completados los campos de fecha_fin
		 * y fecha_tope_bajas, o bien los campos dias_previos_fin, hs_previas_fin, dias_previos_tope_bajas y hs_previas_tope_bajas a partir de las cuales se pueden
		 * calcular las fechas correspondientes tomando en cuenta la fecha y horas definidas para el Llamado de la Mesa.
		 */
		$sql = "SELECT		DISTINCT sga_periodos_inscripcion_fechas.periodo_insc,
							sga_periodos_inscripcion_fechas.periodo_inscripcion,
							sga_periodos_inscripcion_fechas.fecha_inicio,
							CASE sga_periodos_inscripcion_fechas.tipo_fin
								WHEN ".turno_de_examen::tope_fecha." THEN sga_periodos_inscripcion_fechas.fecha_fin 
								WHEN ".turno_de_examen::tope_dias_antes." THEN get_fecha_fin_examen(CAST(sga_periodos_inscripcion_fechas.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', sga_periodos_inscripcion_fechas.dias_previos_fin, false, sga_mesas_examen.ubicacion)
								WHEN ".turno_de_examen::tope_dias_antes_habiles." THEN get_fecha_fin_examen(CAST(sga_periodos_inscripcion_fechas.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', sga_periodos_inscripcion_fechas.dias_previos_fin, true, sga_mesas_examen.ubicacion)
								WHEN ".turno_de_examen::tope_hs_antes." THEN get_fecha_fin_examen(CAST(sga_periodos_inscripcion_fechas.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', sga_periodos_inscripcion_fechas.hs_previas_fin, false, sga_mesas_examen.ubicacion)
								WHEN ".turno_de_examen::tope_hs_antes_habiles." THEN get_fecha_fin_examen(CAST(sga_periodos_inscripcion_fechas.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', sga_periodos_inscripcion_fechas.hs_previas_fin, true, sga_mesas_examen.ubicacion)
								ELSE CAST(NULL as timestamp)
							END as fecha_fin,
							CASE sga_periodos_inscripcion_fechas.tipo_tope_bajas
								WHEN ".turno_de_examen::tope_fecha." THEN sga_periodos_inscripcion_fechas.fecha_tope_bajas
								WHEN ".turno_de_examen::tope_dias_antes." THEN get_fecha_fin_examen(CAST(sga_periodos_inscripcion_fechas.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', sga_periodos_inscripcion_fechas.dias_previos_tope_bajas, false, sga_mesas_examen.ubicacion)
								WHEN ".turno_de_examen::tope_dias_antes_habiles." THEN get_fecha_fin_examen(CAST(sga_periodos_inscripcion_fechas.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', sga_periodos_inscripcion_fechas.dias_previos_tope_bajas, true, sga_mesas_examen.ubicacion)
								WHEN ".turno_de_examen::tope_hs_antes." THEN get_fecha_fin_examen(CAST(sga_periodos_inscripcion_fechas.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', sga_periodos_inscripcion_fechas.hs_previas_tope_bajas, false, sga_mesas_examen.ubicacion)
								WHEN ".turno_de_examen::tope_hs_antes_habiles." THEN get_fecha_fin_examen(CAST(sga_periodos_inscripcion_fechas.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', sga_periodos_inscripcion_fechas.hs_previas_tope_bajas, true, sga_mesas_examen.ubicacion)
								ELSE CAST(NULL as timestamp)
							END as fecha_tope_bajas,
							sga_periodos.nombre,
							sga_periodos.anio_academico
				FROM		sga_periodos_inscripcion_aplanado
				JOIN		sga_planes_versiones ON sga_periodos_inscripcion_aplanado.plan_version = sga_planes_versiones.plan_version
				JOIN		sga_periodos_inscripcion_fechas ON sga_periodos_inscripcion_aplanado.periodo_insc = sga_periodos_inscripcion_fechas.periodo_insc
				JOIN		sga_periodos_inscripcion ON sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion
				JOIN		sga_periodos ON sga_periodos_inscripcion.periodo = sga_periodos.periodo
				JOIN		sga_llamados_turno ON sga_periodos.periodo = sga_llamados_turno.periodo
				JOIN		sga_llamados_mesa ON sga_llamados_turno.llamado = sga_llamados_mesa.llamado
				JOIN		sga_mesas_examen ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
				JOIN		sga_per_insc_modalidad ON sga_per_insc_modalidad.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion
				JOIN		sga_per_insc_ubicacion ON sga_per_insc_ubicacion.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion
				$where
				ORDER BY	sga_periodos.anio_academico, sga_periodos.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_fecha_fin_examen($llamado_mesa, $fecha_inicio_excep, $fecha_fin_excep, $tipo, $dias_previos, $hs_previas)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$fecha_inicio_excep = toba::db()->quote($fecha_inicio_excep);
		$fecha_fin_excep = empty($fecha_fin_excep) ? 'CAST(NULL as timestamp)' : toba::db()->quote($fecha_fin_excep);
		$tipo = toba::db()->quote($tipo);
		$dias_previos = empty($dias_previos) ? 0 : toba::db()->quote($dias_previos);
		$hs_previas = empty($hs_previas) ? 0 : toba::db()->quote($hs_previas);
		$sql = "SELECT	CASE $tipo
							WHEN '".turno_de_examen::tope_fecha."' THEN $fecha_fin_excep
							WHEN '".turno_de_examen::tope_dias_antes."' THEN get_fecha_fin_examen(CAST($fecha_inicio_excep as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', $dias_previos, false, sga_mesas_examen.ubicacion)
							WHEN '".turno_de_examen::tope_dias_antes_habiles."' THEN get_fecha_fin_examen(CAST($fecha_inicio_excep as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', $dias_previos, true, sga_mesas_examen.ubicacion)
							WHEN '".turno_de_examen::tope_hs_antes."' THEN get_fecha_fin_examen(CAST($fecha_inicio_excep as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', $hs_previas, false, sga_mesas_examen.ubicacion)
							WHEN '".turno_de_examen::tope_hs_antes_habiles."' THEN get_fecha_fin_examen(CAST($fecha_inicio_excep as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', $hs_previas, true, sga_mesas_examen.ubicacion)
							ELSE CAST(NULL as timestamp)
						END as fecha_fin
				FROM	sga_llamados_mesa
				JOIN	sga_mesas_examen ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
				WHERE	sga_llamados_mesa.llamado_mesa = $llamado_mesa
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['fecha_fin'];
	}

	/**
 	  * Valida si un nombre de Mesa de Exámen se puede usar o no para una Actividad y un Turno de Exámen dados.
      *	@param string  nombre	 Nombre de Mesa que se intenta validar.
      * @param integer act		 Id de la Actividad a la cual está asociada la Mesa.
      * @param integer turno	 Id del Turno de Exámen para el cual se definen los Llamados de la Mesa.
	  * @param integer ubicacion Id de la Ubicación para la cual se definen los Llamados de la Mesa.
      * @param integer mesa		 Id de una Mesa a excluir en la comparación si es distinto de nulo.
    */
	function validar_nombre_mesa($nombre, $act, $turno, $ubicacion, $mesa=null)
	{
		if (is_null($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		$act = toba::db()->quote($act);
		$turno = toba::db()->quote($turno);
		$ubicacion = toba::db()->quote($ubicacion);
		$sql = "SELECT 	count(sga_mesas_examen.mesa_examen) as cant 	
                FROM 	sga_mesas_examen,
                		sga_llamados_mesa,
                		sga_llamados_turno
				WHERE 	sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen AND
						sga_llamados_mesa.llamado = sga_llamados_turno.llamado AND
						sga_mesas_examen.elemento = $act AND
						sga_mesas_examen.ubicacion = $ubicacion AND
						sga_llamados_turno.turno_examen = $turno AND
						".guarani_sql::armar_condicion_compara_cadenas('sga_mesas_examen.nombre', $nombre)."
				";
		if (!is_null($mesa)) {
			$mesa = toba::db()->quote($mesa);
			$sql .= " AND sga_mesas_examen.mesa_examen <> $mesa ;";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Obtiene las mesas disponibles para incribirse a examen para un alumno y una actividad dada.
	 */
	function get_mesas_disponibles_para_inscripcion($param_exa_mostrar_mesas_todas_modalidades, $param_exa_rinde_en_cualquier_ubicacion, $param_exa_multiples_actas_examen, $param_cur_division_estricta, 
													$alumno, $elemento, $propuesta, $plan, $plan_version, $ubicacion, $inicial_apellido, $modalidad, $fuera_de_termino, $tipo_plan)
	{
	
		$filtro_division = '';
		if ($param_cur_division_estricta == comunes::si ) {
			$datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno));
			$division = $datos_alumno['division'];
			if(isset($division)) {
				$filtro_division = " AND (sga_mesas_examen.division = $division OR sga_mesas_examen.division IS NULL ) ";
			} 
		}
		
		$en_periodo_inscripcion = (!$fuera_de_termino) ? 'true' : 'false';
		
		$alumno = toba::db()->quote($alumno);
		$elemento = toba::db()->quote($elemento);
		$propuesta = toba::db()->quote($propuesta);
		$plan = toba::db()->quote($plan);
		$plan_version = toba::db()->quote($plan_version);
		$ubicacion = toba::db()->quote($ubicacion);
		$inicial_apellido = toba::db()->quote($inicial_apellido);
		$modalidad = toba::db()->quote($modalidad);
		$interfaz = comunes::interfaz_gestion;

		$filtro_modalidad = '';
		// si exa_mostrar_mesas_todas_modalidades = N
		if ($param_exa_mostrar_mesas_todas_modalidades == comunes::no) {
			$filtro_modalidad = "AND $modalidad IN (SELECT	modalidad
													FROM	sga_mesas_examen_modalidad
													WHERE	sga_mesas_examen_modalidad.mesa_examen = sga_mesas_examen.mesa_examen)
								";
		}

		//SI exa_rinde_en_cualquier_ubicacion = N
		if ($param_exa_rinde_en_cualquier_ubicacion == comunes::si) {
			$ubicacion = 'null';
		}

		$sin_acta_generada = ($param_exa_multiples_actas_examen == comunes::si) ? 'false' : 'true';

		// --------------------------------------------------------------------------------------------------------
		// Para actividades regularizadas en planes anteriores que no se encuentran en el plan actual del alumno. 
		// Regularidades Vigentes para instancia Regular y No vigentes para Reválidas de Cursadas
		// --------------------------------------------------------------------------------------------------------
		$filtro_mesas_regulares = '';
		if ($tipo_plan == 'PLAN_ANTERIOR') { 
			$filtro_mesas_regulares = " AND EXISTS (SELECT 1 FROM sga_mesas_examen_instancias 
			                                         WHERE sga_mesas_examen_instancias.mesa_examen = sga_mesas_examen.mesa_examen 
													   AND sga_mesas_examen_instancias.instancia IN (3,5))";
		} 

		$sql = "SELECT	sga_mesas_examen.mesa_examen,
						sga_mesas_examen.nombre as mesa_nombre,
						sga_llamados_mesa.entidad,
						sga_llamados_mesa.llamado_mesa,
						sga_periodos_llamado.nombre as llamado_nombre,
						sga_llamados_mesa.fecha,
						to_char(sga_llamados_mesa.fecha,'".comunes::formato_fecha_visual."') || ' ' || to_char(cast(sga_llamados_mesa.hora_inicio as time), '".comunes::formato_hora_visual."') as fecha_visual,
						sga_llamados_mesa.cupo as cupo,
						COALESCE(sga_catedras.nombre, ' ') as catedra_nombre,
						sga_mesas_examen.ubicacion,
						sga_ubicaciones.nombre as ubicacion_nombre,
						f_modalidad_cursada_examen(sga_mesas_examen.mesa_examen) as modalidad_nombre,
						sga_periodos_llamado.anio_academico,
						sga_llamados_turno.turno_examen,
						sga_periodos_llamado.anio_academico || ' - ' || periodos_turnos_examen.nombre as turno_examen_nombre,
						(SELECT count(*) FROM sga_docentes_mesa_llamado WHERE sga_docentes_mesa_llamado.llamado_mesa = sga_llamados_mesa.llamado_mesa) as docentes_cant
						
				FROM sga_mesas_examen							
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_mesas_examen.ubicacion
				JOIN sga_llamados_mesa ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
				JOIN sga_llamados_turno ON sga_llamados_turno.llamado = sga_llamados_mesa.llamado
				JOIN sga_periodos as sga_periodos_llamado ON sga_periodos_llamado.periodo = sga_llamados_turno.periodo
				JOIN sga_turnos_examen ON sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
				JOIN sga_periodos as periodos_turnos_examen ON periodos_turnos_examen.periodo = sga_turnos_examen.periodo
				LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_mesas_examen.catedra

				WHERE sga_mesas_examen.elemento = $elemento 
				  AND sga_llamados_mesa.fecha >= ".guarani_sql::fecha_actual."
				  AND sga_llamados_mesa.llamado_mesa IN (SELECT * FROM f_mesas_vigentes_una_actividad($elemento, $propuesta, $plan, $plan_version, $en_periodo_inscripcion, $sin_acta_generada, $ubicacion, $interfaz, $alumno))
				  $filtro_modalidad
				  $filtro_mesas_regulares
				  $filtro_division
					  
				ORDER BY	
						sga_llamados_mesa.fecha,
						sga_llamados_mesa.hora_inicio
  				;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Determina si la fecha ingresada en la carga de nota de exámen para un Alumno es válida, es decir si es mayor
	 * o igual a la determinada para el llamado mesa correspondiente y menor o igual a la fecha actual.
	 *
	 * @param date		$fecha_nota		fecha ingresada en la carga de nota del alumno.
	 * @param integer	$llamado_mesa	llamado mesa para el cual se están cargando notas de exámen.
	 */
	function es_fecha_valida_de_carga_nota_examen($fecha_nota, $llamado_mesa)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		if (!empty($fecha_nota)) {
			$fecha_nota = toba::db()->quote($fecha_nota);
			$sql = "SELECT	(DATE $fecha_nota BETWEEN sga_llamados_mesa.fecha AND " . guarani_sql::fecha_actual . ") AS es_valida
					FROM	sga_llamados_mesa
					WHERE	sga_llamados_mesa.llamado_mesa = $llamado_mesa
					;";
			$rs = guarani_db::consultar_fila($sql);
			return $rs['es_valida'];
		}
	}
	
	/*
	 * Obtiene la evaluación automatica de una mesa de examen dada su entidad
	 */
	function get_evaluacion_automatica_mesa($entidad)
	{
		$entidad = toba::db()->quote($entidad);
		
		$sql = "SELECT  sga_evaluaciones.evaluacion
				FROM	sga_evaluaciones,
						sga_evaluaciones_tipos
				WHERE	sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo AND
						sga_evaluaciones.entidad = $entidad AND
						sga_evaluaciones_tipos.automatica = '".comunes::si."'
		";
		
		$datos = guarani_db::consultar($sql);
		if (!empty($datos)) {
			return $datos[0];
		}
	}
	
	/*
	 * Obtiene los alumnos inscriptos en un llamado mesa dado
	 */
	function get_alumnnos_llamado_mesa($llamado_mesa)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		
		$sql = "SELECT  sga_insc_examen.inscripcion,
						sga_insc_examen.alumno,
						sga_insc_examen.llamado_mesa,
						sga_insc_examen.instancia
				FROM	sga_insc_examen
				WHERE	sga_insc_examen.llamado_mesa = $llamado_mesa
  				;";
		
		return guarani_db::consultar($sql);
	}
	
	/*
	 * Obtiene un string con las instancias de una mesa de examen dada
	 */
	function get_string_instancias_mesa_examen($mesa_examen)
	{
		$mesa_examen = toba::db()->quote($mesa_examen);
		
		$sql = "SELECT	f_instancias_mesa_examen
				FROM	f_instancias_mesa_examen($mesa_examen)
  				";
		
		return current(guarani_db::consultar($sql));
	}
	
	/**
	 * Determina si existe alguna mesa para una dada cátedra y (opcionalmente) actividad.
	 *
	 */
	function existe_mesa_asociada_catedra($catedra, $elemento='')
	{
		$catedra = toba::db()->quote($catedra);
		$where = '';
		
		if ($elemento) {
			$elemento = toba::db()->quote($elemento);
			$where = " AND sga_mesas_examen.elemento = $elemento";
		}
		
		$sql = "SELECT 
					sga_mesas_examen.mesa_examen
				FROM 
					sga_mesas_examen
				WHERE
					sga_mesas_examen.catedra = $catedra
					$where
				";
		
		$res = guarani_db::consultar($sql);
		
		return guarani_arreglos::count($res) > 0;
	}

	/**
	 * Retorna el código del llamado anterior a un llamado-mesa dado (por fecha de inicio del período asociado).
	 * Turnos de Examen Normal y llamados donde existen mesas para la actividad del llamado-mesa pasado por parametro.
	 * @param integer $llamado_mesa
	 * @param integer $tipo_turno  Tipo de turno de examen en donde se buscarán los examenes anteriores del alumno
	 * @param integer $propuesta
	 * @param boolean $misma_actividad
	 * @return integer
	 */
	function get_llamado_anterior($llamado_mesa, $tipo_turno, $propuesta, $misma_actividad = true)
	{
		$where = '';
		if ($misma_actividad) {
			$where = ' AND mesa.elemento = mesa_examen.elemento  -- Misma Actividad';
		}		
		// Variables a usar en la query.
		$periodo_generico_llamado = periodo_generico::tipo_llamado;
		$estado_activo            = toba::db()->quote(comunes::estado_activo);

		$sql = "
				SELECT 
					llamados_turno.llamado as llamado
				FROM 
					sga_llamados_mesa as llamado_mesa_examen
				JOIN sga_mesas_examen as mesa_examen ON mesa_examen.mesa_examen = llamado_mesa_examen.mesa_examen
				JOIN sga_llamados_turno as sga_llamados_turno ON sga_llamados_turno.llamado = llamado_mesa_examen.llamado
				JOIN sga_periodos as periodo_llamado_examen ON periodo_llamado_examen.periodo = sga_llamados_turno.periodo
				JOIN sga_periodos as p ON true
				JOIN sga_periodos_genericos as periodos_genericos ON periodos_genericos.periodo_generico = p.periodo_generico

				JOIN sga_llamados_turno as llamados_turno ON llamados_turno.periodo = p.periodo
				JOIN sga_llamados_mesa as llamado_mesa ON llamado_mesa.llamado = llamados_turno.llamado
				JOIN sga_mesas_examen as mesa ON mesa.mesa_examen = llamado_mesa.mesa_examen
				JOIN sga_turnos_examen as turno_examen ON turno_examen.turno_examen = llamados_turno.turno_examen
				JOIN sga_mesas_examen_propuestas ON sga_mesas_examen_propuestas.mesa_examen = mesa.mesa_examen
				WHERE 
					llamado_mesa_examen.llamado_mesa = $llamado_mesa
					AND p.fecha_inicio < periodo_llamado_examen.fecha_inicio
					AND p.periodo <> periodo_llamado_examen.periodo
					AND periodos_genericos.periodo_generico_tipo = $periodo_generico_llamado     -- Llamados
					AND llamado_mesa.estado     = $estado_activo             -- Activo					
					AND turno_examen.turno_tipo = $tipo_turno
					AND sga_mesas_examen_propuestas.propuesta = $propuesta --Mesa de la propueta
					$where	
				ORDER BY 
					p.fecha_inicio DESC
				LIMIT 	1
		";

		$rs = guarani_db::consultar_fila($sql, null, false);
		return $rs['llamado'];
	}

	/**
	 * Retorna el código del turno de examen del tipo pasado por parámetro inmediato anterior a un turno de examen que tengan mesas de examen para la misma propuesta y actividad (opcional)
	 * Busca el turno de examen con fecha de inicio anterior a la fecha de inicio del turno que se pasa por parametro y con fecha mas reciente
	 *
	 * @param integer $turno_examen
	 * @param integer $tipo_turno
	 * @param integer $propuesta Con este parámetro se recupera/n la/s responsable/s academica/s de la propuesta 
	 * @param integer $elemento
	 * @param boolean $misma_actividad
	 * @return integer
	 */
	function get_turno_examen_anterior($turno_examen, $tipo_turno, $propuesta, $elemento, $misma_actividad = false)
	{
		// Obtengo datos del turno de examen pasado por parametro.
		$datos_turno_examen = toba::consulta_php('co_turnos_de_examen')->get_datos_turno($turno_examen);
		$periodo_fecha_inicio = toba::db()->quote($datos_turno_examen['fecha_inicio']);
		$periodo = toba::db()->quote($datos_turno_examen['periodo']);
		$estado_activo = toba::db()->quote(comunes::estado_activo);  /* Estado de las mesas de examen en los llamados. */

		$where = '';
		if ($misma_actividad) {
			// Que el turno de examen tenga mesas de la misma actividad.
			$where = " AND sga_mesas_examen.elemento = $elemento ";
		}		
		
		$sql = "SELECT sga_turnos_examen.turno_examen
				  FROM sga_turnos_examen
				  JOIN sga_periodos as periodo_turno ON periodo_turno.periodo = sga_turnos_examen.periodo
				  JOIN sga_llamados_turno ON sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
				  JOIN sga_llamados_mesa ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
				  JOIN sga_mesas_examen ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
				  JOIN sga_mesas_examen_propuestas ON (sga_mesas_examen_propuestas.mesa_examen = sga_llamados_mesa.mesa_examen AND sga_mesas_examen_propuestas.propuesta = $propuesta )
					   
				WHERE sga_turnos_examen.turno_tipo = $tipo_turno
				  AND periodo_turno.fecha_inicio < $periodo_fecha_inicio 
				  AND sga_turnos_examen.turno_examen <> $turno_examen
				  AND sga_llamados_mesa.estado = $estado_activo
				  $where
				ORDER BY periodo_turno.fecha_inicio DESC									 
				LIMIT 1									 
			";

		$rs = guarani_db::consultar_fila($sql);
		return $rs['turno_examen'];
	}

	//-----------------------------------------------------------------------------------
	//---- opciones de autocompletado - ingreso de notas --------------------------------
	//-----------------------------------------------------------------------------------

	function get_opciones_combo_completar()
	{
		return array(array('valor' => mesa_de_examen::autocompletar_campo_nota, 'descr' => 'Nota'),
                     array('valor' => mesa_de_examen::autocompletar_campo_obs, 'descr' => 'Observaciones'),
                     array('valor' => mesa_de_examen::autocompletar_campo_fecha, 'descr' => 'Fecha'),
					 array('valor' => mesa_de_examen::autocompletar_result_ausente, 'descr' => 'Resultado Ausente'));
	}
	
	function get_opciones_combo_para()
	{
		return array(array('valor' => mesa_de_examen::autocompletar_para_todos, 'descr' => 'Todos los alumnos de la página'),
                     array('valor' => mesa_de_examen::autocompletar_para_sin_datos, 'descr' => 'Alumnos de la página sin datos'));
	}
	
	//-----------------------------------------------------------------------------------
	//---- opciones para el criterio de copiado masivo de mesas -------------------------
	//-----------------------------------------------------------------------------------

	function get_opciones_combo_criterio_copiado_mesa()
	{
			return array(array('valor' => mesa_de_examen::dia_semana,		'descr' => 'Respetar día de la semana'),
						 array('valor' => mesa_de_examen::orden_dia,		'descr' => 'Respetar orden del día en el Llamado'),
						 array('valor' => mesa_de_examen::dia_x_catedra ,	'descr' => 'Respetar día por Cátedra'),
						 array('valor' => mesa_de_examen::dia_x_actividad ,	'descr' => 'Respetar día por Actividad'));
	}
	
	function get_opciones_radio_copiado_mesas_destino()
	{
			return array(array('valor' => comunes::si,	'descr' => 'Todas'),
						 array('valor' => comunes::no,	'descr' => 'Sin mesa generada en destino'));
	}

	function get_opciones_combo_criterio_dia_no_habil()
	{
			return array(	array('valor' => mesa_de_examen::crear_la_mesa_ese_dia,				'descr' => 'Crear la mesa'),
							array('valor' => mesa_de_examen::no_crear_la_mesa,					'descr' => 'No crear la mesa'),
						 	array('valor' => mesa_de_examen::crear_la_mesa_el_proximo_habil ,	'descr' => 'Crear la mesa el próximo día hábil'),
						);
	}
	
	//-----------------------------------------------------------------------------------
	//---- opciones para los parámetros de la generación masiva de mesas ----------------
	//-----------------------------------------------------------------------------------

	function get_opciones_mesa_por_catedra()
	{
		return array(	array('valor' => mesa_de_examen::no_considerar_catedras, 'descr' =>  mesa_de_examen::no_considerar_catedras_desc),
						array('valor' => mesa_de_examen::una_mesa_por_catedra, 'descr' => mesa_de_examen::una_mesa_por_catedra_desc),
						array('valor' => mesa_de_examen::una_mesa_por_catedra_si_existe, 'descr' => mesa_de_examen::una_mesa_por_catedra_si_existe_desc)
		);
	}

	function get_opciones_criterio_generacion()
	{
		return array(	array('valor' => mesa_de_examen::no_generar_mesa_si_existe, 'descr' =>  mesa_de_examen::no_generar_mesa_si_existe_desc),
						array('valor' => mesa_de_examen::generar_mesa_siempre, 'descr' => mesa_de_examen::generar_mesa_siempre_desc)
		);
	}

	function get_opciones_fecha_hora_mesa($mesa_por_catedra)
	{
		if ($mesa_por_catedra != mesa_de_examen::no_considerar_catedras) {
			return array(mesa_de_examen::fecha_inicio_llamado => mesa_de_examen::fecha_inicio_llamado_desc,
						 mesa_de_examen::fecha_definida_actividad => mesa_de_examen::fecha_definida_actividad_desc,
						 mesa_de_examen::fecha_definida_catedra => mesa_de_examen::fecha_definida_catedra_desc);
		} else {
			return array(mesa_de_examen::fecha_inicio_llamado => mesa_de_examen::fecha_inicio_llamado_desc,
						 mesa_de_examen::fecha_definida_actividad => mesa_de_examen::fecha_definida_actividad_desc);
		}
	}
	
	function get_opciones_docentes($mesa_por_catedra)
	{
		if ($mesa_por_catedra != mesa_de_examen::no_considerar_catedras) {
			return array(mesa_de_examen::docentes_no_asignar => mesa_de_examen::docentes_no_asignar_desc,
						 mesa_de_examen::docentes_tribunal => mesa_de_examen::docentes_tribunal_desc,
						 mesa_de_examen::docentes_catedra => mesa_de_examen::docentes_catedra_desc);
		} else {
			return array(mesa_de_examen::docentes_no_asignar => mesa_de_examen::docentes_no_asignar_desc,
						 mesa_de_examen::docentes_tribunal => mesa_de_examen::docentes_tribunal_desc);
		}
	}
	
	/*
	 * Obtener las propuestas que se relacionan con planes personalizados activos o vigentes
	 * que tienen asociada la actividad a la que se vincula la propuesta
	 * y además que la actividad del plan personalizado no esté aprobada por el alumno que lo cursa.
	 */
	function get_propuestas_a_vincular($actividad)
	{
		$sql = "SELECT DISTINCT
						vw_elementos_plan.propuesta,
						vw_elementos_plan.plan
				FROM 	vw_elementos_plan,
						sga_planes_versiones,
						sga_planes,
						sga_elementos_plan
				WHERE 	vw_elementos_plan.elemento 				= $actividad
						AND vw_elementos_plan.plan_version		= sga_planes_versiones.plan_version
						AND sga_planes.plan 					= sga_planes_versiones.plan
						AND	vw_elementos_plan.elemento_revision = sga_elementos_plan.elemento_revision
						AND sga_planes.tipo_plan				IN ('".planes::tipo_personalizado."','".planes::tipo_convenio."')
						AND	sga_planes.estado					IN ('".planes::estado_vigente."','".planes::estado_activo."')	
						AND sga_elementos_plan.requiere_examen = '".comunes::si."'
						AND NOT EXISTS (SELECT 	1
										FROM 	vw_hist_academica
										WHERE 	vw_hist_academica.elemento 			= vw_elementos_plan.elemento
												AND vw_hist_academica.plan_version 	= vw_elementos_plan.plan_version
												AND vw_hist_academica.resultado 	= '".escalas_notas::resultado_aprobado."'
										)
		";
		return guarani::db()->consultar($sql);
	}
    
   function get_hay_propuestas_vinculadas_instancia_libre($actividad, $filtro)
	{
	   $actividad = toba::db()->quote($actividad);
	   if ($filtro) {
		   $filtro = ' AND ' . $filtro;
	   }
	   
       $sql = " SELECT  COUNT(*) as cant
                FROM 	sga_elementos_plan,
                        vw_elementos_plan
                WHERE
                        vw_elementos_plan.elemento                  = $actividad
                        AND sga_elementos_plan.elemento_revision    = vw_elementos_plan.elemento_revision
                        AND sga_elementos_plan.requiere_examen      = '".comunes::si."'
                        AND sga_elementos_plan.permite_rendir_libre = '".comunes::si."'
                        AND sga_elementos_plan.plan_version         = vw_elementos_plan.plan_version
                        AND vw_elementos_plan.plan_version_estado   IN ('".planes::estado_vigente."','".planes::estado_activo."')
                        $filtro
                ";
		$datos = guarani::db()->consultar_fila($sql);
        
        if ($datos['cant'] > 0){
            return true;
        } else {
            return false;
        }
	}

	function get_personas_a_notificar_modificacion_mesa_examen($llamado_mesa)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);

		$sql = "SELECT	mdp_personas.persona,
						COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) AS apellido,
						COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) AS nombres,
						'alumno' AS perfil
				FROM	sga_insc_examen
						JOIN sga_alumnos ON (sga_insc_examen.alumno = sga_alumnos.alumno)
						JOIN mdp_personas ON (sga_alumnos.persona = mdp_personas.persona)
				WHERE	llamado_mesa = $llamado_mesa
				
				UNION
				
				SELECT	mdp_personas.persona,
						COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) AS apellido,
						COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) AS nombres,
						'docente' AS perfil
				FROM	sga_docentes_mesa_llamado
						JOIN sga_docentes ON (sga_docentes_mesa_llamado.docente = sga_docentes.docente)
						JOIN mdp_personas ON (sga_docentes.persona = mdp_personas.persona)
				WHERE	llamado_mesa = $llamado_mesa
				ORDER BY perfil";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna las plataformas de educación virtual relacionadas a un llamado-mesa
	 * por las responsables académicas de la actividad a la que corresponde.
	 * 
	 * @param int $llamado_mesa
	 * @return array
	 */
	function get_plataformas_virtuales_llamado_mesa($llamado_mesa)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$sql = "SELECT		int_pv_plataformas.plataforma,
							int_pv_plataformas.nombre,
							int_pv_plataformas.url,
							int_pv_plataformas.host,
							int_pv_plataformas.token,
							int_pv_plataformas.rol_alumno,
							int_pv_plataformas.rol_alumno_curso,
							int_pv_plataformas.categoria_curso_default
				FROM		sga_llamados_mesa
							JOIN sga_mesas_examen ON sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
							JOIN sga_elementos ON sga_mesas_examen.elemento = sga_elementos.elemento
							JOIN sga_elementos_ra ON sga_elementos.elemento = sga_elementos_ra.elemento
							JOIN int_pv_plataformas_ra ON sga_elementos_ra.responsable_academica = int_pv_plataformas_ra.responsable_academica
							JOIN int_pv_plataformas ON int_pv_plataformas_ra.plataforma = int_pv_plataformas.plataforma
				WHERE		sga_llamados_mesa.llamado_mesa = $llamado_mesa AND
							int_pv_plataformas.activa = TRUE
				ORDER BY	int_pv_plataformas.plataforma
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos del curso virtual asociado al llamado-mesa dado.
	 * 
	 * @param int $llamado_mesa
	 * @return array
	 */
	function get_curso_virtual_llamado_mesa($llamado_mesa)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$no = toba::db()->quote(comunes::no);
                
		$sql = "SELECT	int_pv_cursos.curso,
						int_pv_cursos.id_curso_externo,
						int_pv_cursos.nombre,
						int_pv_plataformas.plataforma,
						int_pv_plataformas.nombre as plataforma_nombre,
						int_pv_plataformas.url as plataforma_url,
						int_pv_plataformas.host as plataforma_host,
						int_pv_plataformas.token as plataforma_token,
						int_pv_plataformas.rol_alumno as plataforma_rol_alumno,
						int_pv_plataformas.rol_alumno_curso	AS plataforma_rol_alumno_curso,
						$no                                     AS grupo,
						int_pv_plataformas.categoria_curso_default as plataforma_categoria_curso_default
				FROM	int_pv_cursos
						JOIN int_pv_cursos_llamados_mesa ON int_pv_cursos.curso = int_pv_cursos_llamados_mesa.curso
						JOIN int_pv_plataformas ON int_pv_cursos.plataforma = int_pv_plataformas.plataforma
				WHERE	int_pv_cursos_llamados_mesa.llamado_mesa = $llamado_mesa
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna el listado de docentes de un llamado-mesa con el usuario y rol de Moodle correspondientes.
	 * 
	 * @param int $llamado_mesa
	 * @param int $plataforma
	 * @param string $where
	 * @return array
	 */
	function get_docentes_llamado_mesa_moodle($llamado_mesa, $plataforma=null, $where='')
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		if (is_null($plataforma)) {
			$from = '	LEFT JOIN int_pv_cursos_llamados_mesa ON sga_docentes_mesa_llamado.llamado_mesa = int_pv_cursos_llamados_mesa.llamado_mesa
						LEFT JOIN int_pv_cursos ON int_pv_cursos_llamados_mesa.curso = int_pv_cursos.curso
						LEFT JOIN int_pv_plataformas ON int_pv_cursos.plataforma = int_pv_plataformas.plataforma';
		} else {
			$plataforma = toba::db()->quote($plataforma);
			$from = "JOIN int_pv_plataformas ON int_pv_plataformas.plataforma = $plataforma";
		}
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		DISTINCT vw_personas.persona,
							vw_personas.apellido,
							vw_personas.nombres,
							LOWER(vw_personas.usuario) as usuario,
							vw_personas.tipo_documento,
							vw_personas.desc_tipo_documento,
							vw_personas.nro_documento,
							sga_docentes_mesa_llamado.rol as rol_docente,
							int_pv_roles_docentes.rol_externo as rol_moodle,
							int_pv_usuarios.id_usuario_externo as usuario_moodle
				FROM		sga_docentes_mesa_llamado
							JOIN sga_docentes ON sga_docentes_mesa_llamado.docente = sga_docentes.docente
							JOIN vw_personas ON sga_docentes.persona = vw_personas.persona
							$from
							LEFT JOIN int_pv_usuarios ON (int_pv_plataformas.plataforma = int_pv_usuarios.plataforma AND vw_personas.persona = int_pv_usuarios.persona)
							LEFT JOIN int_pv_roles_docentes ON (int_pv_plataformas.plataforma = int_pv_roles_docentes.plataforma AND sga_docentes_mesa_llamado.rol = int_pv_roles_docentes.rol)
				WHERE		sga_docentes_mesa_llamado.llamado_mesa = $llamado_mesa
							$where
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de alumnos inscriptos a un llamado-mesa dado con el usuario de Moodle correspondiente.
	 * 
	 * @param int $llamado_mesa
	 * @param string $where
	 * @return array
	 */
	function get_alumnos_inscriptos_a_llamado_mesa_moodle($llamado_mesa, $where = '')
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		DISTINCT sga_insc_examen.alumno,
							vw_personas.persona,
							vw_personas.apellido,
							vw_personas.nombres,
							LOWER(vw_personas.usuario) as usuario,
							vw_personas.tipo_documento,
							vw_personas.desc_tipo_documento,
							vw_personas.nro_documento,
							sga_propuestas.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
							sga_propuestas.propuesta_tipo,
							int_pv_usuarios.id_usuario_externo as usuario_moodle						
				FROM		sga_insc_examen							
							JOIN sga_alumnos ON sga_insc_examen.alumno = sga_alumnos.alumno
							JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
							JOIN sga_propuestas	ON sga_alumnos.propuesta = sga_propuestas.propuesta
							LEFT JOIN int_pv_cursos_llamados_mesa ON sga_insc_examen.llamado_mesa = int_pv_cursos_llamados_mesa.llamado_mesa
							LEFT JOIN int_pv_cursos ON int_pv_cursos_llamados_mesa.curso = int_pv_cursos.curso
							LEFT JOIN int_pv_plataformas ON int_pv_cursos.plataforma = int_pv_plataformas.plataforma
							LEFT JOIN int_pv_usuarios ON (int_pv_plataformas.plataforma = int_pv_usuarios.plataforma AND vw_personas.persona = int_pv_usuarios.persona)
				WHERE		sga_insc_examen.llamado_mesa = $llamado_mesa AND
							sga_insc_examen.estado IN ('".inscripcion_examen::estado_aceptada."', '".inscripcion_examen::estado_pendiente."') AND
							sga_alumnos.calidad = '".alumno::calidad_activo."'
							$where
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de alumnos eliminados en un llamado-mesa dado con el usuario de Moodle correspondiente.
	 * 
	 * @param int $llamado_mesa
	 * @param string $where
	 * @return array
	 */
	function get_alumnos_eliminados_a_llamado_mesa_moodle($llamado_mesa, $where = '')
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = "SELECT * FROM (
				SELECT	DISTINCT sga_insc_examen_log.alumno,
						vw_personas.persona,
						vw_personas.apellido,
						vw_personas.nombres,
						LOWER(vw_personas.usuario) as usuario,
						vw_personas.tipo_documento,
						vw_personas.nro_documento,
						sga_propuestas.propuesta,
						sga_propuestas.nombre as propuesta_nombre,
						sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
						sga_propuestas.propuesta_tipo,
						int_pv_usuarios.id_usuario_externo as usuario_moodle						
				FROM	sga_insc_examen_log							
						JOIN sga_alumnos ON sga_insc_examen_log.alumno = sga_alumnos.alumno
						JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
						JOIN sga_propuestas	ON sga_alumnos.propuesta = sga_propuestas.propuesta
						LEFT JOIN int_pv_cursos_llamados_mesa ON sga_insc_examen_log.llamado_mesa = int_pv_cursos_llamados_mesa.llamado_mesa
						LEFT JOIN int_pv_cursos ON int_pv_cursos_llamados_mesa.curso = int_pv_cursos.curso
						LEFT JOIN int_pv_plataformas ON int_pv_cursos.plataforma = int_pv_plataformas.plataforma
						LEFT JOIN int_pv_usuarios ON (int_pv_plataformas.plataforma = int_pv_usuarios.plataforma AND vw_personas.persona = int_pv_usuarios.persona)
				WHERE	sga_insc_examen_log.llamado_mesa = $llamado_mesa AND
						sga_insc_examen_log.operacion IN ('".inscripcion_examen::operacion_baja."', '".inscripcion_examen::operacion_rechazo."')
				UNION
				SELECT	DISTINCT sga_insc_examen.alumno,
						vw_personas.persona,
						vw_personas.apellido,
						vw_personas.nombres,
						LOWER(vw_personas.usuario) as usuario,
						vw_personas.tipo_documento,
						vw_personas.nro_documento,
						sga_propuestas.propuesta,
						sga_propuestas.nombre as propuesta_nombre,
						sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
						sga_propuestas.propuesta_tipo,
						int_pv_usuarios.id_usuario_externo as usuario_moodle
				FROM	sga_insc_examen							
						JOIN sga_alumnos ON sga_insc_examen.alumno = sga_alumnos.alumno
						JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
						JOIN sga_propuestas	ON sga_alumnos.propuesta = sga_propuestas.propuesta
						LEFT JOIN int_pv_cursos_llamados_mesa ON sga_insc_examen.llamado_mesa = int_pv_cursos_llamados_mesa.llamado_mesa
						LEFT JOIN int_pv_cursos ON int_pv_cursos_llamados_mesa.curso = int_pv_cursos.curso
						LEFT JOIN int_pv_plataformas ON int_pv_cursos.plataforma = int_pv_plataformas.plataforma
						LEFT JOIN int_pv_usuarios ON (int_pv_plataformas.plataforma = int_pv_usuarios.plataforma AND vw_personas.persona = int_pv_usuarios.persona)
				WHERE	sga_insc_examen.llamado_mesa = $llamado_mesa AND
						sga_alumnos.calidad = '".alumno::calidad_pasivo."'
			) as alumnos_eliminados	
			$where 
			ORDER BY	apellido, nombres
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de alumnos inscriptos en un curso de Moodle
	 *
	 */
	function get_alumnos_inscriptos_a_curso_moodle($curso, $where = '')
	{
		$curso = toba::db()->quote($curso);
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		DISTINCT vw_personas.persona,
							sga_insc_examen.alumno,
							vw_personas.apellido,
							vw_personas.nombres,
							LOWER(vw_personas.usuario) as usuario,
							vw_personas.tipo_documento,
							vw_personas.nro_documento,
							sga_propuestas.propuesta,
							sga_propuestas.nombre as propuesta_nombre,
							sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
							sga_propuestas.propuesta_tipo,
							int_pv_usuarios.id_usuario_externo as usuario_moodle						
				FROM		sga_insc_examen							
							JOIN sga_alumnos ON sga_insc_examen.alumno = sga_alumnos.alumno
							JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
							JOIN sga_propuestas	ON sga_alumnos.propuesta = sga_propuestas.propuesta
							LEFT JOIN int_pv_cursos_llamados_mesa ON sga_insc_examen.llamado_mesa = int_pv_cursos_llamados_mesa.llamado_mesa
							LEFT JOIN int_pv_cursos	ON int_pv_cursos_llamados_mesa.curso = int_pv_cursos.curso
							LEFT JOIN int_pv_usuarios ON (int_pv_cursos.plataforma = int_pv_usuarios.plataforma AND vw_personas.persona = int_pv_usuarios.persona)
				WHERE		int_pv_cursos.curso = $curso AND
							sga_insc_examen.estado IN ('".inscripcion_examen::estado_aceptada."', '".inscripcion_examen::estado_pendiente."') AND
							sga_alumnos.calidad = '".alumno::calidad_activo."'
							$where
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la lista de docentes vinculados a un curso Moodle dado.
	 * 
	 * @param int $curso
	 * @param string $where
	 * @return array
	 */
	function get_docentes_curso_moodle($curso, $where = '')
	{
		$curso = toba::db()->quote($curso);
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		DISTINCT vw_personas.persona,
							vw_personas.apellido,
							vw_personas.nombres,
							LOWER(vw_personas.usuario) as usuario,
							vw_personas.tipo_documento,
							vw_personas.nro_documento,
							sga_docentes_mesa_llamado.rol as rol_docente,
							int_pv_roles_docentes.rol_externo as rol_moodle,
							int_pv_usuarios.id_usuario_externo as usuario_moodle
				FROM		int_pv_cursos
							JOIN int_pv_cursos_llamados_mesa ON int_pv_cursos.curso = int_pv_cursos_llamados_mesa.curso
							JOIN sga_docentes_mesa_llamado ON int_pv_cursos_llamados_mesa.llamado_mesa = sga_docentes_mesa_llamado.llamado_mesa
							JOIN sga_docentes ON sga_docentes_mesa_llamado.docente = sga_docentes.docente
							JOIN vw_personas ON sga_docentes.persona = vw_personas.persona
							LEFT JOIN int_pv_usuarios ON (int_pv_cursos.plataforma = int_pv_usuarios.plataforma AND vw_personas.persona = int_pv_usuarios.persona)
							LEFT JOIN int_pv_roles_docentes ON (int_pv_cursos.plataforma = int_pv_roles_docentes.plataforma AND sga_docentes_mesa_llamado.rol = int_pv_roles_docentes.rol)
				WHERE		int_pv_cursos.curso = $curso
							$where
				ORDER BY	vw_personas.apellido,
				    		vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	function get_llamados_mesa_curso_virtual($where)
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_mesas_examen.nombre as mesa_examen_nombre,
							sga_llamados_mesa.llamado_mesa,
							sga_llamados_mesa.fecha as mesa_examen_fecha,
							sga_mesas_examen.elemento,
							sga_elementos.codigo as elemento_codigo,
							get_nombre_actividad_mesa_examen(sga_mesas_examen.mesa_examen, 1) as elemento_nombre,
							periodo_turno.nombre as turno_examen_nombre,
							periodo_turno.fecha_inicio as turno_examen_fecha_inicio,
							periodo_turno.fecha_fin as turno_examen_fecha_fin,
							periodo_turno.anio_academico,
							periodo_llamado.nombre as llamado_turno_nombre,
							int_pv_cursos_llamados_mesa.curso,
							int_pv_cursos.id_curso_externo,
							CASE
								WHEN int_pv_cursos_llamados_mesa.curso IS NULL THEN 0 
								ELSE 1
							END as curso_creado, 
							'' as alumnos_sincronizados, 
							'' as docentes_sincronizados
				FROM		sga_mesas_examen
							JOIN sga_llamados_mesa ON sga_mesas_examen.mesa_examen = sga_llamados_mesa.mesa_examen
							JOIN sga_llamados_turno ON sga_llamados_mesa.llamado = sga_llamados_turno.llamado
							JOIN sga_periodos as periodo_llamado ON sga_llamados_turno.periodo = periodo_llamado.periodo
							JOIN sga_turnos_examen ON sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
							JOIN sga_periodos as periodo_turno ON sga_turnos_examen.periodo = periodo_turno.periodo
							JOIN sga_elementos ON sga_mesas_examen.elemento = sga_elementos.elemento
							LEFT JOIN int_pv_cursos_llamados_mesa ON sga_llamados_mesa.llamado_mesa = int_pv_cursos_llamados_mesa.llamado_mesa
							LEFT JOIN int_pv_cursos ON int_pv_cursos_llamados_mesa.curso = int_pv_cursos.curso
				WHERE		sga_llamados_mesa.fecha >= CURRENT_DATE AND
							NOT EXISTS (SELECT	sga_actas.id_acta 
										FROM	sga_actas
										WHERE	sga_actas.llamado_mesa = sga_llamados_mesa.llamado_mesa AND
												sga_actas.estado = '".acta::estado_cerrada."')
							$where
				ORDER BY	elemento_nombre,
							mesa_examen_nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Verifica si el llamado-mesa dado tiene actas cerradas.
	 * 
	 * @param int $llamado_mesa
	 * @return boolean
	 */
	function llamado_mesa_tiene_acta_cerrada($llamado_mesa)
	{
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$sql = "SELECT	count(sga_actas.id_acta) as cant
				FROM	sga_actas
				WHERE	sga_actas.llamado_mesa = $llamado_mesa AND
						sga_actas.estado = '".acta::estado_cerrada."'
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}
}
