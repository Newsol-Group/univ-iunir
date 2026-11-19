<?php
namespace siu\operaciones\actas_cursadas;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_acta extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_acta');
		
		$clase = 'operaciones\actas_cursadas\pagelet_acta';
		$pl = kernel::localizador()->instanciar($clase, 'acta');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_actas_cursada"));
	}
}
?>