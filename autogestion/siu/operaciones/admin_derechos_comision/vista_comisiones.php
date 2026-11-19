<?php
namespace siu\operaciones\admin_derechos_comision;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_comisiones extends vista_g3w2
{
    function ini()
    {
		$this->set_template('template_vista_comisiones');

	    $clase = 'operaciones\admin_derechos_comision\pagelet_filtro';
	    $pl = kernel::localizador()->instanciar($clase, 'filtro');
	    $this->add_pagelet($pl);

		$clase = 'operaciones\admin_derechos_comision\pagelet_comisiones';
	    $pl = kernel::localizador()->instanciar($clase, 'comisiones');
	    $this->add_pagelet($pl);



		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans('derechos_comision_titulo'));
    }
}
?>