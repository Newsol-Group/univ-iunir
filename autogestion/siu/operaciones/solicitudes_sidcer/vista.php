<?php
namespace siu\operaciones\solicitudes_sidcer;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
	
	function set_pagelet($name)
	{
		$clase = "operaciones\\solicitudes_sidcer\\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase, 'sidcer');
		$this->add_pagelet($pl);
	}
	
	function ini()
	{
		$clase = 'operaciones\solicitudes_sidcer\pagelet_listado';
		$pl = kernel::localizador()->instanciar($clase, 'sidcer');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_solicitudes_sidcer"));
	}
}