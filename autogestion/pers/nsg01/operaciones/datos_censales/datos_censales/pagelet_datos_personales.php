<?php

namespace nsg01\operaciones\datos_censales;

use SIU\Chulupi\kernel;
use nsg01\modelo\transacciones\datos_censales as transaccion_datos_censales;

class pagelet_datos_personales extends \siu\operaciones\datos_censales\pagelet_datos_personales
{
	function prepare()
	{
		parent::prepare();
		$this->informar_error_notificacion_honorarios();
	}

	protected function informar_error_notificacion_honorarios()
	{
		if (kernel::sesion()->esta_seteada(transaccion_datos_censales::SESION_ERROR_NOTIFICACION)) {
			kernel::sesion()->borrar(transaccion_datos_censales::SESION_ERROR_NOTIFICACION);
			kernel::renderer()->add_to_ajax_response('info_estado', array(
				'mensaje' => transaccion_datos_censales::MENSAJE_ERROR_NOTIFICACION,
				'exitoso' => false,
			));
		}
	}
}
