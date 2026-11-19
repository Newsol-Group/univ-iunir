<?php

namespace siu\operaciones\solicitar_certificacion;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_g3w2;

class vista extends vista_g3w2
{
    function ini()
    {
        kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_solicitar_certificacion"));
    }

    /**
     * @return \SIU\Chulupi\interfaz\pagelet
     */
    function set_pagelet($name)
    {
        $clase = "operaciones\\solicitar_certificacion\\pagelet_{$name}";
        $pl = kernel::localizador()->instanciar($clase, 'pagelet');
        $this->add_pagelet($pl);
        return $pl;
    }
}
