<?php
/**
 **
 * @package Cursadas
 * @subpackage Reportes
 */
class co_inscripciones_cursadas_nsg extends co_inscripciones_cursadas
{
	/** Newsol (NUEVO) - Retorna el listado de los alumnos con regularidad vencidad en una materia
     	*/
	function get_listado_alumnos_con_reg_vencida($where='')
	{
		if ($where) {
			$where 	= ' AND '.$where;
		}
		
		$sql = "SELECT		sga_ubicaciones.ubicacion			AS ubicacion_id,
							sga_ubicaciones.nombre				AS ubicacion,
							sga_elementos.elemento				AS actividad_id,
        					'(' || sga_elementos.codigo || ') '
        					|| sga_elementos.nombre				AS actividades,
        					vw_personas.apellido_nombres 		AS alumno,
							vw_personas.tipo_nro_documento 		AS nro_identificacion,
 							mdp_personas_contactos.email, 
        					sga_comisiones.nombre				AS comision,
        					vw_regularidades.fecha,
        					vw_regularidades.fecha_vigencia
       
				FROM			vw_regularidades,
							sga_comisiones,
							sga_elementos,
							sga_ubicaciones,
							sga_alumnos,
							vw_personas,
							mdp_personas_contactos

				WHERE    	vw_regularidades.comision = sga_comisiones.comision
           					AND vw_regularidades.alumno = sga_alumnos.alumno
    						AND sga_comisiones.elemento = sga_elementos.elemento
        					AND sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
        					AND sga_alumnos.persona = vw_personas.persona
        					AND mdp_personas_contactos.persona  = vw_personas.persona
						AND vw_regularidades.es_vigente = 0
						AND sga_alumnos.calidad = 'A'
						AND mdp_personas_contactos.email is not null
						AND not exists (select * from vw_hist_academica a where a.elemento = sga_comisiones.elemento
										and a.alumno = sga_alumnos.alumno)
							$where
		
				ORDER BY	ubicacion,
							actividades,
							alumno
  			   ";
		
		return guarani_db::consultar($sql);
	}	
	
	
	/**Newsol (NUEVO) - Retorna el total de inscriptos a cursadas
     	*/
	function get_listado_total_inscriptos_cursada($where='')
	{
		if ($where) {
			$where = "AND $where";
		}

		$sql = "SELECT  	sga_propuestas.nombre		as nombre_propuesta,
					count(distinct vw_insc_cursada.alumno) as cant_inscriptos
    				FROM	sga_comisiones
					JOIN vw_actividades_plan ON sga_comisiones.elemento = vw_actividades_plan.elemento
					JOIN vw_insc_cursada ON sga_comisiones.comision = vw_insc_cursada.comision
					JOIN sga_alumnos ON sga_alumnos.alumno = vw_insc_cursada.alumno
					JOIN sga_ubicaciones ON sga_comisiones.ubicacion = sga_ubicaciones.ubicacion
					JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
					JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
					LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
					JOIN sga_propuestas ON sga_propuestas.propuesta = vw_actividades_plan.propuesta
				WHERE 	sga_alumnos.calidad = 'A' 
				$where
				GROUP BY	sga_propuestas.nombre
		";

		return guarani_db::consultar($sql);

	}
	
