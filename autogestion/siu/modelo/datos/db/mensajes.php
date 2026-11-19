<?php
namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\_comun\comunes;
use siu\modelo\datos\catalogo;
use \siu\errores\error_guarani_usuario;

class mensajes
{
	/**
	 * parametros: persona
	 * cache: no
	 * cache_expiracion: 120
	 * filas: n
	 */
	function get_mensajes_recibidos_vigentes($parametros)
	{
		// Mensajes activos vigentes (leidos o no) de la persona.
		// No se muestra el nombre del usuario que envió el mensaje si es un mensage generado en Gestion.
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		$sql = "
			SELECT
				men_mensajes.mensaje,
				men_mensajes.alcance,
				men_alcances.nombre as alcance_nombre,
				men_mensajes.fecha_desde,
				men_mensajes.fecha_hasta,
				to_char(men_mensajes.fecha_desde, $formato_fecha_visual) AS fecha_desde_visual,
				to_char(men_mensajes.fecha_hasta, $formato_fecha_visual) AS fecha_hasta_visual,
				to_char(men_mensajes.fecha_alta, $formato_fecha_visual) AS fecha_alta_visual,
				men_mensajes.titulo,
				substr(trim(men_mensajes.cuerpo),1,50) as cuerpo_mensaje_resum,
				trim(men_mensajes.cuerpo) as cuerpo_mensaje,
				men_mensajes.enviar_por_mail,
				men_mensajes.enviar_por_twitter,
				men_mensajes.mensaje_twitter,
				men_mensajes.fecha_alta,
				men_destinatarios.fecha_lectura as fecha_lectura,
				men_mensajes.persona_alta as persona_alta,
				CASE men_mensajes.interfaz 
				   WHEN 2 THEN enviado_por.apellido || ' ' || enviado_por.nombres
				   ELSE '' -- Si es por gestion no muestro el nombre del usuario que lo envió
				END as persona_envia
				
			FROM men_destinatarios
			JOIN men_mensajes ON men_mensajes.mensaje = men_destinatarios.mensaje
			JOIN men_alcances ON men_alcances.alcance = men_mensajes.alcance
			LEFT JOIN mdp_personas as enviado_por ON enviado_por.persona = men_mensajes.persona_alta
			
			WHERE men_destinatarios.persona = {$parametros['persona']} 
			  AND CURRENT_DATE BETWEEN men_mensajes.fecha_desde AND men_mensajes.fecha_hasta 
			  AND men_mensajes.activo = 1
			ORDER BY men_mensajes.mensaje DESC
		";
		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		if (!empty($datos))
		{
			foreach($datos as $dato)
			{
				$id = catalogo::generar_id($dato['mensaje']);
				$ids[] = $dato['mensaje'];
				$nuevo[$id] = $dato;
				$nuevo[$id][catalogo::id] = $id;
				$nuevo[$id]['leido'] = isset($dato['fecha_lectura']);
			}

			// Cargo adjuntos
			$sql = "
				SELECT
					men_documentos.mensaje,
					men_documentos.id_archivo,
					rep_archivos.nombre_archivo,
					rep_archivos.path_archivo,
					rep_archivos.tamanio_en_kb,
					rep_archivos.descripcion,
					rep_archivos.extension,
					rep_archivos.url_archivo
				FROM men_documentos
				JOIN rep_archivos ON rep_archivos.id_archivo = men_documentos.id_archivo
				WHERE men_documentos.mensaje IN ('".implode("','",$ids)."')
				";
			$datos = kernel::db()->consultar($sql);
			foreach($datos as $dato)
			{
				$id_m = catalogo::generar_id($dato['mensaje']);
				$id_a = catalogo::generar_id($dato['id_archivo']);
				$dato[catalogo::id] = $id_a;
				$nuevo[$id_m]['ADJUNTOS'][$id_a] = $dato;
			}
		}
		return $nuevo;
	}

