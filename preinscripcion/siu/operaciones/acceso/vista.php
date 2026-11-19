<?php

namespace siu\operaciones\acceso;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_preinscripcion;

class vista extends vista_preinscripcion
{
	function set_pagelet($name)
	{
		$clase = "operaciones\acceso\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase, 'form');
		$this->add_pagelet($pl);
		return $pl;
	}

	function ini()
	{
		$clase = 'operaciones\acceso\pagelet_contenido_login';
		$pl = kernel::localizador()->instanciar($clase, 'contenido_login');
		$this->add_pagelet($pl);
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_acceso'));
	}

	function pagelet($id)
	{
		$clase = "operaciones\acceso\pagelet_$id";
		return kernel::localizador()->instanciar($clase, $id);
	}

	function hay_mensaje_alta_iniciada()
	{
		return kernel::sesion()->esta_seteada('mensaje_alta_iniciada');
	}

	function get_mensaje_alta_iniciada()
	{
		$mensaje = kernel::traductor()->trans(kernel::sesion()->get('mensaje_alta_iniciada'), array('%1%' => kernel::vinculador()->crear('acceso', 'reenvio_mail', array('camb_msj' => 0))));
		return $mensaje;
	}

	function hay_mensaje_clave_reset()
	{
		return kernel::sesion()->esta_seteada('mensaje_clave_reset');
	}

	function get_mensaje_clave_reset()
	{
		$mensaje = kernel::sesion()->get('mensaje_clave_reset');
		kernel::sesion()->borrar('mensaje_clave_reset');
		return $mensaje;
	}

	function hay_cuenta_vinculada()
	{
		return kernel::sesion()->esta_seteada('cuenta_vinculada_ok');
	}

	function get_cuenta_vinculada()
	{
		$mensaje = kernel::sesion()->get('cuenta_vinculada_ok');
		kernel::sesion()->borrar('cuenta_vinculada_ok');
		return $mensaje;
	}

	function hay_error()
	{
		return kernel::sesion()->esta_seteada('mensaje_vista_acceso_error');
	}

	function get_error()
	{
		$error = kernel::sesion()->get('mensaje_vista_acceso_error');
		kernel::sesion()->borrar('mensaje_vista_acceso_error');
		return $error;
	}

	function set_error($error)
	{
		kernel::sesion()->set('mensaje_vista_acceso_error', $error);
	}

}
?>