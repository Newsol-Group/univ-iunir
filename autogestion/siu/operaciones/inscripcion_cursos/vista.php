<?php
namespace siu\operaciones\inscripcion_cursos;

use siu\extension_kernel\vista_g3w2;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\parametro;

class vista extends vista_g3w2
{
    function ini()
    {
        $clase = 'operaciones\inscripcion_cursos\pagelet_cursos';
        $pl = kernel::localizador()->instanciar($clase, 'cursos');
        $this->add_pagelet($pl);

        $clase = 'operaciones\inscripcion_cursos\pagelet_info_curso';
        $pl = kernel::localizador()->instanciar($clase, 'info_curso');
        $this->add_pagelet($pl);

        $notificaciones_activas = parametro::usa_asignacion_horaria() == \comunes::si;
        if (kernel::agente()->es_browser() && $notificaciones_activas) {
            $clase = 'operaciones\_comun\pagelets\pagelet_notificaciones';
            $pl = kernel::localizador()->instanciar($clase, 'notificaciones');
            $this->add_pagelet($pl, 0);
        }

        kernel::pagina()->set_etiqueta('titulo', kernel::traductor()->trans("tit_inscripcion_cursos"));
    }
}