<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class co_asistencias
{
	/**
     *   Retorna las opciones para el combo del campo estado ausencias examen
     */
    function get_opciones_combo_estado_ausencia()
    {
		return array(	array('valor' => asistencia::estado_ausencia_aceptado, 'descr' => asistencia::estado_ausencia_aceptado),
						array('valor' => asistencia::estado_ausencia_rechazado, 'descr' => asistencia::estado_ausencia_rechazado)
					);
    }
    
    function get_ausencias_alumno($alumno, $where='')
    {
    
    
    	$alumno = toba::db()->quote($alumno);
		$filtro = "AND sga_clases_asistencia.alumno = $alumno";
		
		
		
    	if ($where) {
			$filtro .= " AND $where ";
		}
		
		$sql = "SELECT	sga_clases_asistencia.clase,
						sga_clases_asistencia.alumno,
						sga_asignaciones.dia_semana,
						".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
						sga_clases.fecha,
						sga_clases.tema_dictado,
						to_char(sga_asignaciones.hora_inicio, '".comunes::formato_hora_visual."') 		AS hora_inicio_formateada,
						to_char(sga_asignaciones.hora_finalizacion, '".comunes::formato_hora_visual."') AS hora_finalizacion_formateada,
						CASE 
						  WHEN sga_clases_asistencia.cant_justificadas > 0 THEN '".comunes::si_desc."'
						  ELSE '".comunes::no_desc."'
						END AS justificado,
						sga_clases_tipos.nombre			AS clase_tipo_nombre,
						sga_motivos_ausencias.nombre	AS motivo_ausencia_nombre,
						sga_clases_asistencia.estado_justificacion,
						sga_comisiones.nombre			AS comision_nombre,
						'(' || sga_elementos.codigo || ') ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) || ' - ' || sga_comisiones.nombre as actividad_comision,
						'(' || sga_elementos.codigo || ') ' || get_nombre_actividad_comision(sga_comisiones.comision, 1) as actividad_cod_nombre,
						sga_comisiones.comision,
						sga_clases.cantidad_horas_dictadas
				FROM sga_clases
				JOIN sga_clases_asistencia ON sga_clases_asistencia.clase = sga_clases.clase
				JOIN sga_comisiones_bh ON sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria
				JOIN sga_asignaciones ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
				JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_comisiones_bh.tipo_clase
				JOIN sga_comisiones ON sga_comisiones.comision = sga_comisiones_bh.comision
				JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
				JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
				JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
				LEFT JOIN sga_motivos_ausencias ON sga_clases_asistencia.motivo_ausencia = sga_motivos_ausencias.motivo_ausencia
							
				WHERE sga_clases_asistencia.cant_inasistencias > 0
				  AND sga_clases.valido = 1
				  $filtro
		
				ORDER BY	sga_periodos.anio_academico,
							sga_clases.fecha
				";
		
		$datos = guarani_db::consultar($sql);
		
		return $datos;
    }
    
    function get_inasistencias_acum($alumno, $comision)
    {
    	$alumno = toba::db()->quote($alumno);
    	$comision = toba::db()->quote($comision);
    	
    	$sql = "SELECT	sga_clases_asistencia_acum.total_inasistencias,
			sga_clases_asistencia_acum.total_justificadas,
			sga_clases_asistencia_acum.porc_asistencia,
                        CASE
			    WHEN sga_clases_asistencia_acum.total_inasistencias > 0 THEN 'Cantidad Total de Inasistencias' || ': ' || sga_clases_asistencia_acum.total_inasistencias::integer
			    ELSE 'Cantidad Total de Inasistencias: 0'
			    END AS descr_total_inasistencias,
                        CASE
			    WHEN sga_clases_asistencia_acum.total_justificadas > 0 THEN 'Cantidad de Justificaciones' || ': ' || sga_clases_asistencia_acum.total_justificadas::integer
			    ELSE 'Cantidad de Justificaciones: 0'
			    END AS descr_total_inasistencias_justificadas,
                        CASE 
			    WHEN sga_clases_asistencia_acum.porc_asistencia > 0 THEN 'Porcentaje de Asistencia' || ': ' || sga_clases_asistencia_acum.porc_asistencia || '%' 
			    ELSE 'Porcentaje de Asistencia: 0,00%'
			    END AS descr_porcentaje_asistencia
		FROM    sga_clases_asistencia_acum
		WHERE	sga_clases_asistencia_acum.alumno = $alumno
			AND sga_clases_asistencia_acum.comision = $comision
		";
		
		return guarani_db::consultar_fila($sql);
    }
    
	function get_alumno_comision_porc_asistencia($alumno, $comision)
    {
    	$alumno = toba::db()->quote($alumno);
    	$comision = toba::db()->quote($comision);
    	
    	$sql = "SELECT	sga_clases_asistencia_acum.porc_asistencia 
				FROM	sga_clases_asistencia_acum
				WHERE	sga_clases_asistencia_acum.alumno = $alumno
    			AND		sga_clases_asistencia_acum.comision = $comision
  			   ";
		
		$r = guarani_db::consultar_fila($sql);
		
		return $r['porc_asistencia'];
    }

	function get_pct_asistencia_alumno_evaluacion($evaluacion, $alumno)
	{
		if (is_array($evaluacion) && is_array($alumno)) {
			$filtro = guarani_sql::armar_condicion_in_dupla($evaluacion, 'sga_evaluaciones', 'evaluacion', '', $alumno, 'sga_clases_asistencia_acum', 'alumno', '');
		} else {
			$alumno = toba::db()->quote($alumno);
			$evaluacion = toba::db()->quote($evaluacion);
			$filtro = "	sga_clases_asistencia_acum.alumno = $alumno AND
						sga_evaluaciones.evaluacion = $evaluacion";
		}
		$sql = "SELECT	sga_evaluaciones.evaluacion,
						sga_clases_asistencia_acum.alumno,
						sga_clases_asistencia_acum.porc_asistencia as pct_asistencia
				FROM	sga_clases_asistencia_acum
						JOIN sga_comisiones ON sga_clases_asistencia_acum.comision = sga_comisiones.comision
						JOIN sga_evaluaciones ON sga_comisiones.entidad = sga_evaluaciones.entidad
				WHERE	$filtro
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_alumno_comision_libre($alumno, $comision)
    {
    	$alumno = toba::db()->quote($alumno);
    	$comision = toba::db()->quote($comision);
    	
    	$sql = "SELECT	sga_clases_asistencia_acum.libre
				FROM	sga_clases_asistencia_acum
				WHERE	sga_clases_asistencia_acum.alumno = $alumno
    			AND		sga_clases_asistencia_acum.comision = $comision
  			   ";
		
		$r = guarani_db::consultar_fila($sql);
		return $r['libre'];
    }
	
    /**
     * Obtiene el listado de Alumnos de la Comisión junto con la cantidad de inasistencias acumuladas a clases de la misma
     * para cada alumno.
     *
     * @param integer $comision Comisión para la que se buscan los alumnos inscriptos con asistencias acumuladas.
     */
    function get_alumnos_inasistencias_acum_comision($comision, $where='')
    {
    	$comision = toba::db()->quote($comision);
    
    	if ($where) {
    		$where = ' AND ' . $where; 
    	}
    	
    	$sql = "SELECT	sga_alumnos.alumno,
						sga_alumnos.legajo,
						vw_personas.apellido_nombres_elegido as apellido_nombres,
						vw_personas.tipo_nro_documento AS nro_identificacion,
						sga_propuestas.nombre_abreviado AS nombre_propuesta,
						sga_clases_asistencia_acum.total_inasistencias,
						sga_clases_asistencia_acum.total_justificadas,
						sga_clases_asistencia_acum.total_inasistencias || ' (' || sga_clases_asistencia_acum.total_justificadas || ')' as total_inasist_just,
						sga_clases_asistencia_acum.libre,
						CASE
						  WHEN sga_clases_asistencia_acum.libre = 1 THEN '".comunes::si_desc."'
						  ELSE '".comunes::no_desc."'
						END AS libre_descr
				FROM sga_insc_cursada
				JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
				JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
				LEFT JOIN sga_clases_asistencia_acum ON sga_clases_asistencia_acum.alumno = sga_insc_cursada.alumno AND sga_clases_asistencia_acum.comision = sga_insc_cursada.comision
				WHERE sga_insc_cursada.comision = $comision
				$where
				ORDER BY	vw_personas.apellido_nombres
  			   ";
		
		return guarani_db::consultar($sql);
    }
    
     /**
     * Obtiene el listado de Alumnos de la Subcomisión, junto con la cantidad de inasistencias acumuladas para cada uno 
     * en la comisión a la cual corresponde la subcomisión. 
     *
     * @param integer $subcomision
     */
    function get_alumnos_inasistencias_acum_subcomision($subcomision, $where='')
    {
    	$subcomision = toba::db()->quote($subcomision);
    
    	if ($where) {
    		$where = ' AND ' . $where;
    	}
    	// Se modifica la consulta para obtener las clases acumuladas con una subconsulta ya que en la tabla sga_clases_asistencia_acum se guardan los acumulados por comision.
    	$sql = "SELECT		sga_alumnos.alumno,
							sga_alumnos.legajo,
							vw_personas.apellido_nombres_elegido as apellido_nombres,
							(
								(SELECT
								     SUM(ca.cant_inasistencias)
								 FROM sga_insc_cursada as i,
								      sga_insc_subcomision as subc,
								      sga_subcomisiones_bh as bh,
								      sga_clases as c, 
								      sga_clases_asistencia as ca
								 WHERE subc.subcomision = $subcomision
								   AND i.alumno   = sga_alumnos.alumno
								   AND subc.inscripcion = i.inscripcion
								   AND bh.subcomision   = subc.subcomision
								   AND c.banda_horaria  = bh.banda_horaria
								   AND c.valido  = 1
								   AND ca.clase  = c.clase
								   AND ca.estado = 'A'
								   AND ca.alumno = i.alumno) 
								   || ' (' || 
								(SELECT
									 SUM(ca.cant_justificadas) 
								 FROM sga_insc_cursada as i,
									  sga_insc_subcomision as subc,
									  sga_subcomisiones_bh as bh,
									  sga_clases as c, 
									  sga_clases_asistencia as ca
								 WHERE subc.subcomision = $subcomision
								   AND i.alumno   = sga_alumnos.alumno
								   AND subc.inscripcion = i.inscripcion
								   AND bh.subcomision   = subc.subcomision
								   AND c.banda_horaria  = bh.banda_horaria
								   AND c.valido  = 1
								   AND ca.clase  = c.clase
								   AND ca.estado = 'A'
								   AND ca.alumno = i.alumno) || ')' 
							 ) as total_inasist_just,
							sga_clases_asistencia_acum.libre,
							CASE
								WHEN sga_clases_asistencia_acum.libre = 1
									THEN '".comunes::si_desc."'
									ELSE '".comunes::no_desc."'
							END AS libre_descr
				FROM sga_subcomisiones
				JOIN sga_insc_cursada ON sga_insc_cursada.comision = sga_subcomisiones.comision
				JOIN sga_insc_subcomision ON (sga_insc_subcomision.subcomision = sga_subcomisiones.subcomision AND
				                              sga_insc_subcomision.inscripcion = sga_insc_cursada.inscripcion)
				JOIN sga_alumnos ON sga_alumnos.alumno = sga_insc_cursada.alumno
				JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
				LEFT JOIN sga_clases_asistencia_acum ON (sga_clases_asistencia_acum.alumno = sga_insc_cursada.alumno AND 
				                                         sga_clases_asistencia_acum.comision = sga_insc_cursada.comision)
				WHERE sga_subcomisiones.subcomision = $subcomision 
				$where
				ORDER BY	vw_personas.apellido_nombres
  			   ";
		
		return guarani_db::consultar($sql);
    }
    
    function get_inasistencias_docente($docente, $where='')
    {
    	$docente = toba::db()->quote($docente);
		
    	if ($where) {
			$where = " AND $where ";
		}
				
		$sql = "SELECT	sga_clases_ausencias_docentes.clase,
						sga_clases_ausencias_docentes.docente,
						sga_clases.fecha,
						sga_asignaciones.dia_semana,
						".guarani_sql::get_case('sga_asignaciones', 'dia_semana', 'dia_semana_nombre', toba::consulta_php('co_comunes')->get_dias_semana()).",
						sga_clases_ausencias_docentes.cant_inasistencias,
						sga_clases_ausencias_docentes.cant_justificadas,
						sga_clases_ausencias_docentes.observaciones,
						sga_clases_tipos.nombre			AS clase_tipo_nombre,
						sga_motivos_ausencias.nombre	AS motivo_ausencia_nombre,
						sga_comisiones.comision,
						sga_comisiones.nombre			AS comision_nombre,
						sga_docentes_comision.responsabilidad,
						sga_docentes_responsabilidades.nombre AS responsabilidad_nombre,
						COALESCE('(' || sga_elementos.codigo || ') ', '') || get_nombre_actividad_comision(sga_comisiones.comision, 1) as actividad_descr,
						vw_periodos_lectivos.anio_academico,
						vw_periodos_lectivos.nombre AS periodo_nombre
						    
				FROM sga_clases_ausencias_docentes
				JOIN sga_clases ON sga_clases_ausencias_docentes.clase = sga_clases.clase
				JOIN sga_comisiones_bh ON sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria
				JOIN sga_asignaciones ON sga_comisiones_bh.asignacion = sga_asignaciones.asignacion
				JOIN sga_clases_tipos ON sga_comisiones_bh.tipo_clase = sga_clases_tipos.tipo_clase
				JOIN sga_comisiones ON sga_comisiones_bh.comision = sga_comisiones.comision
				JOIN sga_docentes_comision ON (sga_docentes_comision.comision = sga_comisiones.comision AND 
				                               sga_docentes_comision.docente = sga_clases_ausencias_docentes.docente) 
				JOIN sga_docentes_responsabilidades ON sga_docentes_comision.responsabilidad = sga_docentes_responsabilidades.responsabilidad
				JOIN sga_elementos ON sga_comisiones.elemento = sga_elementos.elemento
				JOIN vw_periodos_lectivos ON sga_comisiones.periodo_lectivo = vw_periodos_lectivos.periodo_lectivo
				LEFT JOIN sga_motivos_ausencias ON sga_clases_ausencias_docentes.motivo_ausencia = sga_motivos_ausencias.motivo_ausencia
							
				WHERE sga_clases_ausencias_docentes.docente = $docente	
				  AND sga_clases.valido = 1
 				  $where
		
				ORDER BY	vw_periodos_lectivos.anio_academico,
							sga_elementos.nombre,
							sga_clases.fecha
		";
		
		return guarani_db::consultar($sql);
    }
    
    // Verifica si ya existe algún registro cargado para la ausencia del docente y la clases dados.
    function existe_registro_inasistencia_docente_a_clase($docente, $clase)
    {
    	$docente = toba::db()->quote($docente);
    	$clase = toba::db()->quote($clase);
    	
    			
		$sql = "SELECT		COUNT (*) AS registro_ausencia
				FROM		sga_clases_ausencias_docentes
				WHERE		sga_clases_ausencias_docentes.docente = $docente AND
							sga_clases_ausencias_docentes.clase = $clase
			;";
		
		$res = guarani_db::consultar_fila($sql);
		if ($res['registro_ausencia'] > 0) {
			return true;
		} else {
			return false;
		}
    }
    
    /**
     * Obtiene los Docentes y datos de los mismos, asociados a la Comisión dada como parámetro, 
     * obteniendo además los datos de sus inasistencias, si es que existe registro de ausencias cargado para 
     * los mismos.
     *
     * @param integer	$comision		id de la comisión para la cual se desea recuperar los docentes.
     * @param str		$where			filtro sobre los docentes a recuperar.
     * @return unknown
     */
    function get_docentes_comision_con_inasistencias_clase($comision, $clase, $where='')
    {
    	$comision = toba::db()->quote($comision);
    	$clase = toba::db()->quote($clase);
    	
    	if ($where) {
			$where = " AND $where ";
		}
				
		$sql = "SELECT	sga_docentes.docente,
						COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as docente_nombre,
						sga_docentes.legajo,
						sga_docentes_comision.responsabilidad,
						sga_docentes_responsabilidades.nombre as responsabilidad_nombre,
						CASE sga_clases_ausencias_docentes.cant_inasistencias
							WHEN NULL THEN 0
							ELSE sga_clases_ausencias_docentes.cant_inasistencias
						END as cant_inasistencias
				FROM sga_docentes_comision 
				JOIN sga_docentes ON sga_docentes_comision.docente = sga_docentes.docente
				JOIN mdp_personas ON sga_docentes.persona = mdp_personas.persona
				LEFT JOIN sga_docentes_responsabilidades ON sga_docentes_comision.responsabilidad = sga_docentes_responsabilidades.responsabilidad
				LEFT JOIN sga_clases_ausencias_docentes ON (sga_docentes_comision.docente = sga_clases_ausencias_docentes.docente AND sga_clases_ausencias_docentes.clase = $clase)
				WHERE sga_docentes_comision.comision = $comision
				$where
				ORDER BY  COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
                            COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)
		";
		
		return guarani_db::consultar($sql);
    }
    
     /**
     * Obtiene los Docentes y datos de los mismos, asociados a la Subcomisión dada como parámetro, 
     * obteniendo además los datos de sus inasistencias, si es que existe registro de ausencias cargado para 
     * los mismos.
     *
     * @param integer	$subcomision	id de la subcomisión para la cual se desea recuperar los docentes (puede ser nulo).
     * @param str		$where			filtro sobre los docentes a recuperar.
     */
    function get_docentes_subcomision_con_inasistencias_clase($subcomision, $clase, $where='')
    {
    	$subcomision = toba::db()->quote($subcomision);
    	$clase = toba::db()->quote($clase);
   
    	if ($where) {
			$where = " AND $where ";
		}
				
		$sql = "SELECT	sga_docentes.docente,
						COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as docente_nombre,
						sga_docentes.legajo,
						sga_docentes_comision.responsabilidad,
						sga_docentes_responsabilidades.nombre as responsabilidad_nombre,
						CASE sga_clases_ausencias_docentes.cant_inasistencias
							WHEN NULL THEN 0
							ELSE sga_clases_ausencias_docentes.cant_inasistencias
						END as cant_inasistencias
						    
				FROM sga_docentes_subcomision
				JOIN sga_subcomisiones ON sga_subcomisiones.subcomision = sga_docentes_subcomision.subcomision 
				JOIN sga_docentes_comision ON (sga_docentes_comision.comision = sga_subcomisiones.comision AND 
				                               sga_docentes_comision.docente = sga_docentes_subcomision.docente)
				JOIN sga_docentes ON sga_docentes.docente = sga_docentes_subcomision.docente
				JOIN mdp_personas ON sga_docentes.persona = mdp_personas.persona
				LEFT JOIN sga_clases_ausencias_docentes ON (sga_docentes_subcomision.docente = sga_clases_ausencias_docentes.docente AND sga_clases_ausencias_docentes.clase = $clase)
				LEFT JOIN sga_docentes_responsabilidades ON sga_docentes_comision.responsabilidad = sga_docentes_responsabilidades.responsabilidad
				
				WHERE sga_docentes_subcomision.subcomision = $subcomision
				$where
		
				ORDER BY	 COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
                            COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)
		";
		
		return guarani_db::consultar($sql);
    }
    
    function get_asistencias_alumnos_subc($comision)
    {
                $comision = toba::db()->quote($comision);
		
		$sql = "SELECT		sga_alumnos.alumno,
							vw_personas.apellido_nombres AS apellido_y_nombre,
							vw_personas.tipo_nro_documento AS nro_identificacion,
							sga_propuestas.nombre_abreviado AS nombre_propuesta,
							sga_clases_asistencia_acum.libre,
							CASE
	        					WHEN sga_clases_asistencia_acum.libre = 1
								THEN '".comunes::si_desc."'
	    	        			ELSE '".comunes::no_desc."'
	    					END AS libre_descr,
							sga_clases_asistencia_acum.total_inasistencias,
							sga_clases_asistencia_acum.total_justificadas,
							('Subcomisión: ' || sga_subcomisiones.nombre ||
                             ' - Tipo de Clase: ' || sga_clases_tipos.nombre ||
                             ' - Clases: ' || 
                             ( SELECT COUNT(sga_clases.clase)
                                 FROM sga_clases
                                 JOIN sga_comisiones_bh ON sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria
                                 JOIN sga_subcomisiones_bh ON (sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria AND
                                                               sga_subcomisiones_bh.subcomision = subc.subcomision)
                                 WHERE sga_comisiones_bh.comision = ic.comision
                                   AND sga_clases.valido = 1) ||
                             ' - Horas Clases: ' || 
                             ( SELECT SUM(sga_clases.cantidad_horas_dictadas)
                                 FROM sga_clases
                                 JOIN sga_comisiones_bh ON sga_clases.banda_horaria = sga_comisiones_bh.banda_horaria
                                 JOIN sga_subcomisiones_bh ON (sga_subcomisiones_bh.banda_horaria = sga_comisiones_bh.banda_horaria AND
                                      sga_subcomisiones_bh.subcomision = subc.subcomision)
                                 WHERE sga_comisiones_bh.comision = ic.comision
                                   AND sga_clases.valido = 1)                                                     
                            ) AS subcomision_tipo_clase,
							sga_subcomisiones.subcomision,
							sga_subcomisiones.nombre 	AS subcomision_nombre,
							sga_clases_tipos.nombre		AS clase_tipo_nombre
							
				FROM sga_insc_cursada as ic
				JOIN sga_insc_subcomision as subc ON ic.inscripcion = subc.inscripcion
				JOIN sga_subcomisiones ON sga_subcomisiones.subcomision = subc.subcomision
				JOIN sga_subcomisiones_bh ON sga_subcomisiones_bh.subcomision = subc.subcomision
				JOIN sga_comisiones_bh ON sga_comisiones_bh.banda_horaria = sga_subcomisiones_bh.banda_horaria
				JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_comisiones_bh.tipo_clase
				JOIN sga_alumnos ON ic.alumno = sga_alumnos.alumno
				JOIN sga_propuestas ON sga_alumnos.propuesta = sga_propuestas.propuesta
				JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
				LEFT JOIN sga_clases_asistencia_acum ON (sga_clases_asistencia_acum.alumno = ic.alumno AND 
				                                         sga_clases_asistencia_acum.comision = ic.comision)
				WHERE ic.comision = $comision 
				ORDER BY	vw_personas.apellido,
							vw_personas.nombres
				";
		
		return guarani_db::consultar($sql);
    }

    /**
     * Recupera los alumnos inscriptos en una comision/subcomision para una clase.
     *
     * @param unknown_type $clase
     * @param unknown_type $where
     * @param unknown_type $cant_inasist_con_decimales
     * @return unknown
     */
    function get_registros_asistencias_alumnos_clase($clase, $cant_inasist_con_decimales, $where, $subcomision=null)
    {
    	$clase = toba::db()->quote($clase);
		$select = $cant_inasist_con_decimales ? ' sga_clases_asistencia.cant_inasistencias, sga_clases_asistencia.cant_inasistencias as cant_inasistencias_combo, sga_clases_asistencia.cant_justificadas ' : ' CAST(sga_clases_asistencia.cant_inasistencias AS INTEGER), CAST(sga_clases_asistencia.cant_justificadas AS INTEGER) ';
		$join_subcomision  = '';
		if (! is_null($subcomision)) {
			// Recupero alumnos de la subcomisión pasada por parametro
			$subcomision = toba::db()->quote($subcomision);
			$join_subcomision  = " JOIN sga_insc_subcomision ON sga_insc_subcomision.inscripcion = sga_insc_cursada.inscripcion AND sga_insc_subcomision.subcomision = $subcomision " ;
		}
		$sql = "SELECT		sga_alumnos.alumno,
							sga_alumnos.legajo,
							vw_personas.apellido_nombres_elegido AS apellido_y_nombres,
							vw_personas.tipo_nro_documento AS documento_principal,
							sga_clases_asistencia.cant_justificadas,
							sga_clases_asistencia.estado_justificacion,
							sga_clases_asistencia.observaciones,
							CASE
								WHEN sga_clases_asistencia.alumno IS NOT NULL THEN 1
								ELSE 0
							END as existe_asistencia_alumno_clase,
							$select
				FROM		sga_clases
							JOIN sga_comisiones_bh ON sga_comisiones_bh.banda_horaria = sga_clases.banda_horaria
							JOIN sga_insc_cursada ON sga_insc_cursada.comision = sga_comisiones_bh.comision
							$join_subcomision
							JOIN sga_alumnos ON sga_alumnos.alumno = sga_insc_cursada.alumno
							JOIN vw_personas ON sga_alumnos.persona = vw_personas.persona
							LEFT JOIN sga_clases_asistencia ON (sga_clases_asistencia.clase = sga_clases.clase AND sga_clases_asistencia.alumno = sga_insc_cursada.alumno)
				WHERE		sga_clases.clase = $clase AND
							sga_alumnos.calidad = '".alumno::calidad_activo."' AND
							$where
				ORDER BY	vw_personas.apellido, vw_personas.nombres
		";
		return guarani_db::consultar($sql);
    }

    /**
     * Retorna el porcentaje de asistencia mínimo requerido para una comisión, instancia y alumno dados.
     *
     * @param integer $comision
     * @param integer $instancia
     * @param integer $alumno
     * @return float
     */
	function get_asistencia_minima_comision_instancia_alumno($comision, $instancia, $alumno)
	{
		$comision = toba::db()->quote($comision);
		$instancia = toba::db()->quote($instancia);
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT	CASE
							WHEN $instancia = '".comision::inst_cursar."' THEN COALESCE(sga_alumnos_excep_asistencia.porcentaje_regular, sga_comisiones_instancias.porc_asistencia)
							ELSE COALESCE(sga_alumnos_excep_asistencia.porcentaje_promocion, sga_comisiones_instancias.porc_asistencia)
						END as asistencia_minima
				FROM	sga_comisiones_instancias
						LEFT JOIN sga_alumnos_excep_asistencia ON (	sga_comisiones_instancias.comision = sga_alumnos_excep_asistencia.comision AND
																	sga_alumnos_excep_asistencia.alumno = $alumno)
				WHERE	sga_comisiones_instancias.comision = $comision AND
						sga_comisiones_instancias.instancia = $instancia
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['asistencia_minima'];
    }
	
	function existe_alumno_clase($clase, $alumno) 
	{
		$clase = toba::db()->quote($clase);		
		$alumno = toba::db()->quote($alumno);
		$sql = "
				SELECT 1
				  FROM sga_clases_asistencia
				 WHERE sga_clases_asistencia.clase = $clase 
				   AND sga_clases_asistencia.alumno = $alumno
				";
		return toba::db()->hay_datos($sql);
	}
	
	/**
		Retorna las opciones PRESENTE (0) / AUSENTE (1) / MEDIA FALTA (0.5)
	*/    
	function get_asistencia_combo()
	{	
		return array(array('valor' => asistencia::asist_presente ,'descr' => asistencia::asist_presente_descripcion),
		             array('valor' => asistencia::asist_ausente ,'descr' => asistencia::asist_ausente_descripcion),
		             array('valor' => asistencia::asist_media_falta ,'descr' => asistencia::asist_media_falta_descripcion),
		            );    
	}
}
?>
