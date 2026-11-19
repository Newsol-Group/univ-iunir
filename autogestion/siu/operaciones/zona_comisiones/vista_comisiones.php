<?php
namespace siu\operaciones\zona_comisiones;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_comisiones extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_vista_comisiones');

		$clase = 'operaciones\zona_comisiones\pagelet_comisiones';
		$pl = kernel::localizador()->instanciar($clase, 'comisiones');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('zona.zona_comision_tit'));
	}
}

?>