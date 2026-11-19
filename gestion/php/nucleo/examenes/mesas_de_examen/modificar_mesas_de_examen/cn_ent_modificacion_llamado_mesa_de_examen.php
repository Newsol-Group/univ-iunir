<?php
/**
 *
 * @package Examenes
 * @subpackage Modificar Mesas de Examen
 */
class cn_ent_modificacion_llamado_mesa_de_examen extends cn_ent_llamado_mesa_de_examen
{
	protected $hay_notificaciones = false;
	protected $notificaciones;
	
	/**
	 * Se redefine el manejo de Notificaciones, para que los msjes de Alerta sobre los datos del Llamado
	 * se emitan correctamente junto con la Navegación a la operación de Impresión de actas, que se da al 
	 * modificar la Mesa de Examen. En lugar de ir agregando las notificaciones con la clase de Toba, se
	 * acumulan en una varible local.
	 * 
	 */
	function set_notificacion($descripcion_corta, $parms=null, $tipo=comunes::error_advertencia)
	{
		$mensaje = guarani_mensajes::get_msg_info($descripcion_corta, $parms);
		//Si no está el mensaje definido en la base
		if (!$mensaje) {
			$mensaje = array('mensaje' => $descripcion_corta);
		}
		//Si me definió un tratamiento para el mensaje
		if ($tipo) {
			$mensaje['tipo'] = $tipo;
		}
		$this->hay_notificaciones = true;
		$this->notificaciones[] = $mensaje;
	}

	function get_mensajes_notificaciones($separador="\\n")	
	{
		$notif = implode($separador, aplanar_matriz($this->notificaciones, 'mensaje'));
		toba::logger()->error($notif);
		return $notif;
	}

	function get_notificaciones()
	{
		return $this->notificaciones;
	}

	function hay_notificaciones()
	{
		return $this->hay_notificaciones;	
	}
	
	function resetear_notificaciones()
	{
		$this->hay_notificaciones = false;
		unset($this->notificaciones);
	}
    
    function hay_cambios()
    {
        //Tomo el valor de la fecha cuando carge la relacion.
        // y comparo con la actual
        $llamado = $this->get_datos_llamado();        
        $original = current($this->tabla('llamados_mesa')->get_cambios())['original'];
        if ($llamado['fecha'] != $original['fecha'] ||
            $this->tabla('docentes')->hay_cambios()) {
            return true;
        }
        return false;
    }
}
