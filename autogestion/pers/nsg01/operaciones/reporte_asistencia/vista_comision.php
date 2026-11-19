<?php
namespace nsg01\operaciones\reporte_asistencia;

use SIU\Chulupi\kernel;

class vista_comision extends \siu\operaciones\_comun\operaciones\reporte\vista
{
	function ini()
	{
		$clase = 'operaciones\reporte_asistencia\pagelet_comision';
		$pl = kernel::localizador()->instanciar($clase, 'items');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_reporte_asistencia"));
	}

	function get_extra_params()
	{
		$base = parent::get_extra_params();
		$base['deshabilitar_actualizacion_menu'] = true;
		return $base;
	}

}
?>