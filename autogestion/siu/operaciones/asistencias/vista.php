<?php
namespace siu\operaciones\asistencias;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
	function ini()
	{
		$clase = 'operaciones\asistencias\pagelet_cabecera';
		$pl = kernel::localizador()->instanciar($clase, 'cabecera');
		$this->add_pagelet($pl, 1);

		$clase = 'operaciones\asistencias\pagelet_edicion_asistencias';
		$pl = kernel::localizador()->instanciar($clase, 'edicion_asistencias');
		$this->add_pagelet($pl, 0);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_asistencia_edicion"));
	}

}
?>