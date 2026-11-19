<?php
namespace siu\operaciones\inscriptos_rechazados;

use SIU\Chulupi\interfaz\pagelet;
use siu\modelo\entidades\docente;

class pagelet_comision extends pagelet {

        public function get_nombre(){
        return 'comision';
        }

	public function prepare() {
            $comision = $this->controlador->getComision();
            $this->data['visualiza_datos_contacto'] = docente::visualiza_datos_contacto_por_comision($comision);
        }
}