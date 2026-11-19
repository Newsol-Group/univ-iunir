<?php
namespace siu;

require __DIR__."/../../vendor/autoload.php";

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\config;
use SIU\Chulupi\util\UniversalClassLoader;
use SIU\Chulupi\util\u as util;
use Monolog\Logger;
use siu\modelo\datos\catalogo;
use siu\modelo\autenticacion\fuente_usuarios_guarani;

include 'func_util.php';

//define('TEST_STRESS__NUNCA_ACTIVAR_EN_PRODUCCION',1);
define('CONTROLAR_VERSION_BASE',true);
/**
 * Configura el KERNEL
 */
class bootstrap
{
	const URL_IMAGENES_ALUMNOS = '_img_alumnos';
	static protected $instancia;
	protected $config;
	protected $login_config;
	protected $bloque_acceso;
	protected $bloque_acceso_id;
	protected $acceso_consola;

	static function cargar($consola = false, $acceso = false)
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new bootstrap();
		}
		self::$instancia->_cargar($consola, $acceso);
	}

	/**
	 *
	 * @return \siu\bootstrap
	 */
	static function instancia()
	{
		if (! isset(self::$instancia)) {
			die("no se puede solicitar la instancia sin antes haber invocado al método cargar");
		}

		return self::$instancia;
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

	private function _cargar($consola, $acc_id)
	{
		$this->check_extensiones();
		$this->acceso_consola = $consola;
		$this->bloque_acceso_id = $acc_id;		
		$this->cargar_config();
		$this->configurar_autoloader();
		$this->configurar_localizador();
		// @todo la carga del proyecto no es necesario si el request es de tipo "carga de recurso web".
		$this->cargar_proyecto();
		$this->cargar_schema_toba();
		$this->cargar_jasper_host();
	}

	//-------------------------------------------------

	static function get_dir_raiz()
	{
		return \realpath(static::get_dir_siu() . '/../..');
	}

	static function get_dir_instalacion()
	{
		return static::get_dir_raiz() . '/instalacion';
	}

	static function get_dir_bin()
	{
		return static::get_dir_raiz() . '/bin';
	}

	static function get_dir_siu()
	{
		return dirname(__FILE__);
	}

	//-------------------------------------------------
	// 0) Test extensiones
	//-------------------------------------------------
	private function check_extensiones()
	{
		$this->check_extension('gd');
		$this->check_extension('pdo_pgsql');
	}

	private function check_extension($extension)
	{
		if (!\extension_loaded($extension)) {
			$error = "La extensión de php '$extension' no está instalada.";
			echo("$error Ver documentación");
			die();
		}
	}

	//-------------------------------------------------
	// 1) Cargar Configuracion
	//-------------------------------------------------

	/**
	 * @todo cache
	 */
	private function cargar_config()
	{
		$this->config = $this->cargar_config_archivo();
		$this->test_errores_configuracion();
		$this->bloque_acceso = $this->config['accesos'][$this->get_id_bloque_acceso()];

	}

	private function cargar_config_archivo()
	{
		// cargo el archivo
		$path =  static::get_dir_instalacion() . '/config.php';
		if (!file_exists($path)) {
			echo("No se definió el archivo 'instalacion/config.php'. Puede hacerlo a partir del archivo de configuación de ejemplo 'instalacion/config_template.php'");
			die();
		}
        $config = \SIU\Chulupi\util\config::load($path);
        if(!isset($config['global'])) {
			throw new \Exception('Error en la configuración, falta bloque \'global\'');
		}

		if (! isset($config['global']['accesos'])) {
			throw new \Exception("Error en la configuración, falta bloque 'accesos' dentro del bloque 'global'");
		}

		return $config['global'];
	}

	private function test_errores_configuracion()
	{
		$this->test_error_config_global('produccion');
		$this->test_error_config_global('usar_personalizaciones');
		$this->test_error_config_global('sesion_maxtime');
		$this->test_error_config_global('sesion_timeout');
		$this->test_error_config_global_activo('google_analytics', 'account');
		$this->test_error_acceso($this->get_id_bloque_acceso());
		$this->test_error_acceso($this->get_id_bloque_acceso(), 'database');
	}

	private function test_error_config_global($parametro)
	{
		if (!isset($this->config[$parametro])) {
			$path =  static::get_dir_instalacion() . '/config_template.php';
			$config  = config::load($path);
			unset($config['global']['accesos']);
			$error = "Error en configuración (instalacion/config.php). Falta parámetro '$parametro' en bloque 'global'. Para más información vea la documentación.";
			$ejemplo = str_replace("'$parametro'", "<b>'$parametro'</b>",var_export($config['global'], true));
			echo("$error<br>Ejemplo:<pre>$ejemplo</pre>");
			die();
		}
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

	private function test_error_acceso($bloque_acceso, $parametro = false)
	{

		//Si no se especifica ningun punto de acceso (ACC_ID) se toma el primer punto de acceso configurado en config.php
		if($bloque_acceso === false){
			$bloque_acceso = key($this->config['accesos']);
		}

		if ($parametro === false) {
			if (! isset($this->config['accesos'][$bloque_acceso])) {
				$path =  static::get_dir_instalacion() . '/config_template.php';
				$config  = config::load($path);
				$error = "Error en configuración (instalacion/config.php). Falta el bloque de acceso '$bloque_acceso'. Para más información vea la documentación.";
				$ejemplo = str_replace("'accesos'", "<b>'accesos'</b>",var_export($config['global'], true));
				echo("$error<br>Ejemplo:<pre>$ejemplo</pre>");
				die();
			}
		} else {
			if (! isset($this->config['accesos'][$bloque_acceso][$parametro])) {
				$path =  static::get_dir_instalacion() . '/config_template.php';
				$config  = config::load($path);
				$error = "Error en configuración (instalacion/config.php). Falta el parámetro '$parametro' en el bloque de acceso '$bloque_acceso'. Para más información vea la documentación.";
				$ejemplo = str_replace("'accesos'", "<b>'accesos'</b>",var_export($config['global'], true));
				$ejemplo = str_replace("'$parametro'", "<b>'$parametro'</b>",$ejemplo);
				echo("$error<br>Ejemplo:<pre>$ejemplo</pre>");
				die();
			}
		}
	}

	private function cargar_login_config()
	{
		$path =  static::get_dir_instalacion() . '/login.php';
		if(!file_exists($path)){
			throw new \Exception("No se encuetra el archivo '$path', es obligatorio.");
		}
		$config = config::load($path);
		return $config;
	}

	private function cargar_servicios_web_config()
	{
		$path =  static::get_dir_instalacion() . '/servicios_web_config.php';
		if (!file_exists($path)) return array();
		return config::load($path);
	}

	/**
	 * Recupera el acceso que se está usando en este request
	 * @return string
	 */
	function get_id_bloque_acceso()
	{
		if ($this->acceso_consola) {
			// si es acceso consola la ua viene preseteada por parámetro

			//Si no se especifica ningun punto de acceso (ACC_ID) se toma el primer punto de acceso configurado en config.php
			if($this->bloque_acceso_id === false){
				$this->bloque_acceso_id = key($this->config['accesos']);
			}
			return $this->bloque_acceso_id;
		}
		if(isset($_SERVER['ACC_ID'])) {
			return $_SERVER['ACC_ID'];
		}

		throw new \Exception('La entrada $_SERVER["ACC_ID"] no está definida, hay un error en la configuración');
	}

	private function get_alias()
	{
		if ($this->acceso_consola) {
			return 'SHELL';
		}		
		if(isset($_SERVER['ACC_ALIAS'])) {
			return $_SERVER['ACC_ALIAS'];
		}
		return null;
	}

	//-------------------------------------------------
	// 2) Autoload y Localizador
	//-------------------------------------------------

	private function configurar_autoloader($delay_pers = false)
	{
		$dir = static::get_dir_siu();

		if (! $delay_pers && $this->existe_pers()) {
            $loader = new UniversalClassLoader();
			$this->registrar_namespaces_pers($loader);
            $loader->register();
		}

		// Registrar Zend
        util::agregar_dir_include_path(static::get_dir_raiz().'/vendor/siu/chulupi-framework');

		// para cargar las clases del nucleo de Gestion
		require_once($dir.'/modelo_g3/nucleo/g3w_autoload.php');
		spl_autoload_register(array('g3w_autoload', 'cargar'));

		require_once($dir.'/../pers_gestion/php/guarani_pers_autoload.php');
		spl_autoload_register(array('guarani_pers_autoload', 'cargar'));

		require_once($dir.'/../pers_gestion/php/guarani_autoload_clases_nuevas.php');
		spl_autoload_register(array('guarani_autoload_clases_nuevas', 'cargar'));

		require_once($dir.'/modelo_g3/g3w_autoload_adaptador.php');
		spl_autoload_register(array('g3w_autoload_adaptador', 'cargar'));

		require_once(realpath(static::get_dir_raiz() . '/vendor/siu/rdi/RDILib/RDIAutoload.php'));
		require_once(realpath(static::get_dir_raiz() . '/vendor/siu/rdi/RDILib/RDICliente.php'));
		require_once(realpath(static::get_dir_raiz() . '/vendor/siu/rdi/RDILib/RDIServicios.php'));
		
		\guarani::cargar_fabrica();
	}

	private function existe_pers()
	{
		return $this->config['usar_personalizaciones'] && isset($this->bloque_acceso['personalizacion']);
	}

	private function get_pers()
	{
		if(!$this->existe_pers()) {
			throw new \Exception('No hay una personalizacion definida');
		}

		$pers = $this->bloque_acceso['personalizacion'];
		if (is_string($pers)) {
			$pers = array($pers);
		}

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

	//-------------------------------------------------
	// 3) Proyecto y eventos del kernel
	//-------------------------------------------------

	private function cargar_proyecto()
	{

        //Configuración global
        $config_global = $this->config;
        //Le quito los bloques de accesos a la configuración global
        unset($config_global['accesos']);
        //Acá va todo lo que NO puede ser configurable por bloque de acceso
        unset($this->bloque_acceso['manejador_cache_memoria']);
        unset($this->bloque_acceso['memcached']);
        //Reemplazo en la configuración global lo configurado en el bloque de acceso (configuración en cascada).
        $config_final = array_replace_recursive($config_global, $this->bloque_acceso);

		// VALORES por DEFECTO
		if (!isset($config_final['intentos_login_captcha'])) {
			$config_final['intentos_login_captcha'] = 3;
		}
		$produccion = $config_final['produccion'];
		$id_bloque_acceso = $this->get_id_bloque_acceso();
		$dir_log = static::get_dir_instalacion() . '/log/' . $id_bloque_acceso;
		$dir_cache = static::get_dir_instalacion() . '/cache/' . $id_bloque_acceso;
		$dir_temp = (isset($config_final['dir_temp'])) ? $config_final['dir_temp'] : static::get_dir_instalacion() . '/temp/' . $id_bloque_acceso;
		$dir_attachment = (isset($config_final['dir_attachment'])) ? $config_final['dir_attachment'] : $dir_temp;
		$dir_www_temp = static::get_dir_siu() . '/www/temp';
		$clase_persona = "modelo\\entidades\\persona";
		$clase_fuente_usuarios = "modelo\\autenticacion\\fuente_usuarios_guarani";
		$clase_pagina = "extension_kernel\\pagina";
		$clase_logger = "extension_kernel\\log";
		$url_recursos = (isset($config_final['url_recursos']))	? $config_final['url_recursos']	: $this->get_alias().'/_comp';

        $ssl_alcance = (isset($config_final['ssl']['alcance']))? $config_final['ssl']['alcance'] : 'none';
        $ssl_redirigir_ssl = (isset($config_final['ssl']['redirigir_ssl']))? $config_final['ssl']['redirigir_ssl'] : true;

        $manejador_cache_memoria = (isset($config_final['manejador_cache_memoria'])) ?  $config_final['manejador_cache_memoria'] : 'apc';
        $memcached =	(isset($config_final['memcached']))		? $config_final['memcached']	: null;
		$smtp =			(isset($config_final['smtp']))			? $config_final['smtp']			: null;
		$login_config = $this->cargar_login_config();
		$servicios_web_config = $this->cargar_servicios_web_config();
		$server_name = (isset($config_final['server_name'])) ? $config_final['server_name'] : null;

		$usa_captcha = (isset($config_final['captcha']['activo']) && $config_final['captcha']['activo'] === true);
		$intentos_login_captcha = ($usa_captcha) ?  $config_final['captcha']['intentos_login'] : 0;
		$site_key_captcha = ($usa_captcha && isset($config_final['captcha']['site_key'])) ?  $config_final['captcha']['site_key'] : 'site_key';
		$secret_key_captcha = ($usa_captcha && isset($config_final['captcha']['secret_key'])) ?  $config_final['captcha']['secret_key'] : 'secret_key';
		$opciones_captcha = ($usa_captcha && isset($config_final['captcha']['opciones'])) ?  $config_final['captcha']['opciones'] : [];
		$salt = ($config_final['salt']) ?  $config_final['salt'] : 'no_salt';

		$usa_proxy = (isset($config_final['proxy']['activo']) && $config_final['proxy']['activo'] === true);
		$parametros_proxy = ($usa_proxy) ?  $config_final['proxy'] : 0;

		$parametros_rdi = (!empty($this->config['rdi']['config'])) ?  $this->config['rdi']['config'] : array();
		
		$ga_params = (isset($config_final['google_analytics'])) ? $config_final['google_analytics'] : array('activo' => false);
		// Fotos
		if(isset($config_final['imagen_alumno_dir'])){
			$imagen_alumno_dir = $config_final['imagen_alumno_dir'];
		}else{
			$imagen_alumno_dir = $dir_temp . self::URL_IMAGENES_ALUMNOS;
		}
		if(isset($config_final['imagen_alumno_url'])){
			$imagen_alumno_url = $config_final['imagen_alumno_url'];
		}else{
			$imagen_alumno_url = $url_recursos.'/'.self::URL_IMAGENES_ALUMNOS;
		}
		$url_kolla = isset($config_final['url_kolla']) ? $config_final['url_kolla']: "http://localhost/kolla/3.3/";

        $url_sq_pagos = isset($config_final['url_sq_pagos']) ? $config_final['url_sq_pagos']: "http://localhost/sq_pagos/";
        $ptoacc_ra = isset($config_final['ptoacc_ra']) ? $config_final['ptoacc_ra']: "";
        
                $metodos_autenticacion_todos = [fuente_usuarios_guarani::AUTH_FORM, fuente_usuarios_guarani::AUTH_SAML, fuente_usuarios_guarani::AUTH_CUENTA_EXT, fuente_usuarios_guarani::AUTH_DEV, fuente_usuarios_guarani::AUTH_APP_JWT, fuente_usuarios_guarani::AUTH_LDAP];
                if (isset($config_final['metodos_autenticacion_habilitados'])) {
                    $metodos_autenticacion_habilitados = (!empty($config_final['metodos_autenticacion_habilitados'])) ? $config_final['metodos_autenticacion_habilitados']: $metodos_autenticacion_todos;
                } else {
                    $metodos_autenticacion_habilitados = $metodos_autenticacion_todos;
                }
                
		$personalizacion = '';
                $personalizaciones = [];
		if ($this->existe_pers()) {
			$personalizaciones = $this->get_pers();
			$personalizacion = reset($personalizaciones);
		}

		$cant_emails_a_enviar_por_corrida_cron = (isset($config_final['cant_emails_a_enviar_por_corrida_cron']))? $config_final['cant_emails_a_enviar_por_corrida_cron'] : 0;

        $cant_max_destinatarios_por_email = (isset($config_final['cant_max_destinatarios_por_email']))? $config_final['cant_max_destinatarios_por_email'] : 0;

		$encuestas_cantidad_maxima_respuestas_kolla = (isset($config_final['encuestas_cantidad_maxima_respuestas_kolla'])) ? $config_final['encuestas_cantidad_maxima_respuestas_kolla'] : 0;


		$encuesta_evaluar_rtas_libres_expreg = (isset($config_final['encuesta_evaluar_rtas_libres_expreg'])) ? $config_final['encuesta_evaluar_rtas_libres_expreg'] : false;

        $habilitar_sugerencias_autocompletar_input = (isset($config_final['habilitar_sugerencias_autocompletar_input'])) ? $config_final['habilitar_sugerencias_autocompletar_input'] : false;

		$habilitar_prueba_stress = (isset($config_final['habilitar_prueba_stress'])) ? $config_final['habilitar_prueba_stress'] : false;
		
		$habilitar_agenda_alumno = (isset($config_final['habilitar_agenda_alumno'])) ? $config_final['habilitar_agenda_alumno'] : true;
		
		$chequear_version_bd = (isset($config_final['chequear_version_bd'])) ? $config_final['chequear_version_bd'] : true;

		$default_dir_ops_inactivas = static::get_dir_instalacion()."/operaciones_inactivas"; 
	        $dir_ops_inactivas = isset($config_final['dir_ops_inactivas']) ? $config_final['dir_ops_inactivas'] : $default_dir_ops_inactivas;

		$registrar_log_ingresos = (isset($config_final['registrar_log_ingresos'])) ? $config_final['registrar_log_ingresos'] : true;
		
		$log_nivel_default = Logger::DEBUG;
		// Si es producción
		if ($produccion) {
			$log_nivel_default = Logger::ERROR;
		}
		
		// Logs
		$log_activo		= (isset($config_final['log']['activo'])) ? $config_final['log']['activo'] : false;
		$log_nivel_cli	= (isset($config_final['log']['nivel_consola'])) ? $config_final['log']['nivel_consola'] : $log_nivel_default;
		$log_nivel_web	= (isset($config_final['log']['nivel_web'])) ? $config_final['log']['nivel_web'] : $log_nivel_default;
		
		$huarpe_url = (isset($config_final['huarpe_url'])) ? $config_final['huarpe_url'] : 'http://localhost/huarpe';
		$huarpe_url_bandeja = (isset($config_final['huarpe_url_bandeja'])) ? $config_final['huarpe_url_bandeja'] : 'http://localhost/huarpe/documentos';
		
		$url_identidad_genero = (isset($config_final['url_identidad_genero'])) ? $config_final['url_identidad_genero'] : die("Tiene que setear en 'instalacion/config.php' el parámetro 'url_identidad_genero'");
        $email_ayuda = (isset($config_final['email_ayuda']) && !empty($config_final['email_ayuda'])) ? $config_final['email_ayuda'] : die("Tiene que setear en 'instalacion/config.php' el parámetro 'email_ayuda'");

		// Variables de Jasper
		$jasper_host = (isset($config_final['jasper_host'])) ? $config_final['jasper_host'] : '127.0.0.1';
		$jasper_port = (isset($config_final['jasper_port'])) ? $config_final['jasper_port'] : '8081';
        
        // Tiempo de expiración del token para recuperar contraseña (mdp_personas.token)
        $ttl_token = (isset($config_final['ttl_token'])) ? $config_final['ttl_token'] : '1 day';

        // Si se controla el acceso simultáneo a operaciones, por defecto false
        $control_simultaneidad_operaciones_activo = $config_final['control_simultaneidad_operaciones']['activo'] ?? false;

        // Tiempo en segundos que se bloquea la operación en caso de acceso simultáneo, por defecto 1 segundo
        $control_simultaneidad_operaciones_ttl_segundos = $config_final['control_simultaneidad_operaciones']['ttl_segundos'] ?? 1;

        /**
         * El número mínimo de caracteres que un usuario debe escribir antes de realizar una búsqueda.
         * El cero es útil para datos locales con solo unos pocos elementos,
         * pero se debe usar un valor más alto cuando una búsqueda de un solo carácter puede coincidir con unos pocos miles de elementos.
         * https://api.jqueryui.com/autocomplete/#option-minLength
         */
        $ui_autocomplete_min_length = $config_final['ui_autocomplete_min_length'] ?? 3;

        $proyecto = new extension_kernel\proyecto(array(
			'db'				=> $this->bloque_acceso['database'],
			'alias'				=> $this->get_alias(),
			'produccion'		=> $produccion,
			'personalizacion'	=> $personalizacion,
			'personalizaciones'	=> $personalizaciones,
			'sesion_maxtime'	=> $config_final['sesion_maxtime'],
			'sesion_timeout'	=> $config_final['sesion_timeout'],
			'url_recursos'		=> $url_recursos,
            'manejador_cache_memoria' => $manejador_cache_memoria,
			'memcached'			=> $memcached,
			'dir_log'			=> $dir_log,
			'dir_cache'			=> $dir_cache,
			'dir_temp'			=> $dir_temp,
			'dir_www_temp'		=> $dir_www_temp,
			'dir_instalacion'   => static::get_dir_instalacion(),
			'dir_bin'           => static::get_dir_bin(),
			'bloque_acceso'     => static::get_id_bloque_acceso(),
			'imagen_alumno_dir'	=> $imagen_alumno_dir,
			'imagen_alumno_url'	=> $imagen_alumno_url,
			'salt'				=> $salt,
			'dir_attachment'	=> $dir_attachment,
			'fuente_usuarios'	=> $clase_fuente_usuarios,
			'clase_persona'		=> $clase_persona,
			'clase_persona_anonima' => false,
			'clase_pagina'		=> $clase_pagina,
			'clase_logger'		=> $clase_logger,
			'server_name'		=> $server_name,
			'usa_captcha'		=> $usa_captcha,
			'intentos_login_captcha' => $intentos_login_captcha,
			'captcha_site_key' => $site_key_captcha,
			'captcha_secret_key' => $secret_key_captcha,
			'captcha_opciones' => $opciones_captcha,
			'usa_proxy' => $usa_proxy,
			'dir_ops_inactivas' => $dir_ops_inactivas,
			'parametros_proxy' => $parametros_proxy,
			'parametros_rdi' => $parametros_rdi,
			'barra_dev'		=> 
			isset($config_final['log']['activo'])		&&
			$config_final['log']['activo'] == true		&&
			isset($config_final['log']['barra_dev'])	&&
			$config_final['log']['barra_dev'] == true,
			'smtp'  => $smtp,
			'ssl_alcance'  => $ssl_alcance,
			'ssl_redirigir_ssl'  => $ssl_redirigir_ssl,
			'login_config'=>$login_config,
			'ga_params' => $ga_params,
			'url_kolla' => $url_kolla,
			'url_sq_pagos' => $url_sq_pagos,
			'servicios_web' => $servicios_web_config,
			'accesos' => $this->config['accesos'],
			'cant_emails_a_enviar_por_corrida_cron' => $cant_emails_a_enviar_por_corrida_cron,
            'cant_max_destinatarios_por_email' => $cant_max_destinatarios_por_email,
			'registrar_log_ingresos' => $registrar_log_ingresos,
			'encuestas_cantidad_maxima_respuestas_kolla' => $encuestas_cantidad_maxima_respuestas_kolla,
			'encuesta_evaluar_rtas_libres_expreg' => $encuesta_evaluar_rtas_libres_expreg,
			'habilitar_sugerencias_autocompletar_input' => $habilitar_sugerencias_autocompletar_input,
			'habilitar_prueba_stress' => $habilitar_prueba_stress,
			'puntoacceso_x_ra' => $ptoacc_ra,
			'metodos_autenticacion_habilitados' => $metodos_autenticacion_habilitados,
			'log_activo' => $log_activo,
			'log_nivel_cli' => $log_nivel_cli,
			'log_nivel_web' => $log_nivel_web,
			'huarpe_url' => $huarpe_url,
			'huarpe_url_bandeja' => $huarpe_url_bandeja,
			'url_identidad_genero' => $url_identidad_genero,
            'email_ayuda' => $email_ayuda,
			'jasper_host' => $jasper_host,
			'jasper_port' => $jasper_port,
			'ttl_token' => $ttl_token,
			'control_simultaneidad_operaciones_activo' => $control_simultaneidad_operaciones_activo,
			'control_simultaneidad_operaciones_ttl_segundos' => $control_simultaneidad_operaciones_ttl_segundos,
			'ui_autocomplete_min_length' => $ui_autocomplete_min_length,
		));

		try {
			$eventos = kernel::localizador()->instanciar('extension_kernel\eventos_aplicacion');
		} catch (\Exception $e) {
			die('Debe crear la clase eventos_aplicacion a partir del archivo eventos_aplicacion_template.php. Mensaje: "'.$e->getMessage().'"');
		}

		// registro el proyecto
		kernel::set_tipo_archivos_acceso(kernel::$tipo_archivo_acceso_php);
		kernel::registrar_proyecto($proyecto, $eventos);
		kernel::proyecto()->set_encoding('iso-8859-1');
		kernel::proyecto()->set_bloque_acceso($id_bloque_acceso);
        kernel::proyecto()->set_manejador_cache_memoria($manejador_cache_memoria);

		// Inicializacion del LOG
		if(isset($config_final['log']['activo']) &&
			$config_final['log']['activo'] == true){
				kernel::log()->set_activo(true);
				if(isset($config_final['log']['nivel'])){
					try {
						kernel::log()->set_nivel($config_final['log']['nivel']);
					} catch (\Exception $e) {
						die($e->getMessage());
					}
				}
			}
		
		// Inicializacion handle de debug
		if(isset($config_final['ini_debug']) &&
			$config_final['ini_debug'] == true){
				$debug = kernel::localizador()->instanciar('debug');
				$debug->ini();
				kernel::log()->add_debug('Perfil de debug', get_class($debug));
			}

		//		\guarani::cargar_fabrica();  
		//		\siu\modelo\controles\proceso_control::activar(false);

		\siu\modelo\entidades\parametro::set_usa_asignacion_horaria($habilitar_agenda_alumno);
		\siu\modelo\controles\proceso_control::usar_cache(false);
		if ($chequear_version_bd && !$this->acceso_consola) {
			$this->chequear_version_g3();
		}

	}

	protected function chequear_version_g3() {
		
		// En trunk3 y trunk3_versiones no existe dicho archivo, por lo tanto no se controla
		if (file_exists(static::get_dir_raiz() . '/version')) {

			$version_base = catalogo::consultar('sistema', 'version_base_g3');
			$version_sistema = explode('-', trim(file_get_contents(static::get_dir_raiz() . '/version')))[0];

                        // Si la versión de la DB es distinta a la versión del sistema
			if (version_compare($version_base, $version_sistema, '!=')) {
				$error = kernel::traductor()->trans('sistema.control_version', [
                                    '%version_sistema%' => $version_sistema,
                                    '%version_base%' => $version_base
                                ]);
				echo($error);
				die();
			}
		}
		
	}
	
	protected function cargar_schema_toba(){

		$parametros_db = kernel::proyecto()->get_parametros_db();

		if(isset($parametros_db['schema_toba'])){
			$schema_toba = $parametros_db['schema_toba'];
		}
		else{
			$schema_toba = 'desarrollo';
			kernel::log()->add_debug("No esta definido el parámetro 'schema_toba' en el bloque de acceso, se toma por defecto:", $schema_toba);
		}

		define("SCHEMA_TOBA", $schema_toba);
	}

	private function cargar_jasper_host(){
		$jasper_host = kernel::proyecto()->get_jasper_host();
		$jasper_port = kernel::proyecto()->get_jasper_port();

		define("JAVA_HOSTS", "$jasper_host:$jasper_port");
	}


}