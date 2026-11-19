<?php
namespace siu\operaciones\ficha_alumno;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_home extends vista_g3w2
{
	function ini()
	{
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('ficha_alumno'));
		
		$this->set_template('template_vista_home');

		$clase = 'operaciones\ficha_alumno\pagelet_home';
		$pl = kernel::localizador()->instanciar($clase, 'home');
		$this->add_pagelet($pl);
	}
}
?>