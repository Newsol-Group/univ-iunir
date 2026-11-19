<?php
/**
 * 
 * Esta clase instancia la fábrica central de Guaraní3
 * Determina si está personalizada o no e instancia la que corresponde
 * 
 * @package Centrales
 */
class guarani_nucleo
{

	static protected $fabrica;
	static protected $validador_controles = null;

	static function cargar_fabrica($clase_fabrica = 'fabrica_guarani')
	{
		if (!isset(self::$fabrica)) {
			self::$fabrica = new $clase_fabrica();
		}
		return self::$fabrica;
	}

	/**
	 * @return guarani_db
	 */
	static function db()
	{
		return self::$fabrica->db();
	}
	
	/**
	 * 
	 * @return cache_memoria
	 */
	static function cache()
	{
		return cache_memoria_apc::instancia();
	}

	static function catalogo_comunes()
	{
		return self::$fabrica->catalogo_comunes();
	}

	static function catalogo_elementos_plan()
	{
		return self::$fabrica->catalogo_elementos_plan();
	}

	static function catalogo_personas()
	{
		return self::$fabrica->catalogo_personas();
	}

	/**
	 * 
	 * @return guarani_parametros
	 */
	static function parametros()
	{
		return self::$fabrica->parametros();
	}
	
	/**
	 * 
	 * @return cache_parametros
	 */
	static function cache_parametros()
	{
		return cache_parametros::instancia();
	}
	
	/**
	 * Concentrador de consultas cacheables de alumno
	 * 
	 * @return cache_alumno_apc
	 */
	static function alumno()
	{
		return cache_alumno_apc::instancia();
	}
	
	/**
	 * Concentrador de consultas cacheables de elementos
	 * 
	 * @return cache_elementos_apc
	 */
	static function elementos()
	{
		return cache_elementos_apc::instancia();
	}
	
	/**
	 * Concentrador de consultas cacheables de condiciones
	 * 
	 * @return cache_condiciones_apc
	 */
	static function condiciones()
	{
		return cache_condiciones_apc::instancia();
	}

        /**
	 * Concentrador de consultas cacheables de personas
	 *
	 * @return cache_personas_apc
	 */
	static function personas()
	{
		return cache_personas_apc::instancia();
	}

	/**
	 * Concentrador de consultas cacheables de planes
	 *
	 * @return cache_planes_apc
	 */
	static function planes()
	{
		return cache_planes_apc::instancia();
	}

	/**
	 * @todo: eliminar 3.13.0
	 */
	static function param()
	{
		return static::parametros();
	}

	static function catalogo_g3entidades()
	{
		return self::$fabrica->catalogo_g3entidades();
	}

	static function complemento($id_complemento)
	{
		return self::$fabrica->complemento($id_complemento);
	}

	static function catalogo_planes()
	{
		return self::$fabrica->catalogo_planes();
	}

	static function catalogo_modalidades()
	{
		return self::$fabrica->catalogo_modalidades();
	}

	static function co($clase_consulta)
	{
		return toba::consulta_php($clase_consulta);
	}

	static function act($clase_actualizacion)
	{
		return self::$fabrica->act_php($clase_actualizacion);
	}

	static function rest($clase_rest)
	{
		return self::$fabrica->rest_php($clase_rest);
	}

	static function get_id_operacion()
	{
		return toba::solicitud()->get_datos_item('item_parametro_a');
	}

	static function get_id_operacion_toba()
	{
		return toba::solicitud()->get_id_operacion();
	}

	static function error($mensajes, $mensaje_debug = '')
	{
		return self::$fabrica->error($mensajes, $mensaje_debug);
	}
	
	/**
	 * 
	 * @return guarani_error_params
	 */
	static function error_params($mensajes, $mensaje_debug = '')
	{
		return new guarani_error_params($mensajes, $mensaje_debug);
	}
	
