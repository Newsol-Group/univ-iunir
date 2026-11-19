<?php
namespace siu\operaciones\evaluaciones;

use \SIU\Chulupi\kernel;

class vista_edicion_notas
	extends \siu\extension_kernel\vista_g3w2
{
	function ini()
	{
		$this->set_template('template');
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\evaluaciones\pagelet_edicion_notas', 'pagelet'));
		//$this->agregar_a_contexto('titulo', kernel::traductor()->trans('carga_notas_evaluaciones_parciales'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_notas_parcial_edicion'));
	}
}

?>