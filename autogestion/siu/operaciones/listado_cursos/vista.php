<?php
namespace siu\operaciones\listado_cursos;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends \siu\operaciones\_comun\operaciones\reporte\vista
{
    function ini()
    {

		$clase = 'operaciones\listado_cursos\pagelet_reporte';
		$pl = kernel::localizador()->instanciar($clase, 'reporte');
        $this->add_pagelet($pl);
	
        kernel::pagina()->set_etiqueta('titulo', "titulo");
    }
}
?>