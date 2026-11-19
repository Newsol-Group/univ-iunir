<?php

namespace siu\operaciones\config_form;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;

class pagelet_configuracion extends pagelet_con_form
{
	const FORM_ERROR_GUARDADO = 'form_error_guardado';

	protected $form_tipos_propuesta;

	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'configuracion';
	}

	function hay_tipo_propuesta_seleccionado()
	{
		return kernel::sesion()->esta_seteada('tipo_propuesta_config');
	}

	function get_campos_configurables()
	{
		return $this->controlador->get_campos_configurables();
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\config_form\configuracion\form_configuracion');
		}
		return $this->form;
	}

	function get_form_tipos_propuesta()
	{
		if (!isset($this->form_tipos_propuesta)) {
			$this->form_tipos_propuesta = kernel::localizador()->instanciar('operaciones\config_form\configuracion\form_tipos_propuesta');
		}
		return $this->form_tipos_propuesta;
	}

	function prepare()
	{
		$form = $this->get_form();

		switch ($this->estado) {
			case self::FORM_ERROR_GUARDADO:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('guardado_datos_error'),
					'exitoso' => false
				));
				break;
			case self::FORM_GUARDADO:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('guardado_datos_exitoso'),
					'exitoso' => true
				));
			default:
				$form->inicializar();
				$datos = $this->controlador->get_configuracion();
				$form->set_datos($datos);
		}

		$this->add_form($form);

		$form_tipos_propuesta = $this->get_form_tipos_propuesta();
		$form_tipos_propuesta->inicializar();
		if ($this->hay_tipo_propuesta_seleccionado()) {
			$datos_tipo = array('tipo_de_propuesta' => kernel::sesion()->get('tipo_propuesta_config'));
			$form_tipos_propuesta->set_datos($datos_tipo);
		}
		$this->add_form($form_tipos_propuesta);
	}

}
?>