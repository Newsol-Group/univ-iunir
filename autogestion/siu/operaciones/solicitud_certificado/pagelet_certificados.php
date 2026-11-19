<?php
namespace siu\operaciones\solicitud_certificado;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\modelo\datos\catalogo;

class pagelet_certificados extends pagelet {
	public function get_nombre(){
        return 'certificados';
    }

	function prepare() {
		$this->data['id_form'] = $this->controlador->get_id_certificado();
	}
	
	function opciones() {
		$certs = $this->controlador->modelo()->lista_certificados();
		$opciones = array();
		foreach ($certs as $id => $cert) {
			$opciones[$id] = array(
				'url' => kernel::vinculador()->crear('solicitud_certificado', 'index', $id),
				'nrcert' => $id,
				'nombre' => $cert['nombre'],
				'solicitud_pendiente' => $cert['solicitud_pendiente']
			);
		}
		return $opciones;
	}
}
?>