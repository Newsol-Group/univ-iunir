<?php
namespace siu\operaciones\administrar_cursos_virtuales;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
	
	function set_pagelet($name)
	{
		$clase = "operaciones\\administrar_cursos_virtuales\\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase, 'moodle');
		$this->add_pagelet($pl);
	}
	
	function ini()
	{
		$clase = 'operaciones\administrar_cursos_virtuales\pagelet_sin_plataforma';
		$pl = kernel::localizador()->instanciar($clase, 'moodle');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_moodle"));
	}
}