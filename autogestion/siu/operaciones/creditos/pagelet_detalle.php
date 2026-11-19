<?php
namespace siu\operaciones\creditos;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;

class pagelet_detalle extends pagelet {
	public function get_nombre(){
        return 'detalle';
    }

	public function prepare() {
        $this->data['url_volver'] = kernel::vinculador()->crear('creditos', 'index');
    }
}
?>