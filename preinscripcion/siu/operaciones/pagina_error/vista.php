<?php

namespace siu\operaciones\pagina_error;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_preinscripcion;

class vista extends vista_preinscripcion
{
	function set_pagelet($name)
	{
		$clase = "operaciones\pagina_error\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase);
		$this->add_pagelet($pl);
		return $pl;
	}

	function ini()
	{
		$clase = 'operaciones\pagina_error\pagelet_contenido';
		$pl = kernel::localizador()->instanciar($clase, 'contenido');
		$this->add_pagelet($pl);
	}

	function pagelet($id)
	{
		$clase = "operaciones\pagina_error\pagelet_$id";
		return kernel::localizador()->instanciar($clase, $id);
	}

}
?>