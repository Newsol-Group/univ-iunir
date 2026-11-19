<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage 	-
 * 
 */
class co_mensajes
{
	function get_listado($where = '')
	{
		// Por defecto se recuperan mensajes con las siguientes condiciones:
		//	* enviados desde la interfaz de gestion
		//  * visibles en gestion 
		//  * fecha actual menor o igual a la fecha fin de publicación
		//  * que son de envío manual.
		//  * aplicado perfil de datos segun la persona que envió el mensaje. (si hay perfil de datos)
		// Este filtro se realizan en el cn_man_envio_mensajes.php, método "get_listado"
		if ($where) {
			$where = 'AND ' . $where;
		}
		
		$sql = "SELECT 		men_mensajes.mensaje,
							men_mensajes.titulo,
				            men_alcances.nombre as alcance_nombre,
				            to_char(men_mensajes.fecha_alta,'" . comunes::formato_fecha_visual . "') as fecha_envio,
				            to_char(men_mensajes.fecha_desde,'" . comunes::formato_fecha_visual . "') || ' - ' || to_char(men_mensajes.fecha_hasta,'" . comunes::formato_fecha_visual . "')  as publicar_desde_hasta,
							men_mensajes.enviar_por_mail,
							men_mensajes.enviar_por_facebook,
							men_mensajes.enviar_por_twitter,
							men_mensajes.activo,
							men_mensajes.error_envio_mail
				FROM 		men_mensajes,
							men_alcances
				WHERE		men_alcances.alcance = men_mensajes.alcance
							$where
				ORDER BY	men_mensajes.fecha_alta DESC
				";

		return guarani_db::consultar($sql);
	}

	function get_alcances($where = '')
	{
		$no = toba::db()->quote(comunes::no);

		$sql = "SELECT 	men_alcances.alcance as valor,
						men_alcances.nombre as descr
				FROM 	men_alcances
				WHERE	men_alcances.envio_automatico = $no
						$where
				ORDER BY
						men_alcances.nombre				
				";

		return guarani_db::consultar($sql);
	}

	function get_listado_adjuntos($mensaje)
	{
		$mensaje = toba::db()->quote($mensaje);

		$sql = "SELECT 	rep_archivos.id_archivo,
						rep_archivos.nombre_archivo,
						rep_archivos.nombre_archivo as nombre,
						rep_archivos.path_archivo,
						rep_archivos.path_archivo as path,
						rep_archivos.url_archivo,
						rep_archivos.fecha_upload,
						rep_archivos.descripcion,
						rep_archivos.tamanio_en_kb,
						rep_archivos.extension
				FROM 	rep_archivos
				JOIN	men_documentos ON men_documentos.id_archivo = rep_archivos.id_archivo	
				WHERE	men_documentos.mensaje = $mensaje
				";

		return guarani_db::consultar($sql);
	}

	function get_archivo_adjunto($id_archivo)
	{
		$id_archivo = toba::db()->quote($id_archivo);

		$sql = "SELECT 	rep_archivos.id_archivo,
						rep_archivos.nombre_archivo,
						rep_archivos.path_archivo,
						rep_archivos.url_archivo,
						rep_archivos.fecha_upload,
						rep_archivos.descripcion,
						rep_archivos.tamanio_en_kb,
						rep_archivos.extension
				FROM 	rep_archivos
				WHERE	rep_archivos.id_archivo	= $id_archivo
				";

		return guarani_db::consultar_fila($sql);
	}

	function get_archivo_adjunto_by_hash($hash)
	{
		$hash = toba::db()->quote($hash);

		$sql = "SELECT 	rep_archivos.id_archivo,
						rep_archivos.nombre_archivo,
						rep_archivos.path_archivo,
						rep_archivos.url_archivo,
						rep_archivos.fecha_upload,
						rep_archivos.descripcion,
						rep_archivos.tamanio_en_kb,
						rep_archivos.extension
				FROM 	rep_archivos
				WHERE	rep_archivos.url_archivo LIKE '%' || $hash || '%'
				";

		return guarani_db::consultar_fila($sql);
	}

