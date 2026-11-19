<?php

namespace siu\errores;

use SIU\Chulupi\ErrorKernel as error_kernel;

class error_preinscripcion extends error_kernel
{
	function get_id_error_generico()
	{
		return 'error_indefinido';
	}

}
?>