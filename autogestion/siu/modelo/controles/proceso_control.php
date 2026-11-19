<?php
namespace siu\modelo\controles;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use SIU\Chulupi\util\cache\AdminCache;
use SIU\Chulupi\util\log;
use siu\guarani;
use siu\errores\error_guarani_control;
use siu\modelo\datos\catalogo;
use siu\modelo\datos\util;
use siu\errores\error_guarani;

class proceso_control
{
	static $controles_activos = true;
	static $usar_cache = true;
	// Mensajes
	protected $mensajes = array();
	// Estado
	protected $estado = \guarani_punto_de_control::autorizado;

	static function activar($activado = true)
	{
		kernel::log()->add_debug('ACTIVAR CONTROLES', $activado);
		self::$controles_activos = $activado;
	}

	static function usar_cache($activado = true)
	{
		kernel::log()->add_debug('ACTIVAR CACHE', $activado);
		self::$usar_cache = $activado;
	}

	static function get_peor_resultado($resultados = array())
	{
		$peor_resultado = \guarani_punto_de_control::autorizado;
		foreach ($resultados as $resultado) {
			switch ($resultado) {
				case \guarani_punto_de_control::rechazado:
					return \guarani_punto_de_control::rechazado;
				case \guarani_punto_de_control::advertido;
					$peor_resultado = \guarani_punto_de_control::advertido;
					break;
			}
		}
		return $peor_resultado;
	}

	function __construct($puntos_control, $parametros, $operacion)
	{
		$this->validador = guarani::validador_puntos_de_control($puntos_control, $parametros, $operacion);
	}

	function ejecutar()
	{
		if (!self::$controles_activos) {
			return array();
		}
		try {
			$this->estado = $this->validador->controlar();
		} catch (\guarani_error $e) {
			throw new error_guarani($e->get_mensaje());
		}

		kernel::log()->add_debug('########### ESTADO DE LA EJECUCION DE LOS PUNTOS ##########', $this->estado);
		if ($this->estado == \guarani_punto_de_control::rechazado) {
			$e = new error_guarani(kernel::traductor()->trans('error_ejecutando_controles'));
			$e->add_mensajes_usuario($this->get_mensajes());
			throw $e;
		}
		return $this->estado;
	}

	function get_mensajes()
	{
		if (!empty($this->mensajes)) {
			return $this->mensajes;
		}
		$this->mensajes = array();
		foreach ($this->validador->get_mensajes() as $mensaje) {
			$this->mensajes[] = guarani::crear_mensaje($this->convertir_error_g3_a_g3w($mensaje['tipo']), $mensaje['mensaje']);
		}
		return $this->mensajes;
	}

	/**
	 * @ToDo Agregar esto a una clase comun en modelo_g3
	 */
	protected function convertir_error_g3_a_g3w($error_g3)
	{
		switch ($error_g3) {
			case 'error': return guarani::control_error;
			case 'warning': return guarani::control_advertencia;
			case 'info': return guarani::control_info;
		}
		throw new error_guarani("el tipo de error '$error_g3' no existe en g3w");
	}

	function get_estado()
	{
		return $this->estado;
	}

	function hay_advertencias()
	{
		if (!empty($this->mensajes)) {
			foreach ($this->mensajes as $msg) {
				if ($msg[guarani::mensaje_tipo] == guarani::control_advertencia) {
					return true;
				}
			}
		}
		return false;
	}

}
?>
