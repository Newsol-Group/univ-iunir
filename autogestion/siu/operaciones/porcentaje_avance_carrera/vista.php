<?php

namespace siu\operaciones\porcentaje_avance_carrera;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
	
	function ini()
	{
		$clase = 'operaciones\porcentaje_avance_carrera\pagelet_reporte';
		$pl = kernel::localizador()->instanciar($clase, 'reporte');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_porcentaje_avance_carrera"));
	}
}