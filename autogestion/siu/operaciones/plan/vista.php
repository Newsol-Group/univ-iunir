<?php
namespace siu\operaciones\plan;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;

class vista extends vista_g3w2
{
    function ini()
    {
		$clase = 'operaciones\plan\pagelet_info_plan';
		$pl = kernel::localizador()->instanciar($clase, 'info_plan');
        $this->add_pagelet($pl);

        $clase = 'operaciones\plan\pagelet_notificaciones';
		$pl = kernel::localizador()->instanciar($clase, 'notificaciones');
        $this->add_pagelet($pl);

		kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_plan_estudios"));
    }
}
?>