<?php

namespace siu\operaciones\solicitudes;

use SIU\Chulupi\kernel;

class vista_nueva_solicitud extends \siu\extension_kernel\vista_g3w2
{
	function ini()
	{
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\solicitudes\pagelet_nueva_solicitud', 'pagelet'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_solicitudes'));
	}

}
?>