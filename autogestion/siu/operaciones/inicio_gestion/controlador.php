<?php

namespace siu\operaciones\inicio_gestion;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;

class controlador extends controlador_g3w2
{
    /**
     *
     * @return null
     */
    function modelo()
    {
        return null;
    }

    function accion__index()
    {
		$this->vista()->agregar_a_contexto('persona', kernel::persona()->get_nombre());
    }
}
?>