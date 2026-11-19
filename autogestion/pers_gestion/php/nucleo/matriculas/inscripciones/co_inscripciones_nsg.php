<?php
/**
 * Consultas relativas a Inscripciones
 *
 *
 * @package Matrículas
 * @subpackage Inscripciones
 */
class co_inscripciones_nsg extends co_inscripciones
{

	/**Newsol - Se agrega el mail y se hace una distinción entre grado y diplomaturas, por la diferencia entre propuestas y cursos
	 * Retorna el listado de inscriptos a propuesta.
	 * Si el usuario logueado tiene perfil de datos con dominio "Responsables Académicas"
	 * restringe las inscripciones a aquellas propuestas que gestiona la RA.
	 * @param string $where
	 * @param string $order
	 * @return array
	 */
	function get_inscriptos_perfil_de_datos($where = '', $order = '')
	{
		if ($where) {
			$where = "AND $where";
		}

                if ($order) {
			$order = "ORDER BY $order";
		}
	
		$where_sin_anio = substr($where,52, strlen($where));
		$sql = "SELECT		vw_perfil_de_datos_propuestas.nombre, 
							vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento,
                                                        vw_personas.persona,
							sga_alumnos.propuesta,
							vw_perfil_de_datos_propuestas.propuesta_descripcion,
							sga_planes.codigo as plan_codigo,
							sga_planes_versiones.version,
							sga_alumnos.ubicacion,
							sga_ubicaciones.nombre as ubicacion_nombre,
							sga_propuestas_aspira.fecha_inscripcion,
							" . guarani_sql::get_case('sga_situacion_aspirante', 'resultado_asp', 'inscripcion_estado') . ",
							sga_tipos_ingreso.nombre as ingreso_tipo_nombre,
							sga_modalidad_cursada.nombre as modalidad_cursada_nombre,
							vw_periodos_insc_propuesta.anio_academico,
							vw_periodos_insc_propuesta.periodo_inscripcion,
							vw_periodos_insc_propuesta.fecha_inicio,
							vw_periodos_insc_propuesta.fecha_fin,
							vw_periodos_insc_propuesta.anio_academico || ' - ' || vw_periodos_insc_propuesta.nombre || ' (' || to_char(vw_periodos_insc_propuesta.fecha_inicio,'" . comunes::formato_fecha_visual . "') || ' al ' || to_char(vw_periodos_insc_propuesta.fecha_fin,'" . comunes::formato_fecha_visual . "') || ')' as anio_periodo_desc,
							(select m.email from mdp_personas_contactos m where vw_personas.persona = m.persona and m.contacto_tipo in ('MP', 'MI') and email is not null limit 1) as email	
				FROM		sga_propuestas_aspira
							JOIN vw_personas ON sga_propuestas_aspira.persona = vw_personas.persona
							JOIN sga_alumnos ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
							JOIN vw_perfil_de_datos_propuestas ON sga_propuestas_aspira.propuesta = vw_perfil_de_datos_propuestas.propuesta
							JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
							JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
							JOIN sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_modalidad_cursada ON sga_alumnos.modalidad = sga_modalidad_cursada.modalidad
							JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
							JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
							LEFT JOIN sga_tipos_ingreso ON sga_tipos_ingreso.tipo_ingreso = sga_propuestas_aspira.tipo_ingreso
				WHERE TRUE	
							$where
				
				UNION
				SELECT DISTINCT		vw_perfil_de_datos_propuestas.nombre,
							vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento,
                            				vw_personas.persona,
							sga_alumnos.propuesta,
							vw_perfil_de_datos_propuestas.propuesta_descripcion,
							sga_planes.codigo as plan_codigo,
							sga_planes_versiones.version,
							sga_alumnos.ubicacion,
							sga_ubicaciones.nombre as ubicacion_nombre,
							sga_propuestas_aspira.fecha_inscripcion,
							" . guarani_sql::get_case('sga_situacion_aspirante', 'resultado_asp', 'inscripcion_estado') . ",
							sga_tipos_ingreso.nombre as ingreso_tipo_nombre,
							sga_modalidad_cursada.nombre as modalidad_cursada_nombre,
							vw_periodos_insc_propuesta.anio_academico,
							vw_periodos_insc_propuesta.periodo_inscripcion,
							vw_periodos_insc_propuesta.fecha_inicio,
							vw_periodos_insc_propuesta.fecha_fin,
							vw_periodos_insc_propuesta.anio_academico || ' - ' || vw_periodos_insc_propuesta.nombre || ' (' || to_char(vw_periodos_insc_propuesta.fecha_inicio,'" . comunes::formato_fecha_visual . "') || ' al ' || to_char(vw_periodos_insc_propuesta.fecha_fin,'" . comunes::formato_fecha_visual . "') || ')' as anio_periodo_desc,
							(select m.email from mdp_personas_contactos m where vw_personas.persona = m.persona and m.contacto_tipo in ('MP', 'MI') and email is not null limit 1) as email	
				FROM			sga_propuestas_aspira
							JOIN vw_personas ON sga_propuestas_aspira.persona = vw_personas.persona
							JOIN sga_alumnos ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
							JOIN vw_perfil_de_datos_propuestas ON sga_propuestas_aspira.propuesta = vw_perfil_de_datos_propuestas.propuesta
							JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
							JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
							JOIN sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_modalidad_cursada ON sga_alumnos.modalidad = sga_modalidad_cursada.modalidad
							JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
							JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
							LEFT JOIN sga_tipos_ingreso ON sga_tipos_ingreso.tipo_ingreso = sga_propuestas_aspira.tipo_ingreso

				WHERE 			sga_propuestas_aspira.fecha_rechazo is not null
							AND vw_perfil_de_datos_propuestas.propuesta_tipo in (208)
							AND EXISTS (SELECT * FROM vw_insc_cursada,
     						                   	sga_comisiones
         						               	LEFT JOIN sga_catedras ON sga_catedras.catedra = sga_comisiones.catedra,
                        						sga_periodos_lectivos,
                        						sga_periodos,
                        						sga_inscripciones_estados,
                        						vw_elementos_plan
                       							WHERE sga_alumnos.alumno = vw_insc_cursada.alumno
                        						AND sga_comisiones.comision = vw_insc_cursada.comision
                       							AND sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
                        						AND sga_periodos.periodo = sga_periodos_lectivos.periodo
                        						AND sga_inscripciones_estados.estado = vw_insc_cursada.estado
                        						AND vw_elementos_plan.elemento = sga_comisiones.elemento
                        						AND vw_elementos_plan.plan_version = vw_insc_cursada.plan_version
                        						AND sga_alumnos.propuesta = vw_perfil_de_datos_propuestas.propuesta
									AND sga_periodos.anio_academico = date_part('year', now()))
							$where_sin_anio
							$order

		";
		return guarani_db::consultar($sql);
	}

