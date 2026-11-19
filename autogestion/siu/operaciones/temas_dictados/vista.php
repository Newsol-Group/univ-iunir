<?php
namespace siu\operaciones\temas_dictados;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
    function ini()
    {

                $clase = 'operaciones\temas_dictados\pagelet_cabecera';
		$pl = kernel::localizador()->instanciar($clase, 'cabecera');
		$this->add_pagelet($pl, 1);
                $clase = 'operaciones\temas_dictados\pagelet_edicion_temas';
		$pl = kernel::localizador()->instanciar($clase, 'edicion_temas');
		$this->add_pagelet($pl, 0);
		
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_temas_dictados"));	
    }
}
?>