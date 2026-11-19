<?php
namespace siu\operaciones\mensajes;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_lista_mensajes extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_lista_mensajes');
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\mensajes\pagelet_lista_mensajes', 'lista_mensajes'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_mensajes_enviar'));
	}
}
?>