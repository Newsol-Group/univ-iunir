<?php
namespace siu\operaciones\admin_derechos_mesa;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_edicion_derechos extends vista_g3w2
{
    function ini()
    {
		$this->set_template('template_vista_edicion_derechos');

		$clase = 'operaciones\admin_derechos_mesa\pagelet_derechos';
		$pl = kernel::localizador()->instanciar($clase, 'derechos');
		$this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("derechos_mesa_titulo"));
    }
}
?>