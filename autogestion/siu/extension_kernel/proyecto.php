<?php

namespace siu\extension_kernel;

use SIU\Chulupi\nucleo\proyecto as proyecto_base;
use SIU\Chulupi\util\cache\AdminCache;
use SIU\Chulupi\util\db\InterfaceDb;
use siu\bootstrap;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\ManejadorArchivos;
use SIU\Chulupi\util\config;
use siu\modelo\entidades\parametro;

class proyecto extends proyecto_base
{
	protected $m = null;
	
	protected static $rdi;

	function get_session_name()
	{
		$alias = $this->get_alias();
		return "siu_sess_$alias";
	}

    /**
     * Retorna una instancia de la clase Memcached
     * @return \Memcached
     */
	function memcached()
	{
		if (! isset($this->m)) { 
			$conf_memcached = $this->get('memcached');
			if (empty($conf_memcached)) {
				return null;
			}
			
			$this->m = new \Memcached();
			$servers = array();
			foreach ($conf_memcached as $key => $data) {
				$servers[] = array(
					$data['host'], $data['port'], $data['peso']
				);
			}
			$this->m->addServers($servers);
		}
		
		return $this->m;
	}

    /**
     * Retorna todos los paths donde hay archivos de mensajes
     * @param type $dominio
     * @param type $locale
     * @return type 
     */
	protected function get_paths_mensajes($dominio, $locale)
	{
		$path_relativo = "mensajes/mensajes.$locale.php";
        return kernel::localizador()->encontrar_archivos($path_relativo);
	}

	function get_mensajes($dominio, $locale)
	{
		$apc_enabled = extension_loaded('apcu');
        $apc_key = bootstrap::instancia()->get_id_bloque_acceso().'siu__mensajes';
        if ($this->get_produccion() && $apc_enabled && \apcu_fetch($apc_key) !== false) {
            $mensajes = \apcu_fetch($apc_key);
            return $mensajes;
        }
        
        $paths = $this->get_paths_mensajes('mensajes', $locale);
        
        if (empty($paths)) { // puede ser vacío si se pide una locale especifica; por ej: es_AR
            return array();
        }
        
        $mensajes = array();
        foreach ($paths as $path) {
            $mensajes = array_merge($mensajes, config::load($path));
        }
		
        if ($this->get_produccion()) { // hay que guardar en apc
            \apcu_store($apc_key, $mensajes);
        }
        
        return $mensajes;
	}

	function get_default_locale()
	{
		return 'es';
	}
	
	function get_dir_attachment()
	{
		$dir = $this->get('dir_attachment').'/'.$this->get_bloque_acceso();
		if (! is_dir($dir)) {
			ManejadorArchivos::crear_arbol_directorios($dir);
		}
		
		return $dir;
	}
	
	function get_www_temp()
	{
		$dir = $this->get('dir_www_temp');
		if (! is_dir($dir)) {
			ManejadorArchivos::crear_arbol_directorios($dir);
		}
		return $dir;
	}

    function get_dir_bin()
    {
        return $this->get('dir_bin');
    }
	
		
	function get_url_kolla()
	{
		return $this->get('url_kolla');
	}

    function get_url_sq_pagos()
    {
        return $this->get('url_sq_pagos');
    }

    function get_accesos()
    {
        return $this->get('accesos');
    }

    function get_cant_emails_a_enviar_por_corrida_cron()
    {
        return $this->get('cant_emails_a_enviar_por_corrida_cron');
    }

    function get_cant_max_destinatarios_por_email()
    {
        return $this->get('cant_max_destinatarios_por_email');
    }

    function get_encuesta_evaluar_rtas_libres_expreg()
    {
        return $this->get('encuesta_evaluar_rtas_libres_expreg');
    }

    function set_manejador_cache_memoria($manejador){

        switch ($manejador) {
            case 'apc':
                AdminCache::set_manejador_memoria(AdminCache::manejador_memoria_apc);
                break;
            case 'memcached':
                AdminCache::set_manejador_memoria(AdminCache::manejador_memoria_memcached);
                break;
        }

    }

    function get_encuestas_cantidad_maxima_respuestas_kolla()
    {
        return $this->get('encuestas_cantidad_maxima_respuestas_kolla');
    }

    function autocompletar_input_habilitado()
    {
        return $this->get('habilitar_sugerencias_autocompletar_input');
    }

    function prueba_stress_habilitada()
    {
        return $this->get('habilitar_prueba_stress');
    }

