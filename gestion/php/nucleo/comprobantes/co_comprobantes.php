<?php
/**
 * Consultas relativas a Comprobantes
 *
 *
 * @package Comprobantes
 * @subpackage Comprobantes
 */


class co_comprobantes
{

	/**
	 * Retorna los comprobantes de inscripción del alumno dado como parámetro, según el filtro.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_comprobantes_inscripcion_alumno($alumno, $where='')
	{
		$alumno = toba::db()->quote($alumno);
		
		if ($where) {
			$where = " AND $where";
		}		
		
		$sql = "SELECT 		
							CASE WHEN vw_comprobantes.tipo = '".comprobantes::tipo_comprobante_rec_insc_exa."' THEN 0
								 WHEN vw_comprobantes.tipo = '".comprobantes::tipo_comprobante_rec_insc_act."' THEN 0
								 WHEN vw_comprobantes.tipo = '".comprobantes::tipo_comprobante_baja_insc_prop."' THEN 0
								 WHEN vw_comprobantes.tipo = '".comprobantes::tipo_comprobante_sol_constancia."' THEN 0
							ELSE
								1
							END as tiene_comprobante,
							vw_comprobantes.tipo,
							vw_comprobantes.nro_transaccion,
							vw_comprobantes.inscripcion,
							vw_comprobantes.propuesta,
							vw_comprobantes.ubicacion,
							vw_comprobantes.estado,
							vw_comprobantes.fecha,
							vw_comprobantes.descripcion,
							vw_comprobantes.anio_academico,
							vw_comprobantes.periodo_lectivo_turno,
							sga_propuestas.nombre_abreviado	AS propuesta_nombre,
							sga_ubicaciones.nombre as ubicacion_nombre,
							vw_comprobantes.interfaz_descripcion,
							'(' || vw_comprobantes.actividad_codigo || ') ' || vw_comprobantes.actividad_nombre AS actividad_descr
				FROM 		vw_comprobantes
								LEFT JOIN sga_propuestas ON vw_comprobantes.propuesta = sga_propuestas.propuesta,
							sga_ubicaciones
				WHERE 		vw_comprobantes.alumno = $alumno AND
							vw_comprobantes.ubicacion = sga_ubicaciones.ubicacion 
							$where
				ORDER BY	vw_comprobantes.fecha DESC
				";

		return guarani_db::consultar($sql);
	}
	

	/**
	 * Retorna los datos del comprobante de baja de inscripción a cursadas, según el filtro.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_datos_comprobante_baja_cursadas($where='')
	{
		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT 
                            vw_comprobantes.tipo,
                            vw_comprobantes.nro_transaccion,
                            vw_comprobantes.alumno,
                            vw_comprobantes.inscripcion,
                            vw_comprobantes.propuesta,
                            vw_comprobantes.ubicacion,
                            vw_comprobantes.fecha as fecha_baja,  -- fecha baja inscripcion
							vw_insc_cursada_log.fecha_inscripcion,
                            vw_comprobantes.anio_academico,
                            vw_comprobantes.periodo_lectivo_turno,
                            vw_planes.propuesta_nombre_abreviado AS propuesta_nombre,
                            sga_ubicaciones.nombre as ubicacion_nombre,                            
                            '(' || vw_comprobantes.actividad_codigo || ') ' || vw_comprobantes.actividad_nombre AS actividad_descr,
                            vw_comprobantes.alumno_nombre as apellido_y_nombres,
                            vw_comprobantes.alumno_identificacion as nro_identificacion,
                            vw_comprobantes.alumno_legajo as legajo,
                            vw_comprobantes.persona,
                            vw_periodos_lectivos.nombre AS periodo_nombre,                            
							sga_comisiones.nombre AS comision_nombre,
							sga_turnos_cursadas.nombre AS turno_nombre,
							sga_catedras.nombre AS catedra_nombre  
							
                FROM vw_comprobantes
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comprobantes.ubicacion
                JOIN vw_insc_cursada_log ON vw_insc_cursada_log.inscripcion = vw_comprobantes.inscripcion
				JOIN vw_planes ON vw_planes.plan_version = vw_insc_cursada_log.plan_version
				JOIN sga_comisiones ON sga_comisiones.comision = vw_insc_cursada_log.comision
				JOIN vw_periodos_lectivos ON vw_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
				LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
				LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
                WHERE true
				$where
				";
		return guarani_db::consultar($sql);
	}
	

	/**
	 * Retorna los datos del comprobante de baja de inscripción a cursadas, según el filtro.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_datos_comprobante_cursadas($where='')
	{
		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT 
                            vw_comprobantes.tipo,
                            vw_comprobantes.nro_transaccion,
                            vw_comprobantes.alumno,
                            vw_comprobantes.inscripcion,
                            vw_comprobantes.propuesta,
                            vw_comprobantes.ubicacion,
                            vw_comprobantes.fecha as fecha_inscripcion, -- fecha de inscripcion
							vw_comprobantes.estado,
							vw_comprobantes.estado_descripcion as estado_nombre,
                            vw_comprobantes.anio_academico,
                            vw_comprobantes.periodo_lectivo_turno,
                            vw_planes.propuesta_nombre_abreviado AS propuesta_nombre,
                            sga_ubicaciones.nombre as ubicacion_nombre,                            
                            '(' || vw_comprobantes.actividad_codigo || ') ' || vw_comprobantes.actividad_nombre AS actividad_descr,
                            vw_comprobantes.alumno_nombre as apellido_y_nombres,
                            vw_comprobantes.alumno_identificacion as nro_identificacion,
                            vw_comprobantes.alumno_legajo as legajo,
                            vw_comprobantes.persona,
							vw_insc_cursada.plan_version,
							vw_planes.version_nombre,
							vw_planes.plan_codigo,
							vw_planes.plan_nombre,
							vw_periodos_lectivos.nombre AS periodo_nombre,
							vw_insc_cursada.comision,                 
							sga_comisiones.nombre AS comision_nombre,
							f_subcomisiones_insc_cursada(vw_insc_cursada.inscripcion, false, false) AS subcomisiones_nombre,
							sga_turnos_cursadas.nombre AS turno_nombre,
							sga_catedras.nombre AS catedra_nombre                                                    
							
					FROM vw_comprobantes
					JOIN sga_ubicaciones ON vw_comprobantes.ubicacion = sga_ubicaciones.ubicacion
					JOIN vw_insc_cursada ON vw_comprobantes.inscripcion = vw_insc_cursada.inscripcion
					JOIN vw_planes ON vw_planes.plan_version = vw_insc_cursada.plan_version
					JOIN sga_comisiones ON sga_comisiones.comision = vw_insc_cursada.comision
					JOIN vw_periodos_lectivos ON vw_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
					LEFT JOIN sga_catedras ON sga_comisiones.catedra = sga_catedras.catedra
					LEFT JOIN sga_turnos_cursadas ON sga_comisiones.turno = sga_turnos_cursadas.turno
					
					WHERE true 
					$where
				";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los datos del comprobante de inscripción (tambien de baja) a exámenes, según el filtro.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_datos_comprobantes_examenes($where = '')
	{
		
		$formato_hora_visual = toba::db()->quote(comunes::formato_hora_visual);
		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT 
                            vw_comprobantes.tipo,
                            vw_comprobantes.nro_transaccion,
                            vw_comprobantes.alumno,
                            vw_comprobantes.inscripcion,
                            vw_comprobantes.propuesta,
                            vw_comprobantes.ubicacion,
                            vw_comprobantes.fecha as fecha_inscripcion,
                            vw_comprobantes.anio_academico,
                            vw_comprobantes.periodo_lectivo_turno,
                            vw_planes.propuesta_nombre_abreviado AS propuesta_nombre,
                            sga_ubicaciones.nombre as ubicacion_nombre,                            
                            '(' || vw_comprobantes.actividad_codigo || ') ' || vw_comprobantes.actividad_nombre AS actividad_descr,
                            vw_comprobantes.alumno_nombre as apellido_y_nombres,
                            vw_comprobantes.alumno_identificacion as nro_identificacion,
                            vw_comprobantes.alumno_legajo as legajo,
                            vw_comprobantes.persona,
							COALESCE(sga_catedras.nombre, '') AS catedra_nombre,
                            vw_mesas_examen.turno_examen_nombre as turno_nombre,
                            vw_mesas_examen.llamado_nombre,
                            vw_mesas_examen.mesa_examen_nombre as mesa_nombre,
                            vw_mesas_examen.mesa_examen_fecha,
                            CONCAT_WS(' a ', to_char(date '2001-01-01' + vw_mesas_examen.mesa_examen_hora_inicio, $formato_hora_visual),
                                to_char(date '2001-01-01' + vw_mesas_examen.mesa_examen_hora_finalizacion, $formato_hora_visual)) as horario_examen,
                            sga_instancias.nombre as instancia_nombre,
                            vw_comprobantes.estado,
                            vw_comprobantes.estado_descripcion as estado_nombre
                                                                               
                FROM vw_comprobantes
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comprobantes.ubicacion
                JOIN vw_insc_examen ON vw_insc_examen.inscripcion = vw_comprobantes.inscripcion
				JOIN vw_planes ON vw_planes.plan_version = vw_insc_examen.plan_version
                JOIN sga_instancias ON sga_instancias.instancia = vw_insc_examen.instancia
                JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = vw_insc_examen.llamado_mesa
                LEFT JOIN sga_catedras ON (vw_mesas_examen.mesa_examen_catedra = sga_catedras.catedra)

                WHERE   true     
       			$where
				";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos del comprobante de inscripción (tambien de baja) a exámenes, según el filtro.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_datos_comprobantes_baja_examenes($where='')
	{
		
		$formato_hora_visual = toba::db()->quote(comunes::formato_hora_visual);
		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT 
                            vw_comprobantes.tipo,
                            vw_comprobantes.nro_transaccion,
                            vw_comprobantes.alumno,
                            vw_comprobantes.inscripcion,
                            vw_comprobantes.propuesta,
                            vw_comprobantes.ubicacion,
                            vw_comprobantes.fecha as fecha_baja,
                            vw_insc_examen_log.fecha_inscripcion,
							vw_comprobantes.anio_academico,
                            vw_comprobantes.periodo_lectivo_turno,
                            vw_planes.propuesta_nombre_abreviado AS propuesta_nombre,
                            sga_ubicaciones.nombre as ubicacion_nombre,                            
                            '(' || vw_comprobantes.actividad_codigo || ') ' || vw_comprobantes.actividad_nombre AS actividad_descr,
                            vw_comprobantes.alumno_nombre as apellido_y_nombres,
                            vw_comprobantes.alumno_identificacion as nro_identificacion,
                            vw_comprobantes.alumno_legajo as legajo,
                            vw_comprobantes.persona,
                            COALESCE(sga_catedras.nombre, '') AS catedra_nombre,
                            vw_mesas_examen.turno_examen_nombre as turno_nombre,
                            vw_mesas_examen.llamado_nombre,
                            vw_mesas_examen.mesa_examen_nombre as mesa_nombre,
                            vw_mesas_examen.mesa_examen_fecha,
							CONCAT_WS(' a ', to_char(date '2001-01-01' + vw_mesas_examen.mesa_examen_hora_inicio, $formato_hora_visual),
								to_char(date '2001-01-01' + vw_mesas_examen.mesa_examen_hora_finalizacion, $formato_hora_visual)) as horario_examen,
                            sga_instancias.nombre as instancia_nombre

                FROM vw_comprobantes
				JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comprobantes.ubicacion
                JOIN vw_insc_examen_log ON vw_insc_examen_log.inscripcion = vw_comprobantes.inscripcion
				JOIN vw_planes ON vw_planes.plan_version = vw_insc_examen_log.plan_version
                JOIN sga_instancias ON sga_instancias.instancia = vw_insc_examen_log.instancia
                JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = vw_insc_examen_log.llamado_mesa
                LEFT JOIN sga_catedras ON (vw_mesas_examen.mesa_examen_catedra = sga_catedras.catedra)

                WHERE true       
                $where
				";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los datos del comprobante de inscripción (tambien de baja) a exámenes, según el filtro.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_datos_comprobantes_propuestas($where='')
	{
		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT 
                            vw_comprobantes.tipo,
                            vw_comprobantes.nro_transaccion,
                            vw_comprobantes.alumno,
                            vw_comprobantes.inscripcion,
                            vw_comprobantes.propuesta,
                            vw_comprobantes.ubicacion,
                            vw_comprobantes.fecha as fecha_inscripcion,
                            vw_planes.propuesta_nombre_abreviado AS propuesta_nombre,
                            sga_ubicaciones.nombre as ubicacion_nombre,                            
                            vw_comprobantes.alumno_nombre as apellido_y_nombres,
                            vw_comprobantes.alumno_identificacion as nro_identificacion,
                            vw_comprobantes.alumno_legajo as legajo,
                            vw_comprobantes.persona,
                            vw_planes.plan_nombre || ' ' || vw_planes.version_nombre as plan_version_nombre,
                            vw_planes.plan_nombre as plan_nombre,
                            sga_convenios.nombre as convenio,
                            sga_modalidad_cursada.nombre as modalidad_nombre,
                           	sga_tipos_ingreso.nombre as tipo_ingreso_nombre,
                           	vw_comprobantes.estado,
                           	vw_comprobantes.estado_descripcion as estado_nombre

                FROM  vw_comprobantes
                JOIN  sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_comprobantes.ubicacion
                JOIN  sga_propuestas_aspira ON sga_propuestas_aspira.propuesta_aspira = vw_comprobantes.inscripcion
                JOIN  sga_modalidad_cursada ON sga_modalidad_cursada.modalidad = sga_propuestas_aspira.modalidad
                JOIN  vw_planes ON vw_planes.plan_version = sga_propuestas_aspira.plan_version
                LEFT JOIN sga_convenios ON sga_propuestas_aspira.convenio = sga_convenios.convenio
                LEFT JOIN sga_tipos_ingreso ON sga_tipos_ingreso.tipo_ingreso = sga_propuestas_aspira.tipo_ingreso

                WHERE   true     
       			$where
				";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los datos del comprobante de inscripción (tambien de baja) a exámenes, según el filtro.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_datos_comprobantes_reinsc_propuestas($where='')
	{
		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT 
                            vw_comprobantes.tipo,
                            vw_comprobantes.nro_transaccion,
                            vw_comprobantes.alumno,
                            vw_comprobantes.inscripcion,
                            vw_comprobantes.propuesta,
                            vw_comprobantes.ubicacion,
                            vw_comprobantes.fecha as fecha_reinscripcion,
                            vw_comprobantes.anio_academico,
                            vw_planes.propuesta_nombre_abreviado as propuesta_nombre,
                            sga_ubicaciones.nombre as ubicacion_nombre,                            
                            vw_comprobantes.alumno_nombre as apellido_y_nombres,
                            vw_comprobantes.alumno_identificacion as nro_identificacion,
                            vw_comprobantes.alumno_legajo as legajo,
                            vw_comprobantes.persona,
                            vw_planes.plan_nombre || ' ' || vw_planes.version_nombre as plan_version_nombre,
                            vw_planes.plan_nombre as plan_nombre,
                            sga_modalidad_cursada.nombre as modalidad_nombre

                FROM vw_comprobantes
				JOIN sga_ubicaciones ON vw_comprobantes.ubicacion = sga_ubicaciones.ubicacion
				JOIN sga_alumnos ON sga_alumnos.alumno = vw_comprobantes.alumno
				JOIN sga_modalidad_cursada ON sga_modalidad_cursada.modalidad = sga_alumnos.modalidad
				JOIN vw_planes ON vw_planes.plan_version = sga_alumnos.plan_version

                WHERE        true
				$where
				";

		return guarani_db::consultar($sql);
	}
	
	
	/**
	 * Retorna las opciones Inscripción a Propuesta / Baja inscripción a Propuesta / Reinscripción a Propuesta
	 * Inscripción a Actividad / Baja inscripción a Actividad / Inscripción a Examen / Baja inscripción a Examen
     */
    function get_tipos_comprobantes()
    {
		return array(	array('valor' => comprobantes::tipo_comprobante_baja_insc_act,	'descr' => 'Baja inscripción a Actividad'),
						array('valor' => comprobantes::tipo_comprobante_baja_insc_exa,	'descr' => 'Baja inscripción a Examen'),
						array('valor' => comprobantes::tipo_comprobante_baja_insc_prop,	'descr' => 'Baja inscripción a Propuesta'),
						array('valor' => comprobantes::tipo_comprobante_insc_act,		'descr' => 'Inscripción a Actividad'),
						array('valor' => comprobantes::tipo_comprobante_insc_exa,		'descr' => 'Inscripción a Examen'),
						array('valor' => comprobantes::tipo_comprobante_insc_prop,		'descr' => 'Inscripción a Propuesta'),
						array('valor' => comprobantes::tipo_comprobante_reinsc_prop,	'descr' => 'Reinscripción a Propuesta'));
    }
    
}
?>