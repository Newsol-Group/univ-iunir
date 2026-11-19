<?php

namespace siu\extension_kernel;

use siu\preinscripcion;

class login
{
	/**
	 * Retorna el NUMERO DE INSCRIPCION de la persona
	 */
	function autenticar($id, $clave)
	{
		$usuario = preinscripcion::usuario()->login($id, $clave);
		return $usuario;
	}

	function autenticar_login_externo($datos)
	{
		$id = $datos['id'];
		$proveedor = $datos['proveedor'];
		$login = 'login_' . $proveedor;
		$usuario = preinscripcion::usuario()->$login($id);
		return $usuario;
	}

}
?>