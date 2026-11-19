<?php

namespace siu\operaciones\plan;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;

class pagelet_notificaciones extends pagelet
{
	protected $notificaciones = array();
	
	function ini()
	{		
	}
	
    function get_nombre()
    {
        return 'notificaciones';
    }
    
    function prepare()
    {
    }
}
?>