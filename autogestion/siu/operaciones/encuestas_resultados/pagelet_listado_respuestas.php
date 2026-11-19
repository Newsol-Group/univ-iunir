<?php

namespace siu\operaciones\encuestas_resultados;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_listado_respuestas extends pagelet
{

    public function get_nombre()
    {
        return 'listado_respuestas';
    }

    function prepare()
    {
        
    }
}