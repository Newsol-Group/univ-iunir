<?php
namespace siu\operaciones\estado_titulo;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\modelo\datos\catalogo;

class pagelet_reporte extends pagelet {

	public function get_nombre(){
        return 'reporte';
    }

	function prepare()
	{

	}
	
}
?>