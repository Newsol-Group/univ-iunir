<?php

namespace nsg01\operaciones\datos_facturacion;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_g3w2;

class vista_cargar_datos_facturacion extends vista_g3w2
{
    function ini()
    {
		$clase = 'operaciones\datos_facturacion\pagelet_cargar_datos_facturacion';
		$pl = kernel::localizador()->instanciar($clase, 'pagelet');
		$this->add_pagelet($pl);
	
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_datos_facturacion"));		
    }
}
