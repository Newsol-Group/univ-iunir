<?php
namespace siu\operaciones\inscriptos_examenes;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_reporte extends \siu\operaciones\_comun\operaciones\reporte\vista
{
	function ini()
	{
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\inscriptos_examenes\pagelet_reporte', 'items'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_inscriptos_examenes'));
	}
}
?>