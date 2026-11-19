<?php
namespace siu\operaciones\reporte_actas;

use \SIU\Chulupi\kernel;

class vista_detalle
	extends \siu\extension_kernel\vista_g3w2
{
	function ini()
	{
		$this->set_template('template_detalle');
		$pl = pagelet_acta::instanciar('detalle');
		$pl->set_mode(pagelet_acta::DETALLE);
		$this->add_pagelet($pl);
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_reporte_actas.detalle'));
	}
}
?>