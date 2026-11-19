<?php

namespace siu\operaciones\pagina_error;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

class pagelet_contenido extends pagelet
{
	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'contenido';
	}

	function prepare()
	{
		if (kernel::sesion()->esta_seteada('msj_pagina_error')) {
			$mensaje = kernel::sesion()->get('msj_pagina_error');
		} else {
			$mensaje = kernel::traductor()->trans('error');
		}
		$this->data['mensaje'] = $mensaje;
		$this->data['url_volver'] = kernel::vinculador()->crear(kernel::persona()->perfil()->get_parametro('index'), 'index');
	}

}
?>