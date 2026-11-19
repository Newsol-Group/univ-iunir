<?php
namespace siu\operaciones\zona_examenes;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_mesas extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_vista_mesas');

		$clase = 'operaciones\zona_examenes\pagelet_mesas';
		$pl = kernel::localizador()->instanciar($clase, 'mesas');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('zona.zona_examenes_tit'));
	}
}
?>