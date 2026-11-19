<?php

namespace siu\extension_kernel;

use SIU\Chulupi\kernel;
use SIU\Chulupi\nucleo\InterfaceEventosAplicacion;

class eventos_aplicacion implements InterfaceEventosAplicacion
{
	function ini()
	{
		kernel::log()->add_debug('ALIAS', kernel::proyecto()->get_alias());
		if (kernel::proyecto()->get('barra_dev')) {
			header('guarani-log: ' . kernel::log()->get_id());
		}
	}

	function fin()
	{
		
	}

	function pre_ini__sesion()
	{
		
	}

	function ini__conexion($db)
	{
		
	}

	function fin__conexion($db)
	{
		
	}

	function fin__request()
	{
		
	}

	function fin__response()
	{
		
	}

}
?>