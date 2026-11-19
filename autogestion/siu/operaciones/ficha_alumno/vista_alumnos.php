<?php
namespace siu\operaciones\ficha_alumno;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_alumnos extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_vista_alumnos');

		$clase = 'operaciones\ficha_alumno\pagelet_alumnos';
		$pl = kernel::localizador()->instanciar($clase, 'alumnos');
		$this->add_pagelet($pl);


		/* url botón Volver */
		$operacion = kernel::ruteador()->get_id_operacion();
		$this->agregar_a_contexto('url_back', kernel::vinculador()->crear($operacion));

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('ficha_alumno'));
	}
	
	function get_accion_volver_ficha()
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		return kernel::vinculador()->crear($operacion);
	}
}
?>