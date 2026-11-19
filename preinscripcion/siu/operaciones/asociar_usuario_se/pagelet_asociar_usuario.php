<?php

namespace siu\operaciones\asociar_usuario_se;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;

class pagelet_asociar_usuario extends pagelet_con_form
{
	function get_nombre()
	{
		return 'asociar_usuario';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\asociar_usuario_se\asociar_usuario\form_asociar_usuario');
		}
		return $this->form;
	}

	function prepare()
	{
		$datos = kernel::sesion()->get('datos_usuario');
		$this->data['datos_usuario'] = '<strong>' . kernel::traductor()->trans('rep_apellido_y_nombre') . '</strong>: ' . $datos['apellido'] . ', ' . $datos['nombres'] . '</br></br>';
		$this->data['datos_usuario'] .= '<strong>' . kernel::traductor()->trans('rep_identificacion') . '</strong>: ' . $datos['tipo_documento_desc_abreviada'] . ' ' . $datos['nro_documento'] . '</br></br>';
		$this->data['url_volver'] = kernel::vinculador()->crear('acceso', 'index');

		$form = $this->get_form();

		switch ($this->estado) {
			case self::FORM_ERROR_VALIDACION:
				$this->data['mensajes'] = $this->controlador->get_var('mensajes');
				$this->data['error'] = true;
				break;
			default:
				$this->data['nombre_usuario'] = $this->controlador->get_var_default('nombre_usuario', '');
				$this->data['action_asociar'] = kernel::vinculador()->crear('asociar_usuario_se', 'asociar');
				$form->inicializar();
		}

		$this->add_form($form);
	}

}
?>