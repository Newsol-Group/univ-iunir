<?php
namespace siu\operaciones\zona_comisiones;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\guarani;

class pagelet_home extends pagelet
{
	function get_nombre()
	{
		return 'home';
	}

	function prepare()
	{
		$this->data = array();
		$this->data = $this->controlador->get_encabezado();
		$this->add_var_js('msg_valor', ucfirst(kernel::traductor()->trans('valor')));
		$this->add_var_js('msg_descripcion', ucfirst(kernel::traductor()->trans('descripcion')));
		$this->add_var_js('msg_resultado', ucfirst(kernel::traductor()->trans('resultado')));
	}
}

?>
