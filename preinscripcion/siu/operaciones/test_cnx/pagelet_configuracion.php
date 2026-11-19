<?php

namespace siu\operaciones\test_cnx;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

class pagelet_configuracion extends pagelet
{
	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'configuracion';
	}

	function prepare()
	{
		$this->data['datos_cnx'] = kernel::proyecto()->get_parametros_db_gestion();
	}

}
?>