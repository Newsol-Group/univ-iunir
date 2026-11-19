<?php
namespace siu\modelo\entidades;

use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\cache\AdminCache;

class parametro
{
	static protected $parametros = array();
	// cursadas
	static protected $usa_asignacion_horaria = true;
	// examenes
	static protected $filtro_materias_con_mesa_exa;

        //constantes
        
        /**
         * Define si se habilita o no en la interfaz de Autogestión el alta de usuarios para cursos
         */
        const cur_alta_usuario_cursos_autogestion = 'cur_alta_usuario_cursos_autogestion';
        
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
	 * Enmascara una llamada a la función homónima desarrollada para el módulo de 
	 * Gestión, indicando que los atributos son definidos explícitamente, es decir,
	 * por fuera del esquema de caché de parámetros de Gestión.
	 * 
	 * En caso que esté activo el administrador de caché, se mantiene el resultado 
	 * por 15 segundos.
	 * 
	 * @param string $parametro
	 * @param array $atributos
	 * @return string
	 */
	static function get_valor($parametro, $atributos = null)
	{
		if ((!is_null($atributos)) && (!is_array($atributos))) {
			$atributos = array($atributos);
		}
		$atributos_serializados = (!is_null($atributos)) ? implode(',', $atributos) : '__TODA_LA_INST__';
		$resuelto_por_cache = false;
		$usar_cache = AdminCache::esta_activo(AdminCache::tipo_memoria);
		if ($usar_cache) {
			$cache = AdminCache::instancia(AdminCache::tipo_memoria);
			$id_cache = 'PARAM__' . $parametro . '__' . $atributos_serializados;
			if ($cache->existe($id_cache)){
				$valor = $cache->buscar($id_cache);
				$resuelto_por_cache = true;
			} else {
				$valor = \guarani::parametros()->get_valor($parametro, $atributos, false);
				$cache->guardar($id_cache, $valor, 15);
			}
		} else {
			// No se utiliza la caché
			$valor = \guarani::parametros()->get_valor($parametro, $atributos, false);
		}
	
		//---- Log (debug) -----------------------------------------------------
		$msj_log = ($resuelto_por_cache) ? '[RESUELTO POR CACHÉ] ' : '';
		$msj_log .= "PARAMETRO: $parametro | ATRIBUTOS: $atributos_serializados | VALOR: ";
		kernel::log()->add_debug($msj_log, $valor);
		
		return $valor;
	}
	
	//--------------------------------------------------------------------------
	
	static function get_parametro_sistema($parametro)
	{
		if (!isset(self::$parametros[$parametro]['default'])) {
			self::$parametros[$parametro]['default'] = catalogo::consultar('parametro', 'get_parametro_sistema', array('parametro' => $parametro));
		}
		return (self::$parametros[$parametro]['default']);
	}

	static function get_parametro_sistema_ra($parametro, $responsable_academica)
	{
		if (!isset(self::$parametros[$parametro]['ra'][$responsable_academica])) {
			self::$parametros[$parametro]['ra'][$responsable_academica] = catalogo::consultar('parametro', 'get_parametro_sistema_ra', array(
						'parametro' => $parametro,
						'responsable_academica' => $responsable_academica
			));
		}
		return (self::$parametros[$parametro]['ra'][$responsable_academica]);
	}

	static function get_parametro_sistema_propuesta_tipo($parametro, $propuesta_tipo)
	{
		if (!isset(self::$parametros[$parametro]['propuesta_tipo'][$propuesta_tipo])) {
			self::$parametros[$parametro]['propuesta_tipo'][$propuesta_tipo] = catalogo::consultar('parametro', 'get_parametro_sistema_propuesta_tipo', array(
						'parametro' => $parametro,
						'propuesta_tipo' => $propuesta_tipo
			));
		}
		return (self::$parametros[$parametro]['propuesta_tipo'][$propuesta_tipo]);
	}

	static function get_parametro_sistema_propuesta($parametro, $propuesta)
	{
		if (is_array($propuesta)) {
			// si el parametro es diferente para las diferentes propuestas
			// tomo el valor por defecto
			$propuestas = implode(",", $propuesta);

			$parametro_unico = catalogo::consultar('parametro', 'get_parametro_sistema_propuesta_unico', array(
						'parametro' => $parametro,
						'propuestas' => $propuestas
			));

			if (!$parametro_unico) {
				return static::get_parametro_sistema($parametro);
			}
			return $parametro_unico;
		} else {
			if (!isset(self::$parametros[$parametro]['propuesta'][$propuesta])) {
				self::$parametros[$parametro]['propuesta'][$propuesta] = catalogo::consultar('parametro', 'get_parametro_sistema_propuesta', array(
							'parametro' => $parametro,
							'propuesta' => $propuesta
				));
			}
			return (self::$parametros[$parametro]['propuesta'][$propuesta]);
		}
	}

	static function get_parametro_sistema_plan($parametro, $plan)
	{
		if (!isset(self::$parametros[$parametro]['plan'][$plan])) {
			self::$parametros[$parametro]['plan'][$plan] = catalogo::consultar('parametro', 'get_parametro_sistema_plan', array(
						'parametro' => $parametro,
						'plan' => $plan
			));
		}
		return (self::$parametros[$parametro]['plan'][$plan]);
	}

	static function get_parametro_sistema_plan_version($parametro, $plan_version)
	{
		if (!isset(self::$parametros[$parametro]['plan_version'][$plan_version])) {
			self::$parametros[$parametro]['plan_version'][$plan_version] = catalogo::consultar('parametro', 'get_parametro_sistema_plan_version', array(
						'parametro' => $parametro,
						'plan_version' => $plan_version
			));
		}
		return (self::$parametros[$parametro]['plan_version'][$plan_version]);
	}

	static function set_parametro_sistema_plan($parametro, $plan, $valor)
	{
		self::$parametros[$parametro]['plan'][$plan] = $valor;
	}

	//--------------------------------------------------------------------------

	static function set_parametro_sistema($parametro, $valor)
	{
		self::$parametros[$parametro]['default'] = $valor;
	}

	//---- Funciones -----------------------------------------------------------

	static function usa_asignacion_horaria()
	{
		return self::$usa_asignacion_horaria;
	}

	static function set_usa_asignacion_horaria($valor)
	{
		self::$usa_asignacion_horaria = $valor;
	}

	//--------------------------------------------------------------------------
	
	function filtro_materias_con_mesa_exa()
	{
		if (isset(self::$filtro_materias_con_mesa_exa)) {
			return self::$filtro_materias_con_mesa_exa;
		}
		$datos = catalogo::consultar('parametro', 'filtro_materias_con_mesa_exa');
		return $datos[0];
	}

	static function set__filtro_materias_con_mesa_exa($valor)
	{
		self::$filtro_materias_con_mesa_exa = $valor;
	}

	//--------------------------------------------------------------------------

	function reinscripcion_ingresante()
	{
		$datos = catalogo::consultar('parametro', 'reinscripcion_ingresante');
		return $datos[0];
	}

	static function banda_horaria_cursado()
	{
		$datos = catalogo::consultar('parametro', 'banda_horaria_cursado');
		return $datos[0];
	}

	function permitir_login_mail()
	{
		$datos = catalogo::consultar('parametro', 'permitir_login_mail');
		return $datos[0];
	}

	function registrar_log_ingresos()
	{
		$datos = catalogo::consultar('parametro', 'registrar_log_ingresos');
		return $datos[0];
	}
}
?>