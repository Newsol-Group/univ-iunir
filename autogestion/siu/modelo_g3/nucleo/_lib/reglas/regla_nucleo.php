<?php
/**
 * Catalogo de reglas
 *
 * @package Propuestas
 * @subpackage Reglas
 */


class regla_nucleo
{
	// Tipos de Reglas
	const tipo_cumplimiento_modulos = 1;
	const tipo_condiciones_correlativas = 2;
	const tipo_requisitos_correlativas = 2;
	const tipo_condiciones_certificados = 3;
	const tipo_requisitos_certificados = 3;
	const tipo_procesos = 3;

	// Reglas, formas de cumplimiento.
	const por_puntaje_aprobadas = 100;
	const por_puntaje_cursadas = 114;
	const porc_aprobadas = 108;
	const correlativas_cursada = 307;
	const correlativas_aprobacion = 308;
	
	// Parametros
	const separador_parametros = '|';
	
	// Tipos de Parametros
	const param_tipo_fecha 		= 'D';
	const param_tipo_entero 	= 'E';
	const param_tipo_decimal 	= 'F';
	const param_tipo_cadena 	= 'S';
	const param_tipo_hora 		= 'T';
	const param_tipo_fecha_hora = 'X';

	// Regla Default
	const regla_default = 101;
	const parametros_default = null;
	const regla_actividad_aprobada = 1;
	const regla_actividad_cursada = 2;
	
	protected $mensaje;
	protected $mensajes_validacion;
	protected $parametros = array();
	protected $parametros_contexto = array();
	protected $regla_id;
	protected $info;
	protected $operador_not=false; //false -> deshabilitado

	// Cache con info acumulada de la regla.
	protected $cache;
		
	function __construct($regla_id=null, $regla_info=null)
	{
		if (isset($regla_id)) {
			$this->regla_id = $regla_id;
			if (isset($regla_info)) {
				$this->info = $regla_info;
			} else {
				$this->info = toba::consulta_php('co_reglas',true)->get_info_regla($this->regla_id);
			}
		}
	}
	
	function mk_obs($resultado) 
	{
		return "<b>Regla</b>: ".$this->info['nombre'].".<br/><b>Requisitos</b>: ".$this->get_mensaje()."<br/><b>Resultado</b>: ".$resultado;
	}

	function set_parametros($parametros) 
	{
		$this->parametros = static::separar_parametros($parametros);
		$params = toba::consulta_php('co_reglas',true)->get_regla_params_def($this->regla_id);
		foreach($params as $k => $param) {
			if(!static::validar_parametro($param, $this->parametros[$k])) {
				throw new guarani_error('El parámetro '.$param['etiqueta'].' con valor "'.$this->parametros[$k].'" no es válido para esta regla.');
				$this->parametros = array();
				break;
			}
			$this->parametros[$param['parametro']] = $this->parametros[$k];
		}
		if($this->parametros || $parametros == '') {
			$this->mensaje = toba::consulta_php('co_reglas',true)->get_mensaje_usuario($this->regla_id, $parametros);
			$this->set_parametros_mensaje_validacion($this->parametros);
		}
	}

	function set_parametros_asociativos($parametros=array())
	{
		$params = toba::consulta_php('co_reglas',true)->get_regla_params_def($this->regla_id);
		foreach($params as $k => $param) {
			if (! isset($parametros[$param['parametro']])) {
				throw new guarani_error('No se encuentra el parámetro: '.$param['etiqueta'].'.');
				$this->parametros = array();
				break;
			}
			$this->parametros[$k] = $parametros[$param['parametro']];
			$this->parametros[$param['parametro']] = $parametros[$param['parametro']];
			if(!static::validar_parametro($param, $this->parametros[$k])) {
				throw new guarani_error('El parámetro '.$param['etiqueta'].' con valor "'.$this->parametros[$k].'" no es válido para esta regla.');
				$this->parametros = array();
				break;
			}
		}
		if($this->parametros || $parametros == array()) {
			$this->mensaje = toba::consulta_php('co_reglas',true)->get_mensaje_usuario($this->regla_id, static::unir_parametros($this->parametros));
			$this->set_parametros_mensaje_validacion($this->parametros);
		}
	}

	function set_parametros_contexto($parametros_contexto=array())
	{
		$params = toba::consulta_php('co_reglas',true)->get_regla_params_contexto_def($this->regla_id);
		foreach($params as $k => $param) {
			$param['etiqueta'] = $param['parametro'];
			if (! isset($parametros_contexto[$param['parametro']])) {
				throw new guarani_error('No se encuentra el parámetro de contexto: '.$param['etiqueta'].'.');
				$this->parametros_contexto = array();
				break;
			}
			$this->parametros_contexto[$param['parametro']] = $parametros_contexto[$param['parametro']];
			if(!static::validar_parametro($param, $this->parametros_contexto[$param['parametro']])) {
				throw new guarani_error('El parámetro '.$param['etiqueta'].' con valor "'.$this->parametros_contexto[$param['parametro']].'" no es válido.');
				$this->parametros_contexto = array();
				break;
			}
		}
	}
	
