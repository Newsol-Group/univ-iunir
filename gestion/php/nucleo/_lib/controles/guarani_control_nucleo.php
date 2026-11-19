<?php
/**
 * Ejecución de controles
 *
 *
 * @package Centrales
 * @subpackage ArquitecturaBase
 */
class guarani_control_nucleo
{
	protected $definicion;
	protected $parametros;
	protected $parametros_contexto;
	protected $modo;
	protected $regla;
	protected $error;
	protected $operador_not=false; //false -> desactivado
	protected $mensaje;
	protected $registro;
	protected $advertencia_como_estricto=false;
	protected $registrar_error=false;
	
	// Modos en que puede configurarse un control/requisito en una operación del sistema
	const modo_estricto		= 'ESTRICTO';		//ESTRICTO  Indica que si el control falla la ejecución de la operación no puede continuar.
	const modo_advertencia	= 'ADVERTENCIA';	//ADVERTENCIA  En el caso de que falle el control el usuario podrá parar o continuar con la operación.
												//En algunos casos el continuar con el proceso podría implicar cambios en la lógica de la operación donde se ejecute el control.
	const modo_mensaje		= 'MENSAJE';		//MENSAJE  Permite enviar un mensaje al usuario en el caso de que el control falle.
												//Esto no cambia la lógica de la operación. El usuario solo continúa luego de ver el mensaje correrspondiente al control que falló y estaba configurado de este modo.
	const modo_default		= self::modo_mensaje;


	function __construct($datos_control, $parametros_punto_control=null)
	{
		$this->regla = guarani::regla($datos_control['regla']);

		$this->set_definicion($datos_control);
		$this->set_modo($datos_control['actua_como']);
		$this->set_operador_not($datos_control['operador_not']);
		if (isset($datos_control['entidad'])) {
			$datos_entidad = g3entidad::get_entidad_id_nombre($datos_control['entidad']);
			$parametros_punto_control[$datos_entidad['nombre']] = $datos_entidad['id'];
		}
		$this->set_parametros($datos_control['parametros']);
		if (isset($parametros_punto_control)) {
			$this->set_parametros_contexto($parametros_punto_control);
		}
	}
		
	function set_definicion($datos_control)
	{
		$this->definicion['tipo']	= $datos_control['tipo'];
		$this->definicion['estado'] = $datos_control['estado'];
		$this->definicion['nombre'] = $datos_control['nombre'];
		if ($this->definicion['tipo'] == requisito::comp_requisito){
			$this->definicion['id']		= $datos_control['requisito'];
		}else {
			$this->definicion['id']		= $datos_control['entidad'];
		}
	}

	function set_parametros($parametros='')
	{
		$this->parametros = $parametros;
		$this->regla->set_parametros($this->parametros);
	}

	function set_parametros_contexto($parametros_contexto=array())
	{
		if ($this->es_requisito()) {
			$parametros_contexto['requisito'] = $this->get_id_elemento();
		} else {
			$parametros_contexto['entidad'] = $this->get_id_elemento();
			$parametros_contexto['estado'] = $this->definicion['estado'];
		}
		$this->parametros_contexto = $parametros_contexto;
		$this->regla->set_parametros_contexto($this->parametros_contexto);
	}

	function set_mensaje($requisito)
	{
		$this->mensaje['tipo'] = $this->get_tipo_error();
		$this->mensaje['requisito'] = $requisito;
		$mensajes = $this->regla->get_mensaje_validacion();
		foreach ($mensajes as $interfaz => $mensaje) {
			if($this->operador_not) {
				$this->mensaje['mensaje'][$interfaz]['mensaje'] = $mensaje['negado'];
			} else {
				$this->mensaje['mensaje'][$interfaz]['mensaje'] = $mensaje['mensaje'];
			}
		}
	}
	
	function set_registro_error($datos)
	{
		$this->registro = $datos;
	}
	
