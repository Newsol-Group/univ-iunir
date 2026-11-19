<?php
namespace siu\operaciones\actuacion_provisoria;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
    function ini()
    {
		$clase = 'operaciones\actuacion_provisoria\pagelet_reporte_cursada';
		$pl = kernel::localizador()->instanciar($clase, 'cursa');
        $this->add_pagelet($pl);

        $clase = 'operaciones\actuacion_provisoria\pagelet_reporte_promocion';
		$pl = kernel::localizador()->instanciar($clase, 'promo');
        $this->add_pagelet($pl);

        $clase = 'operaciones\actuacion_provisoria\pagelet_reporte_examen';
		$pl = kernel::localizador()->instanciar($clase, 'exa');
        $this->add_pagelet($pl);

        kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_actuacion_provisoria"));
    }
}