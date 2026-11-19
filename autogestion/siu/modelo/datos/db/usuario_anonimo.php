<?php

namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class usuario_anonimo
{
	/**
	 * Devuelve actividades de los planes de una carrera o todas las materias de la base
	 * En codigo_nombre viene parte del codigo visible o parte del nombre de la actividad
	 * parametros: materia, propuesta, plan, persona
	 * param_null: persona
	 * no_quote: materia
	 * cache: memoria
	 * filas: n
	 */
	function lista_actividades_propuesta($parametros)
	{
		$filtro_actividad = '';

		if ($parametros['materia'] != "") {
			$termino = $parametros['materia'];
			$term_actividad = kernel::db()->quote_like($termino, '%', '%');
			$filtro_actividad = " AND (
                                    f_limpiar_acentos(e.nombre) ILIKE $term_actividad OR
                                    f_limpiar_acentos(e.codigo) ILIKE $term_actividad
                                    )";
		}

		$filtro_plan = '';

		if ($parametros['plan'] != "''") {
			$filtro_plan = " AND e.plan = {$parametros['plan']}";
		}

		$filtro_persona = '';
		$filtro_persona2 = '';
		if ($parametros['persona'] != "''") {
			$filtro_persona = " AND e.plan_version IN (SELECT plan_version FROM sga_alumnos WHERE persona = {$parametros['persona']} ";
			$filtro_persona2 = " AND e.elemento IN (SELECT er.elemento 
			                                          FROM sga_alumnos as a, sga_elementos_plan as ep, sga_elementos_revision as er
													  WHERE a.persona = {$parametros['persona']}
													    AND ep.plan_version = a.plan_version
													    AND er.elemento_revision = ep.elemento_revision
													)
 													  ";
		}

		$estado_activo = kernel::db()->quote(\elemento::estado_activo);
		
		// Se seleccionó una propuesta. Se devuelven todas las actividads de los planes de esa propuesta
		if ($parametros['propuesta'] != "''") {
			$sql = "SELECT DISTINCT e.elemento, e.codigo, e.nombre
                      FROM vw_actividades_plan as e
                     WHERE e.propuesta = {$parametros['propuesta']}
                       AND e.estado = $estado_activo
                       $filtro_plan
                       $filtro_actividad
					   $filtro_persona
					 ORDER BY e.nombre, e.codigo  
                    ";
		} else {
			// No se seleccionó una propuesta. Recupera todas las actividades de la institucion.
			$sql = "SELECT e.elemento, e.codigo, e.nombre
                      FROM vw_actividades as e
                     WHERE e.estado = $estado_activo
                       $filtro_actividad
					   $filtro_persona2
					 ORDER BY e.nombre, e.codigo  
                      ";
		}
		$datos = kernel::db()->consultar($sql);

		$nuevo = array();
		foreach ($datos as $clave => $dato) {
			$nuevo[$clave] = $dato;
			$nuevo[$clave]['_ID_'] = catalogo::generar_id($dato['elemento']);
		}
		return $nuevo;
	}

	/**
	 * parametros:  ubicacion, propuesta, plan, elemento, fecha_desde, fecha_hasta, instancia
	 * cache: no
	 * filas: n
	 */
	function mesas_examen_usuario_anonimo($parametros)
	{
		$formato_fecha_bd = kernel::db()->quote(\comunes::formato_fecha_bd);
		$formato_fecha_hora_visual_sin_segundos = kernel::db()->quote(\comunes::formato_fecha_hora_visual_sin_segundos);
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		$mesa_estado_activo = kernel::db()->quote(\mesa_de_examen::mesa_estado_activo);
		$mesa_estado_suspendido = kernel::db()->quote(\mesa_de_examen::mesa_estado_suspendido);
		$si = kernel::db()->quote(\comunes::si);

		// Lista mesas de examen de turnos de examen vigentes o futuros que aun no finalizaron.
		// Filtros
		$filtro_ubicacion = '';
		if ($parametros['ubicacion'] != "''") {
			$filtro_ubicacion = " AND vw_mesas_examen.mesa_examen_ubicacion = {$parametros['ubicacion']} ";
		}

		$filtro_actividad = '';
		if ($parametros['elemento'] != "''") {
			$filtro_actividad = " AND vw_mesas_examen.mesa_examen_elemento = {$parametros['elemento']} ";
		}

		$filtro_tipo_insc = '';
		if ($parametros['instancia'] != "''") {
			$filtro_tipo_insc = " AND EXISTS (SELECT 1 FROM sga_mesas_examen_instancias as mi 
                                               WHERE mi.mesa_examen = vw_mesas_examen.mesa_examen 
                                                 AND mi.instancia = {$parametros['instancia']})  ";
		}

		$filtro_fecha_desde = '';
		if ($parametros['fecha_desde'] != "''") {
			// Viene en formato yyyy-mm-dd
			$filtro_fecha_desde = " AND vw_mesas_examen.mesa_examen_fecha >= to_date({$parametros['fecha_desde']}, $formato_fecha_bd) ";
		}

		$filtro_fecha_hasta = '';
		if ($parametros['fecha_hasta'] != "''") {
			// Viene en formato yyyy-mm-dd
			$filtro_fecha_hasta = " AND vw_mesas_examen.mesa_examen_fecha <= to_date({$parametros['fecha_hasta']}, $formato_fecha_bd) ";
		}

		$filtro_propuesta = '';
		if ($parametros['propuesta'] != "''") {
			$filtro_propuesta = " AND EXISTS (SELECT 1 FROM sga_mesas_examen_propuestas as mp 
                                               WHERE mp.mesa_examen = vw_mesas_examen.mesa_examen 
                                                 AND mp.propuesta  = {$parametros['propuesta']} ) ";
		}

		$filtro_plan = '';
		if ($parametros['plan'] != "''") {
			// Si selecciono plan veo que sea una mesa para ese plan (la propuesta ya se filtro arriba)...
			$filtro_plan .= " AND EXISTS (SELECT 1 FROM sga_mesas_examen_propuestas as mp 
                                          WHERE mp.mesa_examen = vw_mesas_examen.mesa_examen 
                                            AND mp.propuesta  = {$parametros['propuesta']}
                                            AND mp.plan  = {$parametros['plan']} ) ";
		}


		// ****************************************************************************
		// Consulta de mesas de examen con fecha de publicación menor o igual a hoy y turnos de examen que no han finalizado.
		// Ordenado por nombre de actividad + fecha + hora inicio
		// ****************************************************************************
		$sql = "SELECT
                    vw_mesas_examen.turno_examen as turno_examen,
                    vw_mesas_examen.turno_examen_nombre as turno_examen_nombre,
                    vw_mesas_examen.mesa_examen as mesa_examen,
                    vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
                    vw_mesas_examen.anio_academico as anio_academico,
                    
                    vw_mesas_examen.mesa_examen_elemento as actividad,
                    sga_elementos.codigo as actividad_codigo,
                                     
                    sga_elementos.nombre as actividad_nombre,
                    -- f_nombre_actividad_mesa_examen ....

                    vw_mesas_examen.mesa_examen_catedra as catedra,
                    sga_catedras.nombre as catedra_nombre,
                    vw_mesas_examen.mesa_examen_fecha as fecha_examen_orden,
                    vw_mesas_examen.mesa_examen_hora_inicio as hora_inicio_orden,
                    to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha_examen,
                    vw_mesas_examen.llamado as llamado,
                    f_docentes_mesa_examen(vw_mesas_examen.llamado_mesa, true) as docentes,
                    f_instancias_mesa_examen(vw_mesas_examen.mesa_examen) as instancias_mesa,
                    vw_mesas_examen.mesa_examen_hora_inicio as hora_inicio,
                    vw_mesas_examen.mesa_examen_hora_finalizacion as hora_finalizacion,                    
                    to_char(vw_mesas_examen.mesa_examen_hora_inicio::time, $formato_hora_visual) as hora_inicio,
                    to_char(vw_mesas_examen.mesa_examen_hora_finalizacion::time, $formato_hora_visual) as hora_finalizacion,
					get_espacios_mesa_examen(vw_mesas_examen.llamado_mesa, 7) || ' - ' || get_espacios_mesa_examen(vw_mesas_examen.llamado_mesa, 1) as edificio_aula,
                    sga_mesas_examen.observaciones as observaciones,
                    f_propuestas_mesa_examen(vw_mesas_examen.mesa_examen, 2) as propuestas,
                    
					to_char(COALESCE(e.fecha_inicio, pi.fecha_inicio), $formato_fecha_hora_visual_sin_segundos) as f_inicio_insc,
                    to_char(COALESCE(-- Excepciones
									CASE e.tipo_fin
										WHEN 1 THEN (CASE 
														WHEN e.fecha_fin < CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp) THEN e.fecha_fin 
														ELSE CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp) 
													END)
                                        WHEN 2 THEN get_fecha_fin_examen(cast(e.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', e.dias_previos_fin, false, vw_mesas_examen.mesa_examen_ubicacion)
                                        WHEN 3 THEN get_fecha_fin_examen(cast(e.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', e.dias_previos_fin, true, vw_mesas_examen.mesa_examen_ubicacion)
                                        WHEN 4 THEN get_fecha_fin_examen(cast(e.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', e.hs_previas_fin, false, vw_mesas_examen.mesa_examen_ubicacion)
                                        WHEN 5 THEN get_fecha_fin_examen(cast(e.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', e.hs_previas_fin, true, vw_mesas_examen.mesa_examen_ubicacion)
										ELSE cast(NULL as timestamp)
									END,
									-- Periodo de inscripcion
									CASE pi.tipo_fin
										WHEN 1 THEN (CASE 
														WHEN pi.fecha_fin < CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp) THEN pi.fecha_fin
														ELSE CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp)
													END)
										WHEN 2 THEN get_fecha_fin_examen(cast(pi.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', pi.dias_previos_fin, false, vw_mesas_examen.mesa_examen_ubicacion)
										WHEN 3 THEN get_fecha_fin_examen(cast(pi.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', pi.dias_previos_fin, true, vw_mesas_examen.mesa_examen_ubicacion)
										WHEN 4 THEN get_fecha_fin_examen(cast(pi.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', pi.hs_previas_fin, false, vw_mesas_examen.mesa_examen_ubicacion)
										WHEN 5 THEN get_fecha_fin_examen(cast(pi.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', pi.hs_previas_fin, true, vw_mesas_examen.mesa_examen_ubicacion)
										ELSE cast(NULL as timestamp)
									END
									) , $formato_fecha_hora_visual_sin_segundos) as f_fin_insc, 
					to_char(COALESCE(-- Excepciones
								CASE e.tipo_tope_bajas
								WHEN 0 THEN CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp) 
								WHEN 1 THEN e.fecha_tope_bajas
								WHEN 2 THEN get_fecha_fin_examen(cast(e.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', e.dias_previos_tope_bajas, false, vw_mesas_examen.mesa_examen_ubicacion)
								WHEN 3 THEN get_fecha_fin_examen(cast(e.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', e.dias_previos_tope_bajas, true, vw_mesas_examen.mesa_examen_ubicacion)
								WHEN 4 THEN get_fecha_fin_examen(cast(e.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', e.hs_previas_tope_bajas, false, vw_mesas_examen.mesa_examen_ubicacion)
								WHEN 5 THEN get_fecha_fin_examen(cast(e.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', e.hs_previas_tope_bajas, true, vw_mesas_examen.mesa_examen_ubicacion)
								ELSE cast(NULL as timestamp)
								END,
								-- Periodo de inscripcion
								CASE pi.tipo_tope_bajas
								WHEN 0 THEN CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp) 
								WHEN 1 THEN pi.fecha_tope_bajas
								WHEN 2 THEN get_fecha_fin_examen(cast(pi.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', pi.dias_previos_tope_bajas, false, vw_mesas_examen.mesa_examen_ubicacion)
								WHEN 3 THEN get_fecha_fin_examen(cast(pi.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'D', pi.dias_previos_tope_bajas, true, vw_mesas_examen.mesa_examen_ubicacion)
								WHEN 4 THEN get_fecha_fin_examen(cast(pi.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', pi.hs_previas_tope_bajas, false, vw_mesas_examen.mesa_examen_ubicacion)
								WHEN 5 THEN get_fecha_fin_examen(cast(pi.fecha_inicio as timestamp), CAST(sga_llamados_mesa.fecha::text || ' ' || sga_llamados_mesa.hora_inicio::text as timestamp), 'H', pi.hs_previas_tope_bajas, true, vw_mesas_examen.mesa_examen_ubicacion)
								ELSE pi.fecha_tope_bajas
								END
   							), $formato_fecha_hora_visual_sin_segundos) as f_tope_baja_mesa,   
                    to_char(vw_mesas_examen.llamado_fecha_inicio, $formato_fecha_visual) as f_inicio_llamado,
                    to_char(vw_mesas_examen.llamado_fecha_fin, $formato_fecha_visual) as f_fin_llamado,
					vw_mesas_examen.llamado_mesa_estado as llamado_mesa_estado,
					CASE vw_mesas_examen.llamado_mesa_estado
					   WHEN $mesa_estado_suspendido THEN 'Suspendido'
					   WHEN $mesa_estado_activo THEN ''
					END as llamado_mesa_estado_desc
                    
               FROM vw_mesas_examen
               JOIN sga_turnos_examen ON sga_turnos_examen.turno_examen = vw_mesas_examen.turno_examen
               JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
               JOIN sga_llamados_mesa ON sga_llamados_mesa.llamado_mesa = vw_mesas_examen.llamado_mesa
			   LEFT JOIN sga_mesas_examen on vw_mesas_examen.mesa_examen=sga_mesas_examen.mesa_examen
			   LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_mesas_examen.mesa_examen_catedra
               LEFT JOIN vw_periodos_inscripcion as pi ON pi.periodo = vw_mesas_examen.llamado_periodo
               LEFT JOIN sga_llamados_mesa_excep_perinsc as e ON e.periodo_insc = pi.periodo_insc AND e.llamado_mesa = vw_mesas_examen.llamado_mesa 
					  
              WHERE vw_mesas_examen.turno_examen_fecha_fin >= CURRENT_DATE
                AND COALESCE(sga_turnos_examen.fecha_publicacion_mesas,CURRENT_DATE) <= CURRENT_DATE
                AND COALESCE(sga_turnos_examen.fecha_publicacion_mesas_docente,CURRENT_DATE) <= CURRENT_DATE
                 
                $filtro_actividad
                $filtro_ubicacion
                $filtro_propuesta
                $filtro_plan
                $filtro_tipo_insc
                $filtro_fecha_desde
                $filtro_fecha_hasta

          ORDER BY actividad_nombre, fecha_examen_orden, hora_inicio_orden";

            /*  
				AND sga_llamados_mesa.estado = $mesa_estado_activo
				-- No tiene en cuenta mesas de examen fuera de calendario
				AND sga_llamados_turno.llamado = sga_llamados_mesa.llamado
				AND vw_periodos_inscripcion.periodo = sga_llamados_turno.periodo			
                AND sga_llamados_mesa.habilitado        = $si
			*/	
		$datos = kernel::db()->consultar($sql);

		return $datos;
	}

	/**
	 * parametros: periodo_lectivo, elemento, turno, ubicacion, dia, propuesta, anio_cursada, responsable_academica
	 * cache: no
	 * filas: n
	 */
	function horarios_cursadas($parametros)
	{
		// Lista las comisiones de un período lectivo, mostrando el aula, horario de cursada, tipo de clase, etc
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		
		$aula_a_determinar = kernel::db()->quote(kernel::traductor()->trans('aula_a_determinar'));
		$edificio_a_determinar = kernel::db()->quote(kernel::traductor()->trans('edificio_a_determinar'));
		$si = kernel::db()->quote(\comunes::si);

		// Filtros
		$filtro_ubicacion = '';
		if ($parametros['ubicacion'] != "''") {
			$filtro_ubicacion = " AND sga_comisiones.ubicacion = {$parametros['ubicacion']} ";
		}

		// Que la actividad pertenezca a la propuesta y que la comision este definida para la propuesta seleccionada.
		$filtro_ra = '';
		$filtro_propuesta = '';
		if ($parametros['propuesta'] != "''") {
			$filtro_propuesta = " AND sga_comisiones.elemento IN (SELECT DISTINCT elemento FROM vw_elementos_plan WHERE propuesta = {$parametros['propuesta']})
                                  AND EXISTS (SELECT 1 FROM sga_comisiones_propuestas as cp WHERE cp.comision = sga_comisiones.comision AND cp.propuesta = {$parametros['propuesta']})
                                  ";
		} else {
			// Solo veo de filtrar por RA si es que no filtra por propuesta, ya que si selecciono propuesta no es necesario ademas filtrar por RA.
			if ($parametros['responsable_academica'] != "''") {
				$filtro_ra = " AND EXISTS (SELECT 1 
							                FROM sga_comisiones_propuestas as cp,
											     sga_propuestas_ra as pra 
										   WHERE cp.comision = sga_comisiones.comision 
											 AND pra.propuesta = cp.propuesta
											 AND pra.propuesta = {$parametros['responsable_academica']}
										)
                             ";
			}
		}

		$filtro_actividad = '';
		if ($parametros['elemento'] != "''") {
			$filtro_actividad = " AND sga_comisiones.elemento = {$parametros['elemento']} ";
		}
		$filtro_periodo_lectivo = '';
		if ($parametros['periodo_lectivo'] != "''") {
			$filtro_periodo_lectivo = " AND sga_comisiones.periodo_lectivo = {$parametros['periodo_lectivo']} ";
		}

		$filtro_anio_de_cursada = '';
		if ($parametros['anio_cursada'] != "''") {
			$estado_activo_no_vigente = kernel::db()->quote(\planes::estado_activo_no_vigente);
			$estado_activo_vigente = kernel::db()->quote(\planes::estado_activo_vigente);
			$filtro_anio_de_cursada = " AND sga_comisiones.elemento IN (SELECT DISTINCT er.elemento 
                                                                          FROM sga_elementos_plan as ep, sga_elementos_revision as er, sga_planes_versiones as pv
                                                                         WHERE er.elemento_revision = ep.elemento_revision
                                                                           AND ep.anio_de_cursada = {$parametros['anio_cursada']}
                                                                           AND pv.plan_version = ep.plan_version
                                                                           AND pv.estado IN ($estado_activo_no_vigente, $estado_activo_vigente)
                                                                        ) ";
		}

		$filtro_turno = '';
		if ($parametros['turno'] != "''") {
			$filtro_turno = " AND sga_comisiones.turno = {$parametros['turno']} ";
		}
		$filtro_dia_semana = '';
		if ($parametros['dia'] != "''") {
			$filtro_dia_semana = " AND sga_asignaciones.dia_semana = {$parametros['dia']} ";
		}

		// Consulta de comisiones 
		// Ordenado por: nombre_ubicacion + fecha inicio periodo_lectivo + periodo lectivo + nombre_actividad + nombre comision + dia semana + hora inicio clase
		$sql = "SELECT 
                     sga_comisiones.comision as comision, 
                     sga_comisiones.nombre as comision_nombre, 
                     sga_comisiones.periodo_lectivo as periodo_lectivo, 
                     sga_periodos.nombre as periodo_lectivo_nombre,
                     sga_comisiones.elemento as actividad, 
                     sga_elementos.codigo as actividad_codigo, 
                     sga_elementos.nombre as actividad_nombre,
                     sga_elementos.nombre_abreviado as actividad_nombre_red,
                     sga_comisiones.turno as turno, 
                     CASE sga_comisiones.turno
                       WHEN '1' THEN 'Mañana'
                       WHEN '2' THEN 'Tarde'
                       WHEN '3' THEN 'Noche'
                       ELSE ' '
                     END as turno_desc,
                     sga_comisiones.ubicacion as ubicacion,
                     sga_ubicaciones.nombre as ubicacion_nombre,
                     sga_comisiones.cupo as cupo,

                     -- datos de la asignacion
					 COALESCE(get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria, 7), $edificio_a_determinar) as edificio,
					 get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria, 2) as aula,
					 COALESCE(get_espacios_banda_horaria(sga_comisiones_bh.banda_horaria, 1), $aula_a_determinar) as aula_nombre,
                     sga_asignaciones.dia_semana as dia_semana,
                     to_char(sga_asignaciones.hora_inicio, $formato_hora_visual) as hs_comienzo_clase,
                     to_char(sga_asignaciones.hora_finalizacion, $formato_hora_visual) as hs_finaliz_clase,
                     COALESCE(to_char(sga_asignaciones.hora_inicio, $formato_hora_visual), '     ') || ' - ' || COALESCE(to_char(sga_asignaciones.hora_finalizacion, $formato_hora_visual),'     ') as inicio_fin_clase,
                     sga_periodos.fecha_inicio as pl_fecha_inicio,
                     sga_comisiones.cupo as cupo,
					 sga_clases_tipos.nombre as tipo_clase_desc

                  FROM sga_periodos_lectivos
					   JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
					   JOIN sga_anios_academicos ON sga_periodos.anio_academico = sga_anios_academicos.anio_academico
                       JOIN sga_comisiones ON sga_comisiones.periodo_lectivo  = sga_periodos_lectivos.periodo_lectivo
                       JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_comisiones.ubicacion
					   JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
                       JOIN sga_comisiones_bh ON sga_comisiones_bh.comision  = sga_comisiones.comision
                       JOIN sga_clases_tipos ON sga_clases_tipos.tipo_clase = sga_comisiones_bh.tipo_clase
                       JOIN sga_asignaciones ON sga_asignaciones.asignacion = sga_comisiones_bh.asignacion
					   
                 WHERE sga_periodos_lectivos.fecha_fin_dictado >= CURRENT_DATE
                   AND sga_periodos_lectivos.fecha_publicacion_comisiones <= CURRENT_DATE
				   AND sga_comisiones.inscripcion_habilitada = $si
                   $filtro_ubicacion
                   $filtro_propuesta
                   $filtro_periodo_lectivo
                   $filtro_actividad
                   $filtro_turno
				   $filtro_dia_semana
                   $filtro_anio_de_cursada
               ORDER BY 11, 21, 3, 6, 2, 
						(CASE sga_asignaciones.dia_semana
						  WHEN 'Lunes' THEN 1
						  WHEN 'Martes' THEN 2
						  WHEN 'Miercoles' THEN 3
						  WHEN 'Jueves' THEN 4
						  WHEN 'Viernes' THEN 5
						  WHEN 'Sabado' THEN 6
						  WHEN 'Domingo' THEN 7 
						 END
						 ), 17
               ";
		$datos = kernel::db()->consultar($sql);
		$aux = array();


		foreach ($datos as $dato) {
			$aux[$dato['ubicacion']]['UBICACION'] = $dato['ubicacion'];
			$aux[$dato['ubicacion']]['UBICACION_NOMBRE'] = $dato['ubicacion_nombre'];
			$aux[$dato['ubicacion']]['PERIODOS_LECTIVOS'][$dato['periodo_lectivo']]['PERIODO_LECTIVO'] = $dato['periodo_lectivo_nombre'];
			$aux[$dato['ubicacion']]['PERIODOS_LECTIVOS'][$dato['periodo_lectivo']]['MATERIAS'][$dato['actividad']]['MATERIA'] = $dato['actividad_codigo'];
			$aux[$dato['ubicacion']]['PERIODOS_LECTIVOS'][$dato['periodo_lectivo']]['MATERIAS'][$dato['actividad']]['MATERIA_NOMBRE'] = $dato['actividad_nombre'];
			$aux[$dato['ubicacion']]['PERIODOS_LECTIVOS'][$dato['periodo_lectivo']]['MATERIAS'][$dato['actividad']]['HORARIOS'][] = array(
				'COMISION' => $dato['comision'],
				'COMISION_NOMBRE' => $dato['comision_nombre'],
				'TURNO' => $dato['turno'],
				'TURNO_DESC' => $dato['turno_desc'],
				'EDIFICIO' => $dato['edificio'],
				'AULA' => $dato['aula'],
				'AULA_NOMBRE' => $dato['aula_nombre'],
				'DIA_SEMANA' => $dato['dia_semana'],
				'DIA_SEMANA_DESC' => $dato['dia_semana'],
				'HS_COMIENZO_CLASE' => $dato['hs_comienzo_clase'],
				'HS_FINALIZ_CLASE' => $dato['hs_finaliz_clase'],
				'INICIO_FIN_CLASE' => $dato['inicio_fin_clase'],
				'TIPO_CLASE_DESC' => $dato['tipo_clase_desc'],
				'PL_FECHA_INICIO' => $dato['pl_fecha_inicio'],
				'CUPO' => $dato['cupo'],
				'AULA_EDIF' => ($dato['aula_nombre']) ? $dato['aula_nombre'] . ' - ' . $dato['edificio'] : "",
			);
		}
		return $aux;
	}

	/**
	 * parametros: 
	 * cache: no
	 * filas: n
	 */
	function get_carreras($ra)
	{
		$where = "";
		if (!empty($ra)) {
			$where = "AND sga_propuestas_ra.responsable_academica = $ra";
		} else {
			$where = "";
		}
		
		$estado_activo_no_vigente = kernel::db()->quote(\planes::estado_activo_no_vigente);
		$estado_activo_vigente = kernel::db()->quote(\planes::estado_activo_vigente);
			
		// Listado de propuestas con planes activos
		$sql = "SELECT DISTINCT sga_propuestas.propuesta, 
								sga_propuestas.nombre, 
								sga_propuestas.nombre_abreviado
			   FROM sga_propuestas,
			        sga_planes,
			        sga_propuestas_ra
			   WHERE sga_propuestas.propuesta = sga_planes.propuesta
			     AND sga_propuestas_ra.propuesta = sga_propuestas.propuesta
			     AND sga_planes.estado IN ($estado_activo_no_vigente, $estado_activo_vigente) 
			     $where
			   ORDER BY sga_propuestas.nombre
			   ";
		$datos = kernel::db()->consultar($sql);
		$nuevo = array();

		foreach ($datos as $key => $dato) {
			$nuevo[$key] = $dato;
			$nuevo[$key]['_ID_'] = catalogo::generar_id($dato['propuesta']);
		}
		return $nuevo;
	}
	/**
	 * parametros: ra, ubicacion
	 * cache: memoria
	 * filas: n
	*/

	function get_carreras_filtro($parametros)
	{
		$estado_activo_no_vigente = kernel::db()->quote(\planes::estado_activo_no_vigente);
		$estado_activo_vigente = kernel::db()->quote(\planes::estado_activo_vigente);
		
		$sql = "SELECT DISTINCT sga_propuestas.propuesta,
						sga_propuestas.nombre, 
						sga_propuestas.nombre_abreviado
				FROM sga_planes
				LEFT JOIN sga_propuestas		ON (sga_propuestas.propuesta = sga_planes.propuesta)
				LEFT JOIN sga_propuestas_ra		ON (sga_propuestas_ra.propuesta = sga_propuestas.propuesta)
				LEFT JOIN sga_propuestas_oferta ON (sga_propuestas_oferta.propuesta = sga_propuestas.propuesta)
				LEFT JOIN sga_ubicaciones		ON (sga_propuestas_oferta.ubicacion = sga_ubicaciones.ubicacion)
				WHERE sga_planes.estado IN ($estado_activo_vigente, $estado_activo_no_vigente)
				AND sga_propuestas_oferta.ubicacion = {$parametros['ubicacion']}
				AND sga_propuestas_ra.responsable_academica = {$parametros['ra']}
				ORDER BY sga_propuestas.nombre;";
		
		$datos = kernel::db()->consultar($sql);
		$nuevo = [];

		foreach ($datos as $key => $dato) {
			$nuevo[$key] = $dato;
			$nuevo[$key]['_ID_'] = catalogo::generar_id($dato['propuesta']);
		}
		return $nuevo;
	}

	/**
	 * parametros: ra
	 * param_null: ra
	 * no_quote: ra
	 * cache: memoria
	 * filas: n
	 * 
	*/
	function get_ubicacion($parametros)
	{	
		$where_ra = "";
		$join_ra = "";
		// Si se filtra por Responsable Académica
		if (!empty($parametros['ra'])) {
			$join_ra = "
				JOIN sga_propuestas_oferta	ON (sga_ubicaciones.ubicacion = sga_propuestas_oferta.ubicacion)
				JOIN sga_propuestas_ra		ON (sga_propuestas_oferta.propuesta = sga_propuestas_ra.propuesta)
				";
			
			$ra = kernel::db()->quote($parametros['ra']);
			$where_ra = " WHERE sga_propuestas_ra.responsable_academica = {$ra}";
		}
		
		$sql = "SELECT DISTINCT sga_ubicaciones.ubicacion  AS id, 
						sga_ubicaciones.nombre || ' (' || sga_ubicaciones_tipos.nombre || ')' AS nombre,
						sga_ubicaciones.nombre AS nombre_ubicacion
				FROM sga_ubicaciones
				JOIN sga_ubicaciones_tipos	ON (sga_ubicaciones_tipos.ubicacion_tipo = sga_ubicaciones.ubicacion_tipo)
				$join_ra
				$where_ra 
				ORDER BY nombre_ubicacion;";

		$datos = kernel::db()->consultar($sql);
		
		$nuevo = [];
		foreach ($datos as $key => $dato) {
			$nuevo[$key] = $dato;
			$nuevo[$key]['_ID_'] = catalogo::generar_id($dato['id']);
		}
		return $nuevo;
	}

	/**
	 * parametros: propuesta 
	 * cache: no
	 * filas: n
	 */
	function get_planes_propuesta($parametros)
	{

		$estado_activo_no_vigente = kernel::db()->quote(\planes::estado_activo_no_vigente);
		$estado_activo_vigente = kernel::db()->quote(\planes::estado_activo_vigente);
		
		// Listado de planes de estudios activos
		$sql = "SELECT sga_planes.nombre, 
					   sga_planes.plan,
					   sga_planes.codigo,
					   sga_planes.plan
				FROM sga_planes
				WHERE sga_planes.propuesta = {$parametros['propuesta']}
				  AND sga_planes.estado IN ($estado_activo_no_vigente, $estado_activo_vigente)
				ORDER BY sga_planes.fecha_entrada_vigencia
				";
		$datos = kernel::db()->consultar($sql);
		$nuevo = array();

		foreach ($datos as $key => $dato) {
			$nuevo[$key] = $dato;
			$nuevo[$key]['_ID_'] = catalogo::generar_id($dato['codigo']);
		}

		return $nuevo;
	}

	/**
	 * parametros:  
	 * cache: no
	 * filas: n
	 */
	function get_mesas_instancia()
	{

		// Listado de instancias relacionadas con mesas de examen
		$sql = "SELECT	instancia, nombre
				FROM	sga_instancias
				WHERE	aplica_a = 'M'
				";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * 
	 * cache: memoria
	 * filas: n
	 */
	function get_responsables_academicas()
	{
		// Listado de Responsables academicas de la institucion de la instalacion.
		$and = '';
                $ra_bloque_acceso = kernel::proyecto()->get_puntoacceso_x_ra();
		if (is_array($ra_bloque_acceso)) {
			$ra_bloque_acceso = array_filter($ra_bloque_acceso, 'strlen');
			$ras = implode(',', $ra_bloque_acceso);
			if (is_numeric($ras)) {
				$and = " AND sga_responsables_academicas.responsable_academica IN (" . $ras . ")";
			}
		} else {
			if (is_numeric($ra_bloque_acceso)) {
				$ras = kernel::db()->quote($ra_bloque_acceso);
				$and = " AND sga_responsables_academicas.responsable_academica IN (" . $ras . ")";
			}
		}

                $sql = "SELECT responsable_academica, nombre, codigo
				FROM sga_responsables_academicas
				WHERE institucion = get_institucion_instalacion()
				$and
                                ORDER BY nombre	";

		$datos = kernel::db()->consultar($sql);
		$nuevo = array();

		foreach ($datos as $key => $dato) {
			$nuevo[$key] = $dato;
			$nuevo[$key]['_ID_'] = catalogo::generar_id($dato['responsable_academica']);
		}
		return $nuevo;
	}

}