	//Newsol (NUEVO) - Genera el reporte de total de reinscriptos a propuestas (aquellos alumnos que se anotaron a propuesta en años anteriores al actual)
	function get_reporte_total_reinscriptos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT 		
							vw_perfil_de_datos_propuestas.nombre as propuesta_datos,
							count(distinct sga_alumnos.alumno) as total_reinscriptos
	 		FROM        			sga_alumnos, 
                            				vw_comisiones, 
                            				sga_elementos_revision, 
                            				sga_elementos_plan, 
							sga_insc_cursada, 
							sga_propuestas_aspira, 
                            				vw_perfil_de_datos_propuestas
                	WHERE       			sga_alumnos.persona = sga_propuestas_aspira.persona AND
							sga_alumnos.propuesta  = sga_propuestas_aspira.propuesta AND
							vw_comisiones.elemento = sga_elementos_revision.elemento AND
                            				sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision AND
                            				sga_alumnos.propuesta = vw_perfil_de_datos_propuestas.propuesta AND
                            				sga_alumnos.alumno = sga_insc_cursada.alumno AND
                            				vw_comisiones.comision = sga_insc_cursada.comision AND
                            				vw_perfil_de_datos_propuestas.propuesta_tipo not in (208) AND
							sga_propuestas_aspira.anio_academico < vw_comisiones.anio_academico						
							$where
				GROUP BY  		vw_perfil_de_datos_propuestas.nombre

