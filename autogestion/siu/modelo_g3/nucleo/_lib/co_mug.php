<?php
/**
 * Datos para combos básicos y comunes
 *
 *
 * @package Centrales
 * @subpackage Módulo de Ubicación Geográfica (MUG)
 */

class co_mug
{
	/**
		Retorna el listado de países.
	*/
	function get_paises($pais=null, $where='')
	{
		$filtro = 'WHERE true';
		if (!is_null($pais)) {
			$pais = toba::db()->quote($pais);
			$filtro .= " AND mug_paises.pais = $pais"; 
		}
		if ($where) {
			$filtro .= " AND $where";
		}
		$sql = "SELECT		pais as valor,
							nombre as descr,
							nombre || ' - (' || pais || ')'  as descr2
				FROM		mug_paises
				$filtro
				ORDER BY	nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
		Retorna las provincias para un pais dado.
	*/
	function get_provincias_pais($pais=null)
	{
		$where = '';
		if (!is_null($pais)) {
			$pais = toba::db()->quote($pais);
			$where .= " AND mug_paises.pais = $pais";
		}
		$sql = "SELECT		mug_provincias.provincia as valor,
							mug_provincias.nombre as descr
				FROM		mug_provincias,
							mug_paises
				WHERE 		mug_provincias.pais = mug_paises.pais
							$where
				ORDER BY	mug_provincias.nombre
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 *	Retorna las localidades para una provincia dada.
	 */
	function get_localidades_provincia($provincia=null)
	{
		$where = '';
		if (isset($provincia)) {
			$provincia = toba::db()->quote($provincia);
			$where .= " WHERE vw_localidades.provincia = $provincia";
		}
		$sql = "
			SELECT	vw_localidades.localidad as valor,
					vw_localidades.localidad_nombre AS descr
			FROM	vw_localidades
			$where
			ORDER BY vw_localidades.localidad_nombre;
		";
		return guarani_db::consultar($sql);
	}

	/**
		Obtiene la clave y descripción del país al que pertenece una localidad dada.
	*/
	function get_localidad_pais($id=null)
	{
		if (!isset($id) || trim($id) == '') {
			return '';
		}
		$id = toba::db()->quote($id);
		$sql = "SELECT	mug_paises.pais as valor,
  						mug_paises.nombre as descr
				FROM	mug_localidades,
 						mug_dptos_partidos,
 						mug_provincias,
 						mug_paises
				WHERE	mug_localidades.dpto_partido = mug_dptos_partidos.dpto_partido AND
						mug_dptos_partidos.provincia = mug_provincias.provincia AND
						mug_provincias.pais = mug_paises.pais AND
						mug_localidades.localidad = $id
		";
		return guarani_db::consultar_fila($sql);
	}

	/**
		Obtiene la clave y descripción de una localidad a partir del id dado para el ef_popup de Localidad.
	*/
	static function get_localidad($id=null)
	{
		if (! isset($id) || trim($id) == '') {
			return array();
		}
		$id = toba::db()->quote($id);
		$sql = "SELECT	mug_localidades.localidad as valor,
						mug_localidades.nombre || ' - ' || mug_dptos_partidos.nombre || ' - ' || mug_provincias.nombre || ' - ' || mug_paises.nombre as descr
				FROM	mug_localidades,
						mug_dptos_partidos,
						mug_provincias,
						mug_paises
				WHERE	mug_localidades.dpto_partido = mug_dptos_partidos.dpto_partido AND
						mug_dptos_partidos.provincia = mug_provincias.provincia AND
						mug_provincias.pais = mug_paises.pais AND
						mug_localidades.localidad = $id
		";
		$result = guarani_db::consultar($sql);
		if (!empty($result)) {
			return $result[0]['descr'];
		}
	}

	/**
		Retorna un listado de localidades con sus departamentos, provincias y países asociados.
	*/
	function get_listado_localidades($con_CP, $filtro='')
	{   
		$where = '';
		$select = '';
		$valor_cp = '';
		$from = '';

		if ($filtro != '') {
			$where .= " AND $filtro";
		}

		if ($con_CP) {
			$select = ' DISTINCT ON (mug_localidades.nombre,mug_localidades.localidad) ';
			$valor_cp = " || ':' || COALESCE(mug_cod_postales.codigo_postal, '') ";
			$from = ' LEFT OUTER JOIN mug_cod_postales ON mug_localidades.localidad = mug_cod_postales.localidad ';
		}

		$sql = "SELECT	$select
						mug_localidades.localidad $valor_cp as localidad_valor,
						mug_localidades.localidad,
						mug_localidades.nombre as localidad_nombre,
						mug_paises.pais as pais_valor,
						mug_paises.nombre as pais_nombre,
						mug_provincias.provincia as provincia_valor,
						mug_provincias.nombre as provincia_nombre,
						mug_dptos_partidos.dpto_partido as departamento_valor,
						mug_dptos_partidos.nombre as departamento_nombre,
						mug_localidades.nombre || ' - ' || mug_dptos_partidos.nombre || ' - ' || mug_provincias.nombre || ' - ' || mug_paises.nombre as identificador_localidad
				FROM	mug_localidades
						$from,
						mug_dptos_partidos,
						mug_provincias,
						mug_paises
				WHERE	mug_localidades.dpto_partido = mug_dptos_partidos.dpto_partido
						AND	mug_dptos_partidos.provincia = mug_provincias.provincia
						AND	mug_provincias.pais = mug_paises.pais
						$where
				ORDER BY localidad_nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
		Retorna los codigos postales de la localidad dada.
	*/
	function get_codigos_postales($localidad=null)
	{  
		$where = '';
		if (isset($localidad)) {
			$localidad = toba::db()->quote($localidad);
			$where = " AND mug_cod_postales.localidad = $localidad ";
		}

		$sql = "SELECT	mug_cod_postales.localidad,
						mug_cod_postales.codigo_postal,
						mug_cod_postales.localidad || ':' || mug_cod_postales.codigo_postal as localidad_valor,
						mug_localidades.nombre as localidad_nombre,
						mug_localidades.nombre || ' - ' || mug_dptos_partidos.nombre || ' - ' || mug_provincias.nombre || ' - ' || mug_paises.nombre as identificador_localidad
				FROM	mug_cod_postales,
						mug_localidades,
						mug_dptos_partidos,
						mug_provincias,
						mug_paises
				WHERE	mug_cod_postales.localidad = mug_localidades.localidad
						AND mug_localidades.dpto_partido = mug_dptos_partidos.dpto_partido
						AND mug_dptos_partidos.provincia = mug_provincias.provincia
						AND mug_provincias.pais = mug_paises.pais 
						$where
				ORDER BY mug_cod_postales.codigo_postal
		";
		return guarani_db::consultar($sql);
	}
	
	function get_departamentos_provincia($provincia)
	{
		$provincia = toba::db()->quote($provincia);
		
		$sql = "SELECT 
					mug_dptos_partidos.dpto_partido, 
					mug_dptos_partidos.nombre 
				FROM 
					mug_dptos_partidos 
				WHERE 
					mug_dptos_partidos.provincia = $provincia
				ORDER BY 
					mug_dptos_partidos.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	function get_localidades_departamento($departamento)
	{
		$departamento = toba::db()->quote($departamento);
		
		$sql = "SELECT 
					mug_localidades.localidad, 
					mug_localidades.nombre
				FROM 
					mug_localidades
				WHERE 
					mug_localidades.dpto_partido = $departamento
				ORDER BY 
					mug_localidades.nombre
				";
		
		return guarani_db::consultar($sql);
	}
}
?>