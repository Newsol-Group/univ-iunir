<?php

/**
 * Clase que mantiene notificaciones al usuario a mostrarse en el página actual
 * 
 * @package SalidaGrafica
 * @jsdoc notificacion notificacion 
 */
class notificacion_controles
{
	private $mensajes = array();
	
	function agregar($mensaje, $nivel='error',$extras=null)
	{
		if (! is_null($mensaje) && trim($mensaje) != '') {
			$this->mensajes[] = array($mensaje, $nivel, $extras);
		}
	}
	
	/**
	 * Agrega un mensaje de error para mostrar al usuario
	 * @param string $mensaje Mensaje completo a mostrar
	 */
	function error($mensaje, $extras=null)
	{
		$this->agregar($mensaje, 'error', $extras);
	}
	
	/**
	 * Agrega un mensaje de advertencia para mostrar al usuario
	 * @param string $mensaje Mensaje completo a mostrar
	 */
	function warning($mensaje, $extras=null)
	{
		$this->agregar($mensaje, 'warning', $extras);
	}	
	
	/**
	 * Agrega un mensaje informativo para mostrar al usuario
	 * @param string $mensaje Mensaje completo a mostrar
	 */	
	function info($mensaje, $extras=null)
	{
		$this->agregar($mensaje, 'info', $extras);
	}

}
?>