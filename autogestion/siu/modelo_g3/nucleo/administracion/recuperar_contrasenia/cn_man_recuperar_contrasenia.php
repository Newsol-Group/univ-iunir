<?php
/**
 *
 *
 * @package Administración
 * 
 */
class cn_man_recuperar_contrasenia extends cn_manejador_entidad
{
	function recuperar_contrasenia($datos)
	{
		$tipo_documento = $datos['tipo_documento'];
		$nro_documento = $datos['nro_documento'];
		$pais = $datos['pais'];
		$persona_id = toba::consulta_php("co_personas")->validar_documento($tipo_documento, $nro_documento, $pais);

		if ($persona_id) {
			// Si la persona existe
			$persona_email = toba::consulta_php("co_personas")->get_datos_persona_mail($persona_id);
			$email = trim($persona_email[0]['email']);

			if ($email) {
				// Si tiene un email registrado
				// Se genera el token
				$token = persona::generar_token();
				
				// Se actualiza el token en la DB
				guarani::act('act_personas')->actualizar_token($persona_id, $token);

				$datos_persona = toba::consulta_php('co_personas')->get_usuario_x_persona($persona_id);				
				$protocolo = 'http://';
				if (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') {
					$protocolo = 'https://';					
				}				
				$server = $protocolo. $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
				$url_recuperar_contrasenia = $server . toba::vinculador()->get_url(toba_proyecto::get_id(), '58000001', array('token' => $token));
				// Se reemplazan los caracteres escapados de la URL por los originales, para evitar que el método 'toba::mensajes()->parsear_parametros' los elimine por considerarlos "comodines".
				$url_recuperar_contrasenia = str_replace('%7C', '|', $url_recuperar_contrasenia);
				$asunto_email = guarani_mensajes::get('800_email_recuperar_contrasenia_asunto');
				$usuario = $datos_persona['usuario'];

				$cuerpo_email = '<html>
                                      <body class="body">
                                        <table class="detalle">
                                          <tr class="detalle_contenido">
                                            <td>' . guarani_mensajes::get('800_email_recuperar_contrasenia_cuerpo', array($email, $usuario, $url_recuperar_contrasenia)) . '</td>
                                          </tr>
                                      </body>
                                    </html>';
				// Se vuelven a escapar los caracteres de la URL.
				$cuerpo_email = str_replace('|', '%7C', $cuerpo_email);
				$mail = new guarani_mail(null, $email, $asunto_email, $cuerpo_email);
				$mail->set_html(true);

				try {
					$mail->ejecutar();
					return $email;
				} catch (toba_error $e) {
					// Falla el envio del email
					toba::logger()->error($e->get_mensaje());
					$mensaje = guarani_mensajes::get('800_no_se_pudo_enviar_email');
					throw guarani::error($mensaje);
				}
			} else {
				// No tiene un email registrado
				$mensaje = guarani_mensajes::get('800_persona_sin_email_registrado');
				throw guarani::error($mensaje);
			}
		} else {
			// La persona no existe
			$mensaje = guarani_mensajes::get('800_no_existe_persona');
			throw guarani::error($mensaje);
		}
	}

	function actualizar_contrasenia($datos)
	{
		$datos_persona = toba::consulta_php('co_personas')->get_persona_x_token($datos['token']);
        
		// Si no existe una persona para ese token
		if (empty($datos_persona)) {
			$mensaje = guarani_mensajes::get('800_no_existe_persona_para_token');
			throw guarani::error($mensaje);
		}
        
        // Si el token ha expirado
		if ($datos_persona['token_expirado']) {
			$mensaje = guarani_mensajes::get('800_token_recuperar_contrasenia_expirado');
			throw guarani::error($mensaje);
		}

		// Se valida valida la longitud de la contraseña
		if (!usuario::validar_clave_permitida($datos['clave'])) {
			$mensaje = guarani_mensajes::get('800_clave_longitud_minima', array(guarani::parametros()->get_valor('login_clave_longitud_minima')));
			throw guarani::error($mensaje);
		}

		$datos_usuario = toba::consulta_php('co_usuarios')->get_datos_autenticacion_usuario($datos_persona['usuario']);

		// Se verifica que la nueva contraseña no coincida con la actual.
		if (guarani_usuario::chequear_clave($datos['clave'], $datos_usuario['clave'])) {
			$mensaje = guarani_mensajes::get('800_clave_repetida');
			throw guarani::error($mensaje);
		}

		// Se actualiza la clave
		$nueva_clave = guarani::encriptar($datos['clave']);
		guarani::act('act_personas')->actualizar_clave($datos_persona['usuario'], $nueva_clave);
		
		// Se limpia el token para la persona
		guarani::act('act_personas')->limpiar_token($datos_persona['persona']);
	}

}