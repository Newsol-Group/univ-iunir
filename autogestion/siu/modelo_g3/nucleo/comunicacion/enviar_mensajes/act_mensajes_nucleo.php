<?php
/**
 *
 *
 * @package	 	Comunicaciones
 * @subpackage 	-
 * 
 */
class act_mensajes_nucleo
{
	function insertar_error_envio_mail($mensaje, $error)
	{
		$mensaje = toba::db()->quote($mensaje);
		$error = toba::db()->quote($error);

		$sql = "UPDATE 	men_mensajes  
				SET 	error_envio_mail = $error
				WHERE 	mensaje = $mensaje
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info("************ Se actualizó error_envio_mail en 'men_mensajes' para el mensaje $mensaje con éxito. ************");
	}

	function marcar_mail($mensaje, $estado = '0')
	{
		$mensaje = toba::db()->quote($mensaje);
		$estado = toba::db()->quote($estado);

		$sql = "UPDATE 	men_mensajes  
				SET 	enviar_por_mail = $estado
				WHERE 	mensaje = $mensaje
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info("************ Se desmarcó la opción 'enviar_por_mail' en 'men_mensajes' con éxito. ************");
	}

	function desmarcar_publicar_twitter($mensaje)
	{
		$mensaje = toba::db()->quote($mensaje);

		$sql = "UPDATE 	men_mensajes  
				SET 	enviar_por_twitter = 0,
						mensaje_twitter = null
				WHERE 	mensaje = $mensaje
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info("************ Se desmarcó la opción 'enviar_por_twitter' en 'men_mensajes' con éxito. ************");
	}

	function desmarcar_publicar_facebook($mensaje)
	{
		$mensaje = toba::db()->quote($mensaje);

		$sql = "UPDATE 	men_mensajes  
				SET 	enviar_por_facebook = 0 
				WHERE 	mensaje = $mensaje
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info("************ Se desmarcó la opción 'enviar_por_facebook' en 'men_mensajes' con éxito. ************");
	}

	//-----------------------------------------------------------------------------------
	//---- Adjuntos
	//-----------------------------------------------------------------------------------

