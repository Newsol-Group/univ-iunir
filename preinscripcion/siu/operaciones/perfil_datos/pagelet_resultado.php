<?php

namespace siu\operaciones\perfil_datos;

use SIU\Chulupi\interfaz\pagelet;

class pagelet_resultado extends pagelet
{
	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'resultado';
	}
	
	function prepare()
	{
		$this->data['resultado'] = $this->controlador->modelo()->get_configuracion_resultante();
	}
	
}
?>