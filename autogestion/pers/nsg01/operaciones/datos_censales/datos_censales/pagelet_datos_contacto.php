<?php

namespace nsg01\operaciones\datos_censales;

use SIU\Chulupi\kernel;

class pagelet_datos_contacto extends \siu\operaciones\datos_censales\pagelet_datos_contacto
{
	function permite_editar_mail_principal()
	{
		return !kernel::persona()->es_perfil_docente();
	}
}

