<?php

namespace siu\extension_kernel;

use SIU\Chulupi\nucleo\proyecto as proyecto_base;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\ManejadorArchivos as manejador_archivos;
use SIU\Chulupi\util\config;
use siu\preinscripcion;
use siu\modelo\g3\g3;
use siu\modelo\comunes;

class proyecto extends proyecto_base
{
	protected $m = null;
	protected $punto_acceso = 'alumno';
	
	protected static $rdi;

	function get_session_name()
	{
		$alias = $this->get_alias();
		return "siu_sesspreinscripcion_$alias";
	}

	function memcached()
	{
		if (!isset($this->m)) {
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
		$id_pto_acceso = $_SERVER['ID_PTO_ACCESO'];
		$apc_key = "siu__preinscripcion__mensajes__$id_pto_acceso";

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
		return kernel::proyecto()->get('locale');
	}

	function get_dir_attachment()
	{
		$dir = $this->get('dir_attachment') . '/' . $this->get_bloque_acceso();
		if (!is_dir($dir)) {
			manejador_archivos::crear_arbol_directorios($dir);
		}
		return $dir;
	}
	
	function get_www_temp()
	{
		$dir = $this->get('dir_www_temp');
		if (! is_dir($dir)) {
			manejador_archivos::crear_arbol_directorios($dir);
		}
		return $dir;
	}

	function get_clave_long_minima()
	{
		if ($this->tiene('clave_long_minima')) {
			return $this->get('clave_long_minima');
		}
		return 8;
	}

	function get_cant_max_propuestas_insc()
	{
		return g3::parametros()->get_valor('pre_cant_max_propuestas_preinsc');
	}

	function carga_turno_presentacion()
	{
		if (!$this->tiene('carga_turno_presentacion')) {
			return false;
		}
		return $this->get('carga_turno_presentacion');
	}

	function modo_impresion_comprobante()
	{
		if (!$this->tiene('modo_impresion_comprobante')) {
			return false;
		}
		return $this->get('modo_impresion_comprobante');
	}

	function get_formateo_campos()
	{
		if ($this->tiene('formateo_campos')) {
			return $this->get('formateo_campos');
		}
		return 'libre';
	}

	function get_parametro($param)
	{
		if ($this->tiene($param)) {
			return $this->get($param);
		}
		return '';
	}

	function get_version_sistema()
	{
		return $this->get_parametro('version_sistema');
	}
	
	function usa_rdi()
	{
		return g3::parametros()->get_valor('usa_repositorio_digital') == comunes::si;
	}
	
	function get_parametros_rdi()
	{
		return $this->get('parametros_rdi');
	}
	
	/**
	 * 
	 * @return \siu\modelo\clienteRDI
	 */
	function rdi()
	{
		if (!isset(self::$rdi)){
			self::$rdi = kernel::localizador()->instanciar('modelo\\clienteRDI');
		}
		return self::$rdi->get_cliente();
	}

	//--------------------------------------------------------------------------

	function set_punto_acceso($punto_acceso)
	{
		$this->punto_acceso = $punto_acceso;
	}

	function get_clase_login()
	{
		switch ($this->punto_acceso) {
			case preinscripcion::perfil_admin :
				return $this->get('clase_login_admin');
			case preinscripcion::perfil_gerencial :
				/**
				 * @todo: clase login del perfil gerencial
				 */
				return $this->get('clase_login_admin');
			case preinscripcion::perfil_alumno :
				return $this->get('clase_login');
			default :
				return $this->get('clase_login');
		}
	}

	function get_accesos_bd()
	{
		return $this->get('accesos_bd');
	}

	
	function get_modo_consultas_g3()
	{
		return $this->get('modo_consultas_g3');
	}
	
	function get_parametros_db_gestion()
	{
		return $this->get('db_guarani');
	}

	function get_personalizaciones()
	{
		return $this->get('personalizaciones');
	}

	function get_url_identidad_genero()
	{
		return $this->get('url_identidad_genero');
	}

	function get_email_ayuda()
	{
		return $this->get('email_ayuda');
	}
    
    function get_mat_validacion_renaper()
	{
		return g3::parametros()->get_valor('mat_validacion_renaper');
	}
    
    /**
     * @return string Tiempo de expiración del código validación para recuperar contraseña (sga_preinscripcion.codigo_validacion)
     */
    function get_ttl_codigo_validacion()
    {
		return $this->get_parametro('ttl_codigo_validacion');
    }

}