	/**
	 * parametros: persona, fecha_desde, fecha_hasta, ultimos
	 * param_null: fecha_desde, fecha_hasta, ultimos
	 * cache: no
	 * cache_expiracion: 120
	 * filas: n
	 */
	function get_mensajes_enviados($parametros)
	{
		// Recupera los mensajes enviados por el docente a los alumnos.
        $no = kernel::db()->quote(\comunes::no);
        $formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		// Por defecto veo los ultimos 10 mensajes enviados activos
		$ultimos_mensajes = " LIMIT 10";
		$where_mensajes_a_ver = "";
		$filtroFechaDesde="";
		$filtroFechaHasta="";
		if($parametros['fecha_desde'] != null){
			$filtroFechaDesde = " AND DATE( men_mensajes.fecha_desde ) >= to_date($parametros[fecha_desde], 'YYYY-MM-DD') ";
		}
		if($parametros['fecha_hasta'] != null) {
			$filtroFechaHasta=" AND DATE( men_mensajes.fecha_desde ) <= to_date($parametros[fecha_hasta], 'YYYY-MM-DD') ";
		}
		if($parametros['ultimos'] != null ){
			$ultimos_mensajes = " LIMIT ".$parametros['ultimos'];
		}	
        
		$sql = "SELECT
					men_mensajes.mensaje,
					men_mensajes.alcance,
					men_alcances.nombre as alcance_nombre,
					men_alcances.descripcion as alcance_descripcion,
					men_mensajes.fecha_desde,
					men_mensajes.fecha_hasta,
					to_char(men_mensajes.fecha_desde, $formato_fecha_visual) AS fecha_desde_visual,
					to_char(men_mensajes.fecha_hasta, $formato_fecha_visual) AS fecha_hasta_visual,
					to_char(men_mensajes.fecha_alta, $formato_fecha_visual) AS fecha_alta_visual,
					men_mensajes.titulo,
					substr(trim(men_mensajes.cuerpo),1,50) as cuerpo_mensaje_resum,
					trim(men_mensajes.cuerpo) as cuerpo_mensaje,
					men_mensajes.enviar_por_mail,
					men_mensajes.enviar_por_twitter,
					men_mensajes.mensaje_twitter,
					men_mensajes.fecha_alta,
					men_mensajes.persona_alta as persona_alta,
					enviado_por.apellido || ' ' || enviado_por.nombres as persona_envia
					
				FROM sga_docentes
				JOIN men_mensajes ON men_mensajes.persona_alta = sga_docentes.persona
				JOIN mdp_personas as enviado_por ON enviado_por.persona = men_mensajes.persona_alta
				JOIN men_alcances ON men_alcances.alcance = men_mensajes.alcance
				
				WHERE sga_docentes.persona = {$parametros['persona']}
				  AND men_alcances.envio_automatico = $no
				  AND men_mensajes.activo = 1
				  AND men_mensajes.personalizado = $no
				  $where_mensajes_a_ver
				  $filtroFechaDesde
				  $filtroFechaHasta
				ORDER BY men_mensajes.mensaje DESC
				$ultimos_mensajes
				";
		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		if (!empty($datos))
		{
			foreach($datos as $key => $dato)
			{
				$id = catalogo::generar_id($dato['mensaje']);
				$ids[] = $dato['mensaje'];
				$nuevo[$id] = $dato;
				$nuevo[$id]['leido'] = true;
				$nuevo[$id][catalogo::id] = $id;
			}

			// Cargo adjuntos
			$sql = "
				SELECT
					men_documentos.mensaje,
					men_documentos.id_archivo,
					rep_archivos.nombre_archivo,
					rep_archivos.path_archivo,
					rep_archivos.tamanio_en_kb,
					rep_archivos.descripcion,
					rep_archivos.extension,
                                        rep_archivos.url_archivo
				FROM men_documentos, rep_archivos
				WHERE
					men_documentos.mensaje IN ('".implode("','",$ids)."') AND
					rep_archivos.id_archivo = men_documentos.id_archivo";
			$datos = kernel::db()->consultar($sql);
			foreach($datos as $dato)
			{
				$id_m = catalogo::generar_id($dato['mensaje']);
				$id_a = catalogo::generar_id($dato['id_archivo']);
				$dato[catalogo::id] = $id_a;
				$nuevo[$id_m]['ADJUNTOS'][$id_a] = $dato;
			}
		}
		return $nuevo;
	}

