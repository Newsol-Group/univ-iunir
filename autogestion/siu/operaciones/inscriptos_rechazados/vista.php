<?php
namespace siu\operaciones\inscriptos_rechazados;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends \siu\operaciones\_comun\operaciones\reporte\vista
{
    function ini()
    {
        $clase = 'operaciones\inscriptos_rechazados\pagelet_reporte';
        $pl = kernel::localizador()->instanciar($clase, 'items');
        $this->add_pagelet($pl);
        
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_inscriptos_rechazados"));
    }
}
?>