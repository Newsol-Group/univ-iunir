<?php
namespace siu\operaciones\controles;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\guarani;

class pagelet_controles extends pagelet
{

	
	function ini()
	{
	}
	
	function get_nombre()
	{
		return 'controles';
	}

	function prepare()
	{
		$this->add_var_js('url_controles_test1', kernel::vinculador()->crear('controles', 'test1'));

	}
}
?>