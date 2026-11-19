<?php

namespace siu\operaciones\asociar_usuario_se;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_preinscripcion;

class vista extends vista_preinscripcion
{
	function set_pagelet($name)
	{
		$clase = "operaciones\asociar_usuario_se\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase, 'form');
		$this->add_pagelet($pl);
		return $pl;
	}

	function ini()
	{
		$clase = 'operaciones\asociar_usuario_se\pagelet_contenido';
		$pl = kernel::localizador()->instanciar($clase, 'contenido');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('asoc_se.titulo'));
	}

	function pagelet($id)
	{
		$clase = "operaciones\asociar_usuario_se\pagelet_$id";
		return kernel::localizador()->instanciar($clase, $id);
	}

}
?>