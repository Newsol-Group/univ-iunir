<?php
namespace siu\operaciones\evaluaciones;

use \SIU\Chulupi\kernel;

class vista
	extends \siu\extension_kernel\vista_g3w2
{
	function ini()
	{
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\evaluaciones\pagelet_lista_materias', 'pagelet'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_notas_parcial_materias'));
	}
}

?>