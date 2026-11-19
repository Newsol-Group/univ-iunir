<?php
require_once(realpath(dirname(__FILE__).'/../bootstrap.php'));

	\siu\bootstrap::cargar();
	if (SIU\Chulupi\kernel::proyecto()->get_produccion()) {
		die();
	}
	\SIU\Chulupi\nucleo\AccesoRecurso::procesar();

?>