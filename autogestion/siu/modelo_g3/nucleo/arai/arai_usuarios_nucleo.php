<?php

/**
 * Clase para consumir los WS de Araí Usuarios
 * @see https://documentacion.siu.edu.ar/apis/?spec=arai-usuarios_v1 API de Araí Usuarios V1.
 * @see https://documentacion.siu.edu.ar/apis/?spec=arai-usuarios_v2 API de Araí Usuarios V2.
 * @package Araí
 */

use GuzzleHttp\Psr7\Response;

class arai_usuarios_nucleo implements interface_arai_usuarios
{
    
    // Códigos de respuesta
    const STATUS_CODE_GET_OK = 200;
    const STATUS_CODE_POST_OK = 201;
    const STATUS_CODE_DELETE_OK = 204;
    
    // Condiciones de filtro
    const CONDICION_CONTIENE = 'contiene';
    const CONDICION_NO_CONTIENE = 'no_contiene';
    const CONDICION_COMIENZA_CON = 'comienza_con';
    const CONDICION_TERMINA_CON = 'termina_con';
    const CONDICION_ES_IGUAL_A = 'es_igual_a';
    const CONDICION_ES_DISTINTO_DE = 'es_distinto_de';
    
    const CONDICION_ES_MENOR_QUE = 'es_menor_que';
    const CONDICION_ES_MENOR_IGUAL_QUE = 'es_menor_igual_que';
    const CONDICION_ES_MAYOR_IGUAL_QUE = 'es_mayor_igual_que';
    const CONDICION_ES_MAYOR_QUE = 'es_mayor_que';
    const CONDICION_ENTRE = 'entre';
    
    // Aplicaciones
    const ETIQUETA_ARAI_USUARIOS = 'ARAI-Usuarios';
    const ETIQUETA_SIU_GUARANI = 'SIU-Guarani';
    const ETIQUETA_SIU_HUARPE = 'SIU-Huarpe';
    
    const PATH_TO_DOCUMENTOS = '/documentos';
	
    const SIU_USUARIO_ARAI = 'sin_usuario_arai';

    const APP_UNIQUE_ID_GESTION	= 'appUniqueIdGestion';
    const APP_UNIQUE_ID_3W	= 'appUniqueId3w';

    const MIN_LENGTH_AUTOCOMPLETE_USUARIOS = 3;

    protected $cliente_arai_usuarios = null;
     
    /**
     * @var string $driverTipo Driver a utilizar, por defecto DRIVER_ARAI.
     */
    protected static $driverTipo = interface_arai_usuarios::DRIVER_ARAI;
    
    /**
     * @var arai_usuarios_v1|arai_usuarios_v2|ext_usuarios $driver Driver
     */
    protected static $driver = null;
	
	/**
     * @var int $versionApi Versión de la API de Araí Usuarios
     */
	protected static $versionApi = null;
	
	/**
	 * @return int Versión de la API de Araí Usuarios
	 */
	public static function getVersionApi()
	{
		if (is_null(static::$versionApi)) {

			// Si se trata de Autogestión
			if (\comunes::es_interfaz_autogestion()) {

				// Busco el archivo 'instalacion/servicios_web_config.php'
				$archivo_config = siu\bootstrap::get_dir_instalacion() . '/servicios_web_config.php';
				// Si el archivo no existe tomo por defecto 1
				if (!file_exists($archivo_config)) {
					$version = 1;
				} else {
					// Parseo el archivo 'instalacion/servicios_web_config.php'
					$datos = SIU\Chulupi\util\config::load($archivo_config);
					// Si la versión no se definio tomo por defecto 1
					$version = $datos['consumidos']['arai_usuarios']['parametros']['version_api'] ?? 1;
				}
				
				\SIU\Chulupi\kernel::log()->add_debug("=== API Araí Usuarios versión ===", $version);
				
			} else {// Si se trata de Gestión
						
				// Busco el archivo 'instalacion/i__desarrollo/p__guarani/rest/arai_usuarios/cliente.ini'
				$archivo_config = toba::instalacion()->get_path_carpeta_instalacion() . '/i__desarrollo/p__guarani/rest/arai_usuarios/cliente.ini';
				// Si el archivo no existe tomo por defecto 1
				if (!file_exists($archivo_config)) {
					$version = 1;
				} else {
					// Parseo el archivo 'instalacion/i__desarrollo/p__guarani/rest/arai_usuarios/cliente.ini'
					$datos = parse_ini_file($archivo_config, true);
					// Si la versión no se definio tomo por defecto 1
					$version = $datos['conexion']['version_api'] ?? 1;
				}

				toba::logger()->debug("=== API Araí Usuarios versión ===");
				toba::logger()->debug($version);
			}
			
			static::$versionApi = $version;
		}

		return static::$versionApi;
	}

