<?php
/**
 * Created by PhpStorm.
 * User: leonel
 * Date: 23/02/15
 * Time: 15:14
 */

namespace siu\operaciones\plan_estudio;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_g3w2;

class vista extends vista_g3w2
{
	function ini()
	{
		$clase = 'operaciones\plan_estudio\pagelet_info_plan';
		$pl = kernel::localizador()->instanciar($clase, 'info_plan');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_plan_estudios"));
	}

}