<?php
/**
 * Datos para combos básicos y comunes
 *
 *
 * @package Centrales
 * @subpackage Módulo de Ubicación Geográfica (MUG)
 */

class co_mug_nsg extends co_mug
{
	/**	Newsol (NUEVO) - Se usa en el reporte de alumnos por localidad
		Retorna las localidades para un pais y provincia dada.
	*/
	function get_localidades_provincia_pais($provincia=null, $localidad=null)
	{
		$where = '';
					
		if (!is_null($provincia)) {
			$provincia = toba::db()->quote($provincia);
			$where .= " AND mug_provincias.provincia = $provincia";
		}

		if (isset($localidad)) {
			$localidad = toba::db()->quote($localidad);
		}

		$sql = "SELECT		mug_localidades.localidad as valor,
					mug_localidades.nombre as descr
				FROM		mug_localidades, 
						mug_dptos_partidos,
						mug_provincias,
						mug_paises
				WHERE 		mug_provincias.pais = mug_paises.pais
				AND		mug_provincias.provincia = mug_dptos_partidos.provincia
				AND		mug_dptos_partidos.dpto_partido = mug_localidades.dpto_partido
						$where
				ORDER BY	mug_localidades.nombre
		";
		return guarani_db::consultar($sql);
	}

}
?>