    /**
     * @return arai_usuarios_v1|arai_usuarios_v2|ext_usuarios
     */
    public static function getDriver() 
	{
        
        if (is_null(static::$driver)) {
            
			switch (static::$driverTipo) {
				// Si se trata de Araí Usuarios
				case interface_arai_usuarios::DRIVER_ARAI:
					
					$versionApi = static::getVersionApi();
					
					// Si se trata de Autogestión
					if (\comunes::es_interfaz_autogestion()) {
						
						$arai_usuarios_class = 'siu\modelo_g3\arai_usuarios_v' . $versionApi;
						\SIU\Chulupi\kernel::log()->add_debug("=== API Araí Usuarios clase ===", $arai_usuarios_class);
						
					} else {// Si se trata de Gestión
						
						$arai_usuarios_class = 'arai_usuarios_v' . $versionApi;
						toba::logger()->debug("=== API Araí Usuarios clase ===");
						toba::logger()->debug($arai_usuarios_class);
						
					}
					
					break;
				// Si se trata de un servicio externo
				case interface_arai_usuarios::DRIVER_EXT:
					$arai_usuarios_class = 'ext_usuarios';
					break;
			}

			static::$driver = new $arai_usuarios_class();
            
        }
        
        return static::$driver;
    }
	
    /**
     * @return GuzzleHttp\Client El cliente de Guzzle.
     */
    public function getClienteRest()
    {
        if (is_null($this->cliente_arai_usuarios)) {
            $cliente = toba::servicio_web_rest('arai_usuarios');
            $this->cliente_arai_usuarios = $cliente->guzzle();
        }
        return $this->cliente_arai_usuarios;
    }
    
	/**
	 * Servicio REST a Araí Usuarios, GET /info:
	 * Devuelve información sobre la API de Araí Usuarios
	 * @throws guarani_error En caso de error
	 * @return array Información sobre la API de Araí Usuarios.
	 */
	public function getInfo()
	{
		// https://docs.guzzlephp.org/en/stable/request-options.html#http-errors
		$options['http_errors'] = false;
		$response = $this->getClienteRest()->get('info', $options);
		$this->validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
		$respuesta = rest_decode(guarani_rest::get_response_json($response));
		toba::logger()->debug("API Araí Usuarios - GET /info:");
		toba::logger()->debug(print_r($respuesta, true));
		return $respuesta;
	}

	/**
	 * Servicio REST a Araí Usuarios, GET /aplicaciones:
	 * Aplicaciones de Araí.
	 * @throws guarani_error En caso de error
	 * @return array Información sobre aplicaciones de Araí Usuarios.
	 */
	public function getAplicaciones()
	{
		// https://docs.guzzlephp.org/en/stable/request-options.html#http-errors
		$options['http_errors'] = false;
		$response = $this->getClienteRest()->get('aplicaciones', $options);
		// print_r($response);
		$this->validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
		$respuesta = rest_decode(guarani_rest::get_response_json($response));
		toba::logger()->debug("API Araí Usuarios - GET /aplicaciones:");
		toba::logger()->debug(print_r($respuesta, true));
		return $respuesta;
	}
	
	/**
	 * @return bool TRUE si se puede conectar, FALSE caso contrario.
	 */
	public function isConexionOk()
	{
		$exito = true;
		try {
			// Intento llamar a GET /aplicaciones, si falla es porque la API esta caida
			$this->getAplicaciones();
		} catch (Exception $e) {
			$exito = false;
		}

		return $exito;
	}