	function set_mensaje($mensaje)
	{
		$this->mensaje = $mensaje;
	}

	function set_operador_not($estado)
	{
		$this->operador_not = ($estado == 1);
	}

	function set_parametros_mensaje($parametros)
	{
		if (! is_array($parametros)) {
			$parametros = array($parametros);
		}
		foreach ($parametros as $key => $param) {
			$this->mensaje = str_replace('%'.($key + 1).'%', $param, $this->mensaje);
		}
	}

	function set_parametros_mensaje_validacion($parametros, $son_parametros_extra=false)
	{
		if (! is_array($parametros)) {
			$parametros = array($parametros);
		}
		if (! isset($this->mensajes_validacion)) {
			$this->mensajes_validacion = $this->info['mensajes_validacion'];
		}
		foreach($this->mensajes_validacion as $interfaz => $mensaje) {
			if (!$son_parametros_extra) {
				// Los parámetros propios del mensaje estan expresados con %nro_parametro%
				foreach ($parametros as $key => $param) {
					if (is_numeric($key)) {
						if ($this->operador_not) {
							$this->mensajes_validacion[$interfaz]['negado'] = str_replace('%'.($key + 1).'%', $param, $this->mensajes_validacion[$interfaz]['negado']);
						} else {
							$this->mensajes_validacion[$interfaz]['mensaje'] = str_replace('%'.($key + 1).'%', $param, $this->mensajes_validacion[$interfaz]['mensaje']);
						}
					}
				}
			} else {
				// Los parámetros extras que se agregan al mensaje estan expresados con $nro_parametro$
				foreach ($parametros as $key => $param) {
					if ($this->operador_not) {
						$this->mensajes_validacion[$interfaz]['negado'] = str_replace('$'.($key + 1).'$', $param, $this->mensajes_validacion[$interfaz]['negado']);						
					} else {
						$this->mensajes_validacion[$interfaz]['mensaje'] = str_replace('$'.($key + 1).'$', $param, $this->mensajes_validacion[$interfaz]['mensaje']);						
					}
				}
			}
		}
	}

	function set_cache($clave, $valor)
	{
		$this->cache[$clave] = $valor;
	}

	function get_clase()
	{
		return $this->info['php_clase'];
	}

	function get_mensaje() 
	{
		return isset($this->mensaje) ? $this->mensaje : $this->info['mensaje_usuario'];
	}
	
	function get_mensaje_validacion()
	{
		if (isset($this->mensajes_validacion)) {
			return $this->mensajes_validacion;
		}
		foreach ($this->info['mensajes_validacion'] as $interfaz => $mensaje_validacion) {
			if (! $this->operador_not) {
				$this->mensajes_validacion[$interfaz]['mensaje'] = $this->info['mensajes_validacion'][$interfaz]['mensaje'];
			} else {
				$this->mensajes_validacion[$interfaz]['negado'] = $this->info['mensajes_validacion'][$interfaz]['negado'];
			}
		}
		return $this->mensajes_validacion;
	}
	
	function get_mensaje_validacion_interfaz($interfaz)
	{
		if (isset($this->mensajes_validacion[$interfaz]['mensaje'])) {
			if (! $this->operador_not) {
				return  $this->mensajes_validacion[$interfaz]['mensaje'];
			} else {
				return  $this->mensajes_validacion[$interfaz]['negado'];
			}
		} else {
			if (! $this->operador_not) {
				return  $this->info['mensajes_validacion'][$interfaz]['mensaje'];
			} else {
				return  $this->info['mensajes_validacion'][$interfaz]['negado'];
			}
		}
	}
	
	function get_id()
	{
		return $this->regla_id;
	}

	function get_parametros() 
	{
		return $this->parametros;
	}

	function get_parametros_contexto()
	{
		return $this->parametros_contexto;
	}
	
	/* 
		Se verifica si existe el parámetro en el array de parametros de contexto.
	*/
	function existe_parametro($param) 
	{
		if (isset($this->parametros[$param]) || (isset($this->parametros_contexto[$param]))) {
			// Existe el parámetro en alguno de los arrays.
			return true;
		}	
		// No existe el parametro en ninguno de los dos arrays.
		return false;
	}

	function get_parametro($param) 
	{
		if (isset($this->parametros[$param])) {
			return $this->parametros[$param];
		}
		if (isset($this->parametros_contexto[$param])) {
			return $this->parametros_contexto[$param];
		}	
		throw guarani::error("No se encuentra el parámetro '$param'.");
	}

	function get_cache($clave)
	{
		if (! isset($this->cache[$clave])) {
			throw new guarani_error("No se encuentra valor en cache para la clave '$clave'.");
		}
		return $this->cache[$clave];
	}

