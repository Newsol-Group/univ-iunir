<?php

namespace siu\operaciones\importar_config_form;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;
use siu\operaciones\importar_config_form\import\form_import;

class pagelet_import extends pagelet_con_form
{
	const IMPORT_OK = 'import_ok';
	const ERROR_BD = 'error_bd';

	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'import';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = new form_import();
		}
		return $this->form;
	}

	function prepare()
	{
		$this->add_var_js('error_vacio', kernel::traductor()->trans('form_validacion_error.vacio'));
		$this->add_var_js('error_tipos_iguales', kernel::traductor()->trans('error_tipos_iguales'));

		$parametros_db = kernel::proyecto()->get_parametros_db();
		$this->data['mensaje_importar'] = kernel::traductor()->trans('importar_msj', array('%1%' => $parametros_db['dbname'], '%2%' => $parametros_db['host'], '%3%' => $parametros_db['port']));

		$form = $this->get_form();
		switch ($this->estado) {
			case self::FORM_ERROR_VALIDACION:
				$this->data['mensaje_error'] = kernel::traductor()->trans('import_config_error');
				break;
			case self::ERROR_BD:
				$this->data['mensaje_error'] = kernel::traductor()->trans('import_error_bd');
				break;
			case self::IMPORT_OK:
				$this->data['mensaje_ok'] = kernel::traductor()->trans('import_config_ok');
			default:
				$form->inicializar();
		}
		$this->add_form($form);
	}

}
?>