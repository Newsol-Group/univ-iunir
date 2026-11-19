<?php

namespace siu\operaciones\copiar_config_form;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;
use siu\operaciones\copiar_config_form\copia\form_copia;

class pagelet_copia extends pagelet_con_form
{
	const COPIA_OK = 'copia_ok';

	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'copia';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = new form_copia();
		}
		return $this->form;
	}

	function prepare()
	{
		$this->add_var_js('error_vacio', kernel::traductor()->trans('form_validacion_error.vacio'));
		$this->add_var_js('error_tipos_iguales', kernel::traductor()->trans('error_tipos_iguales'));

		$form = $this->get_form();
		switch ($this->estado) {
			case self::FORM_ERROR_VALIDACION:
				$this->data['mensaje_error'] = kernel::traductor()->trans('copia_config_error');
				break;
			case self::COPIA_OK:
				$this->data['mensaje_ok'] = kernel::traductor()->trans('copia_config_ok');
			default:
				$form->inicializar();
		}
		$this->add_form($form);
	}

}
?>