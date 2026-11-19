<?php
namespace siu\operaciones\cierre_cursadas;

use SIU\Chulupi\kernel;

class vista_actas extends \SIU\Chulupi\interfaz\vistas\VistaPagelets
{
	function ini()
	{
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\cierre_cursadas\pagelet_actas', 'actas'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("cierre_cursadas_titulo"));
	}
}
?>