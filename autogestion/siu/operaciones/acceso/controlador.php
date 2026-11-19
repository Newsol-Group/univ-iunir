<?php
namespace siu\operaciones\acceso;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\mail;
use siu\extension_kernel\controlador_g3w2;
use siu\errores\error_guarani;
use SIU\Chulupi\util\validador;
use siu\guarani;
use \siu\modelo\entidades\persona;
use siu\errores\error_guarani_usuario;
use siu\modelo\entidades\parametro;

class controlador extends controlador_g3w2
{
	/**
	 * @return null
	 */
	function modelo()
	{
		return null;
	}

	function ini()
	{
		
	}

	function accion__index()
	{
		$this->vista()->set_pagelet('login');

		if (kernel::sesion()->abierta()) {
			$op_defecto = kernel::persona()->perfil()->get_parametro('index');
			$this->nuevo_request($op_defecto);
		}
	}

	function accion__logout()
	{
		kernel::login_manager()->logout();
		$this->nuevo_request('acceso', 'index');
	}

	function accion__perfil()
	{
		$perfil = $this->validate_param('id', 'get', validador::TIPO_ALPHANUM);
		$perfil_activo_previo = kernel::persona()->get_id_perfil_activo();
		if ($perfil_activo_previo != persona::perfil_cursos) {
			kernel::sesion()->set('perfil_activo_previo', $perfil_activo_previo);
		}
                kernel::persona()->set_perfil_activo($perfil);
		$op_defecto = kernel::persona()->perfil()->get_parametro('index');
		$this->nuevo_request($op_defecto);
	}

	function accion__cambiar_carrera()
	{
		$carrera = $this->validate_param('id', 'get', validador::TIPO_ALPHANUM);
		kernel::persona()->set_propuesta_activa($carrera);
		$op = $this->validate_param('op', 'get', validador::TIPO_TEXTO, array('allowempty' => true));
		if (empty($op) || !kernel::persona()->perfil()->existe_operacion($op)) {
			$op = kernel::persona()->perfil()->get_parametro('index');
		}
		$this->nuevo_request($op);
	}

	function accion__cascada_tipo_documentos()
	{
		$tipo_documento = $this->get_param('tipo_documento', 'post');
		$pais_documento = $this->get_param('pais', 'post');
		$datos = \persona::get_tipos_documentos_x_pais($pais_documento);
		$combo = array();
		if (!empty($datos)) {
			foreach ($datos as $dato) {
				$combo[$dato['valor']] = $dato['descr'];
			}
		}
		$this->render_raw_json($combo);
	}

	//--------------------------------------------------------------------------
	//---- RECUPERACIÓN DE CUENTA ----------------------------------------------
	//--------------------------------------------------------------------------
	
