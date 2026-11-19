<?php

/**
 * Maneja los segmentos de memoria y el proceso de creacion de sesiones
 * @package Seguridad
 */
class toba_manejador_sesiones {

	static private $instanciacion;

	/**
	 * @return toba_manejador_sesiones
	 */
	static function instancia() {
		if (!isset(self::$instanciacion)) {
			self::$instanciacion = new toba_manejador_sesiones();
		}
		return self::$instanciacion;
	}

	private function __construct() {
		
	}

	/**
	 * Determina los perfiles de datos del usuario actual
	 * @return array
	 */
	function get_perfiles_datos() {
		return array();
	}

	/**
	 * Fija cuales son los perfiles de datos activos para el usuario actual, chequea contra configurados en base
	 * @param array $perfiles
	 * @throws toba_error_seguridad
	 */
	function set_perfiles_datos_activos($perfiles) {
		
	}

}