	/**
	 * 
	 * @return guarani_error_params_ra
	 */
	static function error_params_ra($mensajes = "Conflicto de Responsables Académicas en resolución de parámetros", $mensaje_debug = '')
	{
		return new guarani_error_params_ra($mensajes, $mensaje_debug);
	}
	
	/**
	 * 
	 * @return guarani_error_param_no_usado
	 */
	static function error_param_no_usado($mensajes = 'No se utiliza en este contexto.', $mensaje_debug = '')
	{
		return new guarani_error_param_no_usado($mensajes, $mensaje_debug);
	}

	static function constructor_arbol()
	{
		return self::$fabrica->constructor_arbol();
	}

	static function arbol_nivel($id, $columna_id, $columna_desc, $nombre = '')
	{
		return self::$fabrica->arbol_nivel($id, $columna_id, $columna_desc, $nombre);
	}

	static function proceso_bk($proceso)
	{
		return self::$fabrica->proceso_bk($proceso);
	}

	static private function invocador_procesos_bk()
	{
		return self::$fabrica->invocador_procesos_bk();
	}

	/**
	 * Instancia el invocador que ejecuta el proceso $proceso
	 * @param $proceso
	 */
	static function ejecutar_proceso_bk($proceso)
	{
		$invocador = guarani::invocador_procesos_bk();
		$invocador->ejecutar_proceso($proceso);
	}

	static function regla($regla_id)
	{
		$regla_info = toba::consulta_php('co_reglas', true)->get_info_regla($regla_id);
		return new $regla_info['php_clase']($regla_id, $regla_info);
	}

	/**
	 * Retorna al validador de puntos de control
	 *     Con parametros, retorna al validador creado con estos parámetros.
	 * 	   Sin parámetros, retorna al validador ya instanciado
	 * 
	 * 
	 * @param array $puntos								Arreglo con los puntos de control a validar
	 * @param array $parametros							Arreglo asociativo con todos los parametros de entorno necesarios		
	 * 													para la ejecución de los controles pertenecientes a los puntos de control pedidos
	 * @param boolean $detener_controles_en_rechazo		Determina si en caso de fallo en algún control se continúa o no validando
	 * 													los demás controles, si se pasa en null se fija en los parámetros del sistema
	 * @param char(1) $interfaz							Intefaz desde la que se accede al validador interfaz_gestion, interfaz_autogestion
	 * @return validador_puntos_de_control
	 */
	static function validador_puntos_de_control($puntos = null, $parametros = null, $detener_controles_en_rechazo = null, $interfaz = comunes::interfaz_gestion)
	{
		if (!isset($puntos) && !isset($parametros)) {
			if (!isset(self::$validador_controles)) {
				throw new guarani_error('Error Interno: El validador de Puntos de Control no está instanciado');
			}
			return self::$validador_controles;
		}

		$operacion = (!empty($parametros['operacion'])) ? $parametros['operacion'] : guarani::get_id_operacion();
		self::$validador_controles = new validador_puntos_de_control(toba::notificacion(), $puntos, $parametros, $interfaz, $operacion);

		if (!isset($detener_controles_en_rechazo)) {
			// Parámetro defino a nivel Institución - se consume de esta forma para mantener compatibilidad con 3w
			$param_estado = guarani::parametros()->get_default('par_detener_ejecucion_en_rechazo');
			self::$validador_controles->set_detener_en_rechazo($param_estado == comunes::si);
		} else {
			self::$validador_controles->set_detener_en_rechazo($detener_controles_en_rechazo);
		}
		return self::$validador_controles;
	}

	/**
	 * 
	 * @return ev_info_datos
	 */
	static function ev_info_datos($datos = array())
	{
		return new ev_info_datos($datos);
	}

	static function get_path_logo_impresion()
	{
		//Obtengo el logo de la Institución correspondiente, si no existe seteo el logo de Guarani
		$logo = toba::proyecto()->get_www_pers('img/logo_institucion.jpg');
		if (!file_exists($logo['path'])) {
			$logo = toba::proyecto()->get_www('img/logo_grande.png');
		}

		return $logo['path'];
	}

