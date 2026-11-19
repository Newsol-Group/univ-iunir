<?php
class ci_edi_reglas extends guarani_pers_ci
{
	protected $interfaz;
	protected $regla;
	protected $s__datos_temp;
	
	function ini()
	{
		parent::ini();
		toba_ef_combo::set_maximo_descripcion(100);
	}

	/**
	 * Retorna un array compuesto por los parámetros de contexto de la regla
	 * pasada como parámetro junto con los parámetros propios de la misma.
	 */
	function ajax__get_parametros($parametros, toba_ajax_respuesta $respuesta)
	{
		$regla = $parametros['regla'];
		$parametros_contexto = toba::consulta_php('co_reglas')->get_regla_params_contexto_def($regla);
		$parametros_regla 	 = toba::consulta_php('co_reglas')->get_regla_params_def($regla);
		$respuesta->set(array_merge($parametros_contexto, $parametros_regla));
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- form -------------------------------------------------------------------------
	
	function conf__form(form $form)
	{
		if (isset($this->s__datos_temp)) {
			$form->set_datos($this->s__datos_temp);
		}
	}
	
	function validar_clase_php($clase_php)
	{
		if (!$clase_php) {
			$mensaje = "La regla $valor no tiene clase php asociada";
			throw guarani::error($mensaje);
		} elseif (!guarani_autoload::existe_clase($clase_php) && !guarani_autoload_clases_nuevas::existe_clase($clase_php)) {
			$mensaje = "Se debe agregar la clase $clase_php en guarani_autoload";
			throw guarani::error($mensaje);
		}
	}
	
	function evt__form__modificacion($datos)
	{
		$this->s__datos_temp = $datos;
		$this->interfaz = $datos['interfaz'];
		$this->negar = $datos['negar'];
		unset($datos['interfaz']);
		unset($datos['negar']);
		$parametros_contexto_valores = array_slice($datos, 0, 25);
		$parametros_propios_valores  = array_slice($datos, 26, 15);
		
		$parametros_contexto = array();
		$parametros_propios  = array();
		
		foreach ($parametros_contexto_valores as $clave => $valor) {
			if ($clave == 'regla') {
				$datos_regla = toba::consulta_php('co_reglas')->get_info_regla($valor);
				$this->validar_clase_php($datos_regla['php_clase']);
				$this->regla = guarani::regla($valor);
			} elseif (!is_null($valor)) {
				$parametros_contexto[$clave] = $valor;
			}
		}

		foreach ($parametros_propios_valores as $clave => $valor) {
			if (!is_null($valor)) {
				$parametros_propios[$clave] = $valor;
			}
		}

		if (!empty($parametros_contexto)) {
			$this->regla->set_parametros_contexto($parametros_contexto);
		}

		if (!empty($parametros_propios)) {
			$this->regla->set_parametros_asociativos($parametros_propios);
		}
	}

	//---- form_resultado ---------------------------------------------------------------
	
	function conf__form_resultado(toba_ei_formulario $form)
	{
		if ($this->regla) {
			$this->regla->set_operador_not($this->negar);
			$resultado = $this->regla->validar();
			$mensaje_validacion = $this->regla->get_mensaje_validacion_interfaz($this->interfaz);
			$datos['resultado'] = $resultado ? 'Verdadero' : 'Falso';
			$resultado ? $form->desactivar_efs(array('mensaje_validacion')) : $datos['mensaje_validacion'] = $mensaje_validacion;
			$form->set_datos($datos);
		}
	}
	
}
?>