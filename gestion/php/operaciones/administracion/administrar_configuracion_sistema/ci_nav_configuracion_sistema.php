<?php
/**
 *
 * @package		Administración
 * @subpackage 	Administrar Configuración del Sistema
 * 
 */
class ci_nav_configuracion_sistema extends ci_navegacion
{
	
	function evt__guardar()
	{
		$this->set_no_volver_al_guardar();
		parent::evt__guardar();
	}
	
   /**
	*	Permite recuperar el mensaje toba actualmente asociado al eof del cuadro.
	*/	
	function get_msg_eof($dep=null)
	{
		return guarani_mensajes::get('800_sin_dato', array('definido', 'Plataformas de Educación Virtual'));
	}
	
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		
		// Si la plataforma tiene datos asociados desactivo el botón eliminar
		if ($this->cn()->entidad()->tiene_datos_asociados()) {
			$pantalla->evento('eliminar')->desactivar();
			$pantalla->evento('eliminar')->set_msg_ayuda(guarani_mensajes::get('800_plataforma_posee_elementos_asociados'));
		}
	}
	
}