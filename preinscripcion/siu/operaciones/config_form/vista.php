<?php

namespace siu\operaciones\config_form;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_preinscripcion;

class vista extends vista_preinscripcion
{
	function set_pagelet($name)
	{
		$clase = "operaciones\config_form\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase);
		$this->add_pagelet($pl);
		return $pl;
	}

	function ini()
	{
		$clase = 'operaciones\config_form\pagelet_configuracion';
		$pl = kernel::localizador()->instanciar($clase, 'conf');
		$this->add_pagelet($pl);
	}

	function pagelet($id)
	{
		$clase = "operaciones\config_form\pagelet_$id";
		return kernel::localizador()->instanciar($clase, $id);
	}

	function set_form_activo($pagelet)
	{
		$this->form_activo = $pagelet;
		$this->id_form_activo = $pagelet->get_id();
		$this->add_pagelet($pagelet, 0, 'conf');
	}

}
?>