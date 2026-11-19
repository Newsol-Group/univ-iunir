<?php

namespace nsg01\operaciones\configuracion;

use SIU\Chulupi\kernel;

class pagelet_configuraciones extends \siu\operaciones\configuracion\pagelet_configuraciones
{
	function permite_editar_email()
	{
		return !kernel::persona()->es_perfil_docente();
	}
}

