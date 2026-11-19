<?php
namespace siu\operaciones\inscriptos_cursadas;

use SIU\Chulupi\kernel;

class vista extends \siu\operaciones\_comun\operaciones\reporte\vista
{
	function ini()
	{
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\inscriptos_cursadas\pagelet_reporte', 'items'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_inscriptos_cursada"));
	}

}
?>