	static function separar_parametros($parametros='')
	{
		return explode(self::separador_parametros, $parametros);
	}

	static function unir_parametros($parametros=array())
	{
		foreach ($parametros as $key => $param) {
			if (is_numeric($key)) {
				$params[] = $param;
			}
		}
		return implode(self::separador_parametros, $params);
	}

	static function validar_parametro($param, $valor)
	{	
		$valido = true;
		switch($param['tipo_de_dato']) {
			case self::param_tipo_fecha: //"Fecha";"yyyy-mm-dd";"Date"
				if (! guarani_validar::es_fecha($valor)) {
					throw guarani::error('El valor del parámetro <B>'.$param['etiqueta'].'</B> debe ser una fecha válida (AAAA-MM-DD).');
					$valido = false;
				}
				break;
			case self::param_tipo_entero: //"Entero";"##########";"Integer - Bigint - smallint"
				if (! guarani_validar::es_entero($valor)) {
					throw guarani::error('El valor del parámetro <B>'.$param['etiqueta'].'</B> debe ser un entero.');
					$valido = false;
				}	
				break;
			case self::param_tipo_decimal:	//"Decimal - Punto Flotante - Real";"#########.####";"Real - Float - Decimal"
				if (! guarani_validar::es_decimal($valor)) {
					throw guarani::error('El valor ('.$valor.') del parámetro <B>'.$param['etiqueta'].'</B> debe ser un entero o un decimal.');
					$valido = false;
				}	
				break;
			case self::param_tipo_cadena: //"Cadena de Caracteres";"XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";"Char - Varchar"
			
				break;
			case self::param_tipo_hora: //"Hora";"hh:mm";"Time"
				if (! guarani_validar::es_hora_minutos($valor)) {
					throw guarani::error('El valor del parámetro <B>'.$param['etiqueta'].'</B> debe ser una hora válida (hh:mm:ss).');
					$valido = false;
				}	
				break;
			case self::param_tipo_fecha_hora: //"Fecha y Hora";"yyyy-mm-dd hh:mm";"Datetime"
				if (! guarani_validar::es_fecha_hora_minutos($valor)) {
					throw guarani::error('El valor del parámetro <B>'.$param['etiqueta'].'</B> debe ser una fecha y hora válida (DD/MM/YYYY hh:mm:ss).');
					$valido = false;
				}	
				break;
		}
		
		if ($valido && isset($param['valor_desde'])) {
			if ($valor < $param['valor_desde']) {
				throw guarani::error('El valor del parámetro <B>'.$param['etiqueta'].'</B> debe ser mayor o igual a '.$param['valor_desde'].'.');
				$valido = false;
			}
		}
		if ($valido && isset($param['valor_hasta'])) {
			if ($valor > $param['valor_hasta']) {
				throw guarani::error('El valor del parámetro <B>'.$param['etiqueta'].'</B> debe ser menor o igual a '.$param['valor_hasta'].'.');
				$valido = false;
			}
		}

		return $valido;
	}

	function validar_regla()
	{
		return ($this->validar() && $this->validar_particular());
	}

	function validar()
	{
		return true;
	}

	function validar_particular()
	{
		return true;
	}
	
	/**
	 * Valida el cumplimiento de una regla en un caso de test en particular.
	 * En las reglas que evalúan requisitos de personas o alumnos (que no aplican para casos de test) el resultado será VERDADERO.
	 *
	 * @param int $caso_test código del caso de test.
	 * @return boolean
	 */
	function validar_caso_test($caso_test)
	{
		return true;
	}

	/**
	 * Valida el cumplimiento de una regla en base a los parámetros que recibe, sin realizar consultas SQL a la BD.
	 * Sólo es útil para cumplimiento de módulos. En las reglas que evalúan cumplimiento de actividades o requisitos el resultado será VERDADERO.
	 * 
	 * @param array $componentes_modulo conjunto de componentes del módulo a evaluar.
	 * @param array $historia_academica conjunto de actividades aprobadas.
	 * @param array $regularidades conjunto de actividades cursadas.
	 * @param array $aprobaciones_por_resolucion conjunto de módulos aprobados por resolución (parcial o totalmente).
	 * @return boolean
	 */
	function validar_cache($componentes_modulo, $historia_academica, $regularidades, $aprobaciones_por_resolucion)
	{
		return true;
	}
	
	function validar_con_config_ha($excluidas)
	{
		return $this->validar();
	}

	/**
	 *  Retorna el resultado teniendo en cuenta el operador_not seteado en la configuracion de requisito y el resultado de la regla.
	 * 
	 * @param type $resultado
	 * @return type 
	 */
	function resultado_final($resultado)
	{
		if($this->operador_not) 
			return !$resultado;
		else
			return $resultado;
	}
}

?>