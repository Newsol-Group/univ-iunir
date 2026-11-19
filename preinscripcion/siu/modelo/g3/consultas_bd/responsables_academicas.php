<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\interface_responsables_academicas;
use siu\modelo\util;

class responsables_academicas extends entidad_g3 implements interface_responsables_academicas
{
	protected static $instancia;
	
	protected $tiempo_cache = 30;
	
	protected $sql = array(
		'select' => 'responsable_academica, nombre, codigo',
		'from' => 'sga_responsables_academicas',
		'where' => '',
		'order_by' => 'nombre',
		'clave' => 'responsable_academica',
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
	 * Listado de opciones.
	 * 
	 * @param string $condiciones
	 * @param boolean $con_no_seleccion
	 */
	function get_opciones($condiciones = null, $con_no_seleccion = true)
	{
		$sql = $this->armar_sql($condiciones);
		$datos = $this->consultar($sql);
		return util::opciones()->armar_combo_opciones($datos, $this->sql['clave'], $this->sql['campo_nombre'], $con_no_seleccion, false);
	}
	
	/**
	 * Obtiene todas las Responsables Académicas sin perfil de datos
	 * 
	 * @param string $condiciones
	 * @return array
	 */
	function get_opciones_sin_perfil_datos($condiciones = null)
	{
		$sql = $this->armar_sql($condiciones, null, false);
		$datos = $this->consultar($sql);
		return util::opciones()->armar_combo_opciones($datos, $this->sql['clave'], $this->sql['campo_nombre'], false, false);
	}
	
	protected function filtro_perfil_datos()
	{
		$config = util::consultas()->get_config_perfil_datos();
		if (!empty($config['ra'])) {
			$filtro = "responsable_academica IN ('" . implode("', '", $config['ra']) . "')";
		} else {
			$filtro = '';
		}
		return $filtro;
	}
	
	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @param $id
	 * @return array
	 */
	function test($id)
	{
		// Se ejecuta el test general
		$resultado = parent::test($id);
		
		// Si falla el test general, no se continúa con el test particular
		if (!$resultado['resultado']) {
			return $resultado;
		}
		
		// Se testean las funciones particualres
		try {
			$datos = $resultado['datos'];
			$datos['opciones_sin_pd'] = $this->get_opciones_sin_perfil_datos();
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