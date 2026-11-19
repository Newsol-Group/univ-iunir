<?php
namespace siu\operaciones\seguimiento_tesis;

use SIU\Chulupi\kernel;

use \siu\operaciones\_comun\operaciones\reporte\vista as vista_reporte;

class vista extends vista_reporte
{
    function ini()
    {

		$clase = 'operaciones\seguimiento_tesis\pagelet_reporte';
		$pl = kernel::localizador()->instanciar($clase, 'reporte');
        $this->add_pagelet($pl);
	
        kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('header.menu.seguimiento_tesis'));
    }
}
?>