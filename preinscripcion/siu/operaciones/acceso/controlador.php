<?php

namespace siu\operaciones\acceso;

use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernelValidacion as error_kernel_validacion;
use SIU\Chulupi\util\mail;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\util\IntentosLogin as intentos_login;
use siu\extension_kernel\controlador_preinscripcion;
use siu\errores\error_preinscripcion;
use siu\errores\error_preinscripcion_login;
use siu\errores\error_preinscripcion_mail_no_validado;
use siu\errores\error_preinscripcion_clave;
use siu\errores\error_preinscripcion_login_externo;
use siu\errores\error_preinscripcion_usuario;
use siu\preinscripcion;
use siu\modelo\util;
use siu\modelo\g3\g3;

class controlador extends controlador_preinscripcion
{
	protected $login_manager;

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
		$pagelet = $this->vista()->set_pagelet('form_login');
		$op_defecto = kernel::persona()->perfil()->get_parametro('index');

                // Recupero el tipo de Autenticación
                $auth = $this->get_param('auth', 'get');
                // Por defecto entra a "Ingresá con tu Email"
                $pagelet->data['auth'] = (empty($auth))? 'form' : $auth;
                
		if (kernel::sesion()->abierta()) {
			$this->nuevo_request($op_defecto);
		}

		/**
		 * Se resetean variables de servicios externos de login, en el caso que 
		 * se regrese de la pantalla de alta habiendo accedido desde Facebook o
		 * Google
		 */
		if (kernel::sesion()->esta_seteada('alta_se_medio')) {
			kernel::sesion()->borrar('alta_se_medio');
		}
		if (kernel::sesion()->esta_seteada('alta_se_id')) {
			kernel::sesion()->borrar('alta_se_id');
		}
		if (kernel::sesion()->esta_seteada('alta_se_mail')) {
			kernel::sesion()->borrar('alta_se_mail');
		}

