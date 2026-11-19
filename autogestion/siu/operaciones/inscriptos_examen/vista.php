<?php
namespace siu\operaciones\inscriptos_examen;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_g3w2;

class vista extends vista_g3w2
{
	function ini()
	{
		$this->add_pagelet(kernel::localizador()->instanciar('operaciones\inscriptos_examen\pagelet_reporte', 'items'));
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_inscriptos_cursada"));
	}

}
?>