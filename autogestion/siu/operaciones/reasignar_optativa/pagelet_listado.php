<?php

namespace siu\operaciones\reasignar_optativa;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_listado extends pagelet
{

	const TIPO_MENSAJE_ERROR	= 'alert-error';
	const TIPO_MENSAJE_EXITO	= 'alert-success';
	const TIPO_MENSAJE_INFO		= 'alert-info';
	
    function ini()
    {
    }

    function get_nombre()
    {
        return 'listado';
    }

	/**
	 * Shortcut al modelo
	 * 
	 * @return \siu\modelo\transacciones\reasignar_optativa
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}
	
    function prepare()
    {
		
		$this->data['optativas_en_mas_de_una_generica'] = $this->modelo()->get_optativas_en_mas_de_una_generica();
		
		$operacion = kernel::ruteador()->get_id_operacion();
		$this->data['url_guardar'] = kernel::vinculador()->crear($operacion, 'guardar');
		
		// Si es la vuelta de la confirmación
		if(kernel::sesion()->esta_seteada('hubo_error') && kernel::sesion()->esta_seteada('cant_cambios')){
			
			$this->data['mostrar_mensaje'] = true;
			
			$hubo_error		= kernel::sesion()->get('hubo_error');
			$cant_cambios	= kernel::sesion()->get('cant_cambios');
			
			// Si hubo error
			if($hubo_error){
				$this->data['tipo_mensaje'] = static::TIPO_MENSAJE_ERROR;
				$this->data['mensaje']		= kernel::traductor()->trans('reasignar_optativa.error_confirmar_reasignacion');
			} // si no hubo error y hubo cambios
			else if ($cant_cambios > 0){
				$this->data['tipo_mensaje'] = static::TIPO_MENSAJE_EXITO;
				$this->data['mensaje']		= kernel::traductor()->trans('reasignar_optativa.reasignacion_exitosa');
			} // si no hubo error y tampoco hubo cambios
			else{
				$this->data['tipo_mensaje'] = static::TIPO_MENSAJE_INFO;
				$this->data['mensaje']		= kernel::traductor()->trans('reasignar_optativa.no_has_reasignado_optativas');
			}
			
			kernel::sesion()->borrar('hubo_error');
			kernel::sesion()->borrar('cant_cambios');
		}
		
    }
}