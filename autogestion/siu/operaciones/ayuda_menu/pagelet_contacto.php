<?php

namespace siu\operaciones\ayuda_menu;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_contacto extends pagelet
{
	const FORM_INICIAL = 'inicial';
	const FORM_ERROR_RECAPTCHA = 'error_recaptcha';
	const FORM_ERROR_VALIDACION = 'form_error_validacion';
	const FORM_ERROR_CSRF = 'form_error_csrf';
	const FORM_ERROR_EMAIL = 'error_email';

	protected $form;

	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'contacto';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\ayuda_menu\contacto\form_contacto');
		}
		//kernel::log()->add_error($this->form);
		return $this->form;
	}

	function prepare()
	{
		//Carga los datos para la vista
		$this->data['mensajes'] = $this->controlador->get_var_default('mensajes', array());
		$this->data['url_volver'] = kernel::vinculador()->crear('acceso', 'index');
		$this->data['usar_captcha'] = $this->controlador->usa_captcha();

		$form = $this->get_form();

		switch ($this->estado) {
			case static::FORM_ERROR_VALIDACION:
				$this->data['mensajes'] = $this->controlador->get_var('mensajes');
				$this->data['error'] = true;
				break;
			case static::FORM_ERROR_RECAPTCHA:
				$this->data['mensajes'] = $this->controlador->get_var('mensajes');
				$this->data['error'] = true;
				$form->inicializar();
				$form->set_datos_desde_post();
				break;
			case static::FORM_ERROR_CSRF:
				$this->data['mensajes'] = $this->controlador->get_var('mensajes');
				$this->data['error'] = true;
				$form->inicializar();
				$form->set_datos_desde_post();
				break;
			case static::FORM_ERROR_EMAIL:
				$this->data['mensajes'] = $this->controlador->get_var('mensajes');
				$this->data['error'] = true;
				$form->inicializar();
				$form->set_datos_desde_post();
				break;
			default:
				$form->inicializar();
				break;
		}

		$this->data['inicial'] = static::FORM_INICIAL;
		$this->data['form_error_validacion'] = static::FORM_ERROR_VALIDACION;
		$this->data['error_recaptcha'] = static::FORM_ERROR_RECAPTCHA;
		$this->data['form_error_csrf'] = static::FORM_ERROR_CSRF;
		$this->data['error_email'] = static::FORM_ERROR_EMAIL;
		$this->data['csrf'] = $this->generar_csrf();
		$this->add_form($form);
		$string = str_replace(['/', 'i'], '', \comunes::er_email);
		$this->add_var_js('regexp_email', $string);

		$exp_dni = $this->controlador->exp_reg_dni();
		$exp_dni = str_replace('/', '', $exp_dni);
		$this->add_var_js('regexp_doc', $exp_dni);
		$this->add_mensaje_js('error_vacio', kernel::traductor()->trans('ayuda_menu.error_vacio'));
		$this->add_mensaje_js('error_dni', kernel::traductor()->trans('ayuda_menu.error_dni'));
		$this->add_mensaje_js('error_email', kernel::traductor()->trans('ayuda_menu.error_email'));
	}

}