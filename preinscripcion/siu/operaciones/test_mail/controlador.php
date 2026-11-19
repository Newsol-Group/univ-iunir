<?php

namespace siu\operaciones\test_mail;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\mail;
use SIU\Chulupi\ErrorKernel;
use siu\extension_kernel\controlador_preinscripcion;
use siu\operaciones\test_mail\pagelet_form_test;
use siu\preinscripcion;

class controlador extends controlador_preinscripcion
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

	function es_usuario_default()
	{
		$smtp = kernel::proyecto()->get_parametros_smtp();
		return ((!empty($smtp['from'])) && ($smtp['from'] == 'preinscripcion@gmail.com')) ||
				((!empty($smtp['usuario'])) && ($smtp['usuario'] == 'preinscripcion@gmail.com'));
	}

	function accion__index()
	{
		$this->vista()->set_pagelet('form_test');
	}

	function accion__probar_mail()
	{
		$pagelet = $this->vista()->set_pagelet('form_test');

		if (kernel::request()->isPost()) {
			// Validación de formulario y alta de usuario
			$form = $pagelet->get_form();
			if ($form->procesar()) {
				$valores = $form->get_datos();
				$email = $valores['e_mail'];
				$exito = $this->enviar_mail_prueba($email);
				if ($exito) {
					$pagelet->set_estado_info(pagelet_form_test::MAIL_ENVIADO);
				} else {
					$pagelet->set_estado_info(pagelet_form_test::ERROR_ENVIO_MAIL);
				}
			} else {
				if ($form->tiene_errores()) {
					$pagelet->set_estado_info(pagelet_form_test::FORM_ERROR_VALIDACION);
				}
			}
		}

		kernel::renderer()->add($pagelet);
	}

	protected function enviar_mail_prueba($dir_mail)
	{
		$asunto = $this->trans('asunto_email_prueba');
		$tpl = kernel::load_template('form_test/mail_prueba.twig');
		$cuerpo = $tpl->render(array());

		$mail = new mail($dir_mail, $asunto, $cuerpo, null, '', preinscripcion::charset);
		$mail->set_html(true);

		try {
			$mail->enviar();
			$exito = true;
		} catch (\Exception $e) {
			$exito = false;
		}

		return $exito;
	}

}