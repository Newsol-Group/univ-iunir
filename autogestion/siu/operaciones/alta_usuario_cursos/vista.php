<?php
namespace siu\operaciones\alta_usuario_cursos;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_g3w2;

class vista extends vista_g3w2
{
	function set_pagelet($name)
	{
		$clase = "operaciones\alta_usuario_cursos\pagelet_$name";
		$pl = kernel::localizador()->instanciar($clase, 'form');
		$this->add_pagelet($pl);
		return $pl;
	}
	
    function ini()
    {
		if ($this->template != 'template_msj') {
			$clase = 'operaciones\alta_usuario_cursos\pagelet_contenido_alta';
			$pl = kernel::localizador()->instanciar($clase, 'contenido_alta');
			$this->add_pagelet($pl);
		}
		
        kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('alta_uc.tit_crear_usuario_cursos'));
    }
	
	function pagelet($id)
    {
		$clase = "operaciones\alta_usuario_cursos\pagelet_$id";
		return kernel::localizador()->instanciar($clase, $id);
    }
}
?>