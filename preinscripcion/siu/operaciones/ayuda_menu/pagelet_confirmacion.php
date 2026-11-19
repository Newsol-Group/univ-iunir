<?php

namespace siu\operaciones\ayuda_menu;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_confirmacion extends pagelet
{
	function get_nombre()
	{
		return 'confirmacion';
	}

	function prepare()
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		$this->data['url_volver'] = kernel::vinculador()->crear($operacion, 'index');
	}

}