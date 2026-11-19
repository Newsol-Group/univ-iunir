<?php
namespace siu\operaciones\kolla_alcance;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
    function ini()
    {
		$clase = 'operaciones\kolla_alcance\pagelet_formulario';
		$pl = kernel::localizador()->instanciar($clase, 'formulario');
        $this->add_pagelet($pl);
		
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_kolla_alcance"));
    }
}
?>