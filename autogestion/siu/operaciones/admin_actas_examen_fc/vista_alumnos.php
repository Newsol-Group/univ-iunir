<?php
namespace siu\operaciones\admin_actas_examen_fc;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_alumnos extends vista_g3w2
{
	function ini()
	{
		$this->set_template('template_alumnos');
		$clase = 'operaciones\admin_actas_examen_fc\pagelet_alumnos';
		$pl = kernel::localizador()->instanciar($clase, 'alumnos');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_admin_actas_examen_fc_alumnos"));
	}

}
?>