	/**
	 * parametros: persona, mensaje, enviado
         * no_quote: enviado
	 * cache: no
	 * filas: n
	 */
	function leer_mensaje($parametros)
	{
		$sql = "UPDATE men_destinatarios
				SET fecha_lectura = CURRENT_TIMESTAMP
				WHERE persona = {$parametros["persona"]}
					AND mensaje = {$parametros["mensaje"]}";
            kernel::db()->ejecutar($sql);
            
            // Si el mensaje fue enviado por el usuario actual
            if ($parametros['enviado'] == true)
            {

                $sql = "SELECT cuerpo FROM men_mensajes WHERE mensaje = {$parametros["mensaje"]}";

            }
            else
            {// Si es un mensaje recibido

                $const_no = kernel::db()->quote(comunes::no);
                $sql = "SELECT men_mensajes.personalizado, 
                        CASE men_mensajes.personalizado WHEN {$const_no} THEN men_mensajes.cuerpo 
                            ELSE men_destinatarios.cuerpo_personalizado END AS cuerpo
                        FROM men_mensajes
                        JOIN men_destinatarios ON (men_mensajes.mensaje = men_destinatarios.mensaje 
                                AND men_destinatarios.persona = {$parametros["persona"]}) 
						WHERE men_mensajes.mensaje = {$parametros["mensaje"]}";
			}
			$retorno = kernel::db()->consultar_fila($sql);
            return $retorno['cuerpo'];
	}

	/**
	 * parametros: alcance, titulo, cuerpo, fecha_desde, fecha_hasta, enviar_por_mail, enviar_por_twitter, mensaje_twitter, enviar_por_facebook, activo, persona_alta, error_envio_mail
	 * param_null: mensaje_twitter, error_envio_mail
	 * cache: no
	 * filas: n
	 */
	function guardar_mensaje($parametros)
	{
		/*
		  ESTE METODO NO SE USA MAS. AHORA SE USA EL NOTIFICADOR PARA EL REGISTRO DE LOS MENSAJES ENVIADOS POR EL DOCENTE.
		*/
		
		$enviar_por_mail = $parametros['enviar_por_mail'] ? 1 : 0;
		$enviar_por_twitter = $parametros['enviar_por_twitter'] ? 1 : 0;
		$enviar_por_facebook = $parametros['enviar_por_facebook'] ? 1 : 0;
		$mensaje_twitter = isset($parametros['mensaje_twitter']) ? $parametros['mensaje_twitter'] : 'null';
		$error_envio_mail = isset($parametros['error_envio_mail']) ? $parametros['error_envio_mail'] : 'null';
		$interfaz_autogestion = \comunes::interfaz_autogestion;

		$sql = "
			INSERT INTO men_mensajes(
				alcance,
				titulo,
				cuerpo,
				fecha_desde,
				fecha_hasta,
				enviar_por_mail,
				enviar_por_twitter,
				mensaje_twitter,
				enviar_por_facebook,
				activo,
				persona_alta,
				error_envio_mail,
				interfaz
			) VALUES (
				{$parametros['alcance']},
				{$parametros['titulo']}, 
				{$parametros['cuerpo']}, 
				{$parametros['fecha_desde']}, 
				{$parametros['fecha_hasta']}, 
				$enviar_por_mail,
				$enviar_por_twitter, 
				$mensaje_twitter, 
				$enviar_por_facebook, 
				{$parametros['activo']}, 
				{$parametros['persona_alta']}, 
				$error_envio_mail, 
				$interfaz_autogestion		
			) RETURNING mensaje;";
		$rs = kernel::db()->consultar_fila($sql);
		return $rs['mensaje'];
	}

	/**
	 * parametros: mensaje, persona
	 * cache: no
	 * filas: n
	 */
	function guardar_destinatario_alumno($parametros)
	{
		return kernel::db()->ejecutar("INSERT INTO men_destinatarios(mensaje, persona) VALUES ({$parametros['mensaje']}, {$parametros['persona']})");
	}

	/**
	 * parametros: mensaje, comision
	 * cache: no
	 * filas: n
	 */
	function guardar_destinatario_alumnos_comision($parametros)
	{
		$mensaje = substr($parametros['mensaje'], 1, -1);
		$sql = "
			INSERT INTO men_destinatarios(persona, mensaje) 
			SELECT DISTINCT sga_alumnos.persona,
				   $mensaje as mensaje
			FROM sga_insc_cursada
			JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
			WHERE sga_insc_cursada.comision = {$parametros['comision']}
			  AND sga_alumnos.calidad = 'A' 
			-- AND sga_insc_cursada.estado = 'A'
			";
		return kernel::db()->ejecutar($sql);
	}

