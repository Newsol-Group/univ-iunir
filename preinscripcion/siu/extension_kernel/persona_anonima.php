<?php

namespace siu\extension_kernel;

use SIU\Chulupi\acceso\PersonaAnonima as persona_anonima_base;
use SIU\Chulupi\acceso\FactoryPerfil;

class persona_anonima extends persona_anonima_base
{
	function __construct()
	{
		$this->perfil = FactoryPerfil::instancia()->construir('anonimo_' . $_SERVER['PERFIL']);
	}

}
?>