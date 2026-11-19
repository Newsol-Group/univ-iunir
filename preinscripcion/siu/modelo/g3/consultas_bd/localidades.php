<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\consultas_bd\entidad_g3;
use siu\modelo\g3\interface_localidades;

class localidades extends entidad_g3 implements interface_localidades
{
	protected static $instancia;
	protected $sql = array(
		'select' => 'localidad, nombre, nombre_abreviado',
		'from' => 'mug_localidades',
		'where' => '',
		'order_by' => 'nombre',
		'clave' => 'localidad',
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
	 * Nombre de localidad, provincia y país
	 * Para el caso de la CABA, sólo localidad y país
	 * 
	 * @param $id
	 * @return string
	 */
	function get_nombre($id = null)
	{
		if (is_null($id)) {
			return null;
		}

		$localidad = kernel::db()->quote($id);

		$sql = "SELECT 	
					mug_localidades.localidad,
					CASE WHEN mug_localidades.localidad = '1' -- CABA
						THEN mug_localidades.nombre || ', ' || mug_paises.nombre 
						ELSE mug_localidades.nombre || ', ' || mug_provincias.nombre || ', ' || mug_paises.nombre 
					END AS localidad_descr
				FROM 	
					mug_localidades,
					mug_dptos_partidos,
					mug_provincias,
					mug_paises
				WHERE
					mug_localidades.localidad = $localidad
					AND mug_localidades.dpto_partido = mug_dptos_partidos.dpto_partido
					AND mug_dptos_partidos.provincia = mug_provincias.provincia
					AND mug_provincias.pais = mug_paises.pais";

		$datos = $this->consultar_fila($sql);

		return formatear($datos['localidad_descr']);
	}

	/**
	 * Para el caso de localidades, con esta función se obtiene sólo el nombre
	 * de la localidad
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
	 * Obtiene una lista de localidades a partir de una parte de su nombre y el país.
	 * Para usar principalmente con el autocomplete de localidades.
	 * 
	 * @param string $localidad_nombre
	 * @param int $pais
	 * @return array
	 */
	function get_lista_localidades($localidad_nombre = '', $pais = null)
	{
		$where_localidad = '';

		if (!empty($localidad_nombre)) {
			$localidad_nombre = "'%$localidad_nombre%'";
			$where_localidad .= " AND f_limpiar_acentos(mug_localidades.nombre) ILIKE f_limpiar_acentos($localidad_nombre)::text";
		}

		if (!empty($pais)) {
			$pais = kernel::db()->quote($pais);
			$where_localidad .= " AND mug_paises.pais = $pais";
		}

		$sql = "SELECT 	
					mug_localidades.localidad,
					CASE WHEN mug_localidades.localidad = '1' -- CABA
						THEN mug_localidades.nombre || ', ' || mug_paises.nombre 
						ELSE mug_localidades.nombre || ', ' || mug_provincias.nombre || ', ' || mug_paises.nombre 
					END AS localidad_descr
				FROM 	
					mug_localidades,
					mug_dptos_partidos,
					mug_provincias,
					mug_paises
				WHERE
					mug_localidades.dpto_partido = mug_dptos_partidos.dpto_partido
					AND mug_dptos_partidos.provincia = mug_provincias.provincia
					AND mug_provincias.pais = mug_paises.pais
					$where_localidad
				ORDER BY 
					lower(mug_localidades.nombre),
					lower(mug_provincias.nombre)
				";
		
		return $this->consultar($sql);
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
			$datos['lista_localidades'] = $this->get_lista_localidades('bahia blan', 54);
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