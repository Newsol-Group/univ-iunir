<?php

namespace siu\operaciones\limpiar_cache;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

class pagelet_limpieza extends pagelet
{
	const BORRADO_OK = 'borrado_ok';
	const ERROR = 'error';

	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'limpieza';
	}

	function get_accion()
	{
		return kernel::vinculador()->crear('limpiar_cache', 'limpieza', array('limpieza'));
	}

	function prepare()
	{
		switch ($this->estado) {
			case self::BORRADO_OK :
				$this->data['mensaje_ok'] = 'cache_borrado_ok';
				break;
			case self::ERROR :
				$this->data['mensaje_error'] = 'cache_borrado_error';
				break;
		}
	}

}
?>