<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\consultas_bd\entidad_g3;
use siu\modelo\g3\interface_paises;
use siu\preinscripcion;

class paises extends entidad_g3 implements interface_paises
{

        const ARGENTINA = 54;

	protected static $instancia;
	
	protected $sql = array(
		'select' => 'pais, nombre',
		'from' => 'mug_paises',
		'where' => '',
		'order_by' => 'nombre',
		'clave' => 'pais',
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
	 * Determina si, de acuerdo a lo configurado en G3, un país requiere visa
	 * 
	 * @param int $pais
	 * @return boolean
	 */
	function necesita_visa($pais)
	{
		$datos = $this->get_datos_paises_hab($pais);
		return (!empty($datos['necesita_visa'])) && ($datos['necesita_visa'] == preinscripcion::si);
	}
	
	/**
	 * Determina si, de acuerdo a lo configurado en G3, un país permite cédula 
	 * 
	 * @param int $pais
	 * @return boolean
	 */
	function permite_cedula($pais)
	{
		$datos = $this->get_datos_paises_hab($pais);
		return (!empty($datos['permite_cedula'])) && ($datos['permite_cedula'] == preinscripcion::si);
	}
	
	/**
	 * Obtiene los datos de configuración de un país a partir de la tabla mdp_paises_habilitados
	 * 
	 * @param int $pais
	 * @return array
	 */
	protected function get_datos_paises_hab($pais)
	{
		$pais = kernel::db()->quote($pais);

		$sql = "SELECT 
					pais,
					permite_cedula,
					necesita_visa
				FROM 
					mdp_paises_habilitados
				WHERE
					pais = $pais
				";
		
		return $this->consultar_fila($sql);
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
			$datos['necesita_visa'] = $this->necesita_visa($id);
			$datos['permite_cedula'] = $this->permite_cedula($id);
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