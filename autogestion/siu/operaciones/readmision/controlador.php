<?php

namespace siu\operaciones\readmision;

use siu\guarani;
use SIU\Chulupi\kernel;
use SIU\Chulupi\nucleo\NuevoRequest;
use siu\extension_kernel\controlador_g3w2;

class controlador extends controlador_g3w2
{
	function modelo()
	{
		return null;
	}

	function accion__procesar_readmision()
	{
		$mensaje = guarani::readmision()->solicitudes();
		kernel::sesion()->set('mensaje_readmision2', $mensaje);
		kernel::sesion()->set('operacion_readmision_activa', true);

		$nuevo_request = new NuevoRequest('inicio_alumno', 'index', false);
		$nuevo_request->set_mensaje_post_nav($mensaje);
		throw $nuevo_request;
	}

}
?>