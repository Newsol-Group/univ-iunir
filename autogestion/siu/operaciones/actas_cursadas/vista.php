<?php
namespace siu\operaciones\actas_cursadas;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
	function ini()
	{
		$clase = 'operaciones\actas_cursadas\pagelet_listado';
		$pl = kernel::localizador()->instanciar($clase, 'listado');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_actas_cursada"));
	}
}
?>