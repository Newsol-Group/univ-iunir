<?php
namespace siu\operaciones\admin_derechos_comision;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista_edicion_derechos extends vista_g3w2
{
    function ini()
    {
		$this->set_template('template_vista_edicion_derechos');


		$clase = 'operaciones\admin_derechos_comision\pagelet_derechos';
		$pl = kernel::localizador()->instanciar($clase, 'derechos');
		$this->add_pagelet($pl);

//		/* url botón Volver */
//		$operacion = kernel::ruteador()->get_id_operacion();
//		$this->agregar_a_contexto('url_back', kernel::vinculador()->crear($operacion));
		
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("derechos_comision_titulo"));
    }
}
?>