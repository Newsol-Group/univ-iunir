<?php

namespace siu\operaciones\requisitos_ingreso;

use SIU\Chulupi\kernel;

class vista extends \siu\operaciones\_comun\operaciones\reporte\vista
{
	function ini()
	{
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_requisitos_ingreso"));
	}

	function vista_reporte()
	{
		$clase = 'operaciones\requisitos_ingreso\pagelet_reporte';
		$pl = kernel::localizador()->instanciar($clase, 'items');
		$this->add_pagelet($pl);
	}

	function vista_doc_digital()
	{
		$clase = 'operaciones\requisitos_ingreso\pagelet_doc_digital';
		$pl = kernel::localizador()->instanciar($clase, 'items');
		$this->add_pagelet($pl);
		return $pl;
	}

}
?>