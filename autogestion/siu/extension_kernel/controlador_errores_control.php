<?php

namespace siu\extension_kernel;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\errores\error_guarani;
use siu\errores\error_guarani_control;
use siu\extension_kernel\controlador_g3w2;

abstract class controlador_errores_control extends controlador_g3w2
{
	/**
	 * Ejecuta una función del controlador que impacta el modelo. Estas funciones
	 * se llaman modelo__*.
	 * Como efecto colateral si hay error deja los mensajes en $this->mensajes
	 * @param string $nombre el nombre de la función a ejecutar
	 * @param mixed $params 
	 * @return boolean si hubo o no error
	 */
	protected function exec_fn_modelo($nombre, $params = array())
	{
		$hubo_error = false;
		$mensajes = array();
		$params = (is_array($params)) ? $params : array($params);
		$resultado = array();
		try {
			$resultado = call_user_func_array(array($this->modelo(), $nombre), $params);
			$mensajes = $resultado['advertencias'] ?? [];
		} catch (error_guarani_control $error_control) {
			$hubo_error = true;
			$mensajes = $error_control->get_mensajes();
			kernel::log()->add_error($error_control);
		} catch (error_guarani $e) {
			$hubo_error = true;
			$mensajes[] = guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans($e->get_id_error_generico()));
			kernel::log()->add_error($e);
		}

		if ($hubo_error) {
			kernel::log()->add_debug('ERROR ejecutando fn modelo', $nombre);
		} else {
			kernel::log()->add_debug('EXITO ejecutando fn modelo', $nombre);
		}
		
		return array($hubo_error, $resultado, $mensajes);
	}
	
	protected function exec_control($nombre, $params = array())
	{
		$hubo_error = false;
		$mensajes = array();
		$params = (is_array($params)) ? $params : array($params);

		try {
			$mensajes = call_user_func_array(array($this->modelo(), $nombre), $params);
		} catch (error_guarani_control $error_control) {
			$hubo_error = true;
			$mensajes = $error_control->get_mensajes();
			kernel::log()->add_error($error_control);
		} catch (error_guarani $e) {
			$hubo_error = true;
			$mensajes[] = guarani::crear_mensaje(guarani::control_error, kernel::traductor()->trans($e->get_id_error_generico()));
			kernel::log()->add_error($e);
		}

		if ($hubo_error) {
			kernel::log()->add_debug('ERROR ejecutando fn modelo', $nombre);
		} else {
			kernel::log()->add_debug('EXITO ejecutando fn modelo', $nombre);
		}
		
		return array($hubo_error, $mensajes);
	}
	
	/**
	 * Acumula mensajes en var mensajes en vez de pisarlos. $this->get_var('mensajes')
	 * 
	 * 
	 * @param type $mensajes
	 */
	
	protected function acum_mensajes($mensajes)
	{
		// TODO Ver los mensajes de exito de inscripcion a cursada que también pasan por acá y son solo un string
		if ($this->hay_var('mensajes')) {
			$this->add_var('mensajes', $this->get_var('mensajes').$mensajes);
			//$this->add_var('mensajes', array_merge($this->get_var('mensajes'), $mensajes));
		} else {
			$this->add_var('mensajes', $mensajes);			
		}
	}
	
	protected function mens($v){
        return array(
            array(
				guarani::mensaje_cuerpo  => '[E]'.$v,
				guarani::mensaje_tipo	 => guarani::control_error
			),
            array(
				guarani::mensaje_cuerpo  => '[W]'.$v,
				guarani::mensaje_tipo	 => guarani::control_advertencia
			),
            array(
				guarani::mensaje_cuerpo  => '[I]'.$v,
				guarani::mensaje_tipo	 => guarani::control_info
			),
        );
    }
}