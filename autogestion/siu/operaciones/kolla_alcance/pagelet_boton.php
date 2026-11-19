<?php

namespace siu\operaciones\kolla_alcance;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_boton extends pagelet
{
    function get_nombre()
    {
        return 'boton';
    }

    function prepare()
    {
		$this->data['alcances'] = catalogo::consultar('encuestas', 'alcances');
		$this->data['url_boton'] = kernel::vinculador()->crear('kolla_alcance', 'boton');
    }
}
?>
