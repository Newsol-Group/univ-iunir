<?php
namespace siu\operaciones\boleto_estudiantil;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_g3w2;

class vista extends vista_g3w2
{

    function ini()
    {
        $clase = 'operaciones\boleto_estudiantil\pagelet_inicial';
        $pl = kernel::localizador()->instanciar($clase, 'inicial');
        $this->add_pagelet($pl);

        kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("boleto_estudiantil.titulo"));
    }

}