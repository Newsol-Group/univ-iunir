<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Acciones
 */
class ci_nav_acciones extends ci_navegacion 
{
	
	/**
	 * Se redefine la configuración de la pantalla 'edicion'.
	 *
	 */
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		if ($this->pantalla('edicion')->existe_evento('eliminar')) {
			if ($this->cn()->entidad_es_nueva()) {
				$pantalla->eliminar_evento('eliminar');
			} else {
				$msj = guarani_mensajes::get('800PRO_eliminar_accion');
				$this->evento('eliminar')->set_msg_confirmacion($msj);
			}
		}
		$this->hay_cambios = $this->cn()->entidad()->dep('datos')->hay_cambios();
	}

}

?>