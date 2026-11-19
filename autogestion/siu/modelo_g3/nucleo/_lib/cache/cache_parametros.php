<?php
/**
 * Caché de parámetros. 
 * Se mantiene una estructura con este formato:
 * 
 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/CacheParametros
 * 
 */
class cache_parametros
{
	const cache_parametros = '__PARAMETROS_G3__';
	const parametros = 'parametros';
	const atributos = 'atributos';
	const dependencias = 'dependencias';
	const no_usados_en_contexto = 'no_usados';
	const clave_default = '__default__';
	
	static protected $instancia;
	
	function __construct()
	{
		if (!guarani::cache()->existe(self::cache_parametros)) {
			$this->inicializar();
		}
	}
	
	protected function inicializar()
	{
		// Creación de la estructura inicial
		$base = array(
			self::parametros => array(),
			self::atributos => array(),
			self::dependencias => array(),
		);
		guarani::cache()->guardar(self::cache_parametros, $base);
	}
	
	/**
	 * Retorna una instancia de clase cache_memoria_apc siguiendo el patrón Singleton
	 * 
	 * @return cache_memoria_apc
	 */
	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new cache_parametros();
		}
		return self::$instancia;
	}
	
	/**
	 * Vuelca en pantalla toda la estructura de caché de parámetros
	 */
	function debug()
	{
		$datos = guarani::cache()->buscar(self::cache_parametros);
		ei_arbol($datos);
	}
	
	//--------------------------------------------------------------------------
	//---- Operaciones de limpieza de caché
	//--------------------------------------------------------------------------
	
	/**
	 * Elimina de la caché los valores almacenados para un determinado parámetro,
	 * además de los valores de parámetros que formen parte de las dependencias
	 * del parámetro recibido.
	 * 
	 * @param string $parametro
	 */
	function limpiar_cache_parametro($parametro)
	{
		$datos = guarani::cache()->buscar(self::cache_parametros);
		// Se eliminan los datos en caché del parámetro
		if (isset($datos[self::parametros][$parametro])) {
			unset($datos[self::parametros][$parametro]);
		}
		// Se verifica si el parámetro es maestro de otro
		foreach ($datos[self::dependencias] as $param_dep => $dependencias) {
			foreach ($dependencias as $datos_dependencia) {
				if ($datos_dependencia['parametro_maestro'] == $parametro) {
					// Si el parámetro es maestro de alguna dependencia, se borra 
					// también el parámetro dependiente
					unset($datos[self::parametros][$param_dep]);
				}
			}
		}
		guarani::cache()->guardar(self::cache_parametros, $datos);
	}
	
	/**
	 * Elimina de la caché todos los valores almacenadoss de parámetros
	 */
	function vaciar_cache_parametros()
	{
		$datos = guarani::cache()->buscar(self::cache_parametros);
		// Se eliminan los datos en caché del parámetro
		if (isset($datos[self::parametros])) {
			unset($datos[self::parametros]);
		}
		guarani::cache()->guardar(self::cache_parametros, $datos);
	}
	
	//--------------------------------------------------------------------------
	//---- Manejo de datos relacionados con los parámetros
	//--------------------------------------------------------------------------
	
	/**
	 * Almacena en caché datos (valor, atributos, nivel, posibles conflictos, etc)
	 * para un parámetro dado.
	 * 
	 * @param string $parametro
	 * @param array $datos_param
	 */
	function guardar_datos_parametro($parametro, $datos_param)
	{
		$datos = guarani::cache()->buscar(self::cache_parametros);
		
		if (!isset($datos[self::parametros][$parametro])) {
			$datos[self::parametros][$parametro] = array();
		}
		$clave_param = $this->get_clave_atributos($datos_param['atributos']);
		$datos[self::parametros][$parametro][$clave_param] = $datos_param;
		
		guarani::cache()->guardar(self::cache_parametros, $datos);
	}
	
	/**
	 * Determina si hay datos guardados para un parámetro y un conjunto de atributos dados.
	 * 
	 * @param string $parametro
	 * @param array $atributos
	 * @return boolean
	 */
	function hay_datos_parametro($parametro, $atributos=null)
	{
		$datos = $this->get_datos_valor_parametro($parametro, $atributos);
		return !empty($datos);
	}
	
	/**
	 * Obtiene toda la información guardada en caché (valor, atributos, nivel, 
	 * posibles conflictos, etc) para un parámetro y un conjunto de atributos
	 * 
	 * @param string $parametro
	 * @param array $atributos
	 * @return array
	 */
	function get_datos_valor_parametro($parametro, $atributos=null)
	{
		$datos = guarani::cache()->buscar(self::cache_parametros);
		
		$atributos_descr = $this->get_clave_atributos($atributos);
		if (isset($datos[self::parametros][$parametro][$atributos_descr])) {
			$salida = $datos[self::parametros][$parametro][$atributos_descr];
		} else {
			$salida = array();
		}
		
		return $salida;
	}
	
	/**
	 * Obtiene únicamente desde la caché el valor del parámetro para un conjunto de atributos.
	 * 
	 * @param string $parametro
	 * @param array $atributos
	 * @return valor del parámetro
	 */
	function get_valor_parametro($parametro, $atributos=null)
	{
		$datos = $this->get_datos_valor_parametro($parametro, $atributos);
		if (isset($datos['valor'])) {
			return $datos['valor'];
		} 
		return null;
	}
	
	/**
	 * Determina la clave de almacenamiendo del valor de un parámetro para un
	 * conjunto de atributos.
	 * 
	 * @param array $atributos
	 * @return string
	 */
	protected function get_clave_atributos($atributos=null)
	{
		if (!empty($atributos)) {
			sort($atributos);
			$atributos_descr = implode(' | ', $atributos);
		} else {
			$atributos_descr = self::clave_default;
		}
		return $atributos_descr;
	}
	
	//--------------------------------------------------------------------------
	//---- Manejo de datos relacionados con las operaciones
	//--------------------------------------------------------------------------
	
	/**
	 * Almacena en caché atributos de parámetros de una operación
	 * 
	 * @param string $operacion
	 * @param int $nivel
	 * @param array $atributos
	 */
	function set_atributos_operacion($operacion, $nivel, $atributos)
	{
		$sessid = session_id();
		$datos = guarani::cache()->buscar(self::cache_parametros);
		$datos[self::atributos][$sessid][$operacion][$nivel] = $atributos;
		guarani::cache()->guardar(self::cache_parametros, $datos);
	}
	
	/**
	 * Elimina del almacén de datos (en caso de existir) los atributos establecidos para una operación 
	 * 
	 * @param string $operacion
	 * @param int $nivel En caso de recibir null, elimina todos los atributos de la operación
	 */
	function reset_atributos_operacion($operacion, $nivel=null)
	{
		$sessid = session_id();
		$datos = guarani::cache()->buscar(self::cache_parametros);
		if (!is_null($nivel)) {
			// Se eliminan sólo los atributos de la operación del nivel especificado
			if (isset($datos[self::atributos][$sessid][$operacion][$nivel])) {
				unset($datos[self::atributos][$sessid][$operacion][$nivel]);
				guarani::cache()->guardar(self::cache_parametros, $datos);
			}
		} else {
			// Se eliminan TODOS los atributos de la operación almacenados en caché
			if (isset($datos[self::atributos][$sessid][$operacion])) {
				unset($datos[self::atributos][$sessid][$operacion]);
				guarani::cache()->guardar(self::cache_parametros, $datos);
			}
		}
	}
	
	/**
	 * Obtiene los atributos establecidos para un determinado nivel en una operación 
	 * 
	 * @param string $operacion
	 * @param int $nivel
	 * @return array
	 * @throws guarani_error_params
	 */
	function get_atributos_operacion($operacion, $nivel)
	{
		$sessid = session_id();
		if ($nivel != parametro::nivel_default) {
			$datos = guarani::cache()->buscar(self::cache_parametros);
			if (empty($datos[self::atributos][$sessid][$operacion][$nivel])) {
				$nivel_desc = parametro::get_descripcion_nivel($nivel);
				throw guarani::error_params("Los atributos para el nivel $nivel_desc no han sido especificados");
			} else {
				return $datos[self::atributos][$sessid][$operacion][$nivel];
			}
		}
		return null;
	}
	
	//---- Dependencias --------------------------------------------------------
	
	/**
	 * Almacena en caché definiciones de dependencias entre parámetros.
	 * 
	 * @param string $parametro
	 * @param array $dependencias
	 */
	function set_dependencias_parametros($parametro, $dependencias)
	{
		$datos = guarani::cache()->buscar(self::cache_parametros);
		$datos[self::dependencias][$parametro] = $dependencias;
		guarani::cache()->guardar(self::cache_parametros, $datos);
	}
	
	/**
	 * Obtiene el conjunto de dependencias entre parámetros.
	 * 
	 * @param string $parametro
	 * @return array
	 */
	function get_dependencias_parametro($parametro)
	{
		$datos = guarani::cache()->buscar(self::cache_parametros);
		if (isset($datos[self::dependencias][$parametro])) {
			return $datos[self::dependencias][$parametro];
		} 
		return array();
	}
	
	/**
	 * Determina si existen dependencias definidas para un parámetro dado.
	 * 
	 * @param string $parametro
	 * @return boolean
	 */
	function existen_dependencias_parametro($parametro)
	{
		$datos = $this->get_dependencias_parametro($parametro);
		return (!empty($datos));
	}
	
	//--------------------------------------------------------------------------
	//---- Parámetros no usados en el contexto de la operación
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene todos los parámetros que han sido marcados como no utilizados en el
	 * contexto actual de una operación dada.
	 * 
	 * @param string $operacion
	 * @return array
	 */
	function get_parametros_no_usados($operacion)
	{
		$sessid = session_id();
		$datos = guarani::cache()->buscar(self::cache_parametros);
		if (isset($datos[self::no_usados_en_contexto][$sessid][$operacion])) {
			return $datos[self::no_usados_en_contexto][$sessid][$operacion];
		}
		return array();
	}
	
	/**
	 * Almacena en un espacio particular de caché que un parámetro no es utilizado
	 * en el contexto actual de una operación.
	 * 
	 * @param string $operacion
	 * @param string $parametro
	 */
	function set_parametro_no_usado($operacion, $parametro)
	{
		$sessid = session_id();
		$datos = guarani::cache()->buscar(self::cache_parametros);
		if (!isset($datos[self::no_usados_en_contexto][$sessid][$operacion])) {
			$datos[self::no_usados_en_contexto][$sessid][$operacion] = array();
		}
		$datos[self::no_usados_en_contexto][$sessid][$operacion][] = $parametro;
		guarani::cache()->guardar(self::cache_parametros, $datos);
	}
	
	/**
	 * Elimina de la caché los parámetros configurados como no usados en el contextos
	 * de una operación.
	 * 
	 * @param string $operacion
	 */
	function reset_parametros_no_usados($operacion)
	{
		$sessid = session_id();
		$datos = guarani::cache()->buscar(self::cache_parametros);
		unset($datos[self::no_usados_en_contexto][$sessid][$operacion]);
		guarani::cache()->guardar(self::cache_parametros, $datos);
	}
}
?>