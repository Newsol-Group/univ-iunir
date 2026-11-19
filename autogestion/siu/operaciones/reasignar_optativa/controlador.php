<?php
namespace siu\operaciones\reasignar_optativa;

use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use SIU\Chulupi\kernel;

class controlador extends controlador_g3w2 {

    /**
     * @return \siu\modelo\transacciones\reasignar_optativa
     */
    function modelo()
    {
        return guarani::reasignar_optativa();
    }
	
    function accion__index() {
		
    }

	function accion__guardar() {
		
		// Si entra por POST guardo
		if(kernel::request()->isPost()){
			
			$optativas = $this->get_post('optativas');
			kernel::log()->add_debug('=== reasignar_optativa/optativas_post ===', $optativas);

			list($hubo_error, $cant_cambios) = $this->modelo()->guardar_asignaciones_optativas($optativas);
			
			kernel::sesion()->set('hubo_error', $hubo_error);
			kernel::sesion()->set('cant_cambios', $cant_cambios);
			
		}
		
		// Redirijo al index (pantalla principal)
		$operacion = kernel::ruteador()->get_id_operacion();
		$this->nuevo_request($operacion, 'index');
    }
	
}