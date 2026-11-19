<?php

namespace siu\operaciones\acceso_admin;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_preinscripcion;
use siu\preinscripcion;

class controlador extends controlador_preinscripcion
{
	/**
	 * @return null
	 */
	function modelo()
	{
		return null;
	}

	function ini()
	{
		
	}

	function accion__index()
	{
		$this->vista()->set_pagelet('form_login');

		if (kernel::sesion()->abierta()) {
			$op_defecto = kernel::persona()->perfil()->get_parametro('index');
			$this->nuevo_request($op_defecto);
		}
	}

	function accion__logout()
	{
		
	}

	//--------------------------------------------------------------------------
	//---- Métodos auxiliares --------------------------------------------------
	//--------------------------------------------------------------------------

	protected function login($email, $password)
	{
		kernel::proyecto()->set_punto_acceso(preinscripcion::perfil_admin);
		kernel::sesion()->abrir_sesion($email, false);
		kernel::persona()->set_email($email);
	}

	protected function add_error($mensaje, $e = null)
	{
		$mensajes = array();
		$mensajes[] = array(
			preinscripcion::mensaje_cuerpo => $mensaje,
			preinscripcion::mensaje_tipo => preinscripcion::control_error,
		);
		$this->add_var('mensajes', $mensajes);
		if (!is_null($e)) {
			kernel::log()->add_error($e);
		}
	}

	protected function redirigir()
	{
		$this->nuevo_request(kernel::persona()->perfil()->get_parametro('index'));
	}

	protected function get_usuario_sin_validar()
	{
		return substr($this->get_post('e_mail'), 0, 100);
	}

}
?>