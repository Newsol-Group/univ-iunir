<?php

namespace siu\operaciones\test_cnx;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_preinscripcion;

class controlador extends controlador_preinscripcion
{
	static protected $modelo;

	function modelo()
	{
		if (!self::$modelo) {
			self::$modelo = kernel::localizador()->instanciar('operaciones\test_cnx\modelo');
		}
		return self::$modelo;
	}

	function ini()
	{
		
	}

	function accion__index()
	{
		
	}

}
?>