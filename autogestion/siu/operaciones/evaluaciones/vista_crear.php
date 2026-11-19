<?php
namespace siu\operaciones\evaluaciones;

use \SIU\Chulupi\kernel;

use siu\extension_kernel\vista_g3w2;

class vista_crear extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template');
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\evaluaciones\pagelet_nueva_evaluacion', 'pagelet'));
		//$this->agregar_a_contexto('titulo', 'nueva evaluación');
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('crear_parcial'));
	}
}

?>