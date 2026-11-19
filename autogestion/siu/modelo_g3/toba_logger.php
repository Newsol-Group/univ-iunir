<?php

use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernelHackToba;

class toba_logger
{
	//------------------------------------------------------------------
	//------ Entradas para los distintos tipos de error
	//------------------------------------------------------------------

	/**
	 * Registra un suceso de emergencia (hecatombe)
	 */
	function emergencia($mensaje, $proyecto = null)
	{
		$this->registrar_error($mensaje, 'EMERGENCY');
	}

	/**
	 * Registra un suceso de alerta (un error que requiere intervencion humana)
	 */
	function alerta($mensaje, $proyecto = null)
	{
		$this->registrar_error($mensaje, 'ALERT');
	}

	/**
	 * Registra un suceso CRITICO (un error muy grave)
	 */
	function crit($mensaje, $proyecto = null)
	{
		$this->registrar_error($mensaje, 'CRITICAL');
	}

	/**
	 * Registra un error en la apl., este nivel es que el se usa en las excepciones
	 */
	function error($mensaje, $proyecto = null)
	{
		$this->registrar_error($mensaje, 'ERROR');
	}
	
	protected function registrar_error($mensaje, $nivel = 'ERROR')
	{
		$exception = null;
		
		// Si $mensaje es una Exception o Error (heredan de Throwable)
		if ($mensaje instanceof \Throwable) {
			$exception = $mensaje;
		} else {// si $mensaje no es una Exception o Error lo convierto en ErrorKernelHackToba para que muestre el mensaje sin traza
			
			// Si no es string lo convierto a este tipo
			if (!is_string($mensaje)) {
				$mensaje = var_export($mensaje, true);
			}
			
			$exception = new ErrorKernelHackToba($mensaje);
		}
		
		kernel::log()->add_error($exception, $nivel);
	}

	/**
	 * Registra un suceso no contemplado pero que posiblemente no afecta la correctitud del proceso
	 */
	function warning($mensaje, $proyecto = null)
	{
		kernel::log()->add_info('modelo_g3 WARNING', $mensaje);
	}

	/**
	 * Registra un suceso no contemplado que no es critico para la aplicacion
	 */
	function notice($mensaje, $proyecto = null)
	{
		kernel::log()->add_info('modelo_g3 NOTICE', $mensaje);
	}

	/**
	 * Registra un suceso netamente informativo, para una inspección posterior
	 */
	function info($mensaje, $proyecto = null)
	{
		kernel::log()->add_info('modelo_g3 INFO', $mensaje);
	}

	/**
	 * Registra un suceso útil para rastrear problemas o bugs en la aplicación
	 */
	function debug($mensaje, $proyecto = null)
	{
		kernel::log()->add_debug('modelo_g3 DEBUG', $mensaje);
	}

	/**
	 * Dumpea el contenido de una variable al logger
	 */
	function var_dump($variable, $proyecto = null)
	{
		$this->debug($variable);
	}

}