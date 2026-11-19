<?php

namespace siu\operaciones\cronograma_evaluaciones;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
	
	function ini()
	{
		$clase = 'operaciones\cronograma_evaluaciones\pagelet_listado';
		$pl = kernel::localizador()->instanciar($clase, 'listado');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_cronograma_evaluaciones"));
	}
}