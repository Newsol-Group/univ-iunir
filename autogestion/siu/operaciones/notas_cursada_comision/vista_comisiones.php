<?php
namespace siu\operaciones\notas_cursada_comision;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_comisiones extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_vista_comisiones');

		$clase = 'operaciones\notas_cursada_comision\pagelet_comisiones';
		$pl = kernel::localizador()->instanciar($clase, 'comisiones');
		$this->add_pagelet($pl);
	}

}
?>