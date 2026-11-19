<?php

namespace siu\operaciones\preinscripcion;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_info extends pagelet
{
	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'info';
	}

	function prepare()
	{
		$this->data['hay_periodo_preinsc_vigente'] = $this->controlador->modelo()->hay_periodo_preinsc_vigente();
		if (!$this->data['hay_periodo_preinsc_vigente']) {
			$proxima_fecha = $this->controlador->modelo()->proximo_inicio_preinscripcion();
			if (!empty($proxima_fecha)) {
				$this->data['prox_periodo_preinsc'] = kernel::traductor()->trans('preinscripcion.prox_periodo_preinsc', array('%1%' => $proxima_fecha));
			}
		}
	}

}
?>