		if (kernel::sesion()->esta_seteada('id_se')) {
			kernel::sesion()->borrar('id_se');
		}
		if (kernel::sesion()->esta_seteada('mail_se')) {
			kernel::sesion()->borrar('mail_se');
		}
		if (kernel::sesion()->esta_seteada('medio_se')) {
			kernel::sesion()->borrar('medio_se');
		}
		if (kernel::sesion()->esta_seteada('datos_usuario')) {
			kernel::sesion()->borrar('datos_usuario');
		}
	}

	function accion__servicios_externos()
	{
		try {
			$servicio = kernel::url()->get_param(0);
			$datos = preinscripcion::servicios_externos()->llamar($servicio);
			if (!empty($datos)) {
				$this->login_externo($datos['id'], $datos['email'], $servicio);
			}
		} catch (error_kernel_validacion $e) {
			$this->vista()->set_pagelet('form_login');
			$this->add_var('msg_error', $this->trans('login_error_formato_mail'));
		} catch (error_preinscripcion_login $e) {
			$this->vista()->set_pagelet('form_login');
			$this->add_var('msg_error', $this->trans('login_error_clave_invalida'));
		} catch (error_preinscripcion_mail_no_validado $e) {
			$this->vista()->set_pagelet('form_login');
			$this->add_var('msg_error', $this->trans('email_no_validado'));
		} catch (error_preinscripcion_login_externo $e) {
			$this->vista()->set_pagelet('form_login');
			$this->add_var('msg_error', $e->getMessage());
		}
	}

	function accion__recuperar()
	{
		$pagelet = $this->vista()->set_pagelet('recuperar_pass');

        // Si la petición es POST es porque hizo el submit del formulario donde pide el E-mail
		if (kernel::request()->isPost()) {
			try {
				$email = $this->get_post('e_mail');
				if (empty($email)){
				//busco por los datos del documento el email
				$email_xdocumento= $this->validar_email_x_documento();
					if(empty($email_xdocumento)){
					//no son validos los datos 
					$pagelet->set_estado_info(pagelet_recuperar_pass::MAIL_NO_EXISTENTE);
					return;
					}else{
					$email=$email_xdocumento['e_mail'];
					
					}				
				}else{
				$email = $this->validate_param('e_mail', 'post', validador::TIPO_MAIL);
				$email_raw = $this->get_usuario_sin_validar();
				}

                // Validación de reCAPTCHA
				$exito = static::check_recaptcha2();
				if (!$exito) {
					$this->add_error($this->trans('error_recaptcha'));
					return;
				}

                // Le cambio 'codigo_validacion' y 'codigo_validacion_alta' para que pueda recuperar clave
				$token = preinscripcion::usuario()->solicitud_resetear_clave($email);
                // Le envío el email de recuperación
				$this->enviar_mail_recuperar_pass($email, $token);
				$pagelet->set_estado_info(pagelet_recuperar_pass::MAIL_ENVIADO);
				$this->add_var('email', $email);
				kernel::sesion()->set('email',$email);
			} catch (error_kernel_validacion $e) {
				$this->add_error($this->trans('login_error_formato_mail'), $e);
				$this->add_var('nombre_usuario', $email_raw);
			} catch (error_preinscripcion_login $e) {
				$pagelet->set_estado_info(pagelet_recuperar_pass::MAIL_NO_EXISTENTE);
			}
		}
	}

	function accion__reenvio_mail()
	{
		$cambiar_mensaje = $this->get_param('camb_msj', 'get');

		$datos_usuario = kernel::sesion()->get('__datos_reenvio_mail__');

		$email = $datos_usuario['e_mail'];
		$token = $datos_usuario['codigo_validacion'];
		$this->enviar_mail_crear_usuario($email, $token);
		if ($cambiar_mensaje) {
			kernel::sesion()->set('mensaje_alta_iniciada', 'mensaje_recuperacion');
		}

		$this->redirigir();
	}

	protected function enviar_mail_crear_usuario($dir_mail, $token)
	{
		// Obtener datos a partir del e_mail
		$datos = util::consultas()->get_datos_basicos_cuenta($dir_mail);

		$asunto = $this->trans('asunto_email_crear_usuario');
		$tpl = kernel::load_template('form_login/mail_crear_usuario.twig');
		$link = kernel::vinculador()->crear('acceso', 'login', array(
			'auth' => 'token',
			't' => $token
		));
		$cuerpo = $tpl->render(array(
			'mail' => $dir_mail,
			'link' => $link,
			'apellido' => $datos['apellido'],
			'nombres' => $datos['nombres'],
			'nacionalidad' => $datos['nacionalidad_desc'],
			'pais_documento' => $datos['pais_documento_desc'],
			'tipo_documento' => $datos['tipo_documento_desc'],
			'nro_documento' => $datos['nro_documento'],
			'id_preinscripcion' => $datos['id_preinscripcion'],
		));

		$mail = new mail($dir_mail, $asunto, $cuerpo, null, '', preinscripcion::charset);
		$mail->set_html(true);

		$mail->enviar();
	}

	function accion__reset_pass()
	{
		$pagelet = $this->vista()->set_pagelet('recuperar_pass');

        // Si la petición es POST es porque hizo el submit del formulario donde pide la nueva contraseña
		if (kernel::request()->isPost()) {
			try {
				$token = $this->validate_param('token', 'post', validador::TIPO_ALPHANUM);
				$password = $this->validate_param('password', 'post', validador::TIPO_TEXTO);
				$rpassword = $this->validate_param('rpassword', 'post', validador::TIPO_TEXTO);

                // Validación de reCAPTCHA
				$exito = static::check_recaptcha2();
				if (!$exito) {
					throw new error_preinscripcion_usuario(kernel::traductor()->trans('error_recaptcha'));
				}
                
                // Validación de contraseña
				$this->check_passwords($token);

				$datos = preinscripcion::usuario()->resetear_clave($password, $token);
				kernel::sesion()->set('mensaje_clave_reset', 'mensaje_clave_reset');
				$this->redirigir();
			} catch (error_preinscripcion_clave $e) {// Cuando falla la contraseña
				$this->add_var('token', $token);
				$this->add_error($this->trans($e->get_mensaje()));
				$pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_CORRECTO);
            } catch (error_preinscripcion_usuario $e) {// Cuando falla el reCAPTCHA
				$this->add_var('token', $token);
				$this->add_error($this->trans($e->get_mensaje()));
				$pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_CORRECTO);
			} catch (error_kernel_validacion $e) {
				$pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_INCORRECTO);
			} catch (error_preinscripcion $e) {
				$pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_INCORRECTO);
			}
		} else {// Si la petición es GET muestro formulario donde pide la nueva contraseña
			try {
				$token = $this->validate_param('t', 'get', validador::TIPO_ALPHANUM);
				$datos = preinscripcion::usuario()->validar_resetear_clave($token);
                $this->add_var('token', $token);
                
                // Si el código validación expiró le muestro un mensaje
                if ($datos['codigo_validacion_expirado']) {
                    $pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_EXPIRADO);
                } else {// Sino lo llevo a la pantalla para cambiar la contraseña
                    $pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_CORRECTO);
                }
				
			} catch (error_preinscripcion $e) {
				$pagelet->set_estado_info(pagelet_recuperar_pass::TOKEN_INCORRECTO);
			}
		}
	}

	function accion__cambiar_email()
	{
		$pagelet = $this->vista()->set_pagelet('cambiar_email');
		$pagelet->set_estado_info(pagelet_cambiar_email::INICIAL);

	}
	function accion__nuevo_email()
	{
		$email_nuevo = $this->validate_param('nuevo_email', 'post', validador::TIPO_MAIL);
		$email_viejo = kernel::sesion()->get('email');
		try {
			$this->controlar_email($email_nuevo);
			preinscripcion::usuario()->cambiar_email($email_viejo, $email_nuevo);
			$token = preinscripcion::usuario()->solicitud_resetear_clave($email_nuevo);
            // Le envío el email de recuperación
			$this->enviar_mail_recuperar_pass($email_nuevo, $token);
			kernel::sesion()->borrar('email');
			$pagelet = $this->vista()->set_pagelet('cambiar_email');
			$pagelet->set_estado_info(pagelet_cambiar_email::EXITO);
		} catch (error_kernel_validacion $e) {
			$this->add_error($e->get_mensaje(), $e);
			$pagelet = $this->vista()->set_pagelet('cambiar_email');
			$pagelet->set_estado_info(pagelet_cambiar_email::CAMBIAR_EMAIL);
		}
	}

	function accion__validar()
	{
		$valido = false;
		$email = kernel::sesion()->get('email');
		$codigo = $this->validate_param('codigo', 'post', validador::TIPO_TEXTO);


		if (empty($codigo)) {//valido por pregunta_secreta
			$respuesta_secreta = $this->validate_param('respuesta_secreta', 'post', validador::TIPO_TEXTO);
			$fecha_nacimiento = $this->validate_param('fecha_nacimiento', 'post', validador::TIPO_TEXTO);
			$propuesta = $this->validate_param('combo_propuestas', 'post', validador::TIPO_TEXTO);
			$id_preinscipcion = preinscripcion::usuario()->email_pregunta_secreta_fecha_nacimento_existente($email, $respuesta_secreta, $fecha_nacimiento);
			$combo_propuestas = array_keys(kernel::sesion()->get('combo_propuestas'));
			if (empty($id_preinscipcion)) {
				$valido = false;
			} elseif (!empty($respuesta_secreta)) {
				$propuestas_ins = array_column(util::consultas()->get_propuestas_insc($id_preinscipcion['id_preinscripcion']), 'propuesta');
				if (in_array($propuesta, $propuestas_ins) or ( $propuesta == 'ninguna' && empty(array_intersect($propuestas_ins, $combo_propuestas)) )) {
					$valido = true;
				} else {
					$valido = false;
				}
			} else {
				$valido = false;
			}
		} else {
			$valido = preinscripcion::usuario()->get_usuario_codigo($email, $codigo);
		}
		kernel::sesion()->borrar('combo_propuestas');
		if ($valido) {
			$pagelet = $this->vista()->set_pagelet('cambiar_email');
			$pagelet->set_estado_info(pagelet_cambiar_email::CAMBIAR_EMAIL);
		} else {
			//mensaje de error die('no coinciden los datos');
			$pagelet = $this->vista()->set_pagelet('cambiar_email');
			$pagelet->set_estado_info(pagelet_cambiar_email::ERROR);
		}
	}

	function accion__logout()
	{
		kernel::sesion()->cerrar();
		$this->nuevo_request('acceso', 'index');
	}
	
	//--------------------------------------------------------------------------
	//---- Métodos auxiliares --------------------------------------------------
	//--------------------------------------------------------------------------

	protected function check_passwords($token)
	{
		$clave = trim($this->validate_param('password', 'post', validador::TIPO_TEXTO));
		$rclave = trim($this->validate_param('rpassword', 'post', validador::TIPO_TEXTO));

		if ($clave != $rclave) {
			$mensaje = kernel::traductor()->trans('error_clave_no_coinciden');
			throw new error_preinscripcion_clave($mensaje);
		}

		$usuario = preinscripcion::usuario()->get_usuario_asociado_token($token);
		if (empty($usuario)) {
			throw new error_preinscripcion();
		}

		// Validaciones genéricas de clave
		preinscripcion::usuario()->clave_permitida($clave, $usuario);

		return true;
	}

	protected function login($email, $password)
	{
		kernel::sesion()->abrir($email, $password);
		kernel::persona()->set_email($email);
		intentos_login::eliminar($email);
	}

	protected function login_externo($id, $mail, $medio, $forzar_login_mail = false)
	{
		if (!empty($mail)) {
			/**
			 * Si existe un usuario registrado con el mail del servicio externo, 
			 * no vinculado al mismo, se redirige a otra operación para asociar
			 * las cuentas.
			 */
			$datos = preinscripcion::usuario()->get_datos_usuario($mail);
			if (!empty($datos) && empty($datos[$medio])) {
				kernel::sesion()->set('id_se', $id);
				kernel::sesion()->set('mail_se', $mail);
				kernel::sesion()->set('medio_se', $medio);
				kernel::sesion()->set('datos_usuario', $datos);
				throw new \kernel\nucleo\nuevo_request('asociar_usuario_se');
			}
		}

		if (!empty($id)) {
			$lm = $this->get_login_manager();
			$id_preinscripcion = $lm->autenticar_login_externo($id, $mail, $medio, $forzar_login_mail);
			if (!empty($id_preinscripcion)) {
				// Se abre la sesión con el usuario asociado a la persona ingresada.
				kernel::sesion()->abrir_login_externo($id, $mail, $medio, false, $forzar_login_mail);

				// Se obtienen los datos para establecer el mail de la persona
				$datos = kernel::persona()->datos()->get_datos_formularios_preinsc();
				kernel::persona()->set_email($datos['e_mail']);

				$this->redirigir();
			} else {
				kernel::sesion()->set('alta_se_medio', $medio);
				kernel::sesion()->set('alta_se_id', $id);
				kernel::sesion()->set('alta_se_mail', $mail);
				throw new \kernel\nucleo\nuevo_request('alta_usuario');
			}
		}
	}

	function get_login_manager()
	{
		if (!isset($this->login_manager)) {
			$clase = kernel::proyecto()->get_clase_login();
			if (!isset($clase)) {
				throw new error_kernel('SESION: no se seteo el manejador de login');
			}
			$this->login_manager = kernel::localizador()->instanciar($clase);
		}
		return $this->login_manager;
	}

	protected function login_post_verificacion($datos)
	{
		kernel::sesion()->abrir_sin_autenticacion($datos['id_preinscripcion']);
		kernel::persona()->set_email($datos['e_mail']);
	}

	protected function add_error($mensaje, $e = null)
	{
		$mensajes = array();
		$mensajes[] = array(
			preinscripcion::mensaje_cuerpo => $mensaje,
			preinscripcion::mensaje_tipo => preinscripcion::control_error,
		);
		$this->add_var('mensajes', $mensajes);
		if (!is_null($e)) {
			kernel::log()->add_error($e);
		}
	}

	protected function redirigir()
	{
		$this->nuevo_request(kernel::persona()->perfil()->get_parametro('index'));
	}

	//--------------------------------------------------------------------------
	//---- RECUPERACIÓN DE CUENTA ----------------------------------------------
	//--------------------------------------------------------------------------

	protected function enviar_mail_recuperar_pass($dir_mail, $token)
	{
		$asunto = $this->trans('asunto_email_recuperar_clave');
		$tpl = kernel::load_template('recuperar_pass/mail_recover.twig');
		$link = kernel::vinculador()->crear('acceso', 'reset_pass', array(
			't' => $token
		));
		$cuerpo = $tpl->render(array(
			'mail' => $dir_mail,
			'link' => $link
		));

		$mail = new mail($dir_mail, $asunto, $cuerpo, null, '', preinscripcion::charset);
		$mail->set_html(true);

		$mail->enviar();
	}

	protected function get_usuario_sin_validar()
	{
		return substr($this->get_post('e_mail'), 0, 100);
	}

	function validar_email_x_documento()
	{
		$pais_documento = $this->validate_param('pais', 'post', validador::TIPO_ALPHANUM);
		$tipo_documento	= $this->validate_param('tipo_documento', 'post', validador::TIPO_ALPHANUM);
		$numero_documento = $this->validate_param('documento', 'post', validador::TIPO_ALPHANUM);
		$fecha_nacimiento = $this->validate_param('fecha_nacimiento', 'post', validador::TIPO_TEXTO);

		$datos_usuario = preinscripcion::usuario()->documento_nacimiento_existente($pais_documento, $tipo_documento, $numero_documento, $fecha_nacimiento);
		return $datos_usuario;
	}

	function get_opciones__paises()
	{
		return util::opciones()->get_paises();
	}
	function get_pregunta_secreta()
	{
		$email = kernel::sesion()->get('email');
				
		$datos= preinscripcion::usuario()->get_datos_usuario($email);

		return trim($datos['pregunta_secreta']);
	}

	function get_combo_propuestas_preinscriptas()
	{
		//$email = $this->validate_param('email', 'get', validador::TIPO_MAIL);
		//$datos = preinscripcion::usuario()->get_datos_usuario($email);
		//$propuestas_ins = util::consultas()->get_propuestas_insc($datos['id_preinscripcion']);
		$propuestas = g3::propuestas()->get_propuestas_sin_perfil_datos();
		$claves = array_keys($propuestas);
		shuffle($claves);
		$nro_elemento=3;
		$valores_aleatorios=['Seleccione' => 'Seleccione','ninguna' => 'Ninguna de las anteriores'];
		for ( $i=0; $i<$nro_elemento ; $i++){
			$clave=$claves[$i];
			$valor=$propuestas[$clave];
			$valores_aleatorios[$clave]=$valor;
		}
		kernel::sesion()->set('combo_propuestas',$valores_aleatorios);
		return $valores_aleatorios;
	}
	protected function controlar_email($valores)
	{
		if (!empty(preinscripcion::usuario()->get_datos_usuario($valores))) {		
			$mensaje = kernel::traductor()->trans('alta.error.email_duplicado');
			throw new error_kernel_validacion($mensaje);
		}
	}
	//--------------------------------------------------------------------------
	//---- Ingresá con tu Documento --------------------------------------------
	//--------------------------------------------------------------------------

        function accion__datos_tipo_documento()
	{
            $pais = $this->get_param('pais', 'post');
            $condicion = "mdp_tipo_documento_pais.pais = {$pais}";
            $entidad_pais_tipo_doc = g3::entidad('tipos_documentos_pais')->get_opciones_pais($condicion);
            $this->render_raw_json($entidad_pais_tipo_doc);
	}
}