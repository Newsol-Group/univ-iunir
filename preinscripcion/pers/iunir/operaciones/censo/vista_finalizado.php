<?php
namespace iunir\operaciones\censo;

use SIU\Chulupi\kernel;
use siu\extension_kernel\vista_preinscripcion;
use siu\operaciones\censo\vista_finalizado as vista_finalizado_base;

class vista_finalizado extends vista_finalizado_base
{

    function get_action_pagar()
	{
		return kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'pagar');
	}

}