        /**
	 * Servicio REST a Araí Usuarios, GET /usuarios:
	 * Usuarios de Araí.
	 * @param string $filtro Filtro a buscar entre los campos nombre, apellido, email e identificador.
	 * @throws guarani_error En caso de error
	 * @return array Retorna array de usuarios ordenado por nombre y apellido.
	 */
	public function getUsuariosByFiltro($filtro)
	{
		// https://docs.guzzlephp.org/en/stable/request-options.html#http-errors
		$options['http_errors'] = false;

                $usuarios = [];

                $campos_condicion_contiene = ['nombre', 'apellido', 'email'];
                $condicion_contiene = static::CONDICION_CONTIENE;
                
                foreach ($campos_condicion_contiene as $campo_condicion_contiene) {

                    $query = "$condicion_contiene;$filtro";
                    $options['query'] = [$campo_condicion_contiene => $query];
                    $response = $this->getClienteRest()->get("usuarios", $options);
                    $this->validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
                    $usuarios_tmp = rest_decode(guarani_rest::get_response_json($response));
                    toba::logger()->debug("API Araí Usuarios - GET /usuarios?$campo_condicion_contiene=$query");
                    toba::logger()->debug(print_r($usuarios_tmp, true));

                    // Se pone como clave el 'identificador' para evitar duplicados
                    foreach ($usuarios_tmp as $usuario_tmp) {
                        $clave = $usuario_tmp['identificador'];
                        $usuario_tmp['orden'] = "{$usuario_tmp['nombre']} {$usuario_tmp['apellido']}";
                        $usuarios[$clave] = $usuario_tmp;
                    }

                }

                $campos_condicion_es_igual_a = ['identificador'];
                $condicion_es_igual_a = static::CONDICION_ES_IGUAL_A;
                
                foreach ($campos_condicion_es_igual_a as $campo_condicion_es_igual_a) {

                    $query = "$condicion_es_igual_a;$filtro";
                    $options['query'] = [$campo_condicion_es_igual_a => $query];
                    $response = $this->getClienteRest()->get("usuarios", $options);
                    $this->validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
                    $usuarios_tmp = rest_decode(guarani_rest::get_response_json($response));
                    toba::logger()->debug("API Araí Usuarios - GET /usuarios?$campo_condicion_es_igual_a=$query");
                    toba::logger()->debug(print_r($usuarios_tmp, true));

                    // Se pone como clave el 'identificador' para evitar duplicados
                    foreach ($usuarios_tmp as $usuario_tmp) {
                        $clave = $usuario_tmp['identificador'];
                        $usuario_tmp['orden'] = "{$usuario_tmp['nombre']} {$usuario_tmp['apellido']}";
                        $usuarios[$clave] = $usuario_tmp;
                    }

                }

                // Ordeno los usuarios manteniendo las claves
                // https://www.php.net/manual/en/function.uasort.php
                uasort($usuarios, function ($a, $b) {

                    // https://www.w3schools.com/Php/func_string_strcasecmp.asp
                    return strcasecmp($a['orden'], $b['orden']);
                    
                });

                toba::logger()->debug("API Araí Usuarios - usuarios");
                toba::logger()->debug(print_r($usuarios, true));

		return $usuarios;
	}


        /**
	 * Servicio REST a Araí Usuarios, GET /usuarios:
	 * Usuarios de Araí que no están asociados a Guaraní.
	 * @param string $filtro Filtro a buscar entre los campos nombre, apellido, email e identificador.
	 * @throws guarani_error En caso de error
	 * @return array Retorna array de usuarios ordenado por nombre y apellido, que no están asociados a Guaraní.
	 */
	public function getUsuariosByFiltroNoAsociados($filtro)
	{
		$usuarios = $this->getUsuariosByFiltro($filtro);
		// Traigo los usuarios de Araí ya asociados
		$usuarios_arai_asociados = toba::consulta_php('co_personas')->get_usuarios_arai_asociados();

		$uids_arai_asociados = [];
		if (!empty($usuarios_arai_asociados)) {
			$uids_arai_asociados = array_column($usuarios_arai_asociados, 'arai_identificador_sso');
		}

                toba::logger()->debug("API Araí Usuarios - usuarios asociados");
                toba::logger()->debug(print_r($uids_arai_asociados, true));

		foreach ($usuarios as $key => $usuario) {
			// Si el usuario de Araí ya esta asociado lo quito del array
			if (in_array($usuario['identificador'], $uids_arai_asociados)) {
				unset($usuarios[$key]);
			}
		}

		return $usuarios;
	}

