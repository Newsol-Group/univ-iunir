<?php

namespace siu\operaciones\test_mail;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;

class pagelet_form_test extends pagelet_con_form
{
	const MAIL_ENVIADO = 'mail_enviado';
	const ERROR_ENVIO_MAIL = 'error_envio_mail';

	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'form_test';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\test_mail\form_test\form_prueba');
		}
		return $this->form;
	}

	function prepare()
	{
		if ($this->controlador->es_usuario_default()) {
			$this->data['usuario_default'] = true;
		} else {
			$this->data['usuario_default'] = false;

			$form = $this->get_form();

			switch ($this->estado) {
				case self::MAIL_ENVIADO:
					$this->data['mensaje'] = kernel::traductor()->trans('mensaje_prueba_enviado');
					break;
				case self::ERROR_ENVIO_MAIL:
					$this->data['mensaje'] = kernel::traductor()->trans('mensaje_prueba_error');
					break;
				case self::FORM_ERROR_VALIDACION :
					break;
				default:
					$form->inicializar();
			}

			$this->add_form($form);
		}
	}

}
?>