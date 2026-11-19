<?php
namespace siu\operaciones\censo;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_preinscripcion;

class vista_finalizado extends vista_preinscripcion
{
	function ini()
	{
		$this->set_template('template_finalizado');
		
		$clase = 'operaciones\censo\pagelet_finalizado';
		$pl = kernel::localizador()->instanciar($clase, 'finalizado');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('carga_datos_censales'));
	}

    function get_action_imprimir()
	{
		return kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'imprimir');
	}
}

