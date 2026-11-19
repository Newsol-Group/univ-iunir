<?php

namespace siu\operaciones\acceso_admin;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_preinscripcion;

class vista extends vista_preinscripcion
{
	function set_pagelet($name)
	{
		$clase = "operaciones\acceso_admin\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase);
		$this->add_pagelet($pl);
		return $pl;
	}

	function ini()
	{
		$clase = 'operaciones\acceso_admin\pagelet_form_login';
		$pl = kernel::localizador()->instanciar($clase, 'form');
		$this->add_pagelet($pl);
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_acceso'));
	}

	function pagelet($id)
	{
		$clase = "operaciones\acceso_admin\pagelet_$id";
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