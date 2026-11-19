<?php
namespace siu\operaciones\cierre_cursadas;

use SIU\Chulupi\kernel;

class vista_comprobante extends \SIU\Chulupi\interfaz\vistas\VistaPagelets
{
	function ini()
	{
		$this->set_template('template_comprobante');
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\cierre_cursadas\pagelet_comprobante', 'comprobante'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("cierre_cursadas_titulo"));
	}
}
?>