<?php

namespace siu\operaciones\wsf;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_boton extends pagelet
{
    function get_nombre()
    {
        return 'boton';
    }

    function prepare()
    {
		$this->data = array();
		$this->data['url_boton'] = kernel::vinculador()->crear('wsf', 'boton');
    }
}
?>
