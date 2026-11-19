<?php

namespace siu\extension_kernel;

use SIU\Chulupi\nucleo\InterfaceEventosAplicacion;
use siu\guarani;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\cache\AdminCache;

class eventos_aplicacion implements InterfaceEventosAplicacion
{

    function ini()
    {
      kernel::log()->add_debug('ALIAS', kernel::proyecto()->get_alias());

      // if (kernel::persona()->get_id() == 2) {
      //   kernel::proyecto()->set('barra_dev', true);
      //   header('guarani-log: ' . kernel::log()->get_id());

      // }

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