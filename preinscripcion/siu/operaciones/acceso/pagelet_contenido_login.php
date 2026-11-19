<?php

namespace siu\operaciones\acceso;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\nucleo\NuevoRequest as nuevo_request;
use SIU\Chulupi\kernel;
use siu\preinscripcion;
use siu\errores\error_preinscripcion_cnx_bd;

class pagelet_contenido_login extends pagelet
{
	function ini()
	{
		if (!preinscripcion::mail_configurado()) {
			kernel::sesion()->set('msj_pagina_error', kernel::traductor()->trans('smpt_no_configurado'));
			throw new nuevo_request('pagina_error');
		}
		try {
			if (!preinscripcion::versiones_compatibles()) {
				kernel::sesion()->set('msj_pagina_error', kernel::traductor()->trans('versiones_no_compatibles'));
				throw new nuevo_request('pagina_error');
			}
		} catch (error_preinscripcion_cnx_bd $e) {
			$mensaje = kernel::traductor()->trans($e->get_mensaje());
			kernel::sesion()->set('msj_pagina_error', $mensaje);
			throw new nuevo_request('pagina_error');
		}
	}

	function get_nombre()
	{
		return 'contenido_login';
	}

	function prepare()
	{
		
	}

}
?>