	/**
	 * parametros: mensaje, mesa
	 * cache: no
	 * filas: n
	 */
	function guardar_destinatario_alumnos_mesa($parametros)
	{
		$mensaje = substr($parametros['mensaje'], 1, -1);
		$sql = "
			INSERT INTO men_destinatarios(persona,mensaje) 
			SELECT DISTINCT sga_alumnos.persona,
				   $mensaje as mensaje
			  FROM sga_insc_examen
			  JOIN sga_alumnos ON sga_insc_examen.alumno = sga_alumnos.alumno
			 WHERE sga_insc_examen.llamado_mesa = {$parametros['mesa']}
			   AND sga_alumnos.calidad = 'A'
			-- AND sga_insc_examen.estado = 'A'
			";
		return kernel::db()->ejecutar($sql);
	}

	/**
	 * parametros: mensaje, path, nombre, tamanio, extension, tmp_name
	 * cache: no
	 * filas: n
	 */
	function guardar_documento($parametros)
	{
		$archivo = catalogo::consultar('archivos', 'insertar_archivo', array(
			'path' => substr($parametros['path'], 1, -1),
			'nombre' => substr($parametros['nombre'], 1, -1),
			'tamanio' => substr($parametros['tamanio'], 1, -1),
			'extension' => substr($parametros['extension'], 1, -1),
			'tmp_name' => substr($parametros['tmp_name'], 1, -1),
		));

		$sql = "
			INSERT INTO men_documentos (
				mensaje,
				id_archivo
			) VALUES (
				{$parametros['mensaje']},
				$archivo
			);";
		kernel::db()->ejecutar($sql);
	}

	/**
	 * parametros: persona
	 * cache: memoria
	 * cache_expiracion: 300 
	 * filas: 1
	 */
	function get_cant_mensajes($parametros)
	{
		// Cantidad total de mensajes vigentes recibidos y cantidad de esos mensajes leidos.
		$sql = "SELECT
					COUNT(*) as total,
					COALESCE(SUM(CASE WHEN men_destinatarios.fecha_lectura IS NULL THEN 0 ELSE 1 END), 0) as leidos
				FROM men_destinatarios
				JOIN men_mensajes ON men_destinatarios.mensaje = men_mensajes.mensaje
				WHERE men_destinatarios.persona = {$parametros['persona']}
				  AND CURRENT_DATE BETWEEN men_mensajes.fecha_desde AND men_mensajes.fecha_hasta
				";
		return kernel::db()->consultar_fila($sql);
	}

	/**
	 * cache: no
	 * filas: n
	 */
	function get_adjuntos()
	{
		$sql = "
			SELECT
				men_documentos.mensaje,
				men_documentos.id_archivo,
				rep_archivos.nombre_archivo,
				rep_archivos.path_archivo,
				rep_archivos.tamanio_en_kb,
				rep_archivos.descripcion,
				rep_archivos.extension
			FROM men_documentos, rep_archivos
			WHERE
				rep_archivos.id_archivo = men_documentos.id_archivo";
		$datos = kernel::db()->consultar($sql);
		foreach($datos as $dato)
		{
			$id_m = catalogo::generar_id($dato['mensaje']);
			$id_a = catalogo::generar_id($dato['id_archivo']);
			$dato[catalogo::id] = $id_a;
			$nuevo[$id_m][$id_a] = $dato;
		}
		return $nuevo;
	}

	/**
	 * parametros: comision
	 * cache: no
	 * filas: n
	 */
	function get_personas_destinatarios_comision($parametros)
	{

		$calidad_activo = kernel::db()->quote(\alumno::calidad_activo);
		$estado_aceptada = kernel::db()->quote(\inscripcion_cursada::estado_aceptada);

		$sql = "SELECT DISTINCT sga_alumnos.persona
                  FROM sga_insc_cursada
                  JOIN sga_alumnos ON (sga_insc_cursada.alumno = sga_alumnos.alumno)
                 WHERE sga_insc_cursada.comision = {$parametros['comision']}
				   AND sga_alumnos.calidad = $calidad_activo
               ";

		return kernel::db()->consultar($sql);
	}
	
	/**
	 * parametros: subcomision
	 * cache: no
	 * filas: n
	 */
	function get_personas_destinatarios_subcomision($parametros)
	{

		$calidad_activo = kernel::db()->quote(\alumno::calidad_activo);
		$estado_aceptada = kernel::db()->quote(\inscripcion_cursada::estado_aceptada);

		$sql = "SELECT DISTINCT sga_alumnos.persona
                FROM sga_insc_subcomision
				JOIN sga_insc_cursada ON sga_insc_cursada.inscripcion = sga_insc_subcomision.inscripcion
                JOIN sga_alumnos ON sga_insc_cursada.alumno = sga_alumnos.alumno
                WHERE sga_insc_subcomision.subcomision = {$parametros['subcomision']}
				  AND sga_alumnos.calidad = $calidad_activo
               ";

		return kernel::db()->consultar($sql);
	}
	
