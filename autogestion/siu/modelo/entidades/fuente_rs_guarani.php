<?php

namespace siu\modelo\entidades;

use SIU\Chulupi\util\cache\AdminCache;
use SIU\Chulupi\util\modelo;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\errores\error_guarani_seguridad;
use siu\guarani;

class fuente_rs_guarani implements modelo\FuentePersistencia
{

	const TIPO_ADMIN_CACHE = AdminCache::tipo_memoria;
	protected static $clave_sesion = 'cons-pers-key';
	protected static $sal_sesion;
	protected static $cache_expiration = 3600;

	/**
	 * Atención, la persistencia por default solo sirve para dependencias dentro
	 * del mismo objeto. Para relaciones entre operaciones usar rs_consulta.
	 * @param modelo\PersistidorRs $pers
	 * @param string $id
	 * @return modelo\recordset|mixed|null|rs_consulta
	 * @throws \siu\errores\error_guarani_seguridad
	 */
	function get_recordset(modelo\PersistidorRs $pers, $id)
	{
		$clave_usuario = $this->generar_clave_para_usuario($id);
		$this->validar_clave_para_usuario($clave_usuario);

		$objeto = $pers->fuente_objeto;
		$funcion = $pers->fuente_funcion;
		$destino = $pers->destino_funcion;
		$destino_controlador = $pers->destino_clase;

		$params = $this->recuperar_funcion($clave_usuario, $funcion);

		$tipo = $this->validar_funcion_recuperada($params, $destino, $destino_controlador);

		return $this->regenerar_instancia_rs($tipo, $objeto, $params);
	}

	function persistir_recordset(modelo\PersistidorRs $pers)
	{
		$rs = $pers->recordset;
//		$objeto = $pers->fuente_objeto;
		$funcion = $pers->fuente_funcion;
		$args = $pers->fuente_argumentos;

		$sal_base = $this->get_sal_base();
		$sal_total = sha1($pers->destino_funcion . $pers->destino_clase . $sal_base);

		if ($rs instanceof modelo\persistible) {
			/* @var $rs modelo\persistible */
			$clave = $rs->get_id_unico($sal_total);
			$params = $rs->get_parametros_para_guardar();
		} else {
			list($clave, $params) = $this->persistir_default($funcion, $args, $sal_total);
		}

		$params['tipo_'] = get_class($rs);
		$params['accion_destino__'] = $pers->destino_funcion;
		$params['controlador_destino__'] = $pers->destino_clase;

		$clave_usuario = $this->generar_clave_para_usuario($clave);
		$this->guardar_en_cache($clave_usuario, $params);

		kernel::log()->add_debug("Recordset - persistir $funcion clave[$clave]", $params);
		return $clave;
	}

	//------------------------------------------------------------------------------------
	// Configuraciones
	//------------------------------------------------------------------------------------
	/**
	 * @param $tipo
	 * @param $objeto
	 * @param $params
	 * @return null|modelo\recordset
	 */
	protected function regenerar_instancia_rs($tipo, $objeto, $params)
	{
		switch ($tipo) {
			case 'siu\modelo\entidades\rs_consulta':
			{
				$instancia = rs_consulta::regenerar_instancia($objeto, $params);
				return $instancia;
			}
			case 'siu\modelo\entidades\rs_arreglo':
			{
				$instancia = $this->regenerar_instancia_default($objeto, $params);
				return $instancia;
			}
		}
		return null;
	}

	/**
	 * @return \SIU\Chulupi\util\cache\cache
	 */
	protected function get_cache()
	{
		return AdminCache::instancia(self::TIPO_ADMIN_CACHE);
	}

	protected function error_seguridad()
	{
		throw new error_guarani_seguridad("Opcion inexistente");
	}

	//------------------------------------------------------------------------------------
	// FIN Configuraciones
	//------------------------------------------------------------------------------------


	protected function regenerar_instancia_default($objeto, $params)
	{
		return modelo\FuncionPersistible::regenerar_instancia($objeto, $params);
	}

	protected function get_sal_base()
	{
		return kernel::proyecto()->get('salt');
	}

	protected function persistir_default($funcion, $args, $sal)
	{
		$funcion_persistible = new modelo\FuncionPersistible($funcion, $args);
		$clave = $funcion_persistible->get_id_unico($sal);
		$params = $funcion_persistible->get_parametros_para_guardar();
		return array($clave, $params);
	}

	protected function generar_clave_para_usuario($clave)
	{
		$us = guarani::persona()->get_id();
		return $us . "_" . $clave;
	}

	protected function validar_clave_para_usuario($id)
	{
		$partes = explode("_", $id);
		$persona_guardada = $partes[0];
		$persona_actual = guarani::persona()->get_id();
		if ($persona_guardada != $persona_actual) {
			kernel::log()->add_error(new error_guarani("Error recordset - No se encontro parametro para la persona. Persona
			actual es $persona_actual, y persona guardada es $persona_guardada"));
			$this->error_seguridad();
		}
	}

	/**
	 * @param $id
	 * @param $funcion
	 * @return array
	 */
	protected function recuperar_funcion($id, $funcion)
	{
		$params = $this->recuperar_de_cache($id, false);
		kernel::log()->add_debug("Recordset - recuperar $funcion - $id", $params);

		if ($params === false) {
			kernel::log()->add_error(new error_guarani("Error Recordset - No se encontro recordset funcion: $funcion id: $id. " .
			" No se guardo correctamente la consulta, se vencio la sesion, o se manipulo la primera parte del parametro "));
			$this->error_seguridad();
			return $params;
		}
		return $params;
	}

	/**
	 * @param $params
	 * @param $destino
	 * @param $destino_controlador
	 * @return string el tipo de recordset guardado para poder recuperarlo adecuadamente
	 */
	protected function validar_funcion_recuperada($params, $destino, $destino_controlador)
	{
		$tipo = $params['tipo_'];
		$nombre = $params['accion_destino__'];
		$controlador = $params['controlador_destino__'];
		$destinos = print_r($destino, true);
		if ($destino != '*' && (!in_array($nombre, $destino) || $controlador != $destino_controlador)) {
			kernel::log()->add_error(new error_guarani("Error Recordset - Se pidio un parametro para '$destino_controlador::$destinos', pero la consulta " .
			"recuperada tiene destino '$controlador::$nombre'. Crear un vinculo para esa operacion, o establecer un alias para que la misma acepte parametros de '$nombre'"));
			$this->error_seguridad();

		}
		return $tipo;
	}

	/**
	 * @param $clave
	 * @param $params
	 * @return void
	 */
	protected function guardar_en_cache($clave, $params)
	{
		$cache = $this->get_cache();

		$cache->guardar($clave, $params, self::$cache_expiration);
	}

	/**
	 * @param $clave
	 * @param mixed $default en caso de que no exista la clave
	 * @return array|mixed el arreglo o el default
	 */
	protected function recuperar_de_cache($clave, $default = false)
	{
		$cache = $this->get_cache();
		if ($cache->existe($clave))
			return $cache->buscar($clave);
		return $default;
	}


}

?>
