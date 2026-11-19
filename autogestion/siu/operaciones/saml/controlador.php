<?php

namespace siu\operaciones\saml;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;

class controlador extends controlador_g3w2
{
	public function modelo()
	{
		return null;
	}
	
	function accion__index()
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		$this->nuevo_request($operacion, 'metadata');
	}
	
    function accion__metadata()
    {
		
		$auth_providers = kernel::login_manager()->get_auth_providers();
		if(isset($auth_providers['saml'])) {
			header('Content-Type: text/xml');
			ob_start();
			echo $auth_providers['saml']->get_metadata();
			ob_end_flush();
			die();
		}
    }

}