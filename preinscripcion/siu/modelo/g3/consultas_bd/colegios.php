<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\interface_colegios;

class colegios extends entidad_g3 implements interface_colegios
{
	protected static $instancia;
	protected $sql = array(
		'select' => 'sga_colegios_secundarios.colegio, sga_colegios_secundarios.nombre, mug_localidades.nombre AS localidad_nombre',
		'from' => 'sga_colegios_secundarios 
					LEFT JOIN mug_localidades ON mug_localidades.localidad = sga_colegios_secundarios.localidad',
		'where' => '',
		'order_by' => 'nombre',
		'clave' => 'colegio',
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
	 * Nombre de colegio y localidad
	 * 
	 * @param $id
	 * @return string
	 */
	function get_nombre($id = null)
	{
		if (is_null($id)) {
			return null;
		}

		$colegio = kernel::db()->quote($id);

		$sql = "SELECT 
					sga_colegios_secundarios.colegio,
					sga_colegios_secundarios.nombre || ' (' || mug_localidades.nombre || ')' AS colegio_nombre_localidad
				FROM 
					sga_colegios_secundarios,
					mug_localidades
				WHERE
					sga_colegios_secundarios.colegio = $colegio
					AND sga_colegios_secundarios.localidad = mug_localidades.localidad";

		$datos = $this->consultar_fila($sql);

		return formatear($datos['colegio_nombre_localidad']);
	}

	/**
	 * Para el caso de colegios, con esta función se obtiene sólo el nombre de Colegio
	 * 
	 * @param $id
	 * @return string
	 */
	function get_nombre_abreviado($id = null)
	{
		if (is_null($id)) {
			return null;
		}
		$datos = $this->get_entidad($id);
		return formatear($datos[$this->sql['campo_nombre']]);
	}

	/**
	 * Obtiene una lista de colegios a partir de una parte de su nombre y su ciudad.
	 * Para usar con el autocomplete de colegios secundarios.
	 * 
	 * @param string $colegio_nombre
	 * @param int $localidad
	 * @return array
	 */
	function get_colegios($colegio_nombre, $localidad)
	{
		$localidad = kernel::db()->quote($localidad);

		if ($colegio_nombre) {
			$colegio_nombre = "'%$colegio_nombre%'";
			$where_colegio = "AND f_limpiar_acentos(sga_colegios_secundarios.nombre) ILIKE f_limpiar_acentos($colegio_nombre::text)";
		} else {
			$where_colegio = '';
		}

		$sql = "SELECT 
					sga_colegios_secundarios.colegio,
					sga_colegios_secundarios.nombre,
					mug_localidades.nombre AS localidad_nombre
				FROM 
					sga_colegios_secundarios 
					LEFT JOIN mug_localidades ON
						mug_localidades.localidad = sga_colegios_secundarios.localidad
				WHERE 
					sga_colegios_secundarios.localidad = $localidad
					$where_colegio
				";
		
		return $this->consultar($sql);
	}

	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @param $id
	 * @return array
	 */
	function test($id)
	{
		$salida = parent::test($id);
		// Se elimina de la respuesta el listado de opciones para evitar saturar 
		// el log por la gran cantidad de datos
		unset($salida['datos']['opciones']);
		$salida['lista_colegios'] = $this->get_colegios('don bo', 17159);
		return $salida;
	}

}