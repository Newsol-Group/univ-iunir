<?php

namespace siu\modelo\autenticacion;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\config;

use OneLogin_Saml2_Auth;
use siu\bootstrap;
use siu\errores\error_guarani_login;
use SIU\Chulupi\nucleo\AccesoWeb;

class auth_saml extends auth_guarani{

	/**
	 * @var \OneLogin_Saml2_Auth
	 */
	protected $saml_auth;
	protected $errors;

        public const SAML_USER_DATA = 'samlUserdata';

        function __construct($id, $conf)
	{
        //Obtengo el id del bloque de acceso
        $id_bloque_acceso = bootstrap::instancia()->get_id_bloque_acceso();

        $settingsInfo = array();
        $settingsInfoGlobal = array();
        $settingsInfoBloqueAcceso = array();

        if(isset($conf['settings_file'])){
            $settings_file = $conf['settings_file'];
            //Si existe el archivo settings_file global
            if(file_exists($settings_file)){
                $settingsInfoGlobal = config::load($settings_file);
            }
        }

        if(isset($conf['accesos'][$id_bloque_acceso]['settings_file'])){
            $settings_file_bloque_acceso = $conf['accesos'][$id_bloque_acceso]['settings_file'];
            //Si existe el archivo settings_file del bloque de acceso
            if(file_exists($settings_file_bloque_acceso)){
                $settingsInfoBloqueAcceso = config::load($settings_file_bloque_acceso);
            }
        }
        //Reemplazo los valores de los archivos en cascada
        $settingsInfo = array_replace_recursive($settingsInfo, $settingsInfoGlobal, $settingsInfoBloqueAcceso);

        kernel::log()->add_debug("Configuracion SAML (settings.php)", $settingsInfo);

        //Tomo la configuración del bloque de acceso correspondiente
        $conf_bloque_acceso = isset($conf['accesos'][$id_bloque_acceso])? $conf['accesos'][$id_bloque_acceso] : array();
        //Elimino las configuraciones de los bloques de acceso
        unset($conf['accesos']);
        //Reemplazo los valores en cascada
        $conf = array_replace_recursive($conf, $conf_bloque_acceso);

        kernel::log()->add_debug("Configuracion SAML (login.php)", $conf);

        parent::__construct($id, $conf);
		$this->saml_auth  = new OneLogin_Saml2_Auth($settingsInfo);

//		$this->show_metadata();
	}


	function autenticar()
	{
		if($this->es_callback_saml()){
			$this->saml_auth->processResponse();
			$errors = $this->saml_auth->getErrors();

			if (!empty($errors)) {
				kernel::log()->add_debug("Errores SAML", $errors);
				$this->errors = $errors;
				return false;
			}

			if (!$this->saml_auth->isAuthenticated()) {
				$this->errors = 'saml.no_autenticado';
				return false;
			}

//			TODO: analizar guardar el callback en este atributo para redirigir luego del login
//			if (isset($_POST['RelayState']) && OneLogin_Saml2_Utils::getSelfURL() != $_POST['RelayState']) {
//				$auth->redirectTo($_POST['RelayState']);
//			}
			try{
				$attr = $this->saml_auth->getAttributes();
				kernel::log()->add_debug("Atributos SAML", $attr);
				$id_persona = $this->get_clase_usuarios()->autenticar_login_saml($attr, $this->conf['local_uid'], $this->conf['saml_uid']);
                                kernel::sesion()->set(static::SAML_USER_DATA, $attr);
				return $id_persona;
			} catch (error_guarani_login $e) {
				//guardar errores para mostrar en el render_login()
				kernel::log()->add_debug("=== Error autenticar ===", kernel::traductor()->trans($e->get_mensaje()));
				$this->errors = $e->get_mensaje();
			}
			return false;

		} else {
            AccesoWeb::$redireccionando = true;
			$this->saml_auth->login();
//			$this->saml_auth->login($returnTo);
			return true;
		}

	}


	public function esta_logueado()
	{
                return kernel::sesion()->esta_seteada(static::SAML_USER_DATA);
	}



	public function get_modelo()
	{
		$datos = array();
		$datos['url_login'] = kernel::vinculador()->crear('acceso', 'index', array('auth' => 'saml'));
		if($this->errors){
			$datos['error'] = $this->errors;
		}
		return $datos;
	}


	public function logout()
	{
		// Ver ticket #34224 (https://redmine.siu.edu.ar/redmine/issues/34224)
		$this->registrar_propuesta_inicial_logout();
		
		AccesoWeb::$redireccionando = true;
		//Si el logout se inicia desde g3, hace un redirect. Luego vuelve y cierra la sesion.
		if( isset($_GET) && (isset($_GET['SAMLResponse']) || isset($_GET['SAMLRequest']) ) ){
			$this->saml_auth->processSLO();
		}else {
			$this->saml_auth->logout();
		}

	}

	protected function es_callback_saml(){

		//TODO: hay algo mejor?
		return isset($_POST) && isset($_POST['SAMLResponse']);
	}

	protected function show_metadata(){
		//DEBUG PARA VER LA CONFIGURACION LOCAL
		header('Content-Type: text/xml');
		$settings = $this->saml_auth->getSettings()->getSPMetadata();
		echo '<pre><xmp>' .$settings .'</xmp></pre>';
		die;
	}
	
	public function get_metadata()
	{
		return $this->saml_auth->getSettings()->getSPMetadata();
	}

	/**
	 * Se invoca luego de abrir sesion en el kernel (luego de una autenticacion exitosa)
	 */
	public function evt_sesion_abierta()
	{
	}

	/**
	 * Se invoca cada request en el cual existe una sesiòn abierta (para actualizar maxtimes, estadisticas, etc). No se
	 * llama si la sesion se abre este request.
	 */
	public function evt_nuevo_request()
	{
	}
}