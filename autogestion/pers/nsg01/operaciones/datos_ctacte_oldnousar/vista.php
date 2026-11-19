<?php

namespace nsg01\operaciones\datos_ctacte;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
	
	function ini()
	{
		$clase = 'operaciones\datos_ctacte\pagelet_reporte';
		$pl = kernel::localizador()->instanciar($clase, 'reporte');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_datos_ctacte"));
	}
}