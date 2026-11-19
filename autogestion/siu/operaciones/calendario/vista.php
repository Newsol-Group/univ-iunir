<?php

namespace siu\operaciones\calendario;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\parametro;

class vista extends vista_g3w2
{
	function ini()
	{
		$clase = 'operaciones\_comun\pagelets\pagelet_notificaciones';
		$pl = kernel::localizador()->instanciar($clase, 'notificaciones');
		$this->add_pagelet($pl);
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_calendario"));
	}

}
?>