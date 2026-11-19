<?php

namespace siu\extension_kernel;

use SIU\Chulupi\interfaz\vistas\VistaPagelets;
use SIU\Chulupi\kernel;

abstract class vista_g3w2 extends VistaPagelets
{
	function get_extra_params()
	{
		$params = parent::get_extra_params();
		if (kernel::sesion()->abierta()) {
			$params['cant_mensajes'] = kernel::persona()->get_cantidad_mensajes();
			$params['cant_req_pendientes'] = kernel::persona()->get_cant_requisitos_pendientes();			
		}
		
		return $params;
	}
}
?>
