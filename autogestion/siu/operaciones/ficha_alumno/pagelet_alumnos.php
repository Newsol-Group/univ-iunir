<?php
namespace siu\operaciones\ficha_alumno;

use SIU\Chulupi\interfaz\pagelet;

class pagelet_alumnos extends pagelet
{
	function get_nombre()
	{
		return 'alumnos';
	}

	function prepare()
	{
		$this->data = array();
		$this->data['encabezado_comision'] = $this->controlador->encabezado_comision();
		$this->data['encabezado_mesa'] = $this->controlador->encabezado_mesa();
		$this->data['encabezado_acta'] = $this->controlador->encabezado_acta_fc();
		$this->data['alumnos'] = $this->controlador->get_listado_alumnos();
	}
}
?>