	function set_modo($modo)
	{
		$this->modo = $modo;
	}

	function set_operador_not($estado)
	{
		// 1 true , 0 false
		$this->operador_not = $estado == 1;
		$this->regla->set_operador_not($estado == 1);
	}
	
	/**
	 * Define si se debe registrar el error (util para controles en procesos)
	 * 
	 * @param type $valor 
	 */
	function set_registrar_error($valor=true)
	{
		$this->registrar_error = $valor;
	}
	
	/**
	 * Define que control que actua como advertencia entonces debe tenerse en cuenta como estricto
	 * 
	 * @param type $valor 
	 */
	function set_advertencia_como_estricto($valor=true)
	{
		//@TODO VER SI ESTO PUEDE IR A NIVEL DEL PUNTO DE CONTROL
		$this->advertencia_como_estricto = $valor;
	}

	function co()
	{
		return guarani::co('co_puntos_de_control');
	}

	function get_tipo()
	{
		return $this->definicion['tipo'];
	}

	function es_requisito()
	{
		return ($this->get_tipo() == requisito::comp_requisito);
	}

	function es_entidad()
	{
		return ($this->get_tipo() == requisito::comp_entidad);
	}

	function get_id_elemento()
	{
		return $this->definicion['id'];
	}

	function get_clase()
	{
		return $this->regla->get_clase();
	}

	function get_modo()
	{
		return $this->modo;
	}

	function get_mensaje($parte=null)
	{
		return $parte ? $this->mensaje[$parte] : $this->mensaje ;
	}
				
	function get_registro_error()
	{
		return $this->registro;
	}

	function get_nombre()
	{
		return $this->definicion['nombre'];
	}
	
	private function get_tipo_error()
	{
		switch ($this->get_modo()) {
			case guarani_control::modo_estricto: 
				return comunes::error_critico;
			case guarani_control::modo_advertencia: 
				if ($this->advertencia_como_estricto) {
					return comunes::error_critico;
				} else {
					// Se envía un mensaje al usuario y se actúa de acuerdo a la respuesta (continuar o no)
					//Hasta que se implemente se presupone que siempre acepta seguir
					return comunes::error_advertencia;
				}
			case guarani_control::modo_mensaje: 
				return comunes::error_informacion;
		}
	}
	
	/**
	 * Setea el mensaje y el registro de error si el control es escricto o advertencia y se debe tener en cuenta como estricto
	 * 
	 */
	function set_mensaje_y_registro_error()
	{
		$this->set_mensaje($this->definicion['id']);
		if($this->registrar_error)	{
			// solo registro los controles estrictos y las advertencias que actuan como estricto
			if ($this->get_modo() == guarani_control::modo_estricto || ($this->get_modo() == guarani_control::modo_advertencia && $this->advertencia_como_estricto)) {
				$datos['regla']						= $this->regla->get_id();
				$datos['parametros']				= $this->parametros;
				$datos['advertencia_como_estricto'] = $this->advertencia_como_estricto;
				$datos['operador_not']				= $this->operador_not;
				$datos['tipo']						= $this->definicion['tipo']; 
				$datos['actua_como']				= $this->get_modo();
				
				if ($this->definicion['tipo'] == requisito::comp_requisito) {
					$datos['requisito']				= $this->definicion['id'];
				} else {
					$datos['entidad']				= $this->definicion['id'];
					$datos['estado']				= $this->definicion['estado'];
				}
			$this->set_registro_error($datos);
			}

			
		}
	}

	function validar()
	{
		toba::logger()->debug('Validando Control: '.$this->get_nombre());
		$resultado = $this->regla->validar_regla();
		if (!$resultado) {
			$this->set_mensaje_y_registro_error();
		}
		toba::logger()->debug('Resultado Control: '. $this->get_nombre()." = ". ($resultado ? 'paso' : 'no paso'));
		return $resultado;
	}
	
}
?>