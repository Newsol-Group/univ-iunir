<?php

namespace siu\operaciones\kolla_alcance;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_formulario extends pagelet
{
    function get_nombre()
    {
        return 'formulario';
    }

	function get_alcances(){
		return catalogo::consultar('encuestas', 'alcances');
	}
	 
    function prepare()
    {
		$this->data['alcances'] = $this->get_alcances();
		$this->data['url_boton'] = kernel::vinculador()->crear('kolla_alcance', 'boton');
		if ($this->controlador->hay_var('mensaje')) {
			$this->data['mensaje'] = $this->controlador->get_var('mensaje');
		}
    }
}
?>