	/**
	 * parametros: docente
	 * cache: no
	 * filas: n
	 */
	function get_personas_destinatarios_subcomisiones_by_docente($parametros)
	{

		$calidad_activo = kernel::db()->quote(\alumno::calidad_activo);
		$estado_aceptada = kernel::db()->quote(\inscripcion_cursada::estado_aceptada);

		$sql = "SELECT DISTINCT sga_alumnos.persona
                FROM sga_insc_cursada
				JOIN sga_insc_subcomision ON (sga_insc_cursada.inscripcion = sga_insc_subcomision.inscripcion)
                JOIN sga_alumnos ON (sga_insc_cursada.alumno = sga_alumnos.alumno)
                WHERE sga_alumnos.calidad = $calidad_activo
                AND sga_insc_subcomision.subcomision IN (
						SELECT subcomision
						FROM sga_docentes_subcomision
						WHERE docente = {$parametros['docente']}
						AND CURRENT_DATE BETWEEN fecha_desde AND fecha_hasta
					)
               ";

		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: llamado_mesa
	 * cache: no
	 * filas: n
	 */
	function get_personas_destinatarios_mesa($parametros)
	{

		$calidad_activo = kernel::db()->quote(\alumno::calidad_activo);
		$estado_aceptada = kernel::db()->quote(\inscripcion_examen::estado_aceptada);

		$sql = "SELECT DISTINCT sga_alumnos.persona
                  FROM sga_insc_examen
                  JOIN sga_alumnos ON (sga_insc_examen.alumno = sga_alumnos.alumno)
                 WHERE sga_insc_examen.llamado_mesa = {$parametros['llamado_mesa']}
				   AND sga_alumnos.calidad = $calidad_activo
			  ";

		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: mensaje
	 * cache: no
	 * filas: n
	 */
	function get_personas_destinatarios_alumnos($parametros)
	{
		$sql = "SELECT CONCAT (mdp_personas.apellido, ' ', mdp_personas.nombres, ' (', mdp_tipo_documento.desc_abreviada, ': ', mdp_personas_documentos.nro_documento, ')') as apellido_nombres 
				FROM men_destinatarios
				JOIN mdp_personas ON men_destinatarios.persona = mdp_personas.persona
				JOIN mdp_personas_documentos ON mdp_personas_documentos.documento = mdp_personas.documento_principal
				JOIN mdp_tipo_documento ON mdp_personas_documentos.tipo_documento = mdp_tipo_documento.tipo_documento 
				WHERE men_destinatarios.mensaje = {$parametros['mensaje']}
				ORDER BY mdp_personas.apellido, mdp_personas.nombres
			";

		return kernel::db()->consultar($sql);
	}
	
	/**
	 * parametros: mensaje
	 * cache: no
	 * filas: n
	 */
	function get_anio_academico_mensaje($parametros)
	{
		$sql= "SELECT anio_academico 
			FROM men_dominio
			WHERE mensaje = {$parametros['mensaje']}";
		return kernel::db()->consultar($sql);
	}
	
	/**
	 * parametros: mensaje
	 * cache: no
	 * filas: n
	 */
	function get_comisiones($parametros){
		$sql= "SELECT 
					men_dominio.mensaje, 
					men_dominio.propuesta, 
					men_dominio.catedra,
					men_dominio.comision,
					men_dominio.responsable_academica, 
					vw_comisiones.comision_nombre as nombre, 
					vw_comisiones.elemento_nombre as activ_nombre 
				FROM men_dominio
				JOIN vw_comisiones ON vw_comisiones.comision = men_dominio.comision
				WHERE men_dominio.mensaje = {$parametros['mensaje']}
				";
				
		return kernel::db()->consultar_fila($sql);
	}
	
	/**
	 * parametros: mensaje
	 * cache: no
	 * filas: n
	 */
	function get_mesa($parametros){
		// Recupera el nombre de la actividad y el nombre de la mesa de examen.
		$sql= "SELECT 
					vw_mesas_examen.mesa_examen_elemento_nombre as nombre,
					vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre
				FROM men_dominio
				JOIN vw_mesas_examen ON vw_mesas_examen.llamado_mesa = men_dominio.llamado_mesa
				WHERE men_dominio.mensaje = {$parametros['mensaje']}";
		return kernel::db()->consultar_fila($sql);
	}

}
?>