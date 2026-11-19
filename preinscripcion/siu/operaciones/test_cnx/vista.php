<?php

namespace siu\operaciones\test_cnx;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_preinscripcion;

class vista extends vista_preinscripcion
{
	function set_pagelet($name)
	{
		$clase = "operaciones\\test_cnx\\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase);
		$this->add_pagelet($pl);
		return $pl;
	}

	function ini()
	{
		$clase = 'operaciones\test_cnx\pagelet_contenido';
		$pl = kernel::localizador()->instanciar($clase, 'test');
		$this->add_pagelet($pl);
		
		$clase = 'operaciones\test_cnx\pagelet_configuracion';
		$pl = kernel::localizador()->instanciar($clase, 'configuracion');
		$this->add_pagelet($pl);
	}

	function pagelet($id)
	{
		$clase = "operaciones\test_cnx\pagelet_$id";
		return kernel::localizador()->instanciar($clase, $id);
	}

}
?>