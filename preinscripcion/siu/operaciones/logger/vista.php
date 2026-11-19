<?php

namespace siu\operaciones\logger;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_preinscripcion;

class vista extends vista_preinscripcion
{
	function ini()
	{
		$clase = 'operaciones\logger\pagelet_lista_logs';
		$pl = kernel::localizador()->instanciar($clase, 'lista_logs');
		$this->add_pagelet($pl);

		$clase = 'operaciones\logger\pagelet_info_log';
		$pl = kernel::localizador()->instanciar($clase, 'info_log');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', "Logger");
		kernel::pagina()->set_etiqueta('sub_titulo', "");
	}

}
?>
