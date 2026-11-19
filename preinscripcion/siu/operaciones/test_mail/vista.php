<?php

namespace siu\operaciones\test_mail;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_preinscripcion;

class vista extends vista_preinscripcion
{
	function set_pagelet($name)
	{
		$clase = "operaciones\\test_mail\\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase, 'form');
		$this->add_pagelet($pl);
		return $pl;
	}

	function ini()
	{
		$clase = 'operaciones\\test_mail\\pagelet_configuracion';
		$pl = kernel::localizador()->instanciar($clase, 'configuracion');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_test_mail'));
	}

	function pagelet($id)
	{
		$clase = "operaciones\\test_mail\\pagelet_$id";
		return kernel::localizador()->instanciar($clase, $id);
	}

}
?>