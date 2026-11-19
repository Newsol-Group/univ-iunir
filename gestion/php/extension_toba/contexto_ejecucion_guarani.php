<?php
class contexto_ejecucion_guarani extends toba_contexto_ejecucion
{
	function conf__inicial()
	{
		toba::db()->set_parser_errores(new guarani_parser_error_db_postgres7());
		guarani::cargar_fabrica();
		toba_componente::set_modo_compatible_manejo_sesion(true);
		toba_ef_combo::set_maximo_descripcion(comunes::chars_maximo_combo);
		
		// Se cargan las clases creadas por las instituciones en el autoload generados por ellos
		$this->resgistrar_autoload_uunn();
		
		//Comparo la version de la BD con la de la aplicacion
		if (toba::proyecto()->get_parametro('proyecto', 'controlar_versiones_app_bd')) {
			$version_app = toba::proyecto()->get_version();
			$version_bd = toba::consulta_php('co_parametros')->get_version_app_base();
			$version_bd = new toba_version($version_bd);
			if ($version_app->es_mayor($version_bd)) {
				throw new guarani_error(guarani_mensajes::get('800_version_app_mayor_bd'));
			} elseif ($version_app->es_menor($version_bd)) {
				throw new guarani_error(guarani_mensajes::get('800_version_app_menor_bd'));
			}
		}

                // Si no esta ya definida la constante JAVA_HOSTS
                if (!defined('JAVA_HOSTS')) {
                    // Variables de Jasper
                    $jasper_host = guarani_instalacion_conf::get_parametro_instalacion('jasper_host', false, '127.0.0.1');
                    $jasper_port = guarani_instalacion_conf::get_parametro_instalacion('jasper_port', false, '8081');
                    define("JAVA_HOSTS", "$jasper_host:$jasper_port");
                }
	}

	function resgistrar_autoload_uunn()
	{
		$clase = 'guarani_autoload_clases_nuevas';
		$file = toba::proyecto()->get_path_pers_php() . "/$clase.php";
		$existe_archivo = file_exists($file);
		if ($existe_archivo) {
			require_once($file);
			spl_autoload_register(array($clase, 'cargar'));
		}
	}

	function conf__final()
	{
		
	}
	
	/**
	 * Ventana que se ejecuta antes de ejecutar un servicio web REST
	 * @param SIUToba\rest\rest $app Clase de rest a configurar
	 * @ventana
	 */	
	function conf__rest($app)
	{
		parent::conf__rest($app);
		// Este control es para poder ingresar al Swagger
		if (!is_null($app)) {
			$app->set_autorizador(new autorizacion_guarani_pers());
		}
		
	}
	
}