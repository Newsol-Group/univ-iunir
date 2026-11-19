<?php

namespace siu\operaciones\ayuda_menu;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_g3w2;

class vista extends vista_g3w2
{
	function set_pagelet($name)
	{
		$clase = "operaciones\\ayuda_menu\\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase, 'contacto');
		$this->add_pagelet($pl);
		return $pl;
	}

	function ini()
	{
		$clase = 'operaciones\ayuda_menu\pagelet_contacto';
		$pl = kernel::localizador()->instanciar($clase, 'contacto');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('header.menu.ayuda_menu'));
	}

}