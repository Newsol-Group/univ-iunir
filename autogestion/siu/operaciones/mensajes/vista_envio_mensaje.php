<?php
namespace siu\operaciones\mensajes;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_envio_mensaje
	extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_envio_mensaje');
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\mensajes\pagelet_envio_mensajes', 'envio_mensajes'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_mensajes_enviar'));
	}
}
?>