        /**
	 * Servicio REST a Araí Usuarios, GET /usuarios/{identificador}:
	 * Usuario de Araí.
	 * @param string $usuario Identificador del usuario.
	 * @throws guarani_error En caso de error
	 * @return array Retorna datos de un usuario.
	 */
	public function getUsuario($usuario)
	{
		// https://docs.guzzlephp.org/en/stable/request-options.html#http-errors
		$options['http_errors'] = false;
		$response = $this->getClienteRest()->get("usuarios/$usuario", $options);
		$this->validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
		$respuesta = rest_decode(guarani_rest::get_response_json($response));
		toba::logger()->debug("API Araí Usuarios - GET /usuarios/{identificador}:");
		toba::logger()->debug(print_r($respuesta, true));
		return $respuesta;
	}
    
    /**
     * Servicio REST a Araí Usuarios, GET /uuid/{identificador}:
     * Devuelve uid de un usuario.
     * Solo disponible a partir de API V2
     * @param string $identificador Identificador del usuario.
     * @throws guarani_error En caso de error
     * @return string uid de un usuario.
     */
    public function getUidUsuario($identificador)
    {
        return null;
    }

        /**
	 * Devuelve el identificador_usuario de SIU-Araí dependiendo de la versión de la API.
         *
         * @param string $arai_identificador_sso Valor de la columna mdp_personas.arai_identificador_sso
         * @param string $arai_uuid Valor de la columna mdp_personas.arai_uuid
         *
         * @return string ID de usuario de SIU-Araí, para la API v2 es un UUID.
	 */
	function getIdentificadorUsuarioByVersionApi($arai_identificador_sso, $arai_uuid)
	{

            $identificador_usuario = '';

            // Si la versión de la API de Araí Usuarios es 1
            if (static::getVersionApi() == 1) {

                // Araí Usuarios API v1 usa mdp_personas.arai_identificador_sso
                $identificador_usuario = $arai_identificador_sso;

            // Si la versión de la API de Araí Usuarios es mayor o igual a 2
            } else {

                // Si el UUID viene vacío de la DB lo busco en la API de Araí Usuarios
                if (empty($arai_uuid) && !empty($arai_identificador_sso)) {
                    // recupero el UUID de la API de Araí Usuarios
                    $arai_uuid = $this->getUidUsuario($arai_identificador_sso);
                }

                // Araí Usuarios API v2
                $identificador_usuario = $arai_uuid;
            }

            return $identificador_usuario;
	}

    /**
	 * Valida si la respuesta tiene el Status Code esperado.
	 *
	 * @param Response  $response       Objeto de tipo Response.
	 * @param int	$statusEsperado	Status Code esperado.
	 * @param string	$funcion	Función que llama.
	 * @throws \Exception
	 */
    protected function validarResponse(Response $response, $statusEsperado, $funcion)
    {
        if ($response->getStatusCode() != $statusEsperado) {
            toba::logger()->error("Error en '{$funcion}', se esperaba {$statusEsperado} y se obtuvo {$response->getStatusCode()}.");
            toba::logger()->var_dump(rest_decode(guarani_rest::get_response_json($response)));
            throw guarani::error(guarani_mensajes::get('800_error_conexion_arai_usuarios'));
        }
    }
    
	/**
	 * Servicio REST a Araí Usuarios API v1 y v2, GET aplicaciones/{identificador}/cuentas:
	 * Usuario de Araí.
	 * @throws guarani_error En caso de error
	 * @return array Retorna todos los usuario de araí.
	 */
	function getCuentasByAplicacion($identificador, $limit = 50, $page = 1)
	{
		// https://docs.guzzlephp.org/en/stable/request-options.html#http-errors
		$options['http_errors'] = false;
		$options['query'] = ['limit' => $limit, 'page' => $page];
		$response = $this->getClienteRest()->get("aplicaciones/$identificador/cuentas", $options);
		$this->validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
		$respuesta = rest_decode(guarani_rest::get_response_json($response));
		toba::logger()->debug("API Araí Usuarios - GET aplicaciones/{identificador}/cuentas:");
		toba::logger()->debug(print_r($respuesta, true));
		return $respuesta;
	}


