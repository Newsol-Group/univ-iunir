<?php
namespace siu\operaciones\impresion_actas_examen;

use SIU\Chulupi\kernel;

class vista_actas extends \SIU\Chulupi\interfaz\vistas\VistaPagelets
{
	function ini()
	{
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\impresion_actas_examen\pagelet_actas', 'actas'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('impresion_actas_examen_titulo'));
	}

}
?>