	function insertar_rep_archivo($path_archivo, $nombre_archivo, $tamanio_en_kb, $extension)
	{

		try{

			toba::db()->abrir_transaccion();

			$path_archivo = toba::db()->quote($path_archivo);
			$nombre_archivo = toba::db()->quote($nombre_archivo);
			$tamanio_en_kb = toba::db()->quote($tamanio_en_kb);
			$extension = toba::db()->quote($extension);

			$sql = "INSERT INTO rep_archivos
				(
					nombre_archivo,
					path_archivo,
					tamanio_en_kb,
					extension
				) 
				VALUES 
				(
					$nombre_archivo,
					$path_archivo,
					$tamanio_en_kb,
					$extension
				)";

			guarani_db::ejecutar($sql);
			$id_archivo = toba::db()->recuperar_secuencia('rep_archivos_seq');

			// Si estamos en Gestión
			if(comunes::es_interfaz_gestion()){

                            $server = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}";
                            $vinculo_raiz = $server . toba::vinculador()->get_url(toba_proyecto::get_id(), '41000129');
                            $parametros_formateados = "&id_archivo={$id_archivo}";
                            $url_archivo = $vinculo_raiz . '&' . apex_hilo_qs_parametros . '=' . toba::encriptador()->cifrar($parametros_formateados);

			}
			else{ // sino existe estamos en 3W
                            
                            $hash = siu\modelo\datos\catalogo::generar_id($id_archivo);
                            $hash = urlencode($hash);
                            $url_archivo = \SIU\Chulupi\kernel::vinculador()->crear('notificador', 'descargar_adjunto', $hash);
                            
			}
                        
			$url_archivo = toba::db()->quote($url_archivo);
			$sql = "UPDATE rep_archivos SET url_archivo = $url_archivo WHERE id_archivo = $id_archivo";
			guarani_db::ejecutar($sql);

			toba::db()->cerrar_transaccion();
			toba::logger()->info('************ Se insertó el registro rep_archivos con éxito. ************');
			return $id_archivo;
		}
		catch (Exception $e) {
			toba::db()->abortar_transaccion();
			return -1;
		}
	}

	function eliminar_rep_archivo($id_archivo)
	{
		$id_archivo = toba::db()->quote($id_archivo);

		$sql = "DELETE
				FROM	rep_archivos
				WHERE	id_archivo = $id_archivo
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó el registro rep_archivos con éxito. ************');
	}

	function insertar_men_documentos($mensaje, $id_archivo)
	{
		$mensaje = toba::db()->quote($mensaje);
		$id_archivo = toba::db()->quote($id_archivo);

		$sql = "INSERT INTO men_documentos
				(
					mensaje,
					id_archivo
				) 
				VALUES (
					$mensaje,
					$id_archivo
				);
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el registro men_documentos con éxito. ************');
	}

	function eliminar_men_documentos($mensaje, $id_archivo)
	{
		$mensaje = toba::db()->quote($mensaje);
		$id_archivo = toba::db()->quote($id_archivo);

		$sql = "DELETE 	
				FROM 	men_documentos
				WHERE	mensaje = $mensaje
						AND id_archivo = $id_archivo
				";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó el registro men_documentos con éxito. ************');
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares
	//-----------------------------------------------------------------------------------

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
			$filtro .= ' ROW (sga_alumnos.propuesta, sga_planes_versiones.plan, sga_propuestas_ra.responsable_academica, sga_propuestas_oferta.ubicacion) IN (';
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

	/**
	 * Guarda mensajes personalizados en la tabla 'men_mensajes' y devuelve el id del mensaje.
	 * @param string $asunto Asunto del mensaje.
	 * @param int $tipo Tipo de mensaje.
	 * @param int $alcance_grupo_alumnos Alcance del mensaje.
	 * @param int $visible_gestion Si es visible o no en gestión el mensaje.
	 */
	function set_mensajes_men_mensajes_personalizado($asunto, $tipo, $alcance_grupo_alumnos = mensajes::grupo_alumnos, $visible_gestion = comunes::no, $interfaz = comunes::interfaz_gestion)
	{
		//Trunco el titulo para que pueda ser guardado en la base
		$asunto = mb_strimwidth($asunto, 0, 100, '...');
		
		$alcance_grupo_alumnos = toba::db()->quote($alcance_grupo_alumnos);
		$asunto          = toba::db()->quote($asunto);
		$si              = toba::db()->quote(comunes::si);
		$tipo            = toba::db()->quote($tipo);
		$visible_gestion = toba::db()->quote($visible_gestion);
		$interfaz        = toba::db()->quote($interfaz);

		$sql_men_mensajes = "
					INSERT INTO men_mensajes (
						alcance, 
						titulo, 
						cuerpo, 
						fecha_desde, 
						fecha_hasta, 
						tipo_mensaje, 
						personalizado,
						visible_en_gestion,
						interfaz
					) 
                	VALUES (
                		$alcance_grupo_alumnos, 
                		$asunto, 
                		'', 
                		NOW(),
                		(NOW() + interval '1 week' ),
                		$tipo,
                		$si,
						$visible_gestion,
                		$interfaz)";

		guarani_db::ejecutar($sql_men_mensajes);

		return toba::db()->ultimo_insert_id('men_mensajes_seq');
	}

	/**
	 * Guarda mensajes NO personalizados en la tabla 'men_mensajes' y devuelve el id del mensaje.
	 * @param string $asunto Asunto del mensaje.
	 * @param int $tipo Tipo de mensaje.
	 */
	function set_mensajes_men_mensajes_no_personalizado($asunto, $tipo, $cuerpo, $alcance, $fecha_desde, $fecha_hasta, $enviar_por_mail = true, $responder_a = '', $enviar_por_twitter = 0, $mensaje_twitter = '', $enviar_por_facebook = 0, $visible_gestion = 'N', $interfaz = comunes::interfaz_gestion)
	{
		//Trunco el titulo para que pueda ser guardado en la base
		$asunto = mb_strimwidth($asunto, 0, 100, '...');
		// Si existe el método 'usuario' de la clase 'toba' quiere decir que estamos en Gestión
		if(method_exists("toba", "usuario")){
			$usuario = toba::usuario()->get_id();
			$persona = toba::consulta_php('co_personas')->get_persona_x_usuario($usuario);
			$persona_alta = $persona['persona'];
		}
		else{ // sino existe estamos en 3W
			$persona_alta = \SIU\Chulupi\kernel::persona()->get_id();
		}

		$alcance_grupo_alumnos = toba::db()->quote($alcance);
		$asunto = toba::db()->quote($asunto);
		$cuerpo = toba::db()->quote($cuerpo);
		$fecha_desde = toba::db()->quote($fecha_desde);
		$fecha_hasta = toba::db()->quote($fecha_hasta);
		$no = toba::db()->quote(comunes::no);
		$tipo = toba::db()->quote($tipo);
		$persona_alta = toba::db()->quote($persona_alta);
		$enviar_por_mail = toba::db()->quote($enviar_por_mail);
		$responder_a = toba::db()->quote($responder_a);
		$enviar_por_twitter = toba::db()->quote($enviar_por_twitter);
		$mensaje_twitter = toba::db()->quote($mensaje_twitter);
		$enviar_por_facebook = toba::db()->quote($enviar_por_facebook);
		$visible_gestion = toba::db()->quote($visible_gestion);
		$interfaz        = toba::db()->quote($interfaz);
		
		$sql_men_mensajes = "
					INSERT INTO men_mensajes (
						alcance, 
						titulo, 
						cuerpo, 
						fecha_desde, 
						fecha_hasta, 
						persona_alta, 
						tipo_mensaje, 
						personalizado,
						enviar_por_mail,
						responder_a,
						enviar_por_twitter,
						mensaje_twitter,
						enviar_por_facebook,
						visible_en_gestion,
						interfaz
					) 
                	VALUES (
                		$alcance_grupo_alumnos, 
                		$asunto, 
                		$cuerpo, 
                		$fecha_desde,
                		$fecha_hasta, 
                		$persona_alta, 
                		$tipo, 
                		$no,
                		$enviar_por_mail, 
                		$responder_a,
						$enviar_por_twitter,
						$mensaje_twitter,
						$enviar_por_facebook,
						$visible_gestion,
						$interfaz
                		)";		
		
		guarani_db::ejecutar($sql_men_mensajes);

		return toba::db()->ultimo_insert_id('men_mensajes_seq');
	}
	
	/**
	 * Guarda los destinatarios en 'men_destinatarios'.
	 * @param int $mensaje Id del mensaje en 'men_mensajes'.
	 * @param int $persona Id de la persona/destinatario.
	 * @param string|null $cuerpo Cuerpo del mensaje para mensajes personalizados o null para mensajes NO personalizados.
	 */
	function set_mensajes_men_destinatarios($mensaje, $persona, $cuerpo = null)
	{
		$mensaje = toba::db()->quote($mensaje);
		$persona = toba::db()->quote($persona);
		$cuerpo = is_null($cuerpo)? 'null' : toba::db()->quote($cuerpo);

		$sql_men_destinatarios = "
					INSERT INTO men_destinatarios (
						mensaje, 
						persona, 
						cuerpo_personalizado
					) 
					VALUES (
						$mensaje, 
						$persona, 
						$cuerpo
					)";

		guarani_db::ejecutar($sql_men_destinatarios);
	}
	
	function insertar_men_dominio($mensaje, $columnas, $valores)
	{
		$sql = "
				INSERT INTO men_dominio ($columnas, mensaje)
				VALUES ($valores, $mensaje)";
		
		guarani_db::ejecutar($sql);
	}

}