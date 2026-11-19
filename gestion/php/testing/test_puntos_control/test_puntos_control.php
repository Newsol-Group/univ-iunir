<?php

class test_puntos_control extends toba_test
{
	
	/*
	 * Autorizado = 'A'	 Todos los controles que se ejecutaron tuvieron resultados satisfactorios
		Rechazado = 'R'	 Alguno de los controles que se ejecutaron no cumplió lo requerido
		Advertido = 'P'	 Alguno de los controles que se ejecutaron no cumplió con lo requerido, pero estaba configurado como ADVERTENCIA
	 * 
	 * 
	 */
	
	
	static function get_descripcion()
	{
		return "Test de Controles";
	}
	
	function test_falla_a()
	{
		
		$ptos_control_a_validar = array(guarani_punto_de_control::persona_operacion, guarani_punto_de_control::alumno_operacion);
		$param = array(	'alumno'		=> '2296',
						'persona'		=> '19',
						'fecha'			=> guarani_fecha::get_hoy(),
						'plan_version'	=> '1110');	
		try{
			$operacion = '800SIUCUR001';			
			 $validador = static::validador_puntos_de_control($ptos_control_a_validar, $param, $operacion, true);
			 $retorno = $validador->controlar();
			 
//			 echo "$retorno\n";
//			 echo var_export($validador->get_mensajes(),true) . "\n";
			 
			 if ($retorno == guarani_punto_de_control::advertido OR $retorno == guarani_punto_de_control::rechazado) {
				 $mensajes = var_export($validador->get_mensajes(),true);
				 throw new guarani_error($mensajes);
			 }
			$this->fail();
		}catch(guarani_error $e){
		//	echo var_export($e->getMessage(),true);
			toba::logger()->error($e->getMessage());
			$this->pass();
		}
	}
	
	function test_positivo()
	{
		try{
			$this->pass();
		}catch(guarani_error $e){
			$this->fail($e->getMessage());
		}
	}

	static function validador_puntos_de_control($puntos, $parametros, $operacion, $detener_controles_en_rechazo=null )
	{
		$notificador = new notificacion_controles();
		$interfaz = 2;
		$validador_controles = new validador_puntos_de_control($notificador, $puntos, $parametros, $interfaz, $operacion);
		if (!isset($detener_controles_en_rechazo)) {
			// @HACER, cambiar por el metodo del G3W3 DE RECUPERAR PARAMETROS
			$param_estado = guarani::parametros()->get_default('par_detener_ejecucion_en_rechazo');
			$validador_controles->set_detener_en_rechazo($param_estado == 'S');
		} else {
			$validador_controles->set_detener_en_rechazo($detener_controles_en_rechazo);
		}
		return $validador_controles;
	}

    function pre_run()
    {
		parent::pre_run();
		toba::logger()->info('Inicio TEST');
    }
    
    function post_run()
	{
		toba::logger()->guardar();
	}	
	
}



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
