<?php

namespace siu\util\consola\comandos;

use Symfony\Component\Console\Command\Command;
use siu\bootstrap;

class comando_preinscripcion extends Command
{	
	function bootstrap_kernel($acc_id=false)
	{
		$_SERVER['PERFIL'] = 'consola';
		bootstrap::cargar(true, $acc_id);
	}
}