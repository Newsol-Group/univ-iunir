<?php
namespace siu\operaciones\orientacion;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\guarani;

class pagelet_formulario extends pagelet
{
	function ini()
	{
	}
	
	function get_nombre()
	{
		return 'formulario';
	}

	function hay_mensaje()
	{
		return $this->controlador->get_var('mens_actualizacion');
	}
	
	function prepare()
	{
		$link_form = kernel::vinculador()->crear('orientacion', 'index');
		$this->data['form_url'] = $link_form;

		$this->add_var_js("confirmacion", ucfirst(kernel::traductor()->trans('confirmacion')));
		$this->add_var_js("mensaje_cambio_orientacion", ucfirst(kernel::traductor()->trans('mensaje_selecionar_orientacion')));
		$this->add_var_js("cerrar", ucfirst(kernel::traductor()->trans('cerrar')));
		$this->add_var_js("acteptar", ucfirst(kernel::traductor()->trans('se grabo correctamente la orientacion seleccionada')));
	}
}
?>