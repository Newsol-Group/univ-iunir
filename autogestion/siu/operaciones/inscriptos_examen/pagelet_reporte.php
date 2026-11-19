<?php
namespace siu\operaciones\inscriptos_examen;

use SIU\Chulupi\interfaz\pagelet;

class pagelet_reporte extends pagelet
{
	function get_nombre()
	{
		return 'reporte';
	}

	function prepare()
	{
		return array();
	}

}
?>