        /**
         * Servicio REST a Araí Usuarios API v1 y v2, POST /usuarios/{identificador}/cuentas:
         * Crea una cuenta para la aplicación.
         *
         * @param string $identificador_aplicacion ID aplicación en Araí.
         * @param string $cuenta Cuenta de SIU-Guaraní.
         * @param string $identificador_usuario ID de usuario de SIU-Araí, para la API v2 es un UUID.
         *
         * @throws guarani_error En caso de error
         * @return array Retorna datos de la cuenta creada.
         */
        function crearCuentaAplicacion($identificador_aplicacion, $cuenta, $identificador_usuario)
        {
            // https://docs.guzzlephp.org/en/stable/request-options.html#http-errors
            $options['http_errors'] = false;
            $options['json'] = [
                'identificador_aplicacion' => $identificador_aplicacion,
                'cuenta' => $cuenta,
                'identificador_usuario' => $identificador_usuario
            ];
            $response = $this->getClienteRest()->post("aplicaciones/$identificador_aplicacion/cuentas", $options);
            $this->validarResponse($response, static::STATUS_CODE_POST_OK, __FUNCTION__);
            $respuesta = rest_decode(guarani_rest::get_response_json($response));
            toba::logger()->debug("API Araí Usuarios - POST /aplicaciones/{identificador}/cuentas:");
            toba::logger()->debug(print_r($respuesta, true));
            return $respuesta;
        }

        /**
         * Servicio REST a Araí Usuarios API v1 y v2, DELETE /aplicaciones/{identificador}/cuentas/{cuenta}:
         * Elimina la cuenta para la aplicación.
         *
         * @param string $identificador_aplicacion ID aplicación en Araí.
         * @param string $cuenta Cuenta de SIU-Guaraní.
         *
         * @throws guarani_error En caso de error
         * @return void.
         */
        function eliminarCuentaAplicacion($identificador_aplicacion, $cuenta)
        {
            // https://docs.guzzlephp.org/en/stable/request-options.html#http-errors
            $options['http_errors'] = false;
            $response = $this->getClienteRest()->delete("aplicaciones/$identificador_aplicacion/cuentas/$cuenta", $options);
            $this->validarResponse($response, static::STATUS_CODE_DELETE_OK, __FUNCTION__);
            $respuesta = rest_decode(guarani_rest::get_response_json($response));
            toba::logger()->debug("API Araí Usuarios - DELETE /aplicaciones/{identificador}/cuentas/{cuenta}:");
            toba::logger()->debug(print_r($respuesta, true));
        }

        /**
         * Servicio REST a Araí Usuarios API v1 y v2, GET /aplicaciones/{identificador}/cuentas/{cuenta}:
         * Retorna datos de una cuenta asociada a la aplicación.
         *
         * @param string $identificador_aplicacion ID aplicación en Araí.
         * @param string $cuenta Cuenta de SIU-Guaraní.
         *
         * @return array Retorna datos de la cuenta.
         */
        function getCuentaAplicacion($identificador_aplicacion, $cuenta)
        {
            // https://docs.guzzlephp.org/en/stable/request-options.html#http-errors
            $options['http_errors'] = false;
            $response = $this->getClienteRest()->get("aplicaciones/$identificador_aplicacion/cuentas/$cuenta", $options);

            // En caso de status code 404 devuelvo array vacío
            $respuesta = [];

            // Si devuelve status code 200 (puede recuperar la cuenta)
            if ($response->getStatusCode() == static::STATUS_CODE_GET_OK) {
                $respuesta = rest_decode(guarani_rest::get_response_json($response));
            }
            
            toba::logger()->debug("API Araí Usuarios - GET /aplicaciones/{identificador}/cuentas/{cuenta}:");
            toba::logger()->debug(print_r($respuesta, true));
            return $respuesta;
        }

