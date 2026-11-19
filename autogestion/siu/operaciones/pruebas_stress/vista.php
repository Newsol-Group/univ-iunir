<?php
namespace siu\operaciones\pruebas_stress;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{

    function ini()
    {
        $clase = 'operaciones\pruebas_stress\pagelet_contenido';
        $pl = kernel::localizador()->instanciar($clase, 'contenido');
        $this->add_pagelet($pl);

        kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('pruebas_stress.titulo'));
    }
}