	/**Newsol (NUEVO) - Retorna el total de inscriptos a cursadas por año académico
     	*/
	function get_listado_total_inscriptos_cursada_testing($where='')
	{
		if ($where) {
			$where = "AND $where";
		}	

		$anio = substr($where,24,4);
		$where = substr($where,29, strlen($where));

		$sql = "select negocio_nsg.f_generar_total_inscriptos_cursadas($anio)";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	sga_propuestas.nombre as nombre_propuesta,
					t.anio,
					sum(ing_marzo) as ing_marzo,
					sum(reinsc_marzo) as reinsc_marzo,
					sum(ing_julio) as ing_julio,
					sum(reinsc_julio) as reinsc_julio,
					sum(ing_agosto) as ing_agosto,
					sum(reinsc_agosto) as reinsc_agosto,
					sum(ing_diciembre) as ing_diciembre,
					sum(reinsc_diciembre) as reinsc_diciembre
		FROM negocio_nsg.tmp_total_inscriptos_cursadas t,
			sga_propuestas 
			WHERE t.propuesta = sga_propuestas.propuesta
		$where	
			GROUP BY sga_propuestas.nombre, t.anio
			ORDER BY sga_propuestas.nombre, t.anio
		";

		return guarani_db::consultar($sql);

	}

	
	/**Newsol (NUEVO) - Retorna el listado de inscriptos a cursadas (nominal) en un año académico
     	*/
	function get_listado_inscriptos_cursada($where='')
	{
		if ($where) {
			$where = "AND $where";
		}

		$sql = "SELECT  	distinct sga_propuestas.nombre		as nombre_propuesta,
					mdp_personas.apellido, 
					mdp_personas.nombres,
					mdp_personas_documentos.nro_documento,
					(select i.email from mdp_personas_contactos i where i.persona = mdp_personas.persona and i.contacto_tipo in ('MP', 'MI') limit 1) as email, 
					(select fecha_inscripcion from sga_propuestas_aspira a where a.persona = sga_alumnos.persona and a.propuesta = sga_alumnos.propuesta limit 1) as fecha_ingreso,
                    			coalesce((select b.nombre from sga_periodos_inscripcion_fechas a, sga_periodos_inscripcion b, sga_propuestas_aspira c where c.persona = sga_alumnos.persona and c.propuesta = sga_alumnos.propuesta and c.periodo_insc = a.periodo_insc and a.periodo_inscripcion = b.periodo_inscripcion limit 1),'PRIMER CUATRIMESTRE 2013') as periodo_ingreso,
					(select nombre from sga_turnos_cursadas where sga_comisiones.turno = sga_turnos_cursadas.turno) as turno
			FROM	sga_comisiones 
					JOIN vw_actividades_plan ON sga_comisiones.elemento = vw_actividades_plan.elemento
					JOIN vw_insc_cursada ON sga_comisiones.comision = vw_insc_cursada.comision
					JOIN sga_alumnos ON vw_insc_cursada.alumno = sga_alumnos.alumno
					JOIN mdp_personas ON sga_alumnos.persona = mdp_personas.persona
					JOIN mdp_personas_documentos ON mdp_personas.persona = mdp_personas_documentos.persona
					JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
					JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
					LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
					JOIN sga_propuestas ON sga_propuestas.propuesta = vw_actividades_plan.propuesta
			WHERE	mdp_personas_documentos.tipo_documento = 0 
			AND	sga_alumnos.calidad = 'A'
				$where
		";

		return guarani_db::consultar($sql);
	}


	/**Newsol (NUEVO) - Retorna el listado de inscriptos a cursadas (nominal) por año académico
     	*/
	function get_listado_inscriptos_cursada_testing($where='')
	{
		if ($where) {
			$where = "AND $where";
		}

		$anio = substr($where,24,4);
		$where = substr($where,29, strlen($where));

		$sql = "select negocio_nsg.f_generar_inscriptos_cursadas($anio)";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	sga_propuestas.nombre as nombre_propuesta,
					t.anio,
					mdp_personas.apellido, 
					mdp_personas.nombres,
					mdp_personas_documentos.nro_documento,
					(case t.ing_marzo when 1 then character varying 'Inscripto' else character varying '' end) as ing_marzo,
					(case t.reinsc_marzo when 1 then character varying 'Inscripto' else character varying '' end) as reinsc_marzo,
					(case t.ing_julio when 1 then character varying 'Inscripto' else character varying '' end) as ing_julio,
					(case t.reinsc_julio when 1 then character varying 'Inscripto' else character varying '' end) as reinsc_julio,
					(case t.ing_agosto when 1 then character varying 'Inscripto' else character varying '' end) as ing_agosto,
					(case t.reinsc_agosto when 1 then character varying 'Inscripto' else character varying '' end) as reinsc_agosto,
					(case t.ing_diciembre when 1 then character varying 'Inscripto' else character varying '' end) as ing_diciembre,
					(case t.reinsc_diciembre when 1 then character varying 'Inscripto' else character varying '' end) as reinsc_diciembre

		FROM negocio_nsg.tmp_inscriptos_cursadas t 
			JOIN sga_propuestas ON sga_propuestas.propuesta = t.propuesta
			JOIN sga_alumnos ON sga_alumnos.alumno = t.alumno
			JOIN mdp_personas ON sga_alumnos.persona = mdp_personas.persona
			JOIN mdp_personas_documentos ON mdp_personas_documentos.persona = mdp_personas.persona 
		WHERE mdp_personas_documentos.tipo_documento = 0

		$where
		ORDER BY sga_propuestas.nombre
		";	

		return guarani_db::consultar($sql);
	}


	/**Newsol (NUEVO) - Retorna el listado de inscriptos (nominal) por año de cursada 
     	*/
	function get_listado_inscriptos_x_anio_de_cursada($where='')
	{
		if ($where) {
			$where = "AND $where";
		}

		$sql = "SELECT DISTINCT 	
					sga_propuestas.nombre		as nombre_propuesta,
					sga_elementos_plan.anio_de_cursada,
					mdp_personas.apellido, 
					mdp_personas.nombres,
					mdp_personas_documentos.nro_documento
			FROM		sga_alumnos
					JOIN sga_insc_cursada ON sga_alumnos.alumno = sga_insc_cursada.alumno
					JOIN vw_comisiones ON vw_comisiones.comision = sga_insc_cursada.comision
					JOIN sga_elementos_revision ON sga_elementos_revision.elemento = vw_comisiones.elemento
					JOIN sga_elementos_plan ON sga_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN mdp_personas ON sga_alumnos.persona = mdp_personas.persona
					JOIN mdp_personas_documentos ON mdp_personas_documentos.persona = mdp_personas.persona
			WHERE	mdp_personas_documentos.tipo_documento = 0 
					$where
		";

		return guarani_db::consultar($sql);
	}


