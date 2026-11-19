<?php
namespace nsg01\modelo\datos\db;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\datos\catalogo;
use \siu\modelo\_comun\comunes;
use siu\modelo\transacciones\zona_comisiones;

class docente extends \siu\modelo\datos\db\docente
{
	/**
	 * parametros: comision
	 * cache: no
	 * filas: n
	 */
	function alum_inscriptos_comision($parametros)
	{
		// Alumnos inscriptos en una comisión
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$sql = "SELECT 
			   vw_personas.persona,
			   sga_alumnos.legajo as legajo,
			   sga_alumnos.alumno as alumno,
			   vw_personas.tipo_nro_documento as tipo_nro_documento,
			   vw_personas.apellido_nombres_elegido as apellido_nombres,
			   vw_personas.tipo_nro_documento,
			   sga_alumnos.propuesta as propuesta,
			   sga_propuestas.codigo as propuesta_codigo,
			   sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
			   {$this->subquery_email()},
			   {$this->subquery_telefono()},
			   sga_insc_cursada.fecha_inscripcion as fecha_inscripcion,
			   to_char(sga_insc_cursada.fecha_inscripcion, $formato_fecha_visual) as fecha_inscripcion_display,
			   f_instancias_insc_cursada (sga_insc_cursada.inscripcion) as instancias,
			   sga_inscripciones_estados.nombre as estado_inscripcion,
			   sga_insc_cursada.nro_transaccion as nro_transaccion,
			   sga_insc_cursada.inscripcion as inscripcion,
			   (case when negocio_nsg.int_sq_morosos_ext.deuda > 0 THEN 'Moroso' else 'No moroso' END) AS moroso 	
			FROM 
			   sga_insc_cursada
			   JOIN sga_inscripciones_estados ON sga_inscripciones_estados.estado = sga_insc_cursada.estado
			   JOIN sga_alumnos ON sga_alumnos.alumno = sga_insc_cursada.alumno
			   JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
			   JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
			   LEFT JOIN int_sq_morosos ON int_sq_morosos.alumno = sga_alumnos.alumno
			   LEFT JOIN negocio_nsg.int_sq_morosos_ext ON negocio_nsg.int_sq_morosos_ext.id_moroso_ext = int_sq_morosos.id_moroso
			WHERE sga_insc_cursada.comision = {$parametros['comision']}
		  ORDER BY vw_personas.apellido_nombres
			";

		$datos = kernel::db()->consultar($sql);
		Return $datos;
	}	

	/**
	 * parametros: comision
	 * cache: no
	 * filas: n
	 */
	function asistencia_alum_inscriptos_comision($parametros)
	{
		// Asistencia de alumnos inscriptos en una comisión
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$sql = "SELECT    
			sga_alumnos.alumno,
                        sga_alumnos.legajo,
                        vw_personas.apellido_nombres_elegido as apellido_nombres,
                        vw_personas.tipo_nro_documento AS nro_identificacion,
                        sga_propuestas.nombre_abreviado AS nombre_propuesta,
                        sga_clases_asistencia_acum.total_inasistencias,
                        sga_clases_asistencia_acum.total_justificadas,
                        sga_clases_asistencia_acum.total_inasistencias || ' (' || sga_clases_asistencia_acum.total_justificadas || ')' as total_inasist_just,
                        sga_clases_asistencia_acum.libre,
						sga_clases_asistencia_acum.porc_asistencia as porc_asistencia_justificadas,
                        CASE
                          WHEN sga_clases_asistencia_acum.libre = 1 THEN 'Sí'
                          ELSE 'No'
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
				round((100 - ((select coalesce(sum(cant_inasistencias + cant_justificadas),0) from sga_clases_asistencia where clase in (select clase from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1
									AND c.fecha <= date(now()))
									and alumno = sga_alumnos.alumno)/(select sum(cantidad_horas_dictadas) from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1
									AND c.fecha <= date(now()))*100)),2) else 
				 round((100 - ((select coalesce(sum(cant_inasistencias + cant_justificadas),0) from sga_clases_asistencia where clase in (select clase from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1
									AND c.fecha <= date(now()))
									and alumno = sga_alumnos.alumno)/(select count(*) from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1
									AND c.fecha <= date(now()))*100)),2) end) as porc_asistencia_hasta_hoy,
				(case (SELECT sga_comisiones.asistencia_por_horas FROM sga_comisiones WHERE sga_comisiones.comision = sga_insc_cursada.comision) when 'S' then						
				round((100 - ((select coalesce(sum(cant_inasistencias + cant_justificadas),0) from sga_clases_asistencia where clase in (select clase from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1)
									and alumno = sga_alumnos.alumno)/(select sum(cantidad_horas_dictadas) from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
									AND c.valido     = 1      
      								AND c.asistencia = 1)*100)),2) else 
				 round((100 - ((select coalesce(sum(cant_inasistencias + cant_justificadas),0) from sga_clases_asistencia where clase in (select clase from sga_clases c JOIN sga_comisiones_bh as bh ON c.banda_horaria = bh.banda_horaria WHERE bh.comision = sga_insc_cursada.comision
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
                WHERE sga_insc_cursada.comision = {$parametros['comision']}                				
                ORDER BY    vw_personas.apellido_nombres";

		$datos = kernel::db()->consultar($sql);
		Return $datos;
	}	
  	
}