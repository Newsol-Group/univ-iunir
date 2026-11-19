<?php
namespace siu\operaciones\estado_titulo;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends \siu\operaciones\_comun\operaciones\reporte\vista
{
    function ini()
    {

		$clase = 'operaciones\estado_titulo\pagelet_reporte';
		$pl = kernel::localizador()->instanciar($clase, 'reporte');
        $this->add_pagelet($pl);
	
        kernel::pagina()->set_etiqueta('titulo', "titulo");
    }
}
?>