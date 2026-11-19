<?php
namespace siu\operaciones\inscriptos_cursadas;

use SIU\Chulupi\kernel;

class vista_comision extends \siu\operaciones\_comun\operaciones\reporte\vista
{
	function ini()
	{
		$clase = 'operaciones\inscriptos_cursadas\pagelet_comision';
		$pl = kernel::localizador()->instanciar($clase, 'items');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_inscriptos_cursada"));
	}

	function get_extra_params()
	{
		$base = parent::get_extra_params();
		$base['deshabilitar_actualizacion_menu'] = true;
		return $base;
	}

}
?>