<?php
namespace siu\operaciones\zona_examenes;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_home extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_vista_home');

		$clase = 'operaciones\zona_examenes\pagelet_home';
		$pl = kernel::localizador()->instanciar($clase, 'home');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('zona.zona_examenes_tit'));
	}
}
?>