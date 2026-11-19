<?php

namespace siu\extension_kernel;

use siu\preinscripcion;

class login_admin
{
	/**
	 * Retorna el ID interno de la tabla "admin_usuarios"
	 */
	function autenticar($usuario, $clave)
	{
		return preinscripcion::admin()->login($usuario, $clave);
	}

}
?>