<?php
namespace siu\operaciones\wsf;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
    function ini()
    {
		$clase = 'operaciones\wsf\pagelet_boton';
		$pl = kernel::localizador()->instanciar($clase, 'boton');
        $this->add_pagelet($pl);
		
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_reinscripcion"));
    }
}
?>