    /**
     * Crea el usuario para los logs de auditoria.
     * @param InterfaceDb $conexion
     */
    function crear_usuario_para_auditoria($conexion){

        $db_params = kernel::proyecto()->get_parametros_db();

        //Me aseguro que la DB sea Postgres
        if($db_params["vendor"] != "pgsql") return false;

        //Consulto si existe el esquema "auditoria"
        $schema = $conexion->consultar_fila("SELECT EXISTS(SELECT 1 FROM pg_namespace WHERE nspname = ".$conexion->quote($db_params["schema"]."_auditoria").")");

        //Si no existe el esquema auditoria salgo
        if(!$schema["exists"]) return false;
		
        //Obtengo el ID de la persona desde sesion
        $persona = kernel::sesion()->esta_seteada('__kernel_id_persona')? kernel::sesion()->get('__kernel_id_persona') : null;

        //Si la persona esta logueada obtengo sus datos basicos desde la sesion
        if(!is_null($persona)){
            $parametros = array('persona' => $conexion->quote($persona));
            $id_cache = catalogo::generar_id_cache('persona', 'datos_basicos', $parametros);
            $persona_datos_basicos = kernel::sesion()->esta_seteada($id_cache)? kernel::sesion()->get($id_cache) : null;
            if(isset($persona_datos_basicos['identificacion'])) $persona_identificacion = $persona_datos_basicos['identificacion'];
        }
		else{//Si la persona NO esta logueada
				// Si la interfaz es consola (cli)
				// https://stackoverflow.com/questions/933367/php-how-to-best-determine-if-the-current-invocation-is-from-cli-or-web-server
				if(php_sapi_name() == \comunes::interfaz_consola){
					$persona_identificacion = \persona::get_usuario_consola();
				}
				else{ //Si la interfaz es WEB
					$persona_identificacion = \persona::perfil_anonimo;
				}
		}

        //Creo la tabla temporal con el usuario de auditoria
        $sql = "CREATE TEMP TABLE tt_usuario (usuario VARCHAR(60), id_solicitud INTEGER);";
        $postgres_user = $db_params["pdo_user"];
        $sql .= "GRANT SELECT, INSERT ON tt_usuario TO {$postgres_user};";
        $sql .= "INSERT INTO tt_usuario (usuario) VALUES (".$conexion->quote($persona_identificacion).");";
        $conexion->ejecutar($sql);

    }

    /**
     * Función que se ejecuta inmediatamente después de conectarse a la DB.
     * @param InterfaceDb $conexion
     */
    public function pos_conexion($conexion){
        $this->crear_usuario_para_auditoria($conexion);
    }
	
	//--------------------------------------------------------------------------
	//---- Repositorio Digital Integrado (RDI) ---------------------------------
	//--------------------------------------------------------------------------
	
	function usa_rdi()
	{
		return parametro::get_valor('usa_repositorio_digital') == \comunes::si;
	}
	
	function get_parametros_rdi()
	{
		return $this->get('parametros_rdi');
	}
	
	/**
	 * 
	 * @return \siu\modelo\_comun\clienteRDI
	 */
	function rdi()
	{
		if (!isset(self::$rdi)){
			self::$rdi = kernel::localizador()->instanciar('modelo\\_comun\\clienteRDI');
		}
		return self::$rdi->get_cliente();
	}
	
    //--------------------------------------------------------------------------
    //---- Boleto Estudiantil                  ---------------------------------
    //--------------------------------------------------------------------------

    /**
     * Si usa Boleto Estudiantil o no (Parámetro 'usa_boleto_estudiantil').
     *
     * @return bool TRUE si usa Boleto Estudiantil, FALSE en caso contrario.
     */
    function usa_boleto_estudiantil()
    {
        return parametro::get_valor(\boleto_estudiantil::USA_BOLETO_ESTUDIANTIL) == \comunes::si;
    }

    /**
	 * 
	 * @return \siu\modelo\_comun\clienteRDI
	 */
    function get_puntoacceso_x_ra()
    {
        return $this->get('puntoacceso_x_ra');
    }
    
    function get_personalizaciones()
    {
        return $this->get('personalizaciones');
    }
    
    function get_metodos_autenticacion_habilitados()
    {
        return $this->get('metodos_autenticacion_habilitados');
    }
	
	function get_huarpe_url()
    {
        return $this->get('huarpe_url');
    }
	
	function get_huarpe_url_bandeja()
	{
		return $this->get('huarpe_url_bandeja');
	}

	function get_url_identidad_genero()
    {
		return $this->get('url_identidad_genero');
    }
    
    function get_email_ayuda()
    {
		return $this->get('email_ayuda');
    }

    function get_jasper_host()
    {
		return $this->get('jasper_host');
    }

    function get_jasper_port()
    {
		return $this->get('jasper_port');
    }
    
    function valida_con_renaper()
	{
		return parametro::get_valor('mat_validacion_renaper');
	}
    
    /**
     * @return string Tiempo de expiración del token para recuperar contraseña (mdp_personas.token)
     */
    function get_ttl_token()
    {
		return $this->get('ttl_token');
    }

    /**
     * @return bool Si se controla el acceso simultáneo a operaciones, por defecto false
     */
    function es_control_simultaneidad_operaciones_activo()
    {
        return $this->get('control_simultaneidad_operaciones_activo');
    }

    /**
     * @return int Tiempo en segundos que se bloquea la operación en caso de acceso simultáneo, por defecto 1 segundo
     */
    function get_control_simultaneidad_operaciones_ttl_segundos()
    {
        return $this->get('control_simultaneidad_operaciones_ttl_segundos');
    }

    /**
     * @return int El número mínimo de caracteres que un usuario debe escribir antes de realizar una búsqueda, por defecto 3 caracteres
     */
    function get_ui_autocomplete_min_length()
    {
        return $this->get('ui_autocomplete_min_length');
    }

    /**
     * @return string URL a la carpeta 'src/siu/www/_comp'
     */
    function get_url_recursos()
    {
        return $this->get('url_recursos');
    }
}