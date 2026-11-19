<?php
namespace nsg01\operaciones\reporte_asistencia;

use SIU\Chulupi\kernel;

class vista extends \siu\operaciones\_comun\operaciones\reporte\vista
{
	function ini()
	{
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\reporte_asistencia\pagelet_reporte', 'items'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_reporte_asistencia"));
	}

}
?>