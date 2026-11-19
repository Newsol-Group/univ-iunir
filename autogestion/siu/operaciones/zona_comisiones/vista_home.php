<?php
namespace siu\operaciones\zona_comisiones;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_home extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_vista_home');

		$clase = 'operaciones\zona_comisiones\pagelet_home';
		$pl = kernel::localizador()->instanciar($clase, 'home');
		$this->add_pagelet($pl);


		/* url botón Volver */
		$operacion = kernel::ruteador()->get_id_operacion();
		$this->agregar_a_contexto('url_back', kernel::vinculador()->crear($operacion));

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('zona.zona_comision_tit'));
	}
}

?>