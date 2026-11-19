<?php

namespace siu\operaciones\requisitos_becas;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_error extends pagelet
{

    function get_nombre()
    {
        return 'error';
    }

    function prepare()
    {
        $this->prepare_urls();
    }

    function prepare_urls()
    {
        $operacion = kernel::ruteador()->get_id_operacion();
        $this->data['url_listado'] = kernel::vinculador()->crear($operacion, 'index');
    }

}