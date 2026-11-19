<?php
namespace siu\operaciones\inicio_gestion;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\parametro;

class vista extends vista_g3w2
{
    function ini()
    {
		$clase = 'operaciones\inicio_gestion\pagelet_contenido';
		$pl = kernel::localizador()->instanciar($clase, 'contenido');
        $this->add_pagelet($pl);
		
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_inicio_gestion"));
    }
}

?>