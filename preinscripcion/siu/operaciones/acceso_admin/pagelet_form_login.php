<?php

namespace siu\operaciones\acceso_admin;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\nucleo\NuevoRequest;
use siu\preinscripcion;
use siu\errores\error_preinscripcion_login;
use siu\errores\error_preinscripcion_cnx_bd;

class pagelet_form_login extends pagelet
{
	function ini()
	{
		try {
			if (!preinscripcion::versiones_compatibles()) {
				kernel::sesion()->set('msj_pagina_error', kernel::traductor()->trans('versiones_no_compatibles'));
				throw new NuevoRequest('pagina_error');
			}
		} catch (error_preinscripcion_cnx_bd $e) {
			$mensaje = kernel::traductor()->trans($e->get_mensaje());
			kernel::sesion()->set('msj_pagina_error', $mensaje);
			throw new NuevoRequest('pagina_error');
		}
	}

	function get_nombre()
	{
		return 'form_login';
	}

	function prepare()
	{
		$lm = kernel::login_manager();

		$modelo = array();
		foreach ($lm->get_auth_providers() as $auth) {
			/** @var $auth authentication_provider */
			$modelo[$auth->get_id()] = $auth->get_modelo();
		}

		if (!isset($modelo['admin'])) {
			throw new error_preinscripcion_login("Falta bloque de configuración 'admin' en login.php");
		}

		$this->data['form_admin'] = $modelo['admin'];
	}

}
?>