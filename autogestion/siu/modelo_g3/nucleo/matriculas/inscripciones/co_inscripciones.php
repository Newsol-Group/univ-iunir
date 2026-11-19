<?php
/**
 * Consultas relativas a Inscripciones
 *
 *
 * @package Matrículas
 * @subpackage Inscripciones
 */
class co_inscripciones
{
	function get_inscriptos($where = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = " SELECT 	sga_propuestas_aspira.periodo_insc,
    						sga_propuestas_aspira.propuesta_aspira,
							vw_periodos_insc_propuesta.anio_academico,
							vw_periodos_insc_propuesta.nombre AS periodo_inscripcion_nombre,
							vw_periodos_insc_propuesta.fecha_inicio,
							vw_periodos_insc_propuesta.fecha_fin,
							vw_periodos_insc_propuesta.fecha_cierre_requisitos,
							vw_periodos_insc_propuesta.anio_academico || ' - ' || vw_periodos_insc_propuesta.nombre || ' (' || to_char(vw_periodos_insc_propuesta.fecha_inicio,'" . comunes::formato_fecha_visual . "') || ' al ' || to_char(vw_periodos_insc_propuesta.fecha_fin,'" . comunes::formato_fecha_visual . "') || ')' AS anio_periodo_desc,
							sga_propuestas_aspira.propuesta,
							sga_propuestas.codigo AS propuesta_codigo,
							sga_propuestas.nombre AS propuesta_nombre,
							sga_propuestas_tipos.descripcion AS propuesta_tipo_nombre,
							'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS propuesta_desc,
							sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_propuestas_aspira.modalidad,
							sga_propuestas_aspira.ubicacion,
							sga_ubicaciones.nombre AS ubicacion_nombre,
							sga_ubicaciones_tipos.nombre AS ubicacion_tipo_nombre,
							sga_ubicaciones.nombre AS ubicacion_desc,
							sga_propuestas_aspira.fecha_inscripcion,
							to_char(sga_propuestas_aspira.fecha_inscripcion,'" . comunes::formato_fecha_visual . "') AS fecha_inscripcion_desc,
							sga_propuestas_aspira.tipo_ingreso,
							vw_personas.apellido_nombres AS persona_desc,
							vw_personas.tipo_nro_documento AS documento_desc,
							mdp_tipo_documento.desc_abreviada AS documento_tipo_nombre,
							sga_planes.codigo AS plan_desc,
							sga_planes.nombre AS plan_nombre,
							sga_planes_versiones.version AS version_desc,
							" . guarani_sql::get_case('sga_situacion_aspirante', 'resultado_asp', 'inscripcion_estado') . ",
							sga_tipos_ingreso.nombre AS ingreso_tipo_nombre,
							sga_modalidad_cursada.nombre AS modalidad_cursada_nombre,
							f_requisitos_rechazados_insc(sga_propuestas_aspira.propuesta_aspira) AS req_rechazados							
							
					FROM 	sga_propuestas_aspira
							JOIN vw_personas ON sga_propuestas_aspira.persona = vw_personas.persona
							JOIN sga_alumnos ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
							JOIN mdp_tipo_documento ON vw_personas.tipo_documento = mdp_tipo_documento.tipo_documento
							JOIN sga_propuestas ON sga_propuestas_aspira.propuesta = sga_propuestas.propuesta
							JOIN sga_propuestas_tipos ON sga_propuestas.propuesta_tipo = sga_propuestas_tipos.propuesta_tipo
							JOIN sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
							JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
							JOIN sga_ubicaciones ON sga_alumnos.ubicacion = sga_ubicaciones.ubicacion
							JOIN sga_ubicaciones_tipos ON sga_ubicaciones.ubicacion_tipo = sga_ubicaciones_tipos.ubicacion_tipo
							JOIN sga_modalidad_cursada ON sga_alumnos.modalidad = sga_modalidad_cursada.modalidad
							JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
							JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
							LEFT JOIN sga_tipos_ingreso	ON sga_propuestas_aspira.tipo_ingreso = sga_tipos_ingreso.tipo_ingreso

					$where

					ORDER BY 	sga_ubicaciones.nombre,
								sga_ubicaciones_tipos.nombre,
								vw_periodos_insc_propuesta.anio_academico,
								vw_periodos_insc_propuesta.nombre,
								sga_propuestas.nombre,
								vw_personas.apellido,
								vw_personas.nombres
		";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de inscriptos a propuesta.
	 * Si el usuario logueado tiene perfil de datos con dominio "Responsables Académicas"
	 * restringe las inscripciones a aquellas propuestas que gestiona la RA.
	 * 
	 * @param string $where
	 * @param string $order
	 * @return array
	 */
	function get_inscriptos_perfil_de_datos($where = '', $order = '')
	{
		if ($where) {
			$where = "WHERE $where";
		}

                if ($order) {
			$order = "ORDER BY $order";
		}

		$sql = "SELECT                          vw_personas.apellido_nombres,
							vw_personas.tipo_nro_documento,
                                                        vw_personas.persona,
							vw_perfil_de_datos_propuestas.propuesta,
							vw_perfil_de_datos_propuestas.propuesta_descripcion as propuesta_descripcion,
							sga_planes.codigo as plan_codigo,
							sga_planes_versiones.version,
							sga_ubicaciones.ubicacion,
							sga_ubicaciones.nombre as ubicacion_nombre,
							sga_propuestas_aspira.fecha_inscripcion as fecha_inscripcion,
                                                        get_fecha_ingreso_alumno(sga_alumnos.alumno, sga_propuestas_aspira.periodo_insc) as fecha_ingreso,
							" . guarani_sql::get_case('sga_situacion_aspirante', 'resultado_asp', 'inscripcion_estado') . ",
							sga_tipos_ingreso.nombre as ingreso_tipo_nombre,
							sga_modalidad_cursada.nombre as modalidad_cursada_nombre,
							vw_periodos_insc_propuesta.anio_academico,
							vw_periodos_insc_propuesta.periodo_inscripcion,
							vw_periodos_insc_propuesta.fecha_inicio,
							vw_periodos_insc_propuesta.fecha_fin,
							vw_periodos_insc_propuesta.anio_academico || ' - ' || vw_periodos_insc_propuesta.nombre || ' (' || to_char(vw_periodos_insc_propuesta.fecha_inicio,'" . comunes::formato_fecha_visual . "') || ' al ' || to_char(vw_periodos_insc_propuesta.fecha_fin,'" . comunes::formato_fecha_visual . "') || ')' as anio_periodo_desc
				FROM                    sga_propuestas_aspira
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
                                                        $where
                                                        $order";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de alumnos inscriptos a propuesta.
	 * Los datos del plan, modalidad y ubicación se obtienen de la inscripción y NO del alumno.
	 *
	 * @param str $where filtro sobre los alumnos inscriptos a retornar.
	 * @return array
	 */
	function get_alumnos_inscriptos($where = '', $order = '')
	{
		if ($where) {
			$where = " AND $where";
		}
		if (empty($order)) {
			$order = '	vw_personas.apellido,
						vw_personas.nombres';
		}
		$sql = "SELECT	sga_propuestas_aspira.propuesta_aspira,
						sga_propuestas_aspira.persona,
						sga_propuestas_aspira.propuesta,
						sga_propuestas_aspira.ubicacion,
						sga_propuestas_aspira.fecha_inscripcion,
						sga_alumnos.alumno,
						sga_alumnos.legajo,
						vw_periodos_insc_propuesta.anio_academico,
						vw_personas.apellido_nombres AS apellido_y_nombres,
						vw_personas.tipo_nro_documento AS nro_identificacion,
						vw_perfil_de_datos_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
						vw_perfil_de_datos_propuestas.propuesta_descripcion,
						sga_planes.plan,
						sga_planes.nombre AS plan_nombre,
						sga_planes.tipo_plan as plan_tipo,
						'Plan: (' || sga_planes.codigo || ') - ' || sga_planes.nombre || ' - ' || sga_planes_estados.descripcion as plan_descr,
						sga_ubicaciones.nombre AS ubicacion_nombre,
						" . guarani_sql::get_case('sga_situacion_aspirante', 'resultado_asp', 'inscripcion_estado') . ",
						vw_periodos_insc_propuesta.anio_academico AS anio_academico_descr,
						sga_modalidad_cursada.nombre AS modalidad_nombre,
						'Año de Ingreso: ' || vw_periodos_insc_propuesta.anio_academico AS anio_academico_descr,
						sga_planes_versiones.nombre as plan_version_nombre,
						sga_propuestas_aspira.periodo_insc,
						CASE
							WHEN sga_alumnos.calidad = '" . alumno::calidad_activo . "' THEN
								CASE
									WHEN sga_alumnos.regular = '" . comunes::si . "' THEN sga_alumnos_calidad.nombre || ' (Regular)'
									ELSE sga_alumnos_calidad.nombre || ' (No Regular)'
								END
							ELSE sga_alumnos_calidad.nombre
						END as calidad_regular
				FROM	sga_propuestas_aspira  
				JOIN	sga_situacion_aspirante ON sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp
				JOIN	vw_periodos_insc_propuesta ON vw_periodos_insc_propuesta.periodo_insc = sga_propuestas_aspira.periodo_insc
				JOIN	sga_alumnos ON sga_alumnos.persona = sga_propuestas_aspira.persona AND sga_alumnos.propuesta = sga_propuestas_aspira.propuesta
				JOIN	sga_alumnos_calidad ON sga_alumnos_calidad.calidad = sga_alumnos.calidad
				JOIN	sga_planes_versiones ON sga_planes_versiones.plan_version = sga_propuestas_aspira.plan_version
				JOIN	sga_planes ON sga_planes.plan = sga_planes_versiones.plan
				JOIN	vw_personas ON vw_personas.persona = sga_propuestas_aspira.persona
				JOIN	vw_perfil_de_datos_propuestas ON vw_perfil_de_datos_propuestas.propuesta = sga_propuestas_aspira.propuesta
				JOIN	sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_propuestas_aspira.ubicacion
				JOIN	sga_modalidad_cursada ON sga_modalidad_cursada.modalidad = sga_propuestas_aspira.modalidad
				JOIN	sga_planes_estados ON sga_planes_estados.estado = sga_planes.estado
				
				WHERE	TRUE	
						$where
				ORDER BY	$order
		";
		return guarani_db::consultar($sql);
	}

	/**
	  Retorna alumnos inscriptos
	 */
	function get_alumnos_detallado($where = '')
	{
		if (!empty($where)) {
			$where = " AND $where";
		}
		$sql = " SELECT 		vw_personas.apellido_nombres AS apellido_y_nombres,
								vw_personas.tipo_nro_documento AS nro_identificacion,
								sga_planes.nombre AS plan_nombre,
								sga_planes_versiones.version,
								sga_modalidad_cursada.nombre AS modalidad_nombre,
								vw_periodos_insc_propuesta.anio_academico,
								sga_alumnos.propuesta,
								vw_periodos_insc_propuesta.anio_academico || '/' || vw_perfil_de_datos_propuestas.nombre AS anio_propuesta_desc,
								sga_alumnos.calidad,
								sga_alumnos_calidad.nombre AS calidad_nombre,
								vw_personas.nacionalidad,
								mdp_nacionalidades.descripcion AS nacionalidad_desc,
								vw_personas.sexo,
								mdp_personas_sexo.descripcion AS sexo_desc

					FROM 		sga_alumnos,
								vw_personas,
								sga_propuestas_aspira,
								sga_situacion_aspirante,
								vw_perfil_de_datos_propuestas,
								sga_planes,
								sga_planes_versiones,
								sga_modalidad_cursada,
								sga_ubicaciones,
								vw_periodos_insc_propuesta,
								sga_alumnos_calidad,
								mdp_nacionalidades,
								mdp_personas_sexo

					WHERE		sga_alumnos.persona = vw_personas.persona AND
								sga_alumnos.persona = sga_propuestas_aspira.persona AND
								sga_alumnos.propuesta = sga_propuestas_aspira.propuesta AND
								sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp AND
								sga_alumnos.propuesta = vw_perfil_de_datos_propuestas.propuesta AND
								sga_alumnos.plan_version = sga_planes_versiones.plan_version AND
								sga_planes_versiones.plan = sga_planes.plan AND
								sga_alumnos.modalidad = sga_modalidad_cursada.modalidad AND
								sga_alumnos.ubicacion = sga_ubicaciones.ubicacion AND
								sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc AND
								sga_alumnos.calidad = sga_alumnos_calidad.calidad AND
								vw_personas.nacionalidad = mdp_nacionalidades.nacionalidad AND
								vw_personas.sexo = mdp_personas_sexo.sexo
	    		    			$where

					ORDER BY 	vw_periodos_insc_propuesta.anio_academico,
								vw_periodos_insc_propuesta.nombre,
								vw_perfil_de_datos_propuestas.nombre,
								sga_alumnos_calidad.nombre,
								mdp_nacionalidades.descripcion,
								mdp_personas_sexo.descripcion,
								vw_personas.apellido,
								vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	function get_modalidades_cursadas()
	{
		$sql = 'SELECT  	modalidad	AS valor,
							nombre		AS descr
				FROM 		sga_modalidad_cursada
				ORDER BY	nombre
  				;';

		return guarani_db::consultar($sql);
	}

	function get_situaciones_aspirante()
	{
		$sql = "SELECT 	sga_situacion_aspirante.situacion_asp 	AS valor,
  						nombre 									AS descr,
						CASE

						WHEN sga_situacion_aspirante.resultado_asp =  '".propuesta::inscripcion_aceptada."' THEN  sga_situacion_aspirante.nombre || ' ( ' ||'".propuesta::inscripcion_aceptada_descr. "'|| ' )' 
						WHEN sga_situacion_aspirante.resultado_asp =  '".propuesta::inscripcion_pendiente."' THEN  sga_situacion_aspirante.nombre || ' ( ' ||'".propuesta::inscripcion_pendiente_descr."'|| ' )' 
						WHEN sga_situacion_aspirante.resultado_asp =  '".propuesta::inscripcion_rechazada."' THEN sga_situacion_aspirante.nombre || ' ( ' ||'".propuesta::inscripcion_rechazada_descr."'|| ' )' 
						END as nombre_descr
				FROM 	sga_situacion_aspirante
				WHERE	sga_situacion_aspirante.resultado_asp = 'A'
						OR sga_situacion_aspirante.resultado_asp = 'P'";

		return guarani_db::consultar($sql);
	}
	function get_descr_situaciones_aspirante()
	{
		$sql = "SELECT 	sga_situacion_aspirante.resultado_asp 	AS valor,
  						CASE

						WHEN sga_situacion_aspirante.resultado_asp =  '".propuesta::inscripcion_aceptada."' THEN  '".propuesta::inscripcion_aceptada_descr."'
						WHEN sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_pendiente."' THEN  '".propuesta::inscripcion_pendiente_descr."'
						WHEN sga_situacion_aspirante.resultado_asp =  '".propuesta::inscripcion_rechazada."'  THEN '".propuesta::inscripcion_rechazada_descr."'
						END as descr
				FROM 	sga_situacion_aspirante
				WHERE	sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_aceptada."'
						OR sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_pendiente ."'
						OR sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_rechazada."'";

		return guarani_db::consultar($sql);
	}

	function get_situaciones_aspirante_rechazo()
	{
		$sql = "SELECT		sga_situacion_aspirante.situacion_asp as valor,
							sga_situacion_aspirante.nombre as descr
				FROM		sga_situacion_aspirante
				WHERE		sga_situacion_aspirante.resultado_asp = 'R'
				ORDER BY	sga_situacion_aspirante.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la cantidad de inscripciones para una persona y una propuesta dadas.
	 * Si se indica un código de inscripción en particular, se tienen en cuenta sólo las inscripciones diferentes a esta misma.
	 *
	 * @param integer $persona
	 * @param integer $propuesta
	 * @param integer $propuesta_aspira
	 * @return integer
	 */
	function get_cant_inscripciones($persona, $propuesta, $propuesta_aspira = null, $where = '')
	{
		$persona = toba::db()->quote($persona);
		$propuesta = toba::db()->quote($propuesta);
		if ($where) {
			$where = " AND $where";
		}
		if (!empty($propuesta_aspira)) {
			$propuesta_aspira = toba::db()->quote($propuesta_aspira);
			$where .= " AND sga_propuestas_aspira.propuesta_aspira <> $propuesta_aspira";
		}
		$sql = "SELECT	COUNT(sga_propuestas_aspira.propuesta_aspira) as cant_inscripciones
        		FROM	sga_propuestas_aspira,
        				sga_situacion_aspirante
				WHERE	sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp AND
						sga_propuestas_aspira.persona = $persona AND
						sga_propuestas_aspira.propuesta = $propuesta
						$where
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant_inscripciones'];
	}

	//------------------------------------------------------------------------
	//-- Motivos de Rechazo de Inscripciones a Actividad o Examen
	//------------------------------------------------------------------------
	/**
	 * Obtiene una lista de motivos de rechazo de inscripciones a cursadas y examen
	 * según filtro especificado.
	 *
	 */
	function get_listado_motivos_rechazo_inscripcion($where = '')
	{
		if ($where != '') {
			$where = ' WHERE ' . $where;
		}

		$sql = "SELECT 
					sga_inscripciones_motivos_rechazo.motivo_rechazo,
					sga_inscripciones_motivos_rechazo.nombre,
					sga_inscripciones_motivos_rechazo.descripcion,
					sga_inscripciones_motivos_rechazo.alcance,
					sga_inscripciones_motivos_rechazo.activo,
					CASE 
							WHEN sga_inscripciones_motivos_rechazo.activo = 'S' THEN 'Activo'
							ELSE 'Inactivo'
					END as activo_desc
				FROM 
					sga_inscripciones_motivos_rechazo
				$where
    			ORDER BY
    				sga_inscripciones_motivos_rechazo.nombre
		
    			";

		return guarani_db::consultar($sql);
	}

	function get_listado_motivos_rechazo_inscripcion_activos($where = '')
	{
		$filtro = "sga_inscripciones_motivos_rechazo.activo = '" . comunes::si . "'";
		if ($where) {
			$filtro .= ' AND ' . $where;
		}
		return $this->get_listado_motivos_rechazo_inscripcion($filtro);
	}

	/**
	 *  Valida si un nombre de motivo de rechazo de inscripciones se puede usar o no.
	 */
	function validar_nombre_motivo_rechazo_insc($nombre, $motivo)
	{
		if (!isset($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);

		$sql = 'SELECT	count(sga_inscripciones_motivos_rechazo.motivo_rechazo) as cant
                FROM 	sga_inscripciones_motivos_rechazo
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('sga_inscripciones_motivos_rechazo.nombre', $nombre) . '
				';

		if (!is_null($motivo)) {
			$motivo = toba::db()->quote($motivo);
			$sql .= " AND sga_inscripciones_motivos_rechazo.motivo_rechazo <> $motivo";
		}

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Retorna las opciones disponibles para el combo Alcance de motivos de rechazo de inscripciones
	 *
	 */
	function get_motivo_rechazo_insc_alcance()
	{
		return array(array('valor' => 'Cursadas', 'descr' => 'Cursadas'),
			array('valor' => 'Examenes', 'descr' => 'Exámenes'),
			array('valor' => 'Todos', 'descr' => 'Todos')
		);
	}

	//------------------------------------------------------------------------
	//-- Motivos de Excepción de Inscripciones a Actividad o Examen
	//------------------------------------------------------------------------
	/**
	 * Obtiene una lista de motivos de excepción de inscripciones a cursadas y examen
	 * según filtro especificado.
	 *
	 */
	function get_listado_motivos_excepcion_inscripcion($where = '')
	{
		if ($where != '') {
			$where = ' WHERE ' . $where;
		}

		$sql = "SELECT 
					sga_inscripciones_motivos_excepcion.motivo_excepcion,
					sga_inscripciones_motivos_excepcion.nombre,
					sga_inscripciones_motivos_excepcion.descripcion,
					sga_inscripciones_motivos_excepcion.alcance,
					sga_inscripciones_motivos_excepcion.activo
				FROM 
					sga_inscripciones_motivos_excepcion
				$where
    			ORDER BY
    				sga_inscripciones_motivos_excepcion.nombre
    			";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de motivos de excepción de inscripciones a cursadas y examen
	 * según filtro especificado.
	 *
	 */
	function get_motivos_excepcion_inscripcion($where = '')
	{
		if ($where != '') {
			$where = ' WHERE ' . $where;
		}

		$sql = "SELECT 
					sga_inscripciones_motivos_excepcion.motivo_excepcion AS valor,
					sga_inscripciones_motivos_excepcion.nombre AS descr
				FROM 
					sga_inscripciones_motivos_excepcion
				    $where
				ORDER BY
					sga_inscripciones_motivos_excepcion.nombre
    			";

		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de motivos de excepción de inscripciones a CURSADAS:
	 *  - activos 
	 *  - de alcance cursadas o todos.
	 * 
	 */
	function get_motivos_excepcion_inscripcion_cursada($where = '')
	{
		$where = "	sga_inscripciones_motivos_excepcion.activo = '" . comunes::si . "'
					AND ( sga_inscripciones_motivos_excepcion.alcance = 'Cursadas' 
						  OR sga_inscripciones_motivos_excepcion.alcance = 'Todos'
						)
				";

		return $this->get_motivos_excepcion_inscripcion($where);
	}

	/**
	 * Obtiene una lista de motivos de excepción de inscripciones a CURSADAS:
	 *  - activos 
	 *  - de alcance cursadas o todos.
	 * 
	 */
	function get_motivos_excepcion_inscripcion_examen($where = '')
	{
		$where = "	sga_inscripciones_motivos_excepcion.activo = '" . comunes::si . "'
					AND ( sga_inscripciones_motivos_excepcion.alcance = 'Examenes' 
						  OR sga_inscripciones_motivos_excepcion.alcance = 'Todos'
						)
				";

		return $this->get_motivos_excepcion_inscripcion($where);
	}

	/**
	 *  Valida si un nombre de motivo de excepción de inscripciones se puede usar o no.
	 */
	function validar_nombre_motivo_excepcion_insc($nombre, $motivo)
	{
		if (!isset($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);

		$sql = 'SELECT	count(sga_inscripciones_motivos_excepcion.motivo_excepcion) as cant
                FROM 	sga_inscripciones_motivos_excepcion
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('sga_inscripciones_motivos_excepcion.nombre', $nombre) . '
				';

		if (!is_null($motivo)) {
			$motivo = toba::db()->quote($motivo);
			$sql .= " AND sga_inscripciones_motivos_excepcion.motivo_excepcion <> $motivo";
		}

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Retorna los datos de inscripciones tales que:
	 * - Correspondan a la última inscripción por persona+propuesta.
	 * - Estén RECHAZADAS.
	 *
	 * @param str $where
	 * @return array
	 */
	function get_ultimas_inscripciones_rechazadas($where = '')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_propuestas_aspira.propuesta_aspira,
							vw_personas.apellido_nombres AS apellido_y_nombres,
							vw_personas.tipo_nro_documento AS nro_identificacion,
							sga_propuestas_aspira.propuesta,
							vw_perfil_de_datos_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
							sga_planes.nombre AS plan_nombre,
							sga_ubicaciones.nombre AS ubicacion_nombre,
							sga_propuestas_aspira.anio_academico,
							sga_propuestas_aspira.fecha_rechazo
				FROM		sga_propuestas_aspira,
							vw_personas,
							vw_perfil_de_datos_propuestas,
							sga_planes_versiones,
							sga_planes,
							sga_ubicaciones,
							sga_situacion_aspirante
				WHERE		sga_propuestas_aspira.persona = vw_personas.persona AND
							sga_propuestas_aspira.propuesta = vw_perfil_de_datos_propuestas.propuesta AND
							sga_propuestas_aspira.plan_version = sga_planes_versiones.plan_version AND
							sga_planes_versiones.plan = sga_planes.plan AND
							sga_propuestas_aspira.ubicacion = sga_ubicaciones.ubicacion AND
							sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp AND
							-- Última inscripción de la persona en la propuesta.
							sga_propuestas_aspira.fecha_inscripcion = (	SELECT	MAX(a.fecha_inscripcion)
																		FROM	sga_propuestas_aspira AS a
																		WHERE	a.persona = sga_propuestas_aspira.persona AND
																				a.propuesta = sga_propuestas_aspira.propuesta) AND
							-- Inscripciones RECHAZADAS.
							sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_rechazada . "'
							$where
				ORDER BY	vw_personas.apellido,
							vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene el nro. de transacción a partir de los datos de una inscripción.
	 *
	 * @param integer $persona
	 * @param integer $propuesta
	 * @param integer $periodo_insc
	 * @return integer
	 */
	function get_nro_transaccion_insc($persona, $propuesta, $periodo_insc)
	{
		$persona = toba::db()->quote($persona);
		$propuesta = toba::db()->quote($propuesta);
		$periodo_insc = toba::db()->quote($periodo_insc);

		$sql = "SELECT 	sga_propuestas_aspira.nro_transaccion
				FROM 	sga_propuestas_aspira
				WHERE 	sga_propuestas_aspira.persona = $persona AND
						sga_propuestas_aspira.propuesta = $propuesta AND
						sga_propuestas_aspira.periodo_insc = $periodo_insc
		";

		$datos = guarani_db::consultar_fila($sql);
		return $datos['nro_transaccion'];
	}

	/**
	 * Obtiene el nro. de transacción a partir de la columna 'propuesta_aspira' de la tabla 'sga_propuestas_aspira'.
	 *
	 * @param integer $propuesta_aspira
	 * @return integer
	 */
	function get_nro_transaccion_propuesta_aspira($propuesta_aspira)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);

		$sql = "SELECT 	sga_propuestas_aspira.nro_transaccion
				FROM 	sga_propuestas_aspira
				WHERE 	sga_propuestas_aspira.propuesta_aspira = $propuesta_aspira";

		$datos = guarani_db::consultar_fila($sql);
		return $datos['nro_transaccion'];
	}

	/**
	 * Retorna los comprobantes de inscripción del alumno dado como parámetro, según el filtro.
	 *
	 * @param integer $alumno
	 * @return array
	 */
	function get_comprobantes_inscripcion_alumno($alumno, $where = '')
	{
		$alumno = toba::db()->quote($alumno);

		if ($where) {
			$where = " AND $where";
		}

		$sql = "SELECT 		vw_comprobantes.tipo,
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
							'(' || vw_comprobantes.actividad_codigo || ') ' || vw_comprobantes.actividad_nombre AS actividad_descr
				FROM 		vw_comprobantes
								LEFT JOIN sga_propuestas ON vw_comprobantes.propuesta = sga_propuestas.propuesta
				WHERE 		vw_comprobantes.alumno = $alumno
							$where
				ORDER BY	vw_comprobantes.fecha
				";

		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna las opciones Inscripción a Propuesta / Baja inscripción a Propuesta / Reinscripción a Propuesta
	 * Inscripción a Actividad / Baja inscripción a Actividad / Inscripción a Examen / Baja inscripción a Examen
	 */
	function get_tipos_comprobantes()
	{
		return array(
			array('valor' => comprobantes::tipo_comprobante_insc_act ,		'descr' => comprobantes::tipo_comprobante_insc_act_descr),
			array('valor' => comprobantes::tipo_comprobante_baja_insc_act , 'descr' => comprobantes::tipo_comprobante_baja_insc_act_descr),
			array('valor' => comprobantes::tipo_comprobante_rec_insc_act ,	'descr' => comprobantes::tipo_comprobante_rec_insc_act_descr),
			array('valor' => comprobantes::tipo_comprobante_insc_exa ,		'descr' => comprobantes::tipo_comprobante_insc_exa_descr),
			array('valor' => comprobantes::tipo_comprobante_baja_insc_exa , 'descr' => comprobantes::tipo_comprobante_baja_insc_exa_descr),
			array('valor' => comprobantes::tipo_comprobante_rec_insc_exa ,	'descr' => comprobantes::tipo_comprobante_rec_insc_exa_descr),
			array('valor' => comprobantes::tipo_comprobante_insc_prop ,		'descr' => comprobantes::tipo_comprobante_insc_prop_descr),
			array('valor' => comprobantes::tipo_comprobante_reinsc_prop ,	'descr' => comprobantes::tipo_comprobante_reinsc_prop_descr),
			array('valor' => comprobantes::tipo_comprobante_sol_constancia ,	'descr' => comprobantes::tipo_comprobante_sol_constancia_descr)
			);
	}

	function cumple_requisitos_ingreso_documentales($propuesta_aspira, $where='')
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT	sga_requisitos_aspirante.requisito

				FROM	sga_propuestas_aspira
						JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
						JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
						JOIN sga_requisitos_aspirante ON sga_propuestas_aspira.propuesta_aspira = sga_requisitos_aspirante.propuesta_aspira
						JOIN sga_requisitos ON sga_requisitos_aspirante.requisito = sga_requisitos.requisito
						LEFT JOIN sga_requisitos_ingreso_excep ON (sga_requisitos_ingreso_excep.periodo_inscripcion = vw_periodos_insc_propuesta.periodo_inscripcion AND sga_requisitos_ingreso_excep.requisito = sga_requisitos_aspirante.requisito)

				WHERE	sga_propuestas_aspira.propuesta_aspira = $propuesta_aspira AND
						sga_requisitos_aspirante.exceptuado = '" . comunes::no . "' AND
						sga_requisitos.requisito_tipo = " . requisito::tipo_documentales . " AND
						NOT f_requisito_presentado(sga_propuestas_aspira.persona, sga_requisitos_aspirante.requisito, CURRENT_DATE, sga_propuestas_aspira.propuesta_aspira, GREATEST(sga_requisitos_aspirante.fecha_prorroga, COALESCE(sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos)))
						$where
		";
		$rs = guarani_db::consultar($sql);
		return empty($rs);
	}

	function get_requisitos_ingreso_tipo_proceso($propuesta_aspira, $where='')
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT	sga_propuestas_aspira.persona,
						sga_propuestas_aspira.anio_academico,
						sga_propuestas_aspira.propuesta,
						sga_propuestas_aspira.plan_version,
						sga_requisitos.regla,
						sga_requisitos_aspirante.parametros

				FROM	sga_propuestas_aspira
						JOIN vw_periodos_insc_propuesta ON sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc
						JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
						JOIN sga_requisitos_aspirante ON sga_propuestas_aspira.propuesta_aspira = sga_requisitos_aspirante.propuesta_aspira
						JOIN sga_requisitos ON sga_requisitos_aspirante.requisito = sga_requisitos.requisito
						LEFT JOIN sga_requisitos_ingreso_excep ON (sga_requisitos_ingreso_excep.periodo_inscripcion = vw_periodos_insc_propuesta.periodo_inscripcion AND sga_requisitos_ingreso_excep.requisito = sga_requisitos_aspirante.requisito)

				WHERE	sga_propuestas_aspira.propuesta_aspira = $propuesta_aspira AND
						sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_pendiente . "' AND
						sga_requisitos_aspirante.exceptuado = '" . comunes::no . "' AND
						sga_requisitos.requisito_tipo = " . requisito::tipo_procesos . "
						$where
		";
		return guarani_db::consultar($sql);
	}

	function get_fecha_inscripcion($propuesta_aspira)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);

		$sql = "SELECT 	sga_propuestas_aspira.fecha_inscripcion
				FROM 	sga_propuestas_aspira
				WHERE	sga_propuestas_aspira.propuesta_aspira = $propuesta_aspira
				";

		$rs = guarani_db::consultar_fila($sql);
		return $rs['fecha_inscripcion'];
	}

	//--------------------------------------------------------------------------
	//---- Funciones auxiliares para preinscripción ----------------------------
	//--------------------------------------------------------------------------

	function get_datos_inscripcion($propuestas_aspira)
	{
		$propuestas_aspira = implode(',', $propuestas_aspira);

		$sql = "SELECT 
					f_responsables_academicas_propuesta(sga_propuestas_aspira.propuesta) AS ras_descr,
					'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS propuesta_descr,
					sga_planes.nombre || ' - ' || sga_planes_versiones.nombre AS plan_descr,
					sga_modalidad_cursada.nombre AS modalidad_descr,
					sga_ubicaciones.nombre AS ubicacion_descr,
					sga_tipos_ingreso.nombre AS tipo_ingreso_descr,
					CASE 
						WHEN sga_situacion_aspirante.resultado_asp = 'A' THEN 'Aceptado'
						WHEN sga_situacion_aspirante.resultado_asp = 'P' THEN 'Pendiente'
						WHEN sga_situacion_aspirante.resultado_asp = 'A' THEN 'Rechazado'
					END as estado_insc_descr,
					to_char(sga_propuestas_aspira.fecha_inscripcion,'" . comunes::formato_fecha_visual . "') AS fecha_inscripcion_visual,
					sga_propuestas_aspira.nro_transaccion
				FROM 
					sga_propuestas_aspira
					JOIN sga_propuestas ON sga_propuestas_aspira.propuesta = sga_propuestas.propuesta
					JOIN sga_planes_versiones ON sga_propuestas_aspira.plan_version = sga_planes_versiones.plan_version
					JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
					JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
					JOIN sga_modalidad_cursada ON sga_propuestas_aspira.modalidad = sga_modalidad_cursada.modalidad
					JOIN sga_ubicaciones ON sga_propuestas_aspira.ubicacion = sga_ubicaciones.ubicacion
					LEFT JOIN sga_tipos_ingreso ON sga_tipos_ingreso.tipo_ingreso = sga_propuestas_aspira.tipo_ingreso
				WHERE
					sga_propuestas_aspira.propuesta_aspira IN ($propuestas_aspira)
		";

		return guarani_db::consultar($sql);
	}

	function get_descr_estado_propuesta_aspira($propuesta_aspira)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);

		$sql = "SELECT 	CASE 
							WHEN sga_situacion_aspirante.resultado_asp = 'A' THEN 'Aceptado'
							WHEN sga_situacion_aspirante.resultado_asp = 'P' THEN 'Pendiente'
							WHEN sga_situacion_aspirante.resultado_asp = 'A' THEN 'Rechazado'
						END as resultado
				FROM 	sga_propuestas_aspira,
						sga_situacion_aspirante
				WHERE	sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
						AND sga_propuestas_aspira.propuesta_aspira = $propuesta_aspira
				";

		$rs = guarani_db::consultar_fila($sql);
		return $rs['resultado'];
	}

	function get_id_ultima_propuesta_aspira($persona, $propuesta)
	{
		$persona = toba::db()->quote($persona);
		$propuesta = toba::db()->quote($propuesta);

		$sql = "SELECT 	propuesta_aspira
				FROM 	sga_propuestas_aspira
				WHERE	persona = $persona
						AND propuesta = $propuesta
				ORDER BY fecha_inscripcion DESC
				LIMIT 1
				";

		$rs = guarani_db::consultar_fila($sql);
		return $rs['propuesta_aspira'] ?? null;
	}

	//--------------------------------------------------------------------------
	/**
	 * Obtiene los datos asociados a la Inscripción a Propuesta, aceptada o pendiente, para una persona y un plan 
	 * versión dados. Sólo puede existir una única inscripción para una propuseta y una persona en estado aceptada
	 * o pendiente (puede haber más pero el resto deberían estar rechazadas).
	 */
	function get_datos_insc_aceptada_o_pendiente_para_persona_planversion($persona, $plan_version)
	{
		$persona = toba::db()->quote($persona);
		$plan_version = toba::db()->quote($plan_version);

		$sql = "SELECT 	sga_propuestas_aspira.propuesta_aspira,
						sga_propuestas_aspira.periodo_insc,
						sga_propuestas_aspira.anio_academico,
						sga_propuestas_aspira.propuesta,
						sga_propuestas_aspira.modalidad,
						sga_propuestas_aspira.ubicacion,
						sga_propuestas_aspira.fecha_inscripcion,
						sga_propuestas_aspira.convenio,
						sga_propuestas_aspira.situacion_asp
				FROM 	sga_propuestas_aspira
				WHERE	sga_propuestas_aspira.persona = $persona			AND
						sga_propuestas_aspira.plan_version = $plan_version	AND
						(sga_propuestas_aspira.situacion_asp = '" . propuesta::situacion_asp_insc_prop_aceptada . "' OR sga_propuestas_aspira.situacion_asp = '" . propuesta::situacion_asp_insc_aspirante_a_prop . "') 
				";

		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Devuelve datos de una inscripción por Número de transacción.
	 * 
	 * @param int $nro_transaccion
	 * @return array Datos de la inscripción
	 */
	function get_datos_inscripcion_by_nro_transaccion($nro_transaccion)
	{
		$nro_transaccion = toba::db()->quote($nro_transaccion);

		$sql = "SELECT 	sga_alumnos.alumno,
						sga_alumnos.legajo,
						sga_alumnos.persona,
						sga_alumnos.propuesta,
						sga_alumnos.plan_version,
						sga_alumnos.ubicacion,
						sga_alumnos.modalidad,
						sga_alumnos.calidad,
						sga_propuestas_aspira.propuesta_aspira
				FROM	sga_alumnos
				JOIN	sga_propuestas_aspira ON (
							sga_alumnos.persona = sga_propuestas_aspira.persona AND
							sga_alumnos.propuesta = sga_propuestas_aspira.propuesta AND
							sga_alumnos.plan_version = sga_propuestas_aspira.plan_version AND
							sga_alumnos.ubicacion = sga_propuestas_aspira.ubicacion AND
							sga_alumnos.modalidad = sga_propuestas_aspira.modalidad
					)
				WHERE sga_propuestas_aspira.nro_transaccion = $nro_transaccion;
				";

		return guarani_db::consultar_fila($sql);
	}

	/**
	 * Retorna el listado de inscriptos a propuesta en estado aceptado/pendiente. Alumnos en calidad Activo.
	 * Si el usuario logueado tiene perfil de datos con dominio "Responsables Académicas"
	 * restringe las inscripciones a aquellas propuestas que gestiona la RA.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_inscriptos_activos($where = '')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT	vw_personas.apellido_nombres,
						vw_personas.apellido,
						vw_personas.nombres,
						vw_personas.tipo_nro_documento,
						sga_alumnos.propuesta,
						vw_perfil_de_datos_propuestas.propuesta_descripcion,
						sga_planes.codigo as plan_codigo,
						sga_planes.nombre as nombre_plan,
						sga_planes_versiones.version,
						sga_alumnos.ubicacion,
						sga_alumnos.plan_version,
						sga_alumnos.alumno,
						sga_ubicaciones.nombre as ubicacion_nombre,
						sga_propuestas_aspira.fecha_inscripcion,
						" . guarani_sql::get_case('sga_situacion_aspirante', 'resultado_asp', 'inscripcion_estado') . ",
						sga_tipos_ingreso.nombre as ingreso_tipo_nombre,
						sga_modalidad_cursada.nombre as modalidad_cursada_nombre,
						sga_propuestas_aspira.anio_academico,
						vw_periodos_insc_propuesta.periodo_inscripcion,
						vw_periodos_insc_propuesta.anio_academico || ' - ' || vw_periodos_insc_propuesta.nombre || ' (' || to_char(vw_periodos_insc_propuesta.fecha_inicio,'" . comunes::formato_fecha_visual . "') || ' al ' || to_char(vw_periodos_insc_propuesta.fecha_fin,'" . comunes::formato_fecha_visual . "') || ')' as anio_periodo_desc
				FROM	sga_propuestas_aspira
				JOIN	vw_personas ON vw_personas.persona = sga_propuestas_aspira.persona
				JOIN	sga_alumnos ON sga_alumnos.persona = sga_propuestas_aspira.persona AND sga_alumnos.propuesta = sga_propuestas_aspira.propuesta
				JOIN	vw_perfil_de_datos_propuestas ON vw_perfil_de_datos_propuestas.propuesta = sga_propuestas_aspira.propuesta
				JOIN	sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
				JOIN	sga_planes ON sga_planes.plan = sga_planes_versiones.plan
				JOIN	sga_ubicaciones ON sga_ubicaciones.ubicacion = sga_alumnos.ubicacion
				JOIN	sga_modalidad_cursada ON sga_modalidad_cursada.modalidad = sga_alumnos.modalidad
				JOIN	vw_periodos_insc_propuesta ON vw_periodos_insc_propuesta.periodo_insc = sga_propuestas_aspira.periodo_insc
				JOIN	sga_situacion_aspirante ON sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp
				LEFT JOIN sga_tipos_ingreso ON sga_tipos_ingreso.tipo_ingreso = sga_propuestas_aspira.tipo_ingreso
				WHERE	sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."','".propuesta::inscripcion_pendiente."')
				  AND	sga_alumnos.calidad = '" . alumno::calidad_activo . "'
				$where
				ORDER BY vw_personas.apellido_nombres
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Devuelve datos relacionados a una 'propuesta_aspira'.
	 * 
	 * @param int $propuesta_aspira
	 * @return array Datos de la 'propuesta_aspira'
	 */
	function get_detalle_propuesta_aspira($propuesta_aspira)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);

		$sql = "SELECT 	sga_propuestas_aspira.propuesta_aspira,
						sga_propuestas_aspira.persona,
						sga_propuestas_aspira.propuesta,
						sga_planes.plan,
						sga_planes.codigo AS plan_codigo,
						sga_planes.cobrable AS plan_cobrable,
						sga_planes.sq_sincronizado AS plan_sq_sincronizado,
						sga_planes_versiones.plan_version,
						sga_propuestas_aspira.nro_transaccion,
						sga_propuestas_aspira.sq_token,
						sga_propuestas_aspira.sq_sincronizado,
						sga_propuestas_aspira.situacion_asp
				FROM sga_propuestas_aspira
				JOIN sga_planes_versiones ON (sga_propuestas_aspira.plan_version = sga_planes_versiones.plan_version)
				JOIN sga_planes ON (sga_planes.plan = sga_planes_versiones.plan)
				WHERE sga_propuestas_aspira.propuesta_aspira = $propuesta_aspira
				";

		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Devuelve datos relacionados a una 'propuesta_aspira_log'.
	 * 
	 * @param int $propuesta_aspira
	 * @return array Datos de la 'propuesta_aspira_log'
	 */
	function get_detalle_propuesta_aspira_log($propuesta_aspira)
	{
		$propuesta_aspira = toba::db()->quote($propuesta_aspira);

		$sql = "SELECT 	sga_propuestas_aspira_log.propuesta_aspira,
						sga_propuestas_aspira_log.persona,
						sga_propuestas_aspira_log.propuesta,
						sga_planes.plan,
						sga_planes.codigo AS plan_codigo,
						sga_planes.cobrable AS plan_cobrable,
						sga_planes.sq_sincronizado AS plan_sq_sincronizado,
						sga_planes_versiones.plan_version,
						sga_propuestas_aspira_log.nro_transaccion,
						sga_propuestas_aspira_log.sq_token,
						sga_propuestas_aspira_log.sq_sincronizado
				FROM sga_propuestas_aspira_log
				JOIN sga_planes_versiones ON (sga_propuestas_aspira_log.plan_version = sga_planes_versiones.plan_version)
				JOIN sga_planes ON (sga_planes.plan = sga_planes_versiones.plan)
				WHERE sga_propuestas_aspira_log.propuesta_aspira = $propuesta_aspira
				";

		return guarani_db::consultar_fila($sql);
	}
	//Combina Aprobado/pendientes
	function get_descr_situaciones_aspirante_personalizado()
	{
		$sql = "SELECT '".propuesta::inscripcion_aceptada."' || '-' || '".propuesta::inscripcion_pendiente."' AS valor , 
				'".propuesta::inscripcion_aceptada_descr."' || '/' || '".propuesta::inscripcion_pendiente_descr."'	AS descr FROM sga_situacion_aspirante
				UNION 
				SELECT 	sga_situacion_aspirante.resultado_asp 	AS valor,
  						CASE
						WHEN sga_situacion_aspirante.resultado_asp =  '".propuesta::inscripcion_aceptada."' THEN  '".propuesta::inscripcion_aceptada_descr."'
						WHEN sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_pendiente."' THEN  '".propuesta::inscripcion_pendiente_descr."'
						WHEN sga_situacion_aspirante.resultado_asp =  '".propuesta::inscripcion_rechazada."'  THEN '".propuesta::inscripcion_rechazada_descr."'
						END as descr
				FROM 	sga_situacion_aspirante
				WHERE	sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_aceptada."'
						OR sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_pendiente ."'
						OR sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_rechazada."'";

		return guarani_db::consultar($sql);
	}
	//Permite seleccionar 'todos' en el combo
	function get_situaciones_aspirante_rechazo_todos()
	{
		$sql = "SELECT -1 as valor, 'Todos' as descr FROM sga_situacion_aspirante
				UNION
				SELECT		sga_situacion_aspirante.situacion_asp as valor,
							sga_situacion_aspirante.nombre as descr
				FROM		sga_situacion_aspirante
				WHERE		sga_situacion_aspirante.resultado_asp = 'R'
				ORDER BY descr desc				
		";
		return guarani_db::consultar($sql);
	}

}