<?php
namespace siu\operaciones\oferta_comisiones;

use siu\guarani;
use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
	function ini()
	{
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\oferta_comisiones\pagelet_reporte', 'oferta_com'));

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('header.menu.oferta_comisiones'));
	}
}
?>