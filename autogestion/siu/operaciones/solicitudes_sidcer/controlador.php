<?php
namespace siu\operaciones\solicitudes_sidcer;

use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use siu\modelo_g3\sidcer;

class controlador extends controlador_g3w2
{

	/**
	 * @return \siu\modelo\transacciones\sidcer
	 */
	function modelo()
	{
		return guarani::sidcer();
	}

	function accion__index()
	{
		$solicitudes = $this->modelo()->info__solicitudes_sidcer();
		
		// Si hubo un error al comunicarse con SidCer
		if ($solicitudes['error']) {
			
			$solicitudes['tipo_mensaje'] = "alert-error";
			
			// Si no hay resgistros es un mensaje de alerta (amarillo) en lugar de error (rojo)
			if ($solicitudes['mensaje'] == sidcer::MSJ_NO_HAY_REGISTROS) {
				$solicitudes['tipo_mensaje'] = "";
				$solicitudes['error'] = false;
			}
			
			$this->vista()->set_pagelet('error');
			$pagelet = $this->vista()->pagelet('sidcer');
			$pagelet->data = $solicitudes;
                        
		} else {
			
			$pagelet = $this->vista()->pagelet('sidcer');
			$pagelet->data['solicitudes'] = $solicitudes['solicitudes'];
		
		}
		
	}
	
}