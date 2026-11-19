<?php

namespace siu\operaciones\inicio_gestion;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

class pagelet_contenido extends pagelet
{
    function get_nombre()
    {
        return 'contenido';
    }

    function prepare()
    {
    }
}
?>