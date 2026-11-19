<?php
namespace siu\operaciones\constancias_cursos;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_g3w2;

class vista	extends vista_g3w2
{
    function ini()
    {
        $clase = 'operaciones\constancias_cursos\pagelet_constancias';
        $pl = kernel::localizador()->instanciar($clase, 'constancias');
        $this->add_pagelet($pl);

        kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_constancias_cursos'));
    }
}