		";
		return guarani_db::consultar($sql);
	}

	//Newsol (NUEVO) - Genera el reporte de bajas de alumnos
	function get_bajas_de_alumnos($where = '')
	{
		if ($where) {
			$where = " AND $where";
		}

		$sql = "select negocio_nsg.f_generar_reporte_bajas()";
		guarani_db::consultar($sql);

		$sql = "SELECT	DISTINCT	apellido_nombres,
						tipo_nro_documento,
						propuesta,
     						propuesta_descripcion,
						fecha_inscripcion,
						anio_baja,
						periodo_inscripcion,
						anio_periodo_desc,
						fecha_baja,
						motivo_baja,
						observaciones,
						interfaz
				FROM	negocio_nsg.tmp_baja_alumnos
				WHERE 	TRUE
				$where
				ORDER BY 	3,1
		";


		return guarani_db::consultar($sql);
	}


	/**Newsol (NUEVO) - Retorna el listado de inscriptos a propuesta con detalle de inscripción a cursadas con distintos cortes
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_inscriptos_propuestas_y_cursadas($where = '')
	{
		if ($where) {
			$where = "AND $where";
		}
		$anio_sin_quote = substr($where,46, 4);
		$anio = toba::db()->quote($anio_sin_quote);

		$fecha_corte_pc_sin_quote = $anio_sin_quote.'-04-15';
		$fecha_corte_pc = toba::db()->quote($fecha_corte_pc_sin_quote);

		$fecha_corte_sc_sin_quote = $anio_sin_quote.'-09-15';
		$fecha_corte_sc = toba::db()->quote($fecha_corte_sc_sin_quote);

		$sql = "SELECT		vw_perfil_de_datos_propuestas.nombre, 
							vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento,
                                                        vw_personas.persona,
							sga_alumnos.propuesta,
							vw_perfil_de_datos_propuestas.propuesta_descripcion,
							sga_planes.codigo as plan_codigo,
							sga_planes_versiones.version,
							sga_propuestas_aspira.fecha_inscripcion,
							" . guarani_sql::get_case('sga_situacion_aspirante', 'resultado_asp', 'inscripcion_estado') . ",
							sga_propuestas_aspira.anio_academico,
							vw_periodos_insc_propuesta.periodo_inscripcion,
							vw_periodos_insc_propuesta.anio_academico || ' - ' || vw_periodos_insc_propuesta.nombre || ' (' || to_char(vw_periodos_insc_propuesta.fecha_inicio,'" . comunes::formato_fecha_visual . "') || ' al ' || to_char(vw_periodos_insc_propuesta.fecha_fin,'" . comunes::formato_fecha_visual . "') || ')' as anio_periodo_desc,
							(SELECT COUNT(*) FROM vw_insc_cursada,
     						                   	sga_comisiones,
                        						sga_periodos_lectivos,
                        						sga_periodos,
                        						sga_inscripciones_estados,
                        						vw_elementos_plan
                       							WHERE sga_alumnos.alumno = vw_insc_cursada.alumno
                        						AND sga_comisiones.comision = vw_insc_cursada.comision
                       							AND sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
                        						AND sga_periodos.periodo = sga_periodos_lectivos.periodo
                        						AND sga_inscripciones_estados.estado = vw_insc_cursada.estado
                        						AND vw_elementos_plan.elemento = sga_comisiones.elemento
                        						AND vw_elementos_plan.plan_version = vw_insc_cursada.plan_version
                        						AND sga_alumnos.propuesta = vw_perfil_de_datos_propuestas.propuesta
									AND sga_periodos.anio_academico = $anio
							 		AND sga_periodos.periodo_generico = 2
									AND vw_insc_cursada.fecha_inscripcion <= $fecha_corte_pc) AS cant_cursadas_primer_cuatrimestre,
							(SELECT COUNT(*) FROM vw_insc_cursada,
     						                   	sga_comisiones,
                        						sga_periodos_lectivos,
                        						sga_periodos,
                        						sga_inscripciones_estados,
                        						vw_elementos_plan
                       							WHERE sga_alumnos.alumno = vw_insc_cursada.alumno
                        						AND sga_comisiones.comision = vw_insc_cursada.comision
                       							AND sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
                        						AND sga_periodos.periodo = sga_periodos_lectivos.periodo
                        						AND sga_inscripciones_estados.estado = vw_insc_cursada.estado
                        						AND vw_elementos_plan.elemento = sga_comisiones.elemento
                        						AND vw_elementos_plan.plan_version = vw_insc_cursada.plan_version
                        						AND sga_alumnos.propuesta = vw_perfil_de_datos_propuestas.propuesta
							 		AND sga_periodos.periodo_generico = 3
									AND sga_periodos.anio_academico = $anio
									AND vw_insc_cursada.fecha_inscripcion <= $fecha_corte_sc) AS cant_cursadas_segundo_cuatrimestre
							
				FROM			sga_propuestas_aspira
							JOIN vw_personas ON sga_propuestas_aspira.persona = vw_personas.persona
							JOIN sga_alumnos ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
							JOIN vw_perfil_de_datos_propuestas ON sga_propuestas_aspira.propuesta = vw_perfil_de_datos_propuestas.propuesta
							JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
							JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
							JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
							JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp

				WHERE vw_perfil_de_datos_propuestas.propuesta_tipo not in (208)
	
							$where
				
				UNION
				SELECT DISTINCT		vw_perfil_de_datos_propuestas.nombre,
							vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento,
                            				vw_personas.persona,
							sga_alumnos.propuesta,
							vw_perfil_de_datos_propuestas.propuesta_descripcion,
							sga_planes.codigo as plan_codigo,
							sga_planes_versiones.version,
							sga_propuestas_aspira.fecha_inscripcion,
							" . guarani_sql::get_case('sga_situacion_aspirante', 'resultado_asp', 'inscripcion_estado') . ",
							sga_propuestas_aspira.anio_academico,
							vw_periodos_insc_propuesta.periodo_inscripcion,
							vw_periodos_insc_propuesta.anio_academico || ' - ' || vw_periodos_insc_propuesta.nombre || ' (' || to_char(vw_periodos_insc_propuesta.fecha_inicio,'" . comunes::formato_fecha_visual . "') || ' al ' || to_char(vw_periodos_insc_propuesta.fecha_fin,'" . comunes::formato_fecha_visual . "') || ')' as anio_periodo_desc,				
							0 AS cant_cursadas_primer_cuatrimestre,
							0 AS cant_cursadas_segundo_cuatrimestre
				FROM			sga_propuestas_aspira
							JOIN vw_personas ON sga_propuestas_aspira.persona = vw_personas.persona
							JOIN sga_alumnos ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
							JOIN vw_perfil_de_datos_propuestas ON sga_propuestas_aspira.propuesta = vw_perfil_de_datos_propuestas.propuesta
							JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
							JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
							JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
							JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
				
				WHERE 			sga_propuestas_aspira.fecha_rechazo is not null
							AND vw_perfil_de_datos_propuestas.propuesta_tipo in (208)
				$where
				ORDER BY 	1,2,3
		";

		//WHERE			(sga_propuestas_aspira.anio_academico <> date_part('year', now()) and date_part('year', sga_propuestas_aspira.fecha_rechazo) = date_part('year', now())) 

		return guarani_db::consultar($sql);
	}

	//-----------------------------------------------------------------------------------
	//-- Motivos de Baja de Inscripciones a Propuesta para Autogestión - Newsol (01/2025)
	//-----------------------------------------------------------------------------------
	/**
	 * Obtiene una lista de motivos de baja de inscripciones a propuesta según filtro especificado.
	 *
	 */
	function get_listado_motivos_baja_inscripcion($where = '')
	{
		if ($where != '') {
			$where = ' WHERE ' . $where;
		}

		$sql = "SELECT 
					sga_inscripciones_motivos_baja.motivo_baja,
					sga_inscripciones_motivos_baja.nombre,
					sga_inscripciones_motivos_baja.descripcion,
					sga_inscripciones_motivos_baja.disponible_autogestion
					
				FROM 
					negocio_nsg.sga_inscripciones_motivos_baja as sga_inscripciones_motivos_baja
				$where
    			ORDER BY
    				sga_inscripciones_motivos_baja.nombre
		
    			";

		return guarani_db::consultar($sql);
	}

	function get_listado_motivos_baja_inscripcion_activos($where = '')
	{
		$filtro = "sga_inscripciones_motivos_baja.disponible_autogestion = '" . comunes::si . "'";
		if ($where) {
			$filtro .= ' AND ' . $where;
		}
		return $this->get_listado_motivos_baja_inscripcion($filtro);
	}

	/**
	 *  Valida si un nombre de motivo de baja de inscripciones se puede usar o no.
	 */
	function validar_nombre_motivo_baja_insc($nombre, $motivo)
	{
		if (!isset($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);

		$sql = 'SELECT	count(sga_inscripciones_motivos_baja.motivo_baja) as cant
                	FROM 	negocio_nsg.sga_inscripciones_motivos_baja as sga_inscripciones_motivos_baja
			WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('sga_inscripciones_motivos_baja.nombre', $nombre) . '
				';

		if (!is_null($motivo)) {
			$motivo = toba::db()->quote($motivo);
			$sql .= " AND sga_inscripciones_motivos_baja.motivo_baja <> $motivo";
		}

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
        Obtiene una lista de los motivos de baja de cursadas de un alumno según el filtro recibido
    	*/
	function get_motivos_baja()
	{

		$sql = "SELECT sga_inscripciones_motivos_baja.motivo_baja,
					sga_inscripciones_motivos_baja.nombre
			FROM negocio_nsg.sga_inscripciones_motivos_baja as sga_inscripciones_motivos_baja
			WHERE 	sga_inscripciones_motivos_baja.disponible_autogestion = 'S'
			";
		
		return guarani_db::consultar($sql);	
	}
}
?>