	function accion__reset_pass()
	{
		$this->vista()->set_pagelet('recuperar_pass');
		$pagelet = $this->vista()->pagelet('login');

        // Si la petición es POST es porque hizo el submit del formulario donde pide la nueva contraseña
		if (kernel::request()->isPost()) {
            
            try {
                // Validación de parámetros
                $token = $this->validate_param('token', 'post', validador::TIPO_ALPHANUM);
                $password = $this->validate_param('password', 'post', validador::TIPO_TEXTO);
                $rpassword = $this->validate_param('rpassword', 'post', validador::TIPO_TEXTO);
                
                // Validación de reCAPTCHA
				$exito = static::check_recaptcha2();
				if (!$exito) {
					throw new error_guarani_usuario(kernel::traductor()->trans('error_recaptcha'));
				}
                
                // 'Nueva contraseña' vacía
                if (empty($password)) {
                    throw new error_guarani_usuario(kernel::traductor()->trans('acceso.recuperar.error_nueva_contrasenia_vacia'));
                }
                
                // No coincide 'Nueva contraseña' y 'Repetir nueva contraseña'
                if ($password != $rpassword) {
                    throw new error_guarani_usuario(kernel::traductor()->trans('acceso.recuperar.error_contrasenias_no_coinciden'));
                }
                
                // 'Nueva contraseña' no cumple logitud mínima 
                if (!\usuario::validar_clave_permitida($password)) {
                    $longitud_minima = parametro::get_valor('login_clave_longitud_minima');
                    throw new error_guarani_usuario(kernel::traductor()->trans('800_clave_longitud_minima', ['%1%' => $longitud_minima]));
                }
                
                $datos_persona = persona::get_persona_from_token($token);

                // Si el token expiro le muestro un mensaje
                if ($datos_persona['token_expirado']) {
                    $pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_EXPIRADO);
                } else {
                    // Cambio la contraseña
                    persona::reset_password($datos_persona['persona'], $password);
                    // Limpio el token para que no se pueda volver a usar
                    persona::limpiar_token($datos_persona['persona']);
                    kernel::sesion()->set("msg_contrasenia_recuparada_exito", kernel::traductor()->trans('acceso.recuperar.msg_contrasenia_recuparada_exito'));
                    $this->redirigir();
                }
            } catch (error_guarani $e) {
                $pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_INCORRECTO);
            } catch (error_guarani_usuario $e) {
				$mensajes[] = [
					guarani::mensaje_cuerpo => $e->get_mensaje(),
					guarani::mensaje_tipo => guarani::control_error,
				];
				$this->add_var('mensajes', $mensajes);
                $token = $this->validate_param('t', 'get', validador::TIPO_ALPHANUM);
                $this->add_var('token', $token);
				kernel::log()->add_error($e);
                $pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_CORRECTO);
			}
		} else { // Si la petición es GET muestro formulario donde pide la nueva contraseña
			try {
				$token = $this->validate_param('t', 'get', validador::TIPO_ALPHANUM);
				$datos_persona = persona::get_persona_from_token($token);
				$this->add_var('token', $token);
                
                // Si el token expiro le muestro un mensaje
                if ($datos_persona['token_expirado']) {
                    $pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_EXPIRADO);
                } else {// Sino lo llevo a la pantalla para cambiar la contraseña
                    $pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_CORRECTO);
                }
				
			} catch (error_guarani $e) {
				$pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_INCORRECTO);
			}
		}
	}

	function accion__recuperar()
	{
        
        // https://stackoverflow.com/questions/20867559/how-do-i-make-a-token-expire
        
		$this->vista()->set_pagelet('recuperar_pass');

        // Si la petición es POST
		if (kernel::request()->isPost()) {
            // Validación de parámetros
			$pais = $this->validate_param('pais', 'post', validador::TIPO_INT);
			$tipo_doc = $this->validate_param('tipo_doc', 'post', validador::TIPO_INT);
			$documento = $this->validate_param('documento', 'post', validador::TIPO_TEXTO);
			$this->add_var('documento', $documento);
			$pagelet = $this->vista()->pagelet('login');

			try {
                // Validación de reCAPTCHA
				$exito = static::check_recaptcha2();
				if (!$exito) {
					throw new error_guarani_usuario(kernel::traductor()->trans('error_recaptcha'));
				}

                // Validación de documento
				$this->validar_nro_documento($pais, $tipo_doc, $documento);

				list($mail, $nro_ingreso, $usuario) = $this->get_data_persona($pais, $tipo_doc, $documento);

                // Si la persona tiene email
				if (!empty($mail)) {
					$token = \persona::generar_token();
                    // Actualiza mdp_personas.token y mdp_personas.token_alta de la persona
					persona::update_token($nro_ingreso, $token);
                    // Envia email de asistencia de recuperación de contraseña
					$this->enviar_mail_recuperar_pass($mail, $token, $usuario);
					$pagelet->set_estado_info(pagelet_recuperar_pass::MAIL_ENVIADO);
					$this->add_var('mail', $mail);
				} else {
					$pagelet->set_estado_info(pagelet_recuperar_pass::MAIL_NO_EXISTENTE);
				}
			} catch (error_guarani_usuario $e) {
				$mensajes[] = [
					guarani::mensaje_cuerpo => $e->get_mensaje(),
					guarani::mensaje_tipo => guarani::control_error,
				];
				$this->add_var('mensajes', $mensajes);
				kernel::log()->add_error($e);
			}
		}
	}

	protected function validar_nro_documento($pais, $tipo_documento, $nro_documento)
	{
		
		if (trim($nro_documento) == "") {
			$msg = kernel::traductor()->trans('acceso.recuperar.error_documento_requerido');
			throw new error_guarani_usuario($msg);
		}

		$datos_tipo_documento = \toba::consulta_php('co_personas')->get_datos_tipos_documentos_x_pais($pais, $tipo_documento);
		
		$patt = '/' . $datos_tipo_documento[0]['exp_regular_validacion'] . '/';
		$msg = $datos_tipo_documento[0]['exp_regular_mensaje'];

		if (!preg_match($patt, $nro_documento)) {
			// El documento es inválido
			throw new error_guarani_usuario($msg);
		}
	}

	protected function get_data_persona($pais, $tipo_doc, $doc)
	{
		return persona::get_data_recuperar_pass($pais, $tipo_doc, $doc);
	}

	protected function enviar_mail_recuperar_pass($dir_mail, $token, $usuario)
	{
		$asunto = kernel::traductor()->trans('asunto_mail_recuperar_pass');
		$tpl = kernel::load_template('recuperar_pass/mail_recover.twig');
		$link = kernel::vinculador()->crear('acceso', 'reset_pass', array('t' => $token));
		$cuerpo = $tpl->render(array(
			'mail' => $dir_mail,
			'link' => $link,
			'usuario' => $usuario
		));

		$mail = new mail($dir_mail, $asunto, $cuerpo);
		$mail->set_html(true);

		$mail->enviar();
	}

	protected function redirigir()
	{
		$this->nuevo_request(kernel::persona()->perfil()->get_parametro('index'));
	}

}