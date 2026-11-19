<?php
namespace siu\operaciones\controles;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
	function ini()
	{
		$clase = 'operaciones\controles\pagelet_controles';
		$pl = kernel::localizador()->instanciar($clase, 'controles');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("controles"));
	}
}
?>