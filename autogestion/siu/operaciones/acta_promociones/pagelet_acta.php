<?php
namespace siu\operaciones\acta_promociones;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\modelo\datos\catalogo;

class pagelet_acta extends pagelet {
    protected $acta;
    
	public function get_nombre(){
        return 'acta';
    }

	public function prepare() {
    }
}
?>