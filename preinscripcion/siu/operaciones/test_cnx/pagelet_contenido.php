<?php

namespace siu\operaciones\test_cnx;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

class pagelet_contenido extends pagelet
{
	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'contenido';
	}

	function prepare()
	{
		$this->data['resultados'] = $this->controlador->modelo()->test();
	}

}
?>