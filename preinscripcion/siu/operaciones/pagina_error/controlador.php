<?php

namespace siu\operaciones\pagina_error;

use siu\extension_kernel\controlador_preinscripcion;

class controlador extends controlador_preinscripcion
{
	function modelo()
	{
		return null;
	}

	function ini()
	{
		
	}

	function accion__index()
	{
		$this->vista()->set_pagelet('contenido');
	}

}
?>