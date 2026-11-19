<?php

namespace siu\operaciones\asociar_usuario_se;

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
		$datos = kernel::sesion()->get('datos_usuario');
		$mail = $datos['e_mail'];
		$medio = capitalizar(kernel::sesion()->get('medio_se'));
		$this->data['mensaje'] = kernel::traductor()->trans('asoc_se.descripcion', array('%1%' => $mail, '%2%' => $medio));
		$this->data['url_recuperar'] = kernel::vinculador()->crear('acceso', 'recuperar');
	}

}
?>