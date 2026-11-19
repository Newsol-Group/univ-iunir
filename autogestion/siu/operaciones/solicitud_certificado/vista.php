<?php
namespace siu\operaciones\solicitud_certificado;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends \siu\operaciones\_comun\operaciones\reporte\vista
{
    function ini()
    {
		$clase = 'operaciones\solicitud_certificado\pagelet_certificados';
		$pl = kernel::localizador()->instanciar($clase, 'certificados');
		$this->add_pagelet($pl);

		$clase = 'operaciones\solicitud_certificado\pagelet_formulario';
		$pl = kernel::localizador()->instanciar($clase, 'formulario');
        $this->add_pagelet($pl);
	
		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_solicitud_certificado"));		
    }
}
?>