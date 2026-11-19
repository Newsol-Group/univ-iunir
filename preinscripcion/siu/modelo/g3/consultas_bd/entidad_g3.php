<?php

namespace siu\modelo\g3\consultas_bd;

use siu\modelo\g3\interface_entidades_g3;
use siu\modelo\util;

/**
 * Implementación de la interfaz de acceso a datos de G3 interface_entidades_g3
 * a través de consultas a la base de datos de negocio de Guaraní.
 */
abstract class entidad_g3 extends _consultas_bd implements interface_entidades_g3
{
	/**
	 * Datos de los bloques para armar las distintas querys para abastecer con 
	 * datos a las funciones de la interfaz implementada.
	 * 
	 * A completar por cada entidad concreta.
	 * 
	 * @var array
	 */
	protected $sql = array(
		'select' => '',
		'from' => '',
		'where' => '',
		'order_by' => '',
		'clave' => '',
		'campo_nombre' => ''
	);
	
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
		return util::opciones()->armar_combo_opciones($datos, $this->sql['clave'], $this->sql['campo_nombre'], $con_no_seleccion);
	}

	/**
	 * Información completa de una entidad particular
	 * 
	 * @param $id
	 * @return array
	 */
	function get_entidad($id)
	{
		$sql = $this->armar_sql('', $id);
		return $this->consultar_fila($sql);
	}
	
	/**
	 * Determina si un ID corresponde a un valor existente
	 * 
	 * @param $id
	 * @return boolean
	 */
	function existe($id)
	{
		return !empty($this->get_entidad($id));
	}

	/**
	 * Nombre o campo descriptivo de una entidad particular
	 * 
	 * @param $id
	 * @return string
	 */
	function get_nombre($id = null)
	{
		$datos = $this->get_entidad($id);
		return formatear($datos[$this->sql['campo_nombre']]);
	}
	
	/**
	 * Nombre o campo descriptivo abreviado de una entidad particular
	 * Si la entidad no lleva registro de nombre abreviado, toma el nombre general
	 * 
	 * @param $id
	 * @return string
	 */
	function get_nombre_abreviado($id = null)
	{
		return formatear($this->get_nombre($id));
	}

	/**
	 * Nombre o descripción a mostrar en el comprobante PDF. Por defecto se 
	 * retrona el nombre, pero se deja la ventana de extensión por si se necesita
	 * una descripción diferente.
	 * 
	 * @param $id
	 * @return string
	 */
	function get_descr_comprobante($id)
	{
		return formatear($this->get_nombre($id));
	}

	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @param $id
	 * @return array
	 */
	function test($id)
	{
		try {
			$datos = array();
			$datos['opciones'] = $this->get_opciones();
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