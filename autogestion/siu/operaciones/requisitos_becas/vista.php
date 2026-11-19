<?php

namespace siu\operaciones\requisitos_becas;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{

    function ini()
    {
        kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("header.menu.requisitos_becas"));
    }

    /**
     * @return \SIU\Chulupi\interfaz\pagelet
     */
    function set_pagelet($name)
    {
        $clase = "operaciones\\requisitos_becas\\pagelet_{$name}";
        $pl = kernel::localizador()->instanciar($clase, 'pagelet');
        $this->add_pagelet($pl);
        return $pl;
    }

}