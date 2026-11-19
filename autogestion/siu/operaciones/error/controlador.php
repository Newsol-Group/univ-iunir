<?php
namespace siu\operaciones\error;
use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;


class controlador extends controlador_g3w2
{
	
	/**
     * @return null
     */
    function modelo()
    {
        return null;
    }
	
	function accion__index()
	{
        $this->vista()->set_contexto(array(
            'operacion' => kernel::ruteador()->get_id_operacion_error()
        ));
	}
}

?>