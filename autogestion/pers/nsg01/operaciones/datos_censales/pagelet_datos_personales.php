<?php

namespace nsg01\operaciones\datos_censales;

use SIU\Chulupi\kernel;

class pagelet_datos_personales extends \siu\operaciones\datos_censales\pagelet_datos_personales
{
	function es_docente()
	{
		return kernel::persona()->es_perfil_docente();
	}
}
