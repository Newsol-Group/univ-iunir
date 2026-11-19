<?php
namespace siu\operaciones\reporte_inasistencias;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_inasistencias extends pagelet
{	
	function ini()
	{
	}
	
	function get_nombre()
	{
		return 'inasistencias';
	}
	
	function prepare()
	{
		$parametros = array();
		$parametros['persona'] = kernel::persona()->get_id();
		$parametros['propuesta'] = kernel::persona()->get_id_propuesta_activa();
		$this->data['inasistencias'] = catalogo::consultar('alumno', 'get_inasistencias', $parametros);
	}
}
?>