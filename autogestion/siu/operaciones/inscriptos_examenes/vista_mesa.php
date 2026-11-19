<?php
namespace siu\operaciones\inscriptos_examenes;

use \SIU\Chulupi\kernel;

class vista_mesa
	extends \siu\extension_kernel\vista_g3w2
{
	function ini()
	{
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\inscriptos_examenes\pagelet_mesa', 'items'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_inscriptos_examenes'));
	}
}
?>