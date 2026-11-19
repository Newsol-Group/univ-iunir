<?php

namespace siu\operaciones\ayuda_menu;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use SIU\Chulupi\util\mail;

class controlador extends controlador_g3w2
{
	const mensaje_tipo = 'mtipo';
	const mensaje_cuerpo = 'mcuerpo';

	/**
	 * @return null
	 */
	function modelo()
	{
		
	}

	function accion__index()
	{
		
	}

	function accion__confirmacion()
	{
		$this->vista()->set_pagelet('confirmacion');
		$pagelet = $this->vista()->pagelet('contacto');
	}

	function accion__carga_datos_contacto()
	{
		$pagelet = $this->vista()->set_pagelet('contacto');

		if (kernel::request()->isPost()) {

			try {
				// Chequeo de CSRF
				$this->check_csrf();
			} catch (\Exception $e) {
				$this->add_error($this->trans('error_csrf'));
				$pagelet->set_estado_info(pagelet_contacto::FORM_ERROR_CSRF);
				return;
			}

			if ($this->usa_captcha()) {
				// Chequeo de recaptcha
				if (!static::check_recaptcha2()) {
					$this->add_error($this->trans('error_recaptcha'));
					$pagelet->set_estado_info(pagelet_contacto::FORM_ERROR_RECAPTCHA);
					return;
				}
			}

			// Validación de formulario y alta de usuario
			$form = $pagelet->get_form();
			if ($form->procesar()) {
				$datos = $form->get_datos();

				if (!$this->enviar_mensaje($datos)) {
					$this->add_error($this->trans('ayuda_menu.error_email'));
					$pagelet->set_estado_info(pagelet_contacto::FORM_ERROR_EMAIL);
					kernel::renderer()->add($pagelet);
				} else {
					$operacion = kernel::ruteador()->get_id_operacion();
					$this->nuevo_request($operacion, 'confirmacion');
				}
			} else {
				if ($form->tiene_errores()) {
					$this->add_error($this->trans('ayuda_menu.alta_error'));
					$pagelet->set_estado_info(pagelet_contacto::FORM_ERROR_VALIDACION);
					kernel::renderer()->add($pagelet);
				}
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- RECAPTCHA -----------------------------------------------------------
	//--------------------------------------------------------------------------

	function usa_captcha()
	{
		return kernel::proyecto()->get('usa_captcha');
	}

	protected function add_error($mensaje, $e = null)
	{
		$mensajes = array();
		$mensajes[] = array(
			self::mensaje_cuerpo => $mensaje,
			self::mensaje_tipo => guarani::control_error,
		);
		$this->add_var('mensajes', $mensajes);
		if (!is_null($e)) {
			kernel::log()->add_error($e);
		}
	}

	function enviar_mensaje($datos_mail)
	{
		$email = kernel::proyecto()->get_email_ayuda();
		if ($email) {
			$asunto = kernel::traductor()->trans('ayuda_menu.asunto_email');
			$tpl = kernel::load_template('contacto/mail_contacto.twig');
			$cuerpo = $tpl->render($datos_mail);

			$mail = new mail($email, $asunto, $cuerpo);
			$mail->set_html(true);
			$mail->set_reply($datos_mail['email']);

			try {
				$mail->enviar();
				$mail_enviado = true;
			} catch (\SIU\Chulupi\ErrorKernel $e) {
				$mail_enviado = false;
			}
		} else {
			$mail_enviado = false;
		}

		return $mail_enviado;
	}

	function exp_reg_dni()
	{
		try {
			$tipo_doc = 0;
			$tipos_documento = \persona::get_datos_tipo_documento();
			$expresion = $tipos_documento[$tipo_doc]['exp_regular'];
			$patt = '/' . $expresion . '/';
		} catch (\SIU\Chulupi\ErrorKernel $e) {
			$patt = "/^\d{1,8}$/";
		}

		return $patt;
	}

}