<?php

namespace siu\errores;

class error_preinscripcion_login extends error_preinscripcion
{
	protected $error_code;

	function __construct($error_code)
	{
		$this->error_code = $error_code;
	}

	function get_mensaje()
	{
		return $this->error_code;
	}

	function clave_expiro()
	{
		return $this->error_code == -3;
	}

}
?>
