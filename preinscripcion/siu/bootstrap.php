<?php

namespace siu;

include __DIR__."/../../vendor/autoload.php";
include 'func_util.php';

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u as util; 
use SIU\Chulupi\util\config;
use SIU\Chulupi\util\UniversalClassLoader;
use SIU\Chulupi\util\cache\AdminCache;
use Monolog\Logger;

/**
 * Configura el KERNEL
 */
class bootstrap
{
	static protected $instancia;
	
	protected $config;
	protected $bloque_acceso;
	protected $perfil;
	protected $acceso_consola;

	static function cargar($consola = false, $perfil = false)
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new bootstrap();
		}
		self::$instancia->_cargar($consola, $perfil);
	}

	static function registrar_autoload()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new bootstrap();
		}
		self::$instancia->configurar_autoloader(true);
	}

	private function __construct()
	{
		
	}

	private function _cargar($consola, $perfil)
	{
		$this->acceso_consola = $consola;
		$this->perfil = $perfil;
		$this->cargar_config();
		$this->configurar_autoloader();
		$this->configurar_localizador();
		// @todo la carga del proyecto no es necesario si el request es de tipo "carga de recurso web".
		$this->cargar_proyecto();
	}

	//--------------------------------------------------------------------------

	static function get_dir_raiz()
	{
		return \realpath(static::get_dir_siu() . '/../..');
	}

	static function get_dir_instalacion()
	{
		return static::get_dir_raiz() . '/instalacion';
	}

	static function get_dir_siu()
	{
		return dirname(__FILE__);
	}

	//--------------------------------------------------------------------------
	// 1) Cargar Configuracion
	//--------------------------------------------------------------------------
	
	/**
	 * @todo cache
	 */
	private function cargar_config()
	{
		$this->config = $this->cargar_config_archivo();
		if (!$this->acceso_consola) {
			$this->bloque_acceso = $this->config['accesos'][$this->get_id_pto_acceso()];
		}
		$this->cargar_login_config();
	}
	
	private function cargar_login_config()
	{
		$path = static::get_dir_instalacion() . '/login.php';
		if(!file_exists($path)){
			throw new \Exception("No se encuetra el archivo '$path', es obligatorio.");
		}
		$this->login_config = array();
		$config = config::load($path);
		foreach ($config as $service => $conf) {
			$this->login_config[$service] = $conf;
		}
	}
	
	private function cargar_config_archivo()
	{
		$path = static::get_dir_instalacion() . '/config.php';
		if (!file_exists($path)) {
			echo("No se definió el archivo 'instalacion/config.php'. Puede hacerlo a partir del archivo de configuación de ejemplo 'instalacion/config_template.php'");
			die();
		}
		
		$config = \SIU\Chulupi\util\config::load($path);
        if(!isset($config['global'])) {
			throw new \Exception('Error en la configuración, falta bloque \'global\'');
		}

		if (!isset($config['global']['accesos'])) {
			throw new \Exception("Error en la configuración, falta bloque 'accesos' dentro del bloque 'global'");
		}

		$id_pto_acceso = $this->get_id_pto_acceso();

		if ((!$this->acceso_consola) && (!isset($config['global']['accesos'][$id_pto_acceso]))) {
			throw new \Exception("Error en la configuración, falta bloque '$id_pto_acceso' dentro del bloque 'global::accesos'");
		}

		return $config['global'];	
	}

	private function test_errores_configuracion()
	{
		$this->test_error_config_global_activo('google_analytics', 'account');
	}

	/**
	 * Testea que si existe un parametro activo en true en la entrada de configuración
	 * testea que todos los parámetros en la lista de $obligatorios estén seteados.
	 * Si existe la entrada pero no tiene el parametro activo se pone en false
	 */
	private function test_error_config_global_activo($entrada, $obligatorios)
	{
		if (! is_array($obligatorios)) {
			$obligatorios = array($obligatorios);
		}
		if (isset($this->config[$entrada])) {
			if (! isset($this->config[$entrada]['activo'])) {
				$this->config[$entrada]['activo'] = false;
				return;
			}
			if (isset($this->config[$entrada]['activo']) && $this->config[$entrada]['activo'] == true) {
				foreach ($obligatorios as $obligatorio) {
					if (! isset($this->config[$entrada][$obligatorio]) || empty($this->config[$entrada][$obligatorio])) {
						$error = "Error en configuración (instalacion/config.php). La entrada '$entrada' se encuentra activa pero no se incluye el parametro '$obligatorio'";
						echo $error;
						die();
					}
				}
			}
		}
	}

	/**
	 * Recupera el perfil asociado al bloque de acceso
	 * @return string
	 */
	private function get_perfil()
	{
		if ($this->acceso_consola) {
			return $this->perfil;
		}
		if (isset($_SERVER['PERFIL'])) {
			return $_SERVER['PERFIL'];
		}
		return null;
	}

	/**
	 * Recupera el perfil asociado al bloque de acceso
	 * @return string
	 */
	private function get_id_pto_acceso()
	{
		if (isset($_SERVER['ID_PTO_ACCESO'])) {
			return $_SERVER['ID_PTO_ACCESO'];
		}
		return null;
	}

	/**
	 * Genera el ACC_ID concatenando el perfil y el ID de pto. de acceso
	 * 
	 * @return string
	 */
	private function get_acc_id()
	{
		if ($this->acceso_consola) {
			return 'consola';
		}
		if ((isset($_SERVER['PERFIL'])) && (isset($_SERVER['ID_PTO_ACCESO']))) {
			return $_SERVER['PERFIL'] . '_' . $_SERVER['ID_PTO_ACCESO'];
		}
		return null;
	}

	/**
	 * Obtiene todos los accesos de las BD configuradas
	 * @return array
	 */
	private function get_accesos_bd()
	{
		$accesos = array();
		foreach ($this->config['accesos'] as $acceso) {
			if (!in_array($acceso['database'], $accesos)) {
				$accesos[] = $acceso['database'];
			}
		}
		return $accesos;
	}

	private function get_alias()
	{
		if ($this->acceso_consola) {
			return 'SHELL';
		}
		if (isset($_SERVER['ACC_ALIAS'])) {
			return $_SERVER['ACC_ALIAS'];
		}
		return null;
	}

	//--------------------------------------------------------------------------
	// 2) Autoload y Localizador
	//--------------------------------------------------------------------------

	private function configurar_autoloader($delay_pers = false)
	{
		$loader = new UniversalClassLoader();
        util::agregar_dir_include_path(static::get_dir_raiz().'/vendor/siu/chulupi-framework');
		if (!$delay_pers && $this->existe_pers()) {
			$this->registrar_namespaces_pers($loader);
		}
		$loader->register();
		
		require_once(realpath(static::get_dir_raiz() . '/vendor/siu/rdi/RDILib/RDIAutoload.php'));
		require_once(realpath(static::get_dir_raiz() . '/vendor/siu/rdi/RDILib/RDICliente.php'));
		require_once(realpath(static::get_dir_raiz() . '/vendor/siu/rdi/RDILib/RDIServicios.php'));
	}

	private function existe_pers()
	{
		return $this->config['usar_personalizaciones'] && isset($this->bloque_acceso['personalizacion']);
	}

	private function get_pers()
	{
		if (!$this->existe_pers()) {
			throw new \Exception('No hay una personalizacion definida');
		}

		$pers = $this->bloque_acceso['personalizacion'];
		if (is_string($pers)) {
			$pers = array($pers);
		}

		return $pers;
	}

	private function get_pers_activa()
	{
		if (!$this->existe_pers()) {
			throw new \Exception('No hay una personalizacion definida');
		}

		$pers = $this->bloque_acceso['personalizacion'];
		return $pers;
	}

	private function registrar_namespaces_pers($loader)
	{
		$pers = $this->get_pers();
		$dir_pers = static::get_dir_raiz() . '/src/pers';

		foreach ($pers as $plugin) {
			$loader->registerNamespace($plugin, $dir_pers);
		}
	}

	private function configurar_localizador()
	{
		$dir = static::get_dir_raiz() . '/src';
		kernel::localizador()->nuevo_namespace('siu', $dir, 'original');
		if ($this->existe_pers()) {
			$this->config_pers_localizador();
		}
	}

	private function config_pers_localizador()
	{
		$pers = $this->get_pers();
		$dir_pers = static::get_dir_raiz() . '/src/pers';

		foreach ($pers as $plugin) {
			kernel::localizador()->nuevo_namespace($plugin, $dir_pers);
		}
	}

	//--------------------------------------------------------------------------
	// 3) Proyecto y eventos del kernel
	//--------------------------------------------------------------------------

	private function cargar_proyecto()
	{
		$ini_file = static::get_dir_raiz() . '/version.ini';
		$datos_version = parse_ini_file($ini_file);

		// VALORES por DEFECTO
		if (!isset($this->config['intentos_login_captcha'])) {
			$this->config['intentos_login_captcha'] = 3;
		}
		$produccion = $this->config['produccion'];
		$perfil = $this->get_perfil();
		$acc_id = $this->get_acc_id();
		$dir_log = static::get_dir_instalacion() . '/log/' . $acc_id;
		$dir_cache = static::get_dir_instalacion() . '/cache/' . $acc_id;
		$dir_temp = (isset($this->config['dir_temp'])) ? $this->config['dir_temp'] : static::get_dir_instalacion() . '/temp/' . $acc_id;
		$dir_attachment = (isset($this->config['dir_attachment'])) ? $this->config['dir_attachment'] : $dir_temp;
		$dir_www_temp = static::get_dir_siu() . '/www/temp';
		$clase_persona = "extension_kernel\\persona";
		$clase_persona_anonima = "extension_kernel\\persona_anonima";
		$clase_login = "extension_kernel\\login";
		$clase_pagina = "extension_kernel\\pagina";
		$clase_login_admin = "extension_kernel\\login_admin";

		$url_recursos = (isset($this->config['url_recursos'])) ? $this->config['url_recursos'] : $this->get_alias() . '/_comp';

		$ssl_alcance = (isset($this->config['ssl']['alcance'])) ? $this->config['ssl']['alcance'] : 'none';
		$ssl_redirigir_ssl = (isset($this->config['ssl']['redirigir_ssl'])) ? $this->config['ssl']['redirigir_ssl'] : true;

		$memcached = (isset($this->config['memcached'])) ? $this->config['memcached'] : null;
		$smtp = (isset($this->config['smtp'])) ? $this->config['smtp'] : null;
		$locale = (isset($this->config['locale'])) ? $this->config['locale'] : 'es';

		$login_externo = (isset($this->config['login_externo'])) ? $this->config['login_externo'] : null;
		$login_config = (isset($this->login_config)) ? $this->login_config : array();

		$usa_captcha = (isset($this->config['captcha']['activo']) && $this->config['captcha']['activo'] === true);
		$intentos_login_captcha = ($usa_captcha) ? $this->config['captcha']['intentos_login'] : 0;
		$site_key_captcha = ($usa_captcha) ? $this->config['captcha']['site_key'] : 0; 
		$secret_key_captcha = ($usa_captcha) ? $this->config['captcha']['secret_key'] : 0;
		$opciones_captcha = ($usa_captcha && isset($this->config['captcha']['opciones'])) ? $this->config['captcha']['opciones'] : [];
		
		$salt = ($this->config['salt']) ? $this->config['salt'] : 'no_salt';
		$salt_admin = ($this->config['salt_admin']) ? $this->config['salt_admin'] : 'no_salt_admin';

		$ga_params = (isset($this->config['google_analytics'])) ? $this->config['google_analytics'] : array('activo' => false);

		$usa_proxy = (isset($this->config['proxy']['activo']) && $this->config['proxy']['activo'] === true);
		$parametros_proxy = ($usa_proxy) ? $this->config['proxy'] : 0;
		
		$server_name = (isset($this->config['server_name'])) ? $this->config['server_name'] : null;
		
		$parametros_rdi = (!empty($this->config['rdi']['config'])) ?  $this->config['rdi']['config'] : array();

		$accesos_bd = $this->get_accesos_bd();

		$pers = ($this->existe_pers()) ? $this->get_pers_activa() : '';
		
                $personalizaciones = [];
		if ($this->existe_pers()) {
                    $personalizaciones = $this->get_pers();
                }
                
		$log_nivel_default = Logger::DEBUG;
		// Si es producción
		if ($produccion) {
			$log_nivel_default = Logger::ERROR;
		}
		
		// Logs
		$log_activo		= (isset($this->config['log']['activo'])) ? $this->config['log']['activo'] : false;
		$log_nivel_cli	= (isset($this->config['log']['nivel_consola'])) ? $this->config['log']['nivel_consola'] : $log_nivel_default;
		$log_nivel_web	= (isset($this->config['log']['nivel_web'])) ? $this->config['log']['nivel_web'] : $log_nivel_default;
		$url_identidad_genero = (isset($this->config['url_identidad_genero'])) ? $this->config['url_identidad_genero'] : die('Tiene que setear em cofig.php el url_identidad_genero');
		$email_ayuda = (isset($this->config['email_ayuda']) && !empty($this->config['email_ayuda'])) ? $this->config['email_ayuda'] : die("Tiene que setear en 'instalacion/config.php' el parámetro 'email_ayuda'");

        // Tiempo de expiración del código validación para recuperar contraseña (sga_preinscripcion.codigo_validacion)
        $ttl_codigo_validacion = (isset($this->config['ttl_codigo_validacion'])) ? $this->config['ttl_codigo_validacion'] : '1 day';

		$proyecto = new extension_kernel\proyecto(array(
			'db' => $this->bloque_acceso['database'] ?? [],
			'accesos' => $this->config['accesos'],
			'accesos_bd' => $accesos_bd,
			'alias' => $this->get_alias(),
			'produccion' => $produccion,
			'personalizacion' => $pers,
                        'personalizaciones' => $personalizaciones,
			'sesion_maxtime' => $this->config['sesion_maxtime'],
			'sesion_timeout' => $this->config['sesion_timeout'],
			'url_recursos' => $url_recursos,
			'memcached' => $memcached,
			'dir_log' => $dir_log,
			'dir_cache' => $dir_cache,
			'dir_temp' => $dir_temp,
			'dir_www_temp' => $dir_www_temp,
			'dir_instalacion'   => static::get_dir_instalacion(),
			'salt' => $salt,
			'salt_admin' => $salt_admin,
			'dir_attachment' => $dir_attachment,
			'clase_persona' => $clase_persona,
			'clase_persona_anonima' => $clase_persona_anonima,
			'clase_login' => $clase_login,
			'clase_login_admin' => $clase_login_admin,
			'clase_pagina' => $clase_pagina,
			'usa_captcha' => $usa_captcha,
			'intentos_login_captcha' => $intentos_login_captcha,			
			'captcha_site_key' => $site_key_captcha, 
			'captcha_secret_key' => $secret_key_captcha, 
			'captcha_opciones' => $opciones_captcha, 
			'usa_proxy' => $usa_proxy,
			'parametros_proxy' => $parametros_proxy,
			'server_name' => $server_name,
			'parametros_rdi' => $parametros_rdi,
			'barra_dev' =>
			isset($this->config['log']['activo']) &&
			$this->config['log']['activo'] == true &&
			isset($this->config['log']['barra_dev']) &&
			$this->config['log']['barra_dev'] == true,
			'smtp' => $smtp,
			'ga_params' => $ga_params,
			'ssl_alcance' => $ssl_alcance,
			'ssl_redirigir_ssl' => $ssl_redirigir_ssl,
			'clave_long_minima' => (isset($this->bloque_acceso['clave_long_minima'])) ? $this->bloque_acceso['clave_long_minima'] : 6,
			'carga_turno_presentacion' => (isset($this->bloque_acceso['carga_turno_presentacion'])) ? $this->bloque_acceso['carga_turno_presentacion'] : false,
			'modo_impresion_comprobante' => (isset($this->bloque_acceso['modo_impresion_comprobante'])) ? $this->bloque_acceso['modo_impresion_comprobante'] : 'ra',
			'formateo_campos' => (isset($this->bloque_acceso['formateo_campos'])) ? $this->bloque_acceso['formateo_campos'] : 'libre',
			'locale' => $locale,
			'login_externo' => $login_externo,
			'login_config' => $login_config,
			//---- Parametros de reporte ---------------------------------------
			'rep_encabezado_logo' => (isset($this->bloque_acceso['rep_encabezado_logo'])) ? $this->bloque_acceso['rep_encabezado_logo'] : '',
			'rep_nombre_institucion' => (isset($this->bloque_acceso['rep_nombre_institucion'])) ? $this->bloque_acceso['rep_nombre_institucion'] : '',
			'rep_imprime_CBC' => (isset($this->bloque_acceso['rep_imprime_CBC'])) ? $this->bloque_acceso['rep_imprime_CBC'] : false,
			'rep_imprime_periodo_insc' => (isset($this->bloque_acceso['rep_imprime_periodo_insc'])) ? $this->bloque_acceso['rep_imprime_periodo_insc'] : false,
			'rep_avisos' => (!empty($this->bloque_acceso['rep_avisos'])) ? $this->bloque_acceso['rep_avisos'] : array(),
			'rep_imprime_credencial_provisoria' => (isset($this->bloque_acceso['rep_imprime_credencial_provisoria'])) ? $this->bloque_acceso['rep_imprime_credencial_provisoria'] : false,
			'rep_localidad' => (isset($this->bloque_acceso['rep_localidad'])) ? $this->bloque_acceso['rep_localidad'] : 'Buenos Aires',
			//---- Logo del sistema --------------------------------------------
			'logo_pagina' => isset($this->config['logo_pagina']) ? $this->config['logo_pagina'] : 'logo.png',
			'version_sistema' => $datos_version['version'],
			//---- Acceso a datos de G3 ----------------------------------------
			'modo_consultas_g3' => (!empty($this->bloque_acceso['modo_consultas_g3'])) ? $this->bloque_acceso['modo_consultas_g3'] : 'consultas_bd',
			'db_guarani' => (isset($this->bloque_acceso['modo_consultas_g3']) && ($this->bloque_acceso['modo_consultas_g3'] == 'consultas_bd')) ? $this->bloque_acceso['database_guarani'] : null,
			'log_activo' => $log_activo,
			'log_nivel_cli' => $log_nivel_cli,
			'log_nivel_web' => $log_nivel_web,
			//--------validacion del link confirmacion de correo
			'tiempo_confirmacion_alta_preinscripcion' => isset($this->config['tiempo_confirmacion_alta_preinscripcion']) ? $this->config['tiempo_confirmacion_alta_preinscripcion'] : '24',
			'url_identidad_genero' => $url_identidad_genero,
			'email_ayuda' => $email_ayuda,
			'datos_ayuda' =>(!empty($this->bloque_acceso['datos_ayuda'])) ? $this->bloque_acceso['datos_ayuda'] : array(),
			'ttl_codigo_validacion'=> $ttl_codigo_validacion,
			));

		try {
			$eventos = kernel::localizador()->instanciar('extension_kernel\eventos_aplicacion');
		} catch (\Exception $e) {
			die('Debe crear la clase eventos_aplicacion a partir del archivo eventos_aplicacion_template.php. Mensaje: "' . $e->getMessage() . '"');
		}

		// Registro del proyecto
		kernel::registrar_proyecto($proyecto, $eventos);
		kernel::proyecto()->set_encoding('iso-8859-1');
		kernel::proyecto()->set_bloque_acceso($perfil);

		// Inicializacion del LOG
		if (isset($this->config['log']['activo']) &&
				$this->config['log']['activo'] == true) {
			kernel::log()->set_activo(true);
			if (isset($this->config['log']['nivel'])) {
				try {
					kernel::log()->set_nivel($this->config['log']['nivel']);
				} catch (\Exception $e) {
					die($e->getMessage());
				}
			}
		}

		AdminCache::set_manejador_memoria(AdminCache::manejador_memoria_apc);
	}

}