	function get_destinatarios_mail($mensaje)
	{
		$mensaje = toba::db()->quote($mensaje);

		$mail_prim = persona::mail_primario;
		$mail_sec = persona::mail_secundario;
		$mail_inst = persona::mail_institucional;
		
		// Recupera los mails de los destinatarios del mensaje. Solo un mail por persona.
		$sql = "SELECT 	(SELECT 	mdp_personas_contactos.email
                        FROM 	mdp_personas
						JOIN 	mdp_personas_contactos ON mdp_personas_contactos.persona = mdp_personas.persona
                        WHERE	mdp_personas.persona = men_destinatarios.persona
                        AND		mdp_personas.recibe_mensajes_x_mail = 1
                        AND		mdp_personas_contactos.contacto_tipo IN ('$mail_prim','$mail_sec','$mail_inst')
                        AND		mdp_personas_contactos.email IS NOT NULL
                        ORDER BY
								CASE 
									WHEN contacto_tipo = '$mail_prim' THEN 1 
									WHEN contacto_tipo = '$mail_inst' THEN 2
									WHEN contacto_tipo = '$mail_sec' THEN 3
									ELSE 4
								END
                        LIMIT 1) as email
				FROM	men_destinatarios
				WHERE	men_destinatarios.mensaje = $mensaje
				AND EXISTS (SELECT 1 
							FROM 	mdp_personas
							JOIN 	mdp_personas_contactos ON mdp_personas_contactos.persona = mdp_personas.persona
                       		WHERE	mdp_personas.persona = men_destinatarios.persona
                       		AND		mdp_personas.recibe_mensajes_x_mail = 1
                           	AND		mdp_personas_contactos.contacto_tipo IN ('$mail_prim','$mail_sec','$mail_inst')
                       		AND		mdp_personas_contactos.email IS NOT NULL
							)
			";

		$datos = guarani_db::consultar($sql);

		$salida = array();
		foreach ($datos as $email) {
			if (!empty($email['email'])) {
				$salida[] = $email['email'];
			}
		}

