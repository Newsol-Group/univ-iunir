<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class co_asistencias_nsg extends co_asistencias
{
	
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
						END AS libre_descr,
				(select count(*) from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1) as total_clases_previstas_anio,
				(select count(*) from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1
									AND c.fecha <= date(now())) as total_clases_hasta_hoy,
				(select sum(cantidad_horas_dictadas) from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1
									AND c.fecha <= date(now())) as total_horas_dictadas_hoy,
				(select sum(cantidad_horas_dictadas) from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1) as total_horas_previstas_anio,
				
				(case (SELECT sga_comisiones.asistencia_por_horas FROM sga_comisiones WHERE sga_comisiones.comision = sga_insc_cursada.comision) when 'S' then						
				round((100 - ((select coalesce(sum(cant_inasistencias - cant_justificadas),0) from sga_clases_asistencia where clase in (select clase from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1
									AND c.fecha <= date(now()))
									and alumno = sga_alumnos.alumno)/(select sum(cantidad_horas_dictadas) from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1
									AND c.fecha <= date(now()))*100)),2) else 
				 round((100 - ((select coalesce(sum(cant_inasistencias - cant_justificadas),0) from sga_clases_asistencia where clase in (select clase from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1
									AND c.fecha <= date(now()))
									and alumno = sga_alumnos.alumno)/(select count(*) from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1
									AND c.fecha <= date(now()))*100)),2) end) as porc_asistencia_hasta_hoy,
				(case (SELECT sga_comisiones.asistencia_por_horas FROM sga_comisiones WHERE sga_comisiones.comision = sga_insc_cursada.comision) when 'S' then						
				round((100 - ((select coalesce(sum(cant_inasistencias - cant_justificadas),0) from sga_clases_asistencia where clase in (select clase from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1)
									and alumno = sga_alumnos.alumno)/(select sum(cantidad_horas_dictadas) from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1)*100)),2) else 
				 round((100 - ((select coalesce(sum(cant_inasistencias - cant_justificadas),0) from sga_clases_asistencia where clase in (select clase from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1)
									and alumno = sga_alumnos.alumno)/(select count(*) from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1)*100)),2) end) as porc_asistencia_previstas_anio
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
    
}
?>