	/**Newsol (NUEVO) - Retorna el reporte de rendimiento académico 
   	*/
	function get_rendimiento_academico($where='')
	{

		if ($where) {
			$where = "WHERE $where";
		}

		$sql = "select negocio_nsg.f_generar_rendimiento_academico()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	sga_propuestas.nombre as nombre_propuesta,
					t.turno_examen as nombre_turno_examen,
					t.anio_de_cursada,
					t.total, 
					coalesce(a.aprobados,0) as aprobados, 
					coalesce(r.reprobados,0) as reprobados, 
					coalesce(u.ausentes,0) as ausentes
			FROM negocio_nsg.tmp_total t
			LEFT JOIN negocio_nsg.tmp_aprobados a ON t.propuesta = a.propuesta AND t.turno_examen = a.turno_examen AND t.anio_de_cursada = a.anio_de_cursada
			LEFT JOIN negocio_nsg.tmp_reprobados r ON t.propuesta = r.propuesta AND t.turno_examen = r.turno_examen AND t.anio_de_cursada = r.anio_de_cursada
			LEFT JOIN negocio_nsg.tmp_ausentes u ON t.propuesta = u.propuesta AND t.turno_examen = u.turno_examen AND t.anio_de_cursada = u.anio_de_cursada
			LEFT JOIN sga_propuestas ON t.propuesta = sga_propuestas.propuesta
			$where
		";

		return guarani_db::consultar($sql);
	}

	/**Newsol (NUEVO) - Retorna el listado de bajas a cursadas 
     	*/
	function get_listado_bajas_cursada($where='')
	{
		if ($where) {
			$where = "AND $where";
		}

		$sql = "SELECT  	distinct sga_propuestas.nombre		as nombre_propuesta,
					mdp_personas.apellido, 
					mdp_personas.nombres,
					mdp_personas_documentos.nro_documento,
					(select i.email from mdp_personas_contactos i where i.persona = mdp_personas.persona and i.contacto_tipo in ('MP', 'MI') limit 1) as email, 
					(select max(h.fecha) from sga_alumnos_hist_calidad h where h.alumno = sga_alumnos.alumno and h.calidad = 'A' and h.motivo_calidad = 1) as fecha_ingreso,
					coalesce((select p.nombre from sga_periodos p, sga_alumnos_hist_calidad h  where h.alumno = sga_alumnos.alumno and h.calidad = 'A' and h.motivo_calidad = 1 and date(h.fecha) between p.fecha_inicio and p.fecha_fin and p.periodo_generico in (2,3) limit 1),'PRIMER CUATRIMESTRE 2013') as periodo_ingreso,
					(select nombre from sga_turnos_cursadas where sga_comisiones.turno = sga_turnos_cursadas.turno) as turno
			FROM	sga_comisiones 
					JOIN vw_actividades_plan ON sga_comisiones.elemento = vw_actividades_plan.elemento
					JOIN vw_insc_cursada_log ON sga_comisiones.comision = vw_insc_cursada_log.comision
					JOIN sga_alumnos ON vw_insc_cursada_log.alumno = sga_alumnos.alumno
					JOIN mdp_personas ON sga_alumnos.persona = mdp_personas.persona
					JOIN mdp_personas_documentos ON mdp_personas.persona = mdp_personas_documentos.persona
					JOIN sga_periodos_lectivos ON sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
					JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
					LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
					JOIN sga_propuestas ON sga_propuestas.propuesta = vw_actividades_plan.propuesta
			WHERE	mdp_personas_documentos.tipo_documento = 0 
				$where
		";

		return guarani_db::consultar($sql);
	}

	/**Newsol (NUEVO) - Retorna el reporte de retencion_alumnos
     	*/
	function get_retencion_alumnos($where)
	{
		$anio_ingreso = substr($where,18,4);
		$where = substr($where,23, strlen($where));

		$sql = "select negocio_nsg.f_generar_retencion_alumnos($anio_ingreso)";
		guarani_db::consultar($sql);

		$nom_anio_ingreso = 'Anio_'. $anio_ingreso;

		$sql = "SELECT DISTINCT	sga_propuestas.nombre as nombre_propuesta,
					sum(anio1) as anio1,
					sum(anio2) as anio2,
					sum(anio3) as anio3,
					sum(anio4) as anio4,
					sum(anio5) as anio5,
					sum(anio6) as anio6,
					sum(anio7) as anio7,
					sum(anio8) as anio8,
					sum(anio9) as anio9,
					sum(anio10) as anio10
		FROM negocio_nsg.tmp_retencion_alumnos t,
			sga_propuestas 
			WHERE t.propuesta = sga_propuestas.propuesta
			$where
			GROUP BY sga_propuestas.nombre
		";

		return guarani_db::consultar($sql);
	}

}
?>