        /**
         * Servicio REST a Araí Usuarios API v1 y v2, GET /usuarios/{identificador}/cuentas:
         * Retorna todas las cuentas del usuario.
         *
         * @param string $identificador_usuario ID de usuario de SIU-Araí, para la API v2 es un UUID.
         *
         * @throws guarani_error En caso de error
         * 
         * @return array Retorna todas las cuentas del usuario.
         */
        function getCuentasUsuarios($identificador_usuario)
        {
            // https://docs.guzzlephp.org/en/stable/request-options.html#http-errors
            $options['http_errors'] = false;
            $response = $this->getClienteRest()->get("usuarios/$identificador_usuario/cuentas", $options);
            $this->validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
            $respuesta = rest_decode(guarani_rest::get_response_json($response));
            toba::logger()->debug("API Araí Usuarios - GET /usuarios/{identificador}/cuentas:");
            toba::logger()->debug(print_r($respuesta, true));
            return $respuesta;
        }

        /**
         * Servicio REST a Araí Usuarios API v1 y v2, DELETE /usuarios/{identificador}/cuentas/{cuenta}:
         * Elimina la cuenta del usuario en la aplicación.
         *
         * @param string $identificador_aplicacion ID aplicación en Araí.
         * @param string $cuenta Cuenta de SIU-Guaraní.
         * @param string $identificador_usuario ID de usuario de SIU-Araí, para la API v2 es un UUID.
         *
         * @throws guarani_error En caso de error
         * @return void.
         */
        function eliminarCuentaUsuarioAplicacion($identificador_aplicacion, $cuenta, $identificador_usuario)
        {
            // https://docs.guzzlephp.org/en/stable/request-options.html#http-errors
            $options['http_errors'] = false;
            $options['query'] = ['aplicacion' => $identificador_aplicacion];
            $response = $this->getClienteRest()->delete("usuarios/$identificador_usuario/cuentas/$cuenta", $options);
            $this->validarResponse($response, static::STATUS_CODE_DELETE_OK, __FUNCTION__);
            $respuesta = rest_decode(guarani_rest::get_response_json($response));
            toba::logger()->debug("API Araí Usuarios - DELETE /usuarios/{identificador}/cuentas/{cuenta}:");
            toba::logger()->debug(print_r($respuesta, true));
        }

        /**
         * Crea cuentas en SIU-Araí.
         * 
         * @param string $identificador_usuario ID de usuario de SIU-Araí, para la API v2 es un UUID.
         * @param string $usuario Cuenta de SIU-Guaraní.
         * @param string $interfaz 1 para Gestión, 2 para Autogestión o 0 para ambas.
         *
         * @throws guarani_error En caso de error
         * @return void.
         */
	function crearCuentas($identificador_usuario, $usuario, $interfaz)
	{
            // Si no vincula usuarios con SIU-Araí salgo
            if (!toba::instalacion()->vincula_arai_usuarios()) {
                return false;
            }

            $crear_cuenta_gestion = in_array($interfaz, [comunes::interfaz_todas, comunes::interfaz_gestion]);
            $crear_cuenta_autogestion = in_array($interfaz, [comunes::interfaz_todas, comunes::interfaz_autogestion]);

            if ($crear_cuenta_gestion && !toba::config()->existe_valor('instalacion', null, arai_usuarios::APP_UNIQUE_ID_GESTION)) {
                throw new guarani_error("Debe especificar el parámetro '". arai_usuarios::APP_UNIQUE_ID_GESTION ."' en 'instalacion/instalacion.ini'");
            }

            if ($crear_cuenta_autogestion && !toba::config()->existe_valor('instalacion', null, arai_usuarios::APP_UNIQUE_ID_3W)) {
                 throw new guarani_error("Debe especificar el parámetro '". arai_usuarios::APP_UNIQUE_ID_3W ."' en 'instalacion/instalacion.ini'");
            }

            // Si se debe crear cuenta para Gestión
            if ($crear_cuenta_gestion) {
                // ID de Aplicación de Gestión
                $app_unique_id_gestion = toba::config()->get_parametro('instalacion', null, arai_usuarios::APP_UNIQUE_ID_GESTION);
                $this->crearCuentaAplicacion($app_unique_id_gestion, $usuario, $identificador_usuario);
            }

            // Si se debe crear cuenta para Autogestión
            if ($crear_cuenta_autogestion) {
                // ID de Aplicación de Autogestión
                $app_unique_id_3w = toba::config()->get_parametro('instalacion', null, arai_usuarios::APP_UNIQUE_ID_3W);
                $this->crearCuentaAplicacion($app_unique_id_3w, $usuario, $identificador_usuario);
            }
	}

