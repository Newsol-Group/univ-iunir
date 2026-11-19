<?php

use SIU\Chulupi\util\cache\AdminCache;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\ManejadorArchivos;
use siu\bootstrap;
use siu\modelo\datos\catalogo;

class toba_consulta
{
	const prefijo_memcache = 'catalogo_g3__';
	const cache_config_expiracion = 'exp';
	const CACHE_EXPIRATION_MAX = 300; // 5 minutos
	
	protected $clase_co;
	protected $co;
	protected $usar_cache;
	protected $metadata;
	
	static $global_usar_cache = false;
	static $global_tipo_cache = false;
	
	// Valores que no se mostrarán en los logs
	static $no_log = [
		'co_configuracion/get_plataformas_educacion_virtual' => ['token'],
		'co_configuracion/get_plataforma_educacion_virtual' => ['token'],
		'co_configuracion/get_plataforma_responsable_academica' => ['token'],
		'co_configuracion/get_usuarios_by_persona' => ['token'],
		'co_comisiones/get_curso_virtual_by_comision' => ['plataforma_token'],
		'co_comisiones/get_plataformas_by_comision' => ['token'],
		'co_mesas_de_examen/get_plataformas_virtuales_llamado_mesa' => ['token'],
		'co_mesas_de_examen/get_curso_virtual_llamado_mesa' => ['plataforma_token'],
	];
	
	function __construct($co, $usar_cache=false, $metadata_cache=null)
	{
		$this->clase_co = $co;
		
		if (guarani_clases_consultas_nuevas::existe_clase($co)) {
			list($clase, $path) = guarani_clases_consultas_nuevas::consulta($co);
		} else {
			list($clase, $path) = g3w_clases_consultas::consulta($co);
		}
		
		if (!class_exists($clase, false)) {
			kernel::log()->add_debug("############# se incluyo la clase $clase del co $co ##############", $path);
			include_once(ManejadorArchivos::path_a_plataforma(dirname(__FILE__)."/".$path));
		}
	
		$this->co = new $clase();
		$this->usar_cache = $usar_cache;
		$this->metadata = $metadata_cache;
	}
	
	private function cache()
	{
		$tipo_cache = AdminCache::tipo_memoria;
		$cache = AdminCache::instancia($tipo_cache);
		return $cache;
	}
	
	function __call($metodo, $parametros) 
	{
		$usar_cache = $this->usar_cache; // mezclar con global
		kernel::log()->add_debug('############# TOBA_CONSULTA ADAPTER ##############',
										'metodo: ' . $metodo . ' - cache: ' . var_export($usar_cache,true));
		if($usar_cache){
			$cache = $this->cache();
			$id_cache = $this->generar_id_cache($this->clase_co, $metodo, $parametros);
			if($cache->existe($id_cache)){
				kernel::log()->add_debug('## CARGAR CACHE ##', $id_cache);
				$datos = $cache->buscar($id_cache);
			} else {
				kernel::log()->add_debug('## CARGAR DB a CACHE ##', $id_cache);
				$datos = call_user_func_array(array($this->co, $metodo), $parametros);
				//-- crear CACHE
				if($cache->get_tipo()== AdminCache::tipo_memoria){
					$cache->guardar($id_cache, $datos, self::CACHE_EXPIRATION_MAX);
				} else {
					$cache->guardar($id_cache, $datos);
				}
			}
		} else {
			kernel::log()->add_debug('## CARGAR DB ##', $metodo);
			$datos = call_user_func_array(array($this->co, $metodo), $parametros);
		}
		kernel::log()->add_debug('## datos ##', static::procesar_datos_debug("{$this->clase_co}/$metodo", $datos));
		return $datos;
	}

	private function generar_id_cache($clase, $metodo, $parametros) {
		$separador = '__';
		$separador_array = '_';
		$param = '';
		if (empty($parametros)) {
			$clave = '_nopar_';
		} else {
			foreach ($parametros as $key => $param) {
				if (is_array($param)) {
					$parametros[$key] = implode($separador_array, $param);
				}
			}
			ksort($parametros); //ordenar
			$param = implode($separador_array, $parametros);
			$param = base64_encode($param);
			$param = str_replace(array('/', '+', '='), '_', $param);
		}
		$clave = self::prefijo_memcache . $separador . bootstrap::instancia()->get_id_bloque_acceso() . $separador . $clase . $separador . $metodo . $separador . $param;
		return $clave;
	}
	
	static private function procesar_datos_debug($funcion, $datos)
	{
		// Si tiene parámetros con 'no_log'
		if (isset(static::$no_log[$funcion])) {
			$datos = catalogo::procesar_datos_debug($datos, static::$no_log[$funcion]);
		}

		return $datos;
	}

}