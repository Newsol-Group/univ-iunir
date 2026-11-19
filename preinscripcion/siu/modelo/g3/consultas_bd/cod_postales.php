<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\consultas_bd\entidad_g3;
use siu\modelo\util;

class cod_postales extends entidad_g3
{
	protected static $instancia;
	
	protected $sql = array(
		'select' => 'localidad, codigo_postal',
		'from' => 'mug_cod_postales',
		'where' => '',
		'order_by' => 'codigo_postal',
		'clave' => 'localidad, codigo_postal',
		'campo_nombre' => 'codigo_postal'
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
	 * Listado de opciones.
	 * 
	 * @param string $condiciones
	 * @param boolean $con_no_seleccion
	 */
	function get_opciones($condiciones = null, $con_no_seleccion = true)
	{
		$sql = $this->armar_sql($condiciones);
		$datos = $this->consultar($sql);
		return util::opciones()->armar_combo_opciones($datos, 'codigo_postal', 'codigo_postal', $con_no_seleccion);
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