<?php

namespace siu\operaciones\alta_usuario;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_preinscripcion;

class vista extends vista_preinscripcion
{
	function set_pagelet($name)
	{
		$clase = "operaciones\alta_usuario\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase, 'form');
		$this->add_pagelet($pl);
		return $pl;
	}

	function ini()
	{
		$clase = 'operaciones\alta_usuario\pagelet_contenido_alta';
		$pl = kernel::localizador()->instanciar($clase, 'contenido_alta');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('tit_alta'));
	}

	function pagelet($id)
	{
		$clase = "operaciones\alta_usuario\pagelet_$id";
		return kernel::localizador()->instanciar($clase, $id);
	}
        function hay_error()
	{
                return kernel::sesion()->esta_seteada('mensaje_vista_acceso_error_token');
	                }
        function get_error()
	{
            $error = kernel::sesion()->get('mensaje_vista_acceso_error_token');
            kernel::sesion()->borrar('mensaje_vista_acceso_error_token');
                return $error;
            }
        function set_error($error)
	{
		kernel::sesion()->set('mensaje_vista_acceso_error_token', $error);
	}

}
?>