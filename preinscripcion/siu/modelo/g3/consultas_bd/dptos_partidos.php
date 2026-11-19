<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\consultas_bd\entidad_g3;

class dptos_partidos extends entidad_g3
{
	protected static $instancia;
	
	protected $sql = array(
		'select' => 'dpto_partido, nombre',
		'from' => 'mug_dptos_partidos',
		'where' => '',
		'order_by' => 'nombre',
		'clave' => 'dpto_partido',
		'campo_nombre' => 'nombre'
	);

	static function instancia()
	{
		$clase = substr(strrchr(get_class(), "\\"), 1);
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\g3\\consultas_bd\\$clase");
		}
		return self::$instancia;
	}
	
	/**
	 * Se ejecutan las funciones de acceso a datos
	 * 
	 * @return boolean
	 */
	function test($id)
	{
		try {
			$datos = array();
			$datos['entidad'] = $this->get_entidad($id);
			$datos['nombre'] = $this->get_nombre($id);
			$datos['nombre_abreviado'] = $this->get_nombre_abreviado($id);
			$datos['descr_comprobante'] = $this->get_descr_comprobante($id);
			$salida = array(
				'resultado' => true,
				'datos' => $datos,
				'msj' => 'OK',
			);
		} catch (\Exception $ex) {
			$salida = array(
				'resultado' => false,
				'msj' => $ex->getMessage(),
			);
		}
		return $salida;
	}

}
?>