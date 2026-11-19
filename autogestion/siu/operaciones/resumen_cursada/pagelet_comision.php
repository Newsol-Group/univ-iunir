<?php
namespace siu\operaciones\resumen_cursada;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\modelo\datos\catalogo;

class pagelet_comision extends pagelet {
	public function get_nombre(){
		return 'comision';
	}

	public function prepare()
	{
	}
}
?>