		return $salida;
	}

	function get_id_archivo($path)
	{
		$path = toba::db()->quote($path);

		$sql = "SELECT 	rep_archivos.id_archivo
				FROM 	rep_archivos
				WHERE	rep_archivos.path_archivo = $path
				";

		$res = guarani_db::consultar_fila($sql);
		if (!empty($res['id_archivo'])) {
			return $res['id_archivo'];
		} else {
			return null;
		}
	}

	/**
	 * Devuelve texto de una plantilla de la tabla men_mensajes_plantillas
	 * 
	 * @param $plantilla id de la plantilla buscada
	 * @return string texto de la plantilla
	 */
	function get_mensaje_plantilla($plantilla)
	{
		$consulta = $this->get_plantilla($plantilla);
		return $consulta['texto'];
	}

	/**
	 * Devuelve los datos de una plantilla de la tabla men_mensajes_plantillas
	 *
	 * @param $plantilla id de la plantilla buscada
	 * @return array Datos de la plantilla
	 */
	function get_plantilla($plantilla)
	{
		$sql = "SELECT	titulo, 
						texto 
				FROM	men_mensajes_plantillas
				WHERE	plantilla = $plantilla";
		return guarani_db::consultar_fila($sql);
	}

	function get_tipos_mensajes()
	{
		$tipo_mensaje_forma_envio_automatico = toba::db()->quote(mensajes::tipo_mensaje_forma_envio_automatico);

		$sql = "SELECT 	tipo_mensaje AS valor, 
						nombre 		 AS descr
				FROM men_tipos_mensajes
				WHERE forma_envio NOT IN ($tipo_mensaje_forma_envio_automatico)";

		return guarani_db::consultar($sql);
	}

	function get_todos_alumnos_y_docentes()
	{
		$sql = "SELECT DISTINCT sga_alumnos.persona									    
				  FROM sga_alumnos
				 WHERE sga_alumnos.calidad = '" . comunes::estado_activo . "'
				UNION
				SELECT DISTINCT sga_docentes.persona
				  FROM sga_docentes
				 WHERE sga_docentes.estado = '" . comunes::estado_activo . "'
				";
		return guarani::db()->consultar($sql);
	}

	function get_todos_alumnos()
	{
		$sql = "
				SELECT 	DISTINCT sga_alumnos.persona
					FROM 	sga_alumnos
					WHERE	sga_alumnos.calidad = '" . comunes::estado_activo . "'
				";
		return guarani::db()->consultar($sql);
	}

	function get_alumnos_ra($dominio)
	{
		$where = guarani_sql::armar_condicion_in($dominio, 'sga_propuestas_ra', 'responsable_academica', 'responsable_academica');

		$sql = "SELECT 	DISTINCT sga_alumnos.persona
				FROM 	sga_alumnos
				JOIN	sga_propuestas_ra ON sga_alumnos.propuesta = sga_propuestas_ra.propuesta
				WHERE	sga_alumnos.calidad = '" . comunes::estado_activo . "'
				  AND $where
				";
		return guarani::db()->consultar($sql);
	}

	function get_alumnos_ubicacion($dominio)
	{
		$where = guarani_sql::armar_condicion_in($dominio, 'sga_alumnos', 'ubicacion', 'ubicacion');

		$sql = "SELECT 	DISTINCT sga_alumnos.persona				
					FROM 	sga_alumnos
					JOIN	mdp_personas ON mdp_personas.persona = sga_alumnos.persona
					WHERE	sga_alumnos.calidad = '" . comunes::estado_activo . "'
					  AND $where
				";
		return guarani::db()->consultar($sql);
	}

	function get_alumnos_propuesta($dominio)
	{
		$where = guarani_sql::armar_condicion_in($dominio, 'sga_alumnos', 'propuesta', 'propuesta');

		$sql = "SELECT 	DISTINCT sga_alumnos.persona
					FROM 	sga_alumnos
					WHERE	sga_alumnos.calidad = '" . comunes::estado_activo . "'
							AND $where
				";
		return guarani::db()->consultar($sql);
	}

	function get_alumnos_propuesta_plan($dominio)
	{
		$where = $this->armar_row_propuesta_plan($dominio);

		$sql = "	SELECT 	DISTINCT sga_alumnos.persona
					FROM 	sga_alumnos
					JOIN	sga_planes_versiones ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
					WHERE	sga_alumnos.calidad = '" . comunes::estado_activo . "'
					AND 	$where
				";
		return guarani::db()->consultar($sql);
	}

	function get_alumnos_propuesta_plan_ubicacion($dominio)
	{
		$where = $this->armar_row_propuesta_plan_ubicacion($dominio);
		$sql = "
					SELECT	DISTINCT sga_alumnos.persona
					FROM	sga_alumnos
					JOIN	sga_planes_versiones	ON sga_planes_versiones.plan_version	= sga_alumnos.plan_version
					JOIN	sga_propuestas_ra		ON sga_propuestas_ra.propuesta			= sga_alumnos.propuesta
					WHERE	sga_alumnos.calidad = '" . comunes::estado_activo . "'
					AND		$where
				";
		return guarani::db()->consultar($sql);
	}

	function get_alumnos_ingresantes($dominio)
	{
		$where = $this->armar_row_propuesta_ubicacion($dominio);
		$dominio = current($dominio);
		$estados = array();
		if ($dominio['insc_aceptadas'] == comunes::si) {
			$estados[] = toba::db()->quote(propuesta::inscripcion_aceptada);
		}
		if ($dominio['insc_pendientes'] == comunes::si) {
			$estados[] = toba::db()->quote(propuesta::inscripcion_pendiente);
		}
		if ($dominio['insc_rechazadas'] == comunes::si) {
			$estados[] = toba::db()->quote(propuesta::inscripcion_rechazada);
		}
		$where .= empty($estados) ? " AND sga_situacion_aspirante.resultado_asp IN ('".propuesta::inscripcion_aceptada."','".propuesta::inscripcion_pendiente."')" : " AND sga_situacion_aspirante.resultado_asp IN (" . implode(', ', $estados) . ')';

		// Alumnos ingresantes por estado de inscripción. 
		$sql = "SELECT 	DISTINCT sga_propuestas_aspira.persona
				FROM 	sga_propuestas_aspira
				JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
				WHERE	sga_propuestas_aspira.anio_academico = {$dominio['anio_academico']} AND
						$where
		";

		return guarani::db()->consultar($sql);
	}

	function get_alumnos_comision($dominio)
	{
		$select_aceptados_pendiente = '';
		$select_rechazados = '';

		$dominio_estado = current($dominio);
		$estados = array();

		/**
		 * Select de alumnos con inscripciones aceptadas y/o pendientes
		 */
		if (($dominio_estado['insc_aceptadas']) == comunes::si) {
			$estados[] = toba::db()->quote(inscripcion_cursada::estado_aceptada);
		}
		if (($dominio_estado['insc_pendientes']) == comunes::si) {
			$estados[] = toba::db()->quote(inscripcion_cursada::estado_pendiente);
		}
		if (!empty($estados)) {
			$where = guarani_sql::armar_condicion_in($dominio, 'sga_insc_cursada', 'comision', 'comision');
			$where .= " AND sga_insc_cursada.estado IN (" . implode(', ', $estados) . ')';
			$select_aceptados_pendiente = "
				SELECT 	DISTINCT sga_alumnos.persona						
				FROM 	sga_insc_cursada
				JOIN	sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
				WHERE	sga_alumnos.calidad = '" . comunes::estado_activo . "'
				  AND $where";
		}

		/**
		 * Select de alumnos con inscripciones rechazadas
		 */
		if (($dominio_estado['insc_rechazadas']) == comunes::si) {
			if (!empty($select_aceptados_pendiente)) {
				$select_rechazados = "UNION ALL ";
			}
			$select_rechazados .= "
				SELECT 	DISTINCT sga_alumnos.persona
				FROM 	sga_insc_cursada_log
				JOIN	sga_alumnos ON sga_insc_cursada_log.alumno = sga_alumnos.alumno
				WHERE	sga_alumnos.calidad = '" . comunes::estado_activo . "'
				  AND sga_insc_cursada_log.operacion = '" . inscripcion_cursada::operacion_rechazo . "'
				  AND " . guarani_sql::armar_condicion_in($dominio, 'sga_insc_cursada_log', 'comision', 'comision');
		}

		$sql = "
					$select_aceptados_pendiente
					$select_rechazados
				";

		return guarani::db()->consultar($sql);
	}

	function get_alumnos_mesa_examen($dominio)
	{
		$select_aceptados_pendiente = '';
		$select_rechazados = '';

		$dominio_estado = current($dominio);
		$estados = array();

		/**
		 * Select de alumnos con inscripciones aceptadas y/o pendientes
		 */
		if (($dominio_estado['insc_aceptadas']) == comunes::si) {
			$estados[] = toba::db()->quote(inscripcion_examen::estado_aceptada);
		}
		if (($dominio_estado['insc_pendientes']) == comunes::si) {
			$estados[] = toba::db()->quote(inscripcion_examen::estado_pendiente);
		}
		if (!empty($estados)) {
			$where = guarani_sql::armar_condicion_in($dominio, 'sga_insc_examen', 'llamado_mesa', 'llamado_mesa');
			$where .= " AND sga_insc_examen.estado IN (" . implode(', ', $estados) . ')';
			$select_aceptados_pendiente = "
					SELECT 	DISTINCT sga_alumnos.persona
					FROM 	sga_insc_examen
					JOIN	sga_alumnos ON sga_insc_examen.alumno = sga_alumnos.alumno
					WHERE	sga_alumnos.calidad = '" . comunes::estado_activo . "'
					  AND $where";
		}

		/**
		 * Select de alumnos con inscripciones rechazadas
		 */
		if (($dominio_estado['insc_rechazadas']) == comunes::si) {
			if (!empty($select_aceptados_pendiente)) {
				$select_rechazados = "UNION ALL ";
			}
			$select_rechazados .= "
				SELECT 	DISTINCT sga_alumnos.persona
				FROM 	sga_insc_examen_log
				JOIN	sga_alumnos ON sga_insc_examen_log.alumno = sga_alumnos.alumno
				WHERE	sga_alumnos.calidad = '" . comunes::estado_activo . "'
				  AND sga_insc_examen_log.operacion = '" . inscripcion_examen::operacion_rechazo . "'
				  AND " . guarani_sql::armar_condicion_in($dominio, 'sga_insc_examen_log', 'llamado_mesa', 'llamado_mesa');
		}

		$sql = "	$select_aceptados_pendiente
					$select_rechazados				
				";

		return guarani::db()->consultar($sql);
	}

	function get_todos_docentes()
	{
		$sql = "	SELECT 	DISTINCT sga_docentes.persona
					FROM 	sga_docentes
					WHERE	sga_docentes.estado = '" . comunes::estado_activo . "'
				";

		return guarani::db()->consultar($sql);
	}

	function get_docentes_mesa_examen($dominio)
	{
		$where = guarani_sql::armar_condicion_in($dominio, 'sga_docentes_mesa_llamado', 'llamado_mesa', 'llamado_mesa');

		$sql = "	SELECT 	DISTINCT sga_docentes.persona
					FROM 	sga_docentes_mesa_llamado
					JOIN	sga_docentes ON sga_docentes.docente = sga_docentes_mesa_llamado.docente
					WHERE	sga_docentes.estado = '" . comunes::estado_activo . "'
				      AND $where
				";

		return guarani::db()->consultar($sql);
	}

	function get_docentes_comision($dominio)
	{
		$where = guarani_sql::armar_condicion_in($dominio, 'sga_docentes_comision', 'comision', 'comision');

		$sql = "	SELECT 	DISTINCT sga_docentes.persona
					FROM 	sga_docentes_comision
					JOIN	sga_docentes ON sga_docentes.docente = sga_docentes_comision.docente
					WHERE	sga_docentes.estado = '" . comunes::estado_activo . "'
				       AND $where
				";

		return guarani::db()->consultar($sql);
	}

	function get_docentes_catedra($dominio)
	{
		$where = guarani_sql::armar_condicion_in($dominio, 'sga_catedras_docentes', 'catedra', 'catedra');
		$sql = "	SELECT 	DISTINCT sga_docentes.persona
					FROM 	sga_catedras_docentes
					JOIN	sga_docentes ON sga_docentes.docente = sga_catedras_docentes.docente
					WHERE	sga_docentes.estado = '" . comunes::estado_activo . "'
							AND sga_catedras_docentes.estado = '" . comunes::estado_activo . "'
				       		AND $where
				";

		return guarani::db()->consultar($sql);
	}

	function get_grupo_destinatarios($dominio)
	{
		$grupo = toba::db()->quote($dominio[0]['grupo']);

		$sql = "
					SELECT 	DISTINCT men_grupos_personas.persona
					FROM 	men_grupos_personas
					WHERE	men_grupos_personas.grupo = $grupo
				";

		return guarani::db()->consultar($sql);
	}

	function get_alumnos_con_encuestas_pendientes()
	{
		$sql = "SELECT 	DISTINCT e.persona
				FROM 	gde_habilitaciones as h
				JOIN	gde_formularios as f ON f.habilitacion = h.habilitacion
				JOIN	gde_encuestas_pendientes as e ON e.formulario = f.formulario
				WHERE  current_date BETWEEN h.fecha_desde AND h.fecha_hasta
				AND		h.activo = '" . comunes::si . "'
				AND		f.generado_en_kolla = '" . comunes::si . "'
				AND 	e.fecha_respuesta IS NULL
				";

		return guarani::db()->consultar($sql);
	}

	function get_alumnos_extranjeros($dominio)
	{
		$where = guarani_sql::armar_condicion_in($dominio, 'sga_alumnos', 'propuesta', 'propuesta');

		$sql = "SELECT 	DISTINCT sga_alumnos.persona
					FROM 	sga_alumnos
					JOIN	mdp_personas ON mdp_personas.persona = sga_alumnos.persona
					WHERE	sga_alumnos.calidad = '" . comunes::estado_activo . "'
					  AND mdp_personas.nacionalidad = '" . persona::nac_extranjero . "'
					  AND $where
				";
		return guarani::db()->consultar($sql);
	}

	function get_alumnos_adeudan_requisitos($dominio)
	{
		$where = guarani_sql::armar_condicion_in($dominio, 'sga_alumnos', 'propuesta', 'propuesta');
		$sql = "SELECT DISTINCT vw_personas.persona
				FROM		sga_propuestas_aspira,
							vw_personas,
							sga_alumnos,
							sga_ubicaciones,
							vw_perfil_de_datos_propuestas,
							sga_situacion_aspirante,
							vw_periodos_insc_propuesta
				WHERE		sga_propuestas_aspira.persona = vw_personas.persona AND
							sga_propuestas_aspira.persona = sga_alumnos.persona AND
							sga_propuestas_aspira.propuesta = sga_alumnos.propuesta AND
							sga_propuestas_aspira.ubicacion = sga_ubicaciones.ubicacion AND
							sga_propuestas_aspira.propuesta = vw_perfil_de_datos_propuestas.propuesta AND
							sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp AND
							sga_propuestas_aspira.periodo_insc = vw_periodos_insc_propuesta.periodo_insc AND
							sga_situacion_aspirante.resultado_asp = 'P' AND
							-- Existe un requisito que no esta exceptuado ni presentado
							-- (presentado sin vencimiento o con vencimiento posterior a la fecha de inicio de la inscripción a propuesta).
							EXISTS (SELECT	1
									FROM sga_requisitos_aspirante
									JOIN sga_requisitos ON sga_requisitos_aspirante.requisito = sga_requisitos.requisito
									LEFT JOIN sga_requisitos_ingreso_excep ON (sga_requisitos_ingreso_excep.periodo_inscripcion = vw_periodos_insc_propuesta.periodo_inscripcion AND sga_requisitos_ingreso_excep.requisito = sga_requisitos_aspirante.requisito)
									WHERE sga_requisitos_aspirante.propuesta_aspira = sga_propuestas_aspira.propuesta_aspira 
									  AND sga_requisitos_aspirante.exceptuado = '".comunes::no."' 
									  AND sga_requisitos.requisito_tipo = ".requisito::tipo_documentales." 
									  AND NOT f_requisito_presentado(sga_propuestas_aspira.persona, sga_requisitos_aspirante.requisito, CAST(vw_periodos_insc_propuesta.fecha_inicio as DATE), sga_propuestas_aspira.propuesta_aspira, GREATEST(sga_requisitos_aspirante.fecha_prorroga, COALESCE(sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos))))
							AND $where
				";

		return guarani_db::consultar($sql);
	}

	function get_egresados_colacion($dominio)
	{
		$colacion = toba::db()->quote($dominio[0]['colacion']);
		$sql = "SELECT 	DISTINCT sga_certificados_otorg.persona
				FROM 	sga_certificados_otorg
				WHERE	sga_certificados_otorg.colacion = $colacion
		";
		return guarani::db()->consultar($sql);
	}

	function armar_row_propuesta_plan($asignados)
	{
		$filtro = '';
		if (!empty($asignados)) {
			$filtro .= ' ROW (sga_alumnos.propuesta, sga_planes_versiones.plan) IN (';
			$i = 0;
			foreach ($asignados as $asignado) {
				$filtro .= "('" . $asignado['propuesta'] . "', '" . $asignado['plan'] . "')";
				$i++;
				if ($i < guarani_arreglos::count($asignados)) {
					$filtro .= ', ';
				}
			}
			$filtro .= ')';
		}
		return $filtro;
	}

	function armar_row_propuesta_plan_ubicacion($asignados)
	{
		$filtro = '';
		if (!empty($asignados)) {
			$filtro .= ' ROW (sga_alumnos.propuesta, sga_planes_versiones.plan, sga_propuestas_ra.responsable_academica, sga_alumnos.ubicacion) IN (';
			$i = 0;
			foreach ($asignados as $asignado) {
				$filtro .= "('" . $asignado['propuesta'] . "', '" . $asignado['plan'] . "', '" . $asignado['responsable_academica'] . "', '" . $asignado['ubicacion'] . "')";
				$i++;
				if ($i < guarani_arreglos::count($asignados)) {
					$filtro .= ', ';
				}
			}
			$filtro .= ')';
		}
		return $filtro;
	}

	function armar_row_propuesta_ubicacion($asignados)
	{
		$filtro = '';
		if (!empty($asignados)) {
			$filtro .= ' ROW (sga_propuestas_aspira.propuesta, sga_propuestas_aspira.ubicacion) IN (';
			$i = 0;
			foreach ($asignados as $asignado) {
				$filtro .= "('" . $asignado['propuesta'] . "', '" . $asignado['ubicacion'] . "')";
				$i++;
				if ($i < guarani_arreglos::count($asignados)) {
					$filtro .= ', ';
				}
			}
			$filtro .= ')';
		}
		return $filtro;
	}

}
?>
