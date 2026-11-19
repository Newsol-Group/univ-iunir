<?php
namespace siu\operaciones\inscriptos_examen;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_detalle extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_detalle');
		$clase = 'operaciones\inscriptos_examen\pagelet_detalle';
		$pl = kernel::localizador()->instanciar($clase, 'detalle');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_inscriptos_examenes"));
	}

}
?>