	static function get_path_logo_impresion_chico()
	{
		//Obtengo el logo chico de la Institución correspondiente, si no existe seteo el logo chico de Guarani
		$logo = toba::proyecto()->get_www_pers('img/logo_institucion_chico.jpg');
		if (!file_exists($logo['path'])) {
			$logo = toba::proyecto()->get_www('img/logo.png');
		}

		return $logo['path'];
	}
	
	static function get_path_logo_republica()
	{
		//Obtengo el logo de la república
		$logo = toba::proyecto()->get_www_pers('img/logo_republica.jpg');
		if (!file_exists($logo['path'])) {
			$logo = toba::proyecto()->get_www('img/logo_republica.jpg');
		}

		return $logo['path'];
	}

	static function get_path_fondo_diploma()
	{
		//Obtengo el fondo del diploma
		$logo = toba::proyecto()->get_www_pers('img/fondo_diploma.jpg');
		if (!file_exists($logo['path'])) {
			$logo = toba::proyecto()->get_www('img/fondo_diploma.jpg');
		}

		return $logo['path'];
	}
	
	static function get_path_reporte_jasper($archivo)
	{
		// Si existe el reporte personalizado devuelvo esa plantilla, sino la original
		$path_jasper = '/exportaciones/jasper/';
		$path = toba::proyecto()->get_path_pers() . $path_jasper;
		if (!file_exists($path . $archivo)) {
			$path = toba::proyecto()->get_path() . $path_jasper;
		}

		return $path . $archivo;
	}

	static function encriptar($texto)
	{
		$enc = new bcrypt(10);
		return $enc->hash(md5($texto));
	}

	/**
	 * @return notificador
	 */
	static function notificador()
	{
		return notificador::instancia();
	}

        /**
         * Obtiene URL actual de Guaraní.
         * @see https://www.geeksforgeeks.org/get-the-full-url-in-php/
         * @see https://www.tutorialrepublic.com/faq/how-to-get-current-page-url-in-php.php
         *
         * @param boolean $incluir_query_string (por defecto false)
         * @return string URL actual de Guaraní
         */
        static function get_url_actual($incluir_query_string = false)
        {
            $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

            $query_string = $_SERVER['QUERY_STRING'];
            if ($incluir_query_string && !empty($query_string)) {
                $url = "{$url}?{$query_string}";
            }
            
            return $url;
        }

        /**
         * Obtiene URL de una operación de Guaraní.
         * @param string $item Item destino, por defecto el actual
	 * @param array $parametros Parametros pasados al item, es un arreglo asociativo id_parametro => valor
	 * @param array $opciones Arreglo asociativo de opciones ellas son:
	 * <code> zona => Activa la propagación automática del editable en la zona
	 * cronometrar => Indica si la solicitud generada por este vinculo debe cronometrarse
	 * param_html => Parametros para la construccion de HTML. Si esta presente se genera HTML en vez de una URL. Las claves asociativas son: frame, clase_css, texto, tipo [normal,popup], inicializacion, imagen_recurso_origen, imagen
	 * texto => Texto del vínculo
	 * menu => El vinculo esta solicitado por una opción menu?
	 * celda_memoria => Namespace de memoria a utilizar, por defecto el actual
	 * servicio => Servicio solicitado, por defecto get_html
	 * objetos_destino => array(array(proyecto, id_objeto), ...) Objetos destino del vinculo
	 * prefijo => Punto de acceso a llamar.
	 * nombre_ventana => Id con que se creara la ventana hija (solo tipo popup)</code>
         * @return string URL de una operación de Guaraní.
         */
        static function get_url_operacion($item = null, $parametros = [], $opciones = [])
        {
            return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $item, $parametros, $opciones);
        }

}
