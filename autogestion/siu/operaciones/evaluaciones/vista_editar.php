<?php
namespace siu\operaciones\evaluaciones;

use \SIU\Chulupi\kernel;

class vista_editar
	extends \siu\extension_kernel\vista_g3w2
{
	function ini()
	{
		$this->set_template('template');
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\evaluaciones\pagelet_editar_evaluacion', 'pagelet'));
		//$this->agregar_a_contexto('titulo', 'editar evaluación');
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('editar_parcial'));
	}
}

?>