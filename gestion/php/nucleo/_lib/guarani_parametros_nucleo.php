<?php
/**
 * Manejo de parámetros del sistema
 *
 * Se invoca a través de guarani::parametros() 
 *
 * Para obtener el valor de un parámetro, independientemente de su nivel, se 
 * provee la función get_valor. Se proveen también funciones más específicas.
 *
 * @package		Administración
 * @subpackage	Parámetros
 * 
 */
class guarani_parametros_nucleo
{
	static protected $instancia;

	const tiempo_cache_param_reglas = 30; // en segundos
	
	protected $parametros;
	protected $co;

	protected function __construct()
	{
		$this->parametros = array();
		$this->co = toba::consulta_php('co_parametros');
	}

	
	/**
	 * Retorna una instancia de clase guarani_parametros siguiendo el patrón Singleton
	 * 
	 * @return guarani_parametros
	 */
	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new guarani_parametros();
		}
		return self::$instancia;
	}

	//--------------------------------------------------------------------------
	//---- Parámetros por operación --------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene la estructura de parámetros, valores (en caso de que los mismos 
	 * puedan ser determinados), niveles de definición, observaciones, etc. de 
	 * los parámetros involucrados en el contexto de una operación.
	 * 
	 * @param string $operacion
	 * @return array
	 */
	function get_parametros_operacion($operacion)
	{
		$salida = array();
		$parametros_operacion = toba::consulta_php('co_parametros')->get_datos_parametros_operacion($operacion);
		foreach ($parametros_operacion as $key => $dato) {
			try {
				// Se obtienen los atributos para resolver el valor del parámetro
				if ($dato['nivel'] != parametro::nivel_default) {
					$atributos = guarani::cache_parametros()->get_atributos_operacion($operacion, $dato['nivel']);
				} else {
					$atributos = null;
				}

				// Se obtiene el valor del parámetro
				$valor = guarani::parametros()->get_valor($dato['parametro'], $atributos, true, $operacion);

				// Se obtienen los datos de resolución del parámetro
				$datos_param = guarani::cache_parametros()->get_datos_valor_parametro($dato['parametro'], $atributos);

				// Se obtiene la descripción del nivel
				$datos_param['nivel_descr'] = parametro::get_descripcion_nivel_atributos($dato['nivel'], $atributos);

				if (!is_null($valor)) {
					// En caso de tener valor, se le da un formato apropiado para visualización
					$datos_param['valor'] = $valor;

					// Se formatea el valor en función del tipo de parámetro
					if ($dato['tipo_de_parametro'] == parametro::tipo_param_enumerado) {
						$desc_valor_enumerado = toba::consulta_php('co_parametros')->get_desc_enumerado_valor($dato['parametro'], $dato['tipo_de_parametro'], $valor);
						$datos_param['valor_descr'] = '<strong>' . $desc_valor_enumerado['valor_default_desc'] . '</strong>';
					} else {
						$datos_param['valor_descr'] = '<strong>' . $valor . '</strong>';
					}
				}
			} catch (guarani_error_param_no_usado $e) {
				/**
				 * Si se ingresa en este bloque es porque el parámetro se provee
				 * para un nivel distinto de "Para toda la Institución", y todavía
				 * no fueron definidos los atributos para resolverlo en el contexto
				 * de la operación. Se notifica la situación.
				 */
				$datos_param = $dato;
				// Se obtiene la descripción del nivel (sin atributos)
				$datos_param['nivel_descr'] = parametro::get_descripcion_nivel($dato['nivel']);
				// Se deja marcado que el valor del parámetro todavía no puede ser determinado
				$datos_param['valor_descr'] = '--';
				$datos_param['no_cargado_por_dependencia'] = true;
				$datos_param['observaciones'] = $e->get_mensaje();
			} catch (guarani_error_params $e) {
				/**
				 * Si se ingresa en este bloque es porque el parámetro se provee
				 * para un nivel distinto de "Para toda la Institución", y todavía
				 * no fueron definidos los atributos para resolverlo en el contexto
				 * de la operación. Se notifica la situación.
				 */
				$datos_param = $dato;
				// Se obtiene la descripción del nivel (sin atributos)
				$datos_param['nivel_descr'] = parametro::get_descripcion_nivel($dato['nivel']);
				// Se deja marcado que el valor del parámetro todavía no puede ser determinado
				$datos_param['valor_descr'] = '--';
				$datos_param['observaciones'] = 'Aún no es posible determinar el valor';
			}

			$salida[] = $datos_param;
		}
		return $salida;
	}

	/**
	 * Determina si hubo conflictos en la resolución de valores de parámetros en
	 * el contexto de una operación.
	 * 
	 * @param string $operacion
	 * @return boolean
	 */
	function hay_conflictos_en_parametros_operacion($operacion)
	{
		$datos_params = $this->get_parametros_operacion($operacion);
		foreach ($datos_params as $parametro) {
			if ((isset($parametro['hubo_conflicto'])) && ($parametro['hubo_conflicto'])) {
				if (!toba::consulta_php('co_parametros')->no_usar_resolucion_automatica_conflicto($parametro['parametro'], $operacion)) {
					return true;
				}
			}
		}
		return false;
	}

	//--------------------------------------------------------------------------
	//---- Obtención de valores de parámetros ----------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene el valor de un parámetro del sistema, según su nivel de definición 
	 * (Toda la Institución, RA, Tipo de Propuesta, Propuesta o Plan) y los 
	 * eventuales atributos definidos por cada operación para resolverlo.
	 * 
	 * En caso de ocurrir un conflicto de definición de nivel, se resuelve con el 
	 * valor por defecto del parámetro.
	 * Ej: un parámetro de comisiones X está definido con valores diferentes para 
	 * una propuesta A y una propuesta B; una comisión es compartida por las 
	 * propuestas A y B; el valor de X para esa comisión será el definido por defecto.
	 * 
	 * @param string	$parametro
	 * @param array		$atributos		Arreglo aplanado de atributos para resolución de nivel (o null)
	 * 									En caso de no recibirlo, para niveles distinto a "Institución" los busca en caché
	 * @param boolean	$usar_cache		Usar o no la caché de resultados
	 * @param string	$operacion
	 * @return Valor del parámetro
	 * @throws guarani_error_params			en caso que se quiera obtener valor para un parámetro
	 * 										definido por nivel, sin especificar los atributos.
	 * @throws guarani_error_param_no_usado en caso que el parámetro haya sido marcado como no usado
	 * 										en el contexto actual de la operación.
	 */
	function get_valor($parametro, $atributos = null, $usar_cache = true, $operacion = null)
	{
		// Se obtienen los datos del parámetro
		$datos_param = toba::consulta_php('co_parametros')->get_estructura_parametro($parametro);
		$nivel = $datos_param['nivel'] ?? null;

		if ((!is_null($atributos)) && (!is_array($atributos))) {
			$atributos = array($atributos);
		}

		if (!$usar_cache) {
			// Si no se utiliza caché, es obligatorio pasar los atributos para resolver
			// el valor para todo nivel distinto del default
			if ((is_null($atributos)) && ($nivel != parametro::nivel_default)) {
				$nivel_desc = parametro::get_descripcion_nivel($nivel);
				throw guarani::error_params("Los atributos para el nivel $nivel_desc no han sido especificados");
			}
			// Se obtiene el valor del parámetro sin considerar la caché
			$datos_param['atributos'] = $atributos;
			$salida = $this->get_valor_param_nivel($datos_param);
			return (isset($salida['valor'])) ? $salida['valor'] : null;
		} else {
			// Se utiliza la caché
			if (empty($operacion)) {
				$operacion = guarani::get_id_operacion();
			}

			// Se testea si el parámetro fue marcado como no usado 
			$this->verificar_parametro_no_usado($parametro, $operacion);

			if (empty($atributos)) {
				// Si no se especificó un conjunto de atributos explícito, se intenta recuperar de la caché
				$atributos = guarani::cache_parametros()->get_atributos_operacion($operacion, $nivel);
			}

			// Si no hay definidos atributos (ni explícitamente ni en caché) y el nivel de definición del parámetro
			// es distinto de default, no se puede resolver el valor.
			if ((empty($atributos)) && ($nivel != parametro::nivel_default)) {
				$nivel_desc = parametro::get_descripcion_nivel($nivel);
				throw guarani::error_params("Los atributos para el nivel $nivel_desc no han sido especificados");
			}

			$datos_param['atributos'] = $atributos;

			if (guarani::cache_parametros()->hay_datos_parametro($parametro, $atributos)) {
				$salida = guarani::cache_parametros()->get_valor_parametro($parametro, $atributos);
				toba::logger()->notice("Valor del parámetro '$parametro' = '$salida' obtenido desde caché.");
			} else {
				$datos_param = $this->resolver_valor_nivel($datos_param, $operacion);
				guarani::cache_parametros()->guardar_datos_parametro($parametro, $datos_param);
				$salida = (isset($datos_param['valor'])) ? $datos_param['valor'] : null;
			}

			return $salida;
		}
	}
	
	/**
	 * Función especial para obtener valor de parámetros en reglas. 
	 * 
	 * Obtiene el valor del parámetro "bypasseando" el esquema tradicional de caché
	 * de parámetros y guarda el resultado por un tiempo corto (por defecto 30")
	 * en otro espacio de caché, para optimizar consultas recurrentes en reglas, 
	 * cuyo código fuente es compartido por Gestión y 3w.
	 * 
	 * @param string $parametro
	 * @param array $atributos
	 * @return string
	 */
	function get_valor_en_reglas($parametro, $atributos = null)
	{
		if ((!is_null($atributos)) && (!is_array($atributos))) {
			$atributos = array($atributos);
		}
		$atributos_serializados = (!is_null($atributos)) ? implode(',', $atributos) : '__TODA_LA_INST__';
		$id_cache = guarani::cache()->generar_clave('PARAM__' . $parametro . '__' . $atributos_serializados);
		if (guarani::cache()->existe($id_cache)){
			$valor = guarani::cache()->buscar($id_cache);
			$msj_log = "[RESUELTO POR CACHÉ EN REGLA] PARAMETRO: $parametro | ATRIBUTOS: $atributos_serializados | VALOR: $valor";
			toba::logger()->notice($msj_log);
		} else {
			$valor = $this->get_valor($parametro, $atributos, false);
			guarani::cache()->guardar($id_cache, $valor, self::tiempo_cache_param_reglas);
		}
		return $valor;
	}

	/**
	 * Obtiene el valor para un parámetro, teniendo en cuenta su nivel, atributos
	 * y dependencias entre parámetros (en el caso que un parámetro no reciba valor
	 * en función del valor de otro)
	 * 
	 * @param array $datos_param
	 * @param string $operacion
	 * @return array
	 */
	protected function resolver_valor_nivel($datos_param, $operacion = null)
	{
		$parametro = $datos_param['parametro'];

		// Se analiza si hay restriciones de dependencias para obtener el valor del parámetro
		$estructura_dependencias = guarani::cache_parametros()->get_dependencias_parametro($parametro);
		if (!empty($estructura_dependencias)) {
			$dependencia = $this->analizar_dependencias_parametro($estructura_dependencias, $operacion);
		} else {
			$dependencia = null;
		}

		if (!empty($dependencia)) {
			// El parámetro no recibe valor en el contexto actual por sus dependencias con otro parámetro
			$datos_param = array_merge($datos_param, $dependencia);
		} else {
			// Se obtiene el valor del parámetro según el nivel de definición 
			$datos_param = $this->get_valor_param_nivel($datos_param);
		}

		return $datos_param;
	}

	/**
	 * Obtiene el valor de un parámetro en función de su nivel de definición y 
	 * atributos establecidos
	 * 
	 * @param array $datos_param
	 * @param int	$nivel
	 * @return array
	 */
	protected function get_valor_param_nivel($datos_param, $nivel = null)
	{
		if (is_null($nivel)) {
			$nivel = $datos_param['nivel'];
		}

		if ($nivel == parametro::nivel_default) {
			$datos_param['valor'] = $this->get_default($datos_param['parametro']);
		} else {
			// Se obtiene el valor del parámetro según el nivel de definición 
			// (RA, Tipo de Propuesta, Propuesta, Plan)
			// Se determina la función con la que se obtendrá el valor del parámetro para cada nivel
			switch ($nivel) {
				case parametro::nivel_ra :
					$funcion = 'get_param_ra';
					break;
				case parametro::nivel_propuesta_tipo :
					$funcion = 'get_param_propuesta_tipo';
					break;
				case parametro::nivel_propuesta :
					$funcion = 'get_param_propuesta';
					break;
				case parametro::nivel_plan :
					$funcion = 'get_param_plan';
					break;
			}

			$hay_conflictos = false;
			$valores = array();

			foreach ($datos_param['atributos'] as $atributo) {
				try {
					$valor = $this->$funcion($datos_param['parametro'], $atributo);
				} catch (guarani_error_params_ra $e) {
					// En este caso se ingresa sólo cuando se intentó resolver un parámetro que:
					//   1. Admite definición mínima PROPUESTA o PLAN
					//   2. La propuesta pertenece a más de una RA
					//	 3. No hay una definición específica para ese parámetro para el PLAN, PROPUESTA
					//		o TIPO DE PROPUESTA, que permitan resolver el valor sin llegar a la RA
					if ($nivel == parametro::nivel_plan) {
						// Se obtienen todas las propuestas asociadas a los planes recibidos
						$propuestas = $this->get_propuestas_de_planes($datos_param['atributos']);
					}
					if ($nivel == parametro::nivel_propuesta) {
						$propuestas = $datos_param['atributos'];
					}
					
					// Se intenta resolver el valor del parámetro (y sus potenciales conflictos)
					// simulando que el nivel de definición más específico es RA. De esa manera 
					// se reaprovecha la estructura existente.
					$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
					$atributos_originales = $datos_param['atributos'];
					$datos_param['atributos'] = $ras;

					$salida = $this->get_valor_param_nivel($datos_param, parametro::nivel_ra);
					$valor = $salida['valor'];
					$datos_param['atributos'] = $atributos_originales;
					
					$hay_conflictos = !empty($salida['hubo_conflicto']) && $salida['hubo_conflicto'];
				}
				
				if (empty($valores)) {
					// Valor del parámetro para el primer atributo
					$valores[] = $valor;
				} else {
					if (!in_array($valor, $valores)) {
						$hay_conflictos = true;
						$parametro = $datos_param['parametro'];
						$atributos = '[' . implode(', ', $datos_param['atributos']) . ']';
						$nivel_descr = parametro::get_descripcion_nivel($datos_param['nivel'], false);
						toba::logger()->warning("El parámetro $parametro, no pudo ser resuelto para el nivel $nivel_descr con los atributos $atributos. Se le asignó el valor default.");
						break;
					}
				}
			}

			/**
			 * Si se encontraron distintos valores de parámetros para distintos atributos,
			 * se resuelve con el valor por defecto del parámetro y se deja un mensaje
			 * para mostrar en el visualizador de parámetros.
			 */
			if ($hay_conflictos) {
				$datos_param['hubo_conflicto'] = true;
				$datos_param['valor'] = $this->get_default($datos_param['parametro']);
				$datos_param['observaciones'] = 'Se asignó el valor definido <strong>para toda la Institución</strong> para el parámetro, debido a que los valores para los niveles más específicos no eran coincidentes.';
			} else {
				$datos_param['hubo_conflicto'] = false;
				$datos_param['valor'] = current($valores);
			}
			
			$datos_param['function'] = $funcion;
		}
		return $datos_param;
	}

	protected function get_param($parametro, $ra = null, $propuesta_tipo = null, $propuesta = null, $plan = null)
	{
		$valor = false;
		$lanzar_exepcion = false;
		
		if ($plan) {
			$valor = $this->get_valor_param($parametro, 'plan', $plan);
			if (!$this->es_valor_valido($valor) || !$propuesta) {
				$propuesta = $this->get_propuesta_de_plan($plan);
			}
		}
		if (!$this->es_valor_valido($valor) && $propuesta) {
			$valor = $this->get_valor_param($parametro, 'propuesta', $propuesta);
			if (!$this->es_valor_valido($valor)) {
				$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuesta);
				if (guarani_arreglos::count($ras) == 1) {
					$ra = current($ras);
				} else {
					// Se deja una marca para lanzar excepción. Si no se puede 
					// resolver a nivel TIPO DE PROPUESTAS debe ser manejado 
					// con el esquema de resolución automático de conflictos
					$lanzar_exepcion = true;
				}
				$propuesta_tipo = $this->get_propuesta_tipo($propuesta);
			}
		}
		if (!$this->es_valor_valido($valor) && $propuesta_tipo) {
			$valor = $this->get_valor_param($parametro, 'propuesta_tipo', $propuesta_tipo);
		}
		if (!$this->es_valor_valido($valor) && $lanzar_exepcion) {
			throw guarani::error_params_ra();
		}
		if (!$this->es_valor_valido($valor) && $ra) {
			$valor = $this->get_valor_param($parametro, 'ra', $ra);
		}
		if (!$this->es_valor_valido($valor)) {
			$valor = $this->get_valor_param($parametro);
		}
		if (!$this->es_valor_valido($valor)) {
			$nombre = $this->co->get_nombre_parametro($parametro);
			if (!$nombre) {
				$nombre = $parametro;
			}
			throw new guarani_error("Error: no hay definido un valor por defecto para '<strong>$nombre</strong>'.");
		}
		return $valor;
	}

	/**
	 * Obtiene el valor de un parámetro dado
	 */
	protected function get_valor_param($parametro, $de = null, $id = null)
	{
		$parametro = strtoupper($parametro);
		if ($this->esta_cargado_valor($parametro, $de, $id)) {
			return $this->get_valor_cargado($parametro, $de, $id);
		}
		switch ($de) {
			case 'ra':
				$valor = $this->co->get_valor_param_ra($parametro, $id);
				break;
			case 'propuesta_tipo':
				$valor = $this->co->get_valor_param_propuesta_tipo($parametro, $id);
				break;
			case 'propuesta':
				$valor = $this->co->get_valor_param_propuesta($parametro, $id);
				break;
			case 'plan':
				$valor = $this->co->get_valor_param_plan($parametro, $id);
				break;
			case 'default':
			default:
				$valor = $this->co->get_valor_param($parametro);
		}
		if ($this->es_valor_valido($valor)) {
			$this->cargar_valor_param($parametro, $valor, $de, $id);
		}
		return $valor;
	}

	//--------------------------------------------------------------------------
	//---- Funciones específicas para obtener valor de parámetro por nivel -----
	//--------------------------------------------------------------------------
	/**
	 * Obtiene el valor por defecto de un parámetro dado
	 */
	function get_default($parametro)
	{
		return $this->get_param($parametro);
	}

	/**
	 * Obtiene el valor para una Responsable Académica de un parámetro dado
	 */
	function get_param_ra($parametro, $ra)
	{
		return $this->get_param($parametro, $ra);
	}

	/**
	 * Obtiene el valor para un Tipo de Propuesta de un parámetro dado
	 */
	function get_param_propuesta_tipo($parametro, $propuesta_tipo)
	{
		return $this->get_param($parametro, null, $propuesta_tipo);
	}

	/**
	 * Obtiene el valor para una Propuesta de un parámetro dado
	 */
	function get_param_propuesta($parametro, $propuesta)
	{
		return $this->get_param($parametro, null, null, $propuesta);
	}

	/**
	 * Obtiene el valor para un Plan de un parámetro dado
	 */
	function get_param_plan($parametro, $plan)
	{
		return $this->get_param($parametro, null, null, null, $plan);
	}

	/**
	 * Obtiene el valor para un Plan de un Plan Versión para un parámetro dado
	 */
	function get_param_plan_version($parametro, $plan_version)
	{
		$plan = $this->get_plan_de_plan_version($plan_version);
		return $this->get_param_plan($parametro, $plan);
	}

	//--------------------------------------------------------------------------
	//---- Dependencias entre parámetros ---------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Analiza si un parámetro no se carga en función del valor de otros parámetros
	 * 
	 * @param	$estructura_dependencias = array(
	 * 				array(
	 * 					'parametro_maestro' => '...', 
	 * 					'valores_condicionantes' => array('valor1', 'valor2', ...)
	 * 				),
	 * 				...
	 * 			)
	 * Si el 'parametro_maestro' tiene cargado alguno de los 'valores_condicionantes'
	 * no se busca el valor del valor para el parámetro actual.
	 * 
	 * @return	array / null	si no recibe valor por una dependencia, se retorna un arreglo:
	 * 							array (
	 * 								'no_cargado_por_dependencia' => true,
	 * 								'observaciones' => 'abc...'
	 * 							)
	 * 							si no, retorna null
	 * 
	 */
	protected function analizar_dependencias_parametro($estructura_dependencias, $operacion = null)
	{
		foreach ($estructura_dependencias as $maestro) {
			$parametro_maestro = $maestro['parametro_maestro'];
			$valor_maestro = $this->get_valor($maestro['parametro_maestro'], $operacion);
			if (in_array($valor_maestro, $maestro['valores_condicionantes'])) {
				$tipo_de_parametro = toba::consulta_php('co_parametros')->get_tipo_parametro($parametro_maestro);
				if ($tipo_de_parametro == parametro::tipo_param_enumerado) {
					$valor_maestro = current(toba::consulta_php('co_parametros')->get_desc_enumerado_valor($parametro_maestro, $tipo_de_parametro, $valor_maestro));
				}
				return array(
					'no_cargado_por_dependencia' => true,
					'observaciones' => "No se utiliza en este contexto porque el parámetro <strong>$parametro_maestro</strong> tiene valor \"<strong>$valor_maestro</strong>\""
				);
			}
		}
		return null;
	}

	/**
	 * Determina si un parámetro fue marcado como no usado en el presente contexto
	 * de una operación. En ese caso, lanza una excepción de tipo guarani_error_param_no_usado
	 * 
	 * @param string $parametro
	 * @param string $operacion
	 * @throws guarani_error_param_no_usado
	 */
	protected function verificar_parametro_no_usado($parametro, $operacion)
	{
		$params_no_usados = guarani::cache_parametros()->get_parametros_no_usados($operacion);
		if (!empty($params_no_usados)) {
			if (in_array($parametro, $params_no_usados)) {
				throw guarani::error_param_no_usado();
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- Funciones auxiliares ------------------------------------------------
	//--------------------------------------------------------------------------

	protected function cargar_valor_param($parametro, $valor, $de = null, $id = null)
	{
		if (!$de) {
			$this->parametros['default'][$parametro] = $valor;
		} else {
			$this->parametros[$de][$id][$parametro] = $valor;
		}
	}

	/**
	 * Determina si ya está cargado en memoria el valor de un parámetro dado
	 */
	protected function esta_cargado_valor($parametro, $de = null, $id = null)
	{
		if (!$de) {
			return isset($this->parametros['default'][$parametro]);
		} else {
			return isset($this->parametros[$de][$id][$parametro]);
		}
	}

	/**
	 * Obtiene el valor de un parámetro dado que ya está cargado en memoria
	 */
	protected function get_valor_cargado($parametro, $de = null, $id = null)
	{
		if (!$de) {
			return $this->parametros['default'][$parametro];
		} else {
			return $this->parametros[$de][$id][$parametro];
		}
	}

	protected function es_valor_valido($valor)
	{
		return $valor !== false;
	}

	protected function get_propuesta_tipo($propuesta)
	{
		return toba::consulta_php('co_propuestas')->get_propuesta_tipo_de_propuesta($propuesta);
	}

	protected function get_propuesta_de_plan($plan)
	{
		return toba::consulta_php('co_planes')->get_propuesta_de_plan($plan);
	}

	protected function get_plan_de_plan_version($plan_version)
	{
		return toba::consulta_php('co_planes')->get_plan_de_plan_version($plan_version);
	}

	protected function get_ra_de_propuesta($propuesta)
	{
		$ra = toba::consulta_php('co_propuestas')->get_ra_por_defecto($propuesta);
		return $ra ? $ra : false;
	}

	protected function get_propuestas_de_planes($planes)
	{
		$propuestas = array();
		foreach ($planes as $plan) {
			$propuesta = $this->get_propuesta_de_plan($plan);
			if (!in_array($propuesta, $propuestas)) {
				$propuestas[] = $propuesta;
			}
		}
		return $propuestas;
	}

}