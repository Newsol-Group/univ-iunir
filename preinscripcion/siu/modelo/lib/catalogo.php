<?php

namespace siu\modelo\lib;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\cache\AdminCache as admin_cache;
use SIU\Chulupi\util\cache\cache;
use siu\modelo\util;
use siu\bootstrap;

class catalogo
{
	const id = '__preinsc_ID__';

	protected $usar_cache = true;
	protected $tiempo_cache = 300; // En segundos (por defecto: 5  minutos)

	/**
	 * Establecer el uso de caché
	 * 
	 * @param boolean $activo
	 */
	function usar_cache($activo = true)
	{
		$this->usar_cache = $activo;
	}

	/**
	 * Consulta para saber si se usa caché
	 * 
	 * @return boolean
	 */
	function usa_cache()
	{
		return $this->usar_cache;
	}

	/**
	 * 
	 * @return cache
	 */
	function cache()
	{
		return admin_cache::instancia(admin_cache::tipo_memoria);
	}

	/**
	 * Establecer el tiempo de vida por defecto de un dato en caché
	 * 
	 * @param int $tiempo valor expresado en segundos
	 */
	function set_tiempo_cache($tiempo)
	{
		$this->tiempo_cache = $tiempo;
	}

	/**
	 * Genera una clave única para un valor dado, concatenando:
	 * ID de punto de acceso + ID de persona + SALT + parámetro recibido
	 * 
	 * @param $clave
	 * @param $personal	Si es true, se concatena también el ID de persona
	 * @return string
	 */
	function generar_id($clave, $personal = false)
	{
		$salt = kernel::proyecto()->get('salt');
		$id = ($personal) ? kernel::persona()->get_id() . '_' : '';
		$id .= $_SERVER['ID_PTO_ACCESO'] . '_' . $salt . '_' . $clave;
		return sha1($id);
	}

	//--------------------------------------------------------------------------
	//---- Manejo de cache -----------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Borra la caché del sistema
	 * 
	 * @return boolean
	 */
	function limpiar_cache()
	{
		$path_clearapc = bootstrap::get_dir_siu() . '/www/clearapc.php';
		file_put_contents($path_clearapc, "
			<?php
				try {
					apcu_clear_cache();
					echo json_encode(array('success' => true));
				} catch (Exception \$e) {
					echo json_encode(array('success' => false));
				}
			?>");
		$alias = kernel::proyecto()->get_alias();
		$url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $alias . "/clearapc.php";
		$result = json_decode(file_get_contents($url));
		unlink($path_clearapc);
		return $result->success;
	}
	
	/**
	 * Determina si existe una clave en caché
	 * 
	 * @param string $clave
	 * @return boolean
	 */
	function existe($clave)
	{
		return $this->cache()->existe($clave);
	}

	/**
	 * Obtiene un valor almacenado en caché
	 * 
	 * @param string $clave
	 * @return array
	 */
	function buscar($clave)
	{
		return $this->cache()->buscar($clave);
	}

	/**
	 * Almacena un valor en caché
	 * 
	 * @param string $clave
	 * @param array $valor
	 */
	function guardar($clave, $valor, $tiempo_cache = 0)
	{
		$this->cache()->guardar($clave, $valor, $tiempo_cache);
	}

	//--------------------------------------------------------------------------
	//---- Manejo de consultas -------------------------------------------------
	//--------------------------------------------------------------------------

	function consultar($sql, $clave_cache = null)
	{
		if ($this->usar_cache) {
			if (is_null($clave_cache)) {
				$clave_cache = $this->generar_id($sql);
			}
			if ($this->existe($clave_cache)) {
				$datos = $this->buscar($clave_cache);
				kernel::log()->add_debug('CATALOGO', 'BUSCAR: ' . $clave_cache . ' -- ' . $sql);
			} else {
				$datos = kernel::db()->consultar($sql);
				$this->guardar($clave_cache, $datos);
				kernel::log()->add_debug('CATALOGO', 'GUARDAR: ' . $clave_cache . ' -- ' . $sql);
			}
		} else {
			$datos = kernel::db()->consultar($sql);
		}
		return $datos;
	}

	function consultar_fila($sql, $clave_cache = null)
	{
		$datos = $this->consultar($sql, $clave_cache);
		if (isset($datos[0])) {
			return $datos[0];
		}
		return array();
	}

	function invalidar($clave)
	{
		$id = $this->generar_id($clave);
		$this->cache()->eliminar($id);
		kernel::log()->add_debug('CATALOGO', 'INVALIDAR: ' . $id . ' -- ' . $clave);
	}

	function invalidar_por_id($id)
	{
		$this->cache()->eliminar($id);
		kernel::log()->add_debug('CATALOGO', 'INVALIDAR: ' . $id . ' -- ' . $id);
	}

	//--------------------------------------------------------------------------
	//---- Manejo de configuración de campos del formulario --------------------
	//--------------------------------------------------------------------------

	function get_configuracion_formulario($tipos_propuestas)
	{
		if ($this->usar_cache) {
			$clave_config = $this->generar_clave_configuracion_formulario($tipos_propuestas);
			$clave_cache = $this->generar_id($clave_config);
			if ($this->existe($clave_cache)) {
				$configuracion = $this->buscar($clave_cache);
				kernel::log()->add_debug('CATALOGO', 'BUSCAR CONFIGURACIÓN FORM: ' . $clave_cache . ' -- ' . $clave_config);
			} else {
				$configuracion = $this->obtener_configuracion_formulario($tipos_propuestas);
				$this->guardar($clave_cache, $configuracion);
				kernel::log()->add_debug('CATALOGO', 'GUARDAR CONFIGURACIÓN FORM: ' . $clave_cache . ' -- ' . $clave_config);
			}
		} else {
			$configuracion = $this->obtener_configuracion_formulario($tipos_propuestas);
		}
		return $configuracion;
	}

	protected function generar_clave_configuracion_formulario($tipos_propuestas)
	{
		return implode('__', $tipos_propuestas);
	}

	protected function obtener_configuracion_formulario($tipos_propuestas)
	{
		// Se obtiene la configuración del primer tipo de propuestas
		$primer_tipo = current($tipos_propuestas);
		$datos = util::consultas()->get_parametros_campos_form($primer_tipo);
		$parametros_campos = array();
		foreach ($datos as $dato) {
			$parametros_campos[$dato['columna']] = array('visible' => $dato['visible'], 'obligatorio' => $dato['obligatorio'], 'se_imprime' => $dato['se_imprime']);
		}
		// En caso de que haya más de un tipo de propuesta, se obtiene la configuración
		// final como la unión lógica de los conjuntos
		for ($i = 1; $i < count($tipos_propuestas); $i++) {
			$datos = util::consultas()->get_parametros_campos_form($tipos_propuestas[$i]);
			foreach ($datos as $dato) {
				$parametros_campos[$dato['columna']]['visible'] = $parametros_campos[$dato['columna']]['visible'] || $dato['visible'];
				$parametros_campos[$dato['columna']]['obligatorio'] = $parametros_campos[$dato['columna']]['obligatorio'] || $dato['obligatorio'];
				$parametros_campos[$dato['columna']]['se_imprime'] = $parametros_campos[$dato['columna']]['se_imprime'] || $dato['se_imprime'];
			}
		}

		return $parametros_campos;
	}

}
?>