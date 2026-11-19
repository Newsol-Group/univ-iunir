<?php
namespace siu\operaciones\admin_actas_examen_fc;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_actas extends vista_g3w2
{
	function ini()
	{
		$clase = 'operaciones\admin_actas_examen_fc\pagelet_acta';
		$pl = kernel::localizador()->instanciar($clase, 'acta');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', ucfirst(kernel::traductor()->trans("tit_admin_actas_examen_fc")));
	}

}
?>