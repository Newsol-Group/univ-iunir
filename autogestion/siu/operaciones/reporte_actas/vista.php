<?php
namespace siu\operaciones\reporte_actas;

use \SIU\Chulupi\kernel;

class vista
	extends \siu\extension_kernel\vista_g3w2
{
	function ini()
	{
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\reporte_actas\pagelet_origen', 'origen'));
		$pl = pagelet_acta::instanciar('acta');
		$pl->set_mode(pagelet_acta::LISTADO);
		$this->add_pagelet($pl);
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_reporte_actas'));
	}
}
?>