        /**
         * Elimina cuentas en SIU-Araí.
         *
         * @param string $identificador_usuario ID de usuario de SIU-Araí, para la API v2 es un UUID.
         * @param string $interfaz 1 para Gestión, 2 para Autogestión o 0 para ambas.
         *
         * @throws guarani_error En caso de error
         * @return void.
         */
	function eliminarCuentas($identificador_usuario, $interfaz)
	{
            // Si no vincula usuarios con SIU-Araí salgo
            if (!toba::instalacion()->vincula_arai_usuarios()) {
                return false;
            }

            $eliminar_cuenta_gestion = in_array($interfaz, [comunes::interfaz_todas, comunes::interfaz_gestion]);
            $eliminar_cuenta_autogestion = in_array($interfaz, [comunes::interfaz_todas, comunes::interfaz_autogestion]);

            if ($eliminar_cuenta_gestion && !toba::config()->existe_valor('instalacion', null, arai_usuarios::APP_UNIQUE_ID_GESTION)) {
                throw new guarani_error("Debe especificar el parámetro '". arai_usuarios::APP_UNIQUE_ID_GESTION ."' en 'instalacion/instalacion.ini'");
            }

            if ($eliminar_cuenta_autogestion && !toba::config()->existe_valor('instalacion', null, arai_usuarios::APP_UNIQUE_ID_3W)) {
                 throw new guarani_error("Debe especificar el parámetro '". arai_usuarios::APP_UNIQUE_ID_3W ."' en 'instalacion/instalacion.ini'");
            }

            $cuentas_usuario_aux = $this->getCuentasUsuarios($identificador_usuario);
            $cuentas_usuario = [];
            foreach ($cuentas_usuario_aux as $cuenta_usuario) {
                $cuentas_usuario[$cuenta_usuario['identificador_aplicacion']] = $cuenta_usuario;
            }

            // Si se debe eliminar cuenta para Gestión
            if ($eliminar_cuenta_gestion) {
                // ID de Aplicación de Gestión
                $app_unique_id_gestion = toba::config()->get_parametro('instalacion', null, arai_usuarios::APP_UNIQUE_ID_GESTION);
                
                if (isset($cuentas_usuario[$app_unique_id_gestion])) {
                    $datos_cuenta_gestion = $cuentas_usuario[$app_unique_id_gestion];
                    $this->eliminarCuentaUsuarioAplicacion($datos_cuenta_gestion['identificador_aplicacion'], $datos_cuenta_gestion['cuenta'], $datos_cuenta_gestion['identificador_usuario']);
                }
            }

            // Si se debe eliminar cuenta para Autogestión
            if ($eliminar_cuenta_autogestion) {
                // ID de Aplicación de Autogestión
                $app_unique_id_3w = toba::config()->get_parametro('instalacion', null, arai_usuarios::APP_UNIQUE_ID_3W);
                if (isset($cuentas_usuario[$app_unique_id_3w])) {
                    $datos_cuenta_3w = $cuentas_usuario[$app_unique_id_3w];
                    $this->eliminarCuentaUsuarioAplicacion($datos_cuenta_3w['identificador_aplicacion'], $datos_cuenta_3w['cuenta'], $datos_cuenta_3w['identificador_usuario']);
                }
            }
	}
        
    /**
     * @param array $usuario    Usuario de Araí.
     * Consta del siguiente formato:
     * <code>
     *       [
     *           [identificador] => "ddario"
     *           [nombre] => "Dario"
     *           [apellido] => "Docente"
     *           [email] => "eliquid@siu.edu.ar"
     *           [emailRecovery] => null
     *           [persona] => null
     *           [bloqueado] => 0
     *           [jpegPhoto] => "http://siu:8286/siu/resources/resources/img/usuario_foto/usuario_generico.png"
     *       ]
     * </code>
     * @return string   Retorna descripción del Usuario de Araí formateada.
     */
    public static function getDescripcionFormateadaUsuario($usuario)
    {
        $nombre = trim($usuario['nombre']);
        $apellido = trim($usuario['apellido']);
        $email = trim($usuario['email']);
        return "{$nombre} {$apellido} ({$email})";
    }
}