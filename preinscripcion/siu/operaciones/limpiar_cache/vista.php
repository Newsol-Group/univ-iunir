<?php

namespace siu\operaciones\limpiar_cache;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_preinscripcion;

class vista extends vista_preinscripcion
{
	function set_pagelet($name)
	{
		$clase = "operaciones\limpiar_cache\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase);
		$this->add_pagelet($pl);
		return $pl;
	}

	function ini()
	{
		$clase = 'operaciones\limpiar_cache\pagelet_limpieza';
		$pl = kernel::localizador()->instanciar($clase, 'form');
		$this->add_pagelet($pl);
	}

	function pagelet($id)
	{
		$clase = "operaciones\limpiar_cache\pagelet_$id";
		return kernel::localizador()->instanciar($clase, $id);
	}

	function set_form_activo($pagelet)
	{
		$this->form_activo = $pagelet;
		$this->id_form_activo = $pagelet->get_id();
		$this->add_pagelet($pagelet, 0, 'form');
	}

}
?>