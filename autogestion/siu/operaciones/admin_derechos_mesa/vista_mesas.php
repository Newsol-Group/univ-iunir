<?php
namespace siu\operaciones\admin_derechos_mesa;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_mesas extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_vista_mesas');

		$clase = 'operaciones\admin_derechos_mesa\pagelet_filtro';
		$pl = kernel::localizador()->instanciar($clase, 'filtro');
		$this->add_pagelet($pl);

		$clase = 'operaciones\admin_derechos_mesa\pagelet_mesas';
		$pl = kernel::localizador()->instanciar($clase, 'mesas');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('derechos_mesa_titulo'));
	}
}
?>