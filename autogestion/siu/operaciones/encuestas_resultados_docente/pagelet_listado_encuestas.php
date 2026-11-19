<?php

namespace siu\operaciones\encuestas_resultados_docente;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_listado_encuestas extends pagelet {

    public function get_nombre()
    {
        return 'listado_encuestas';
    }

    function prepare()
    {
        $this->add_var_js('url_ver_bloques', kernel::vinculador()->crear('encuestas_resultados', 'ver_bloques'));
    }

} 