<?php

namespace siu\operaciones\reporte_readmisiones;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
	function ini()
	{

		$clase = 'operaciones\reporte_readmisiones\pagelet_reporte_readmisiones';
		$pl = kernel::localizador()->instanciar($clase, 'reporte_solicitudes_readmisiones');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('reporte_readmisiones.titulo'));
		kernel::pagina()->set_etiqueta('sub_titulo', "");
	}

}
?>