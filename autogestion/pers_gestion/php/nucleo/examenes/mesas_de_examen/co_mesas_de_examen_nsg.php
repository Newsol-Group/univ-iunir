<?php

class co_mesas_de_examen_nsg extends co_mesas_de_examen
{

	/**Newsol - Se cambió para que muestre las mesas de examen de las actividades en el plan actual
	 * Obtiene un listado de Mesas de Examen para exhibir en reporte según filtro especificado.
	 */
	function get_listado_reporte_mesas_examen($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
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
				                        
				FROM 		sga_mesas_examen
                			LEFT JOIN sga_catedras ON 
                            	sga_mesas_examen.catedra = sga_catedras.catedra,
							sga_llamados_mesa,
							vw_actividades,
				            sga_ubicaciones,
				            sga_llamados_turno,
				            sga_periodos,
							sga_turnos_examen, 
				sga_elementos, 
				sga_elementos_revision, 
				sga_elementos_plan, 
				sga_planes
				            
				WHERE		vw_actividades.elemento = sga_mesas_examen.elemento
				            		AND sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
							AND sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
							AND sga_llamados_mesa.llamado = sga_llamados_turno.llamado
							AND sga_llamados_turno.periodo = sga_periodos.periodo            
							AND sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
							AND sga_mesas_examen.elemento = sga_elementos.elemento
							AND sga_elementos.elemento = sga_elementos_revision.elemento
							AND sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
							AND sga_elementos_plan.plan_version = sga_planes.plan
							$where
				            
				ORDER BY 	sga_ubicaciones.nombre, 
							vw_actividades.nombre, 
							sga_periodos.nombre,
							sga_llamados_mesa.fecha,
							sga_mesas_examen.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	

	/**Newsol - Se cambió para que muestre las mesas de examen de las actividades en el plan actual
	 * Listado de Total de Inscripciones a Exámenes.
	 */
	function get_listado_reporte_total_insc_examen($where='')
	{
		if ($where) {
			$where = ' AND ' . $where;
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
				                        
				FROM 		sga_mesas_examen,
							sga_llamados_mesa,
							vw_actividades,
				            sga_ubicaciones,
				            sga_llamados_turno,
				            sga_periodos,
							sga_turnos_examen,
							sga_periodos as turno,
				sga_elementos, 
				sga_elementos_revision, 
				sga_elementos_plan, 
				sga_planes
				            
				WHERE			vw_actividades.elemento = sga_mesas_examen.elemento
				            		AND sga_mesas_examen.ubicacion = sga_ubicaciones.ubicacion
							AND sga_llamados_mesa.mesa_examen = sga_mesas_examen.mesa_examen
							AND sga_llamados_mesa.llamado = sga_llamados_turno.llamado
							AND sga_llamados_turno.periodo = sga_periodos.periodo            
							AND sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
							AND sga_turnos_examen.periodo = turno.periodo
							AND sga_mesas_examen.elemento = sga_elementos.elemento
							AND sga_elementos.elemento = sga_elementos_revision.elemento
							AND sga_elementos_revision.elemento_revision = sga_elementos_plan.elemento_revision
							AND sga_elementos_plan.plan_version = sga_planes.plan
							$where
				            
				ORDER BY 	sga_ubicaciones.nombre, 
							actividad_codigo_y_nombre, 
				            sga_periodos.nombre,
				            sga_llamados_mesa.fecha,
				            sga_mesas_examen.nombre
				";

		return guarani_db::consultar($sql);
	}

	/**Newsol (NUEVO) - Recupera las inscripciones para el alumno dado, en una mesa distinta a la recibida 
	 * como parámetro, tales que las mismas estén superpuestas con el día de la semana y rango horario dados.
	 *
	 * @param integer $alumno		alumno asociado a la inscripcion.
	 * @param integer $llamado_mesa		mesa_examen asociada a la inscripcion.
	 * @param time $hora_inicio		hora de inicio correspondiente a la mesa actual
	 * @param time $hora_finalizacion	hora de finalizacion correspondiente a la mesa actual
	 * @param date $fecha_mesa		fecha de la mesa correspondiente a la mesa actual
	 * @return boolean array asignaciones superpuestas con la dada, para otras inscripciones a mesas del alumno.
	 */
	function get_inscripciones_superpuestas($alumno, $llamado_mesa, $fecha_mesa, $hora_inicio, $hora_finalizacion, $where='')
	{
		$alumno = toba::db()->quote($alumno);
		$llamado_mesa = toba::db()->quote($llamado_mesa);
		$hora_inicio = toba::db()->quote($hora_inicio);
		$hora_finalizacion = toba::db()->quote($hora_finalizacion);
		$fecha_mesa = toba::db()->quote($fecha_mesa);

		if ($where) {
			$where = ' AND ' . $where; 
		}
		
		$sql = "-- Inscripciones a mesas de examen.
				SELECT 	sga_insc_examen.inscripcion,
						sga_llamados_mesa.mesa_examen,
						sga_llamados_mesa.llamado_mesa
				FROM 	sga_insc_examen,
						sga_alumnos,
						sga_llamados_mesa
				WHERE	sga_insc_examen.alumno = sga_alumnos.alumno AND
						sga_insc_examen.llamado_mesa = sga_llamados_mesa.llamado_mesa AND
						sga_llamados_mesa.llamado = (select a.llamado from sga_llamados_mesa a where a.llamado_mesa = $llamado_mesa) AND
						sga_alumnos.persona = (	SELECT	alumnos.persona
												FROM	sga_alumnos as alumnos
												WHERE	alumnos.alumno = $alumno) AND
						sga_llamados_mesa.llamado_mesa <> $llamado_mesa AND
						sga_llamados_mesa.fecha = $fecha_mesa AND

						((hora_inicio > $hora_inicio AND hora_inicio < $hora_finalizacion)
							OR
							(hora_finalizacion > $hora_inicio AND hora_finalizacion < $hora_finalizacion)
							OR
							(hora_inicio <= $hora_inicio AND hora_finalizacion >= $hora_finalizacion))

						$where
							
	";
		return guarani_db::consultar($sql);
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
							sga_llamados_turno.turno_examen,
							mesas_examen_ext.cobrable,
							mesas_examen_ext.concepto
				FROM		sga_mesas_examen 
				LEFT OUTER JOIN negocio_nsg.sga_mesas_examen_ext as mesas_examen_ext ON sga_mesas_examen.mesa_examen = mesas_examen_ext.id_mesa_examen,
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


}
?>