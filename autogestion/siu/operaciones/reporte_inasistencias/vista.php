<?php
namespace siu\operaciones\reporte_inasistencias;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
    function ini()
    {
		$clase = 'operaciones\reporte_inasistencias\pagelet_inasistencias';
		$pl = kernel::localizador()->instanciar($clase, 'inasistencias');
		$this->add_pagelet($pl);
	
        kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('ri.titulo'));
        kernel::pagina()->set_etiqueta('sub_titulo', "");
    }
}
?>