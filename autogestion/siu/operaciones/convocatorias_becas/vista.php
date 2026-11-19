<?php

namespace siu\operaciones\convocatorias_becas;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{

	function set_pagelet($name, $data = [])
	{
		$clase = "operaciones\\convocatorias_becas\\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase, 'becas');
		$pl->data = $data;
		$this->add_pagelet($pl);
	}
	
	function ini()
	{
		$clase = 'operaciones\convocatorias_becas\pagelet_pantalla_inicial';
		$pl = kernel::localizador()->instanciar($clase, 'becas');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("convocatorias_becas.titulo"));
	}
	
}