<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;

class etiquetas
{
	/**
	 * cache: memoria
	 * filas: n
	 */
	function empresas_celulares($parametros) 
	{
		$sql = "SELECT celular_compania as id, nombre as desc FROM mdp_celulares_companias";
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		return $datos;
	}
	
	/**
	 * cache: memoria
	 * filas: n
	 */
	function paises (){
		$sql = "SELECT pais as id, nombre as desc FROM mug_paises ORDER BY nombre";
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		return $datos;
	}
	
	/**
	 * cache: memoria
	 * parametros: pais
	 * filas: n
	 */
	function provincias ($parametros)
	{
		$sql = "SELECT	provincia as id, 
						nombre as desc 
				FROM	mug_provincias 
				WHERE	pais = {$parametros['pais']}
				ORDER BY nombre";
		
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		return $datos;
	}
	
	/**
	 * cache: memoria
	 * parametros: provincia_id, localidad
	 * no_quote: localidad
	 * filas: n
	 */
	function localidades ($parametros) 
	{
		$localidad = $parametros['localidad'];
		$localidad = kernel::db()->quote_like($localidad, '%', '%');
		$localidad = strtoupper($localidad);
		
		$sql = "SELECT 
				mug_localidades.localidad || '_'  || mug_cod_postales.codigo_postal || '_'  || mug_localidades.nombre as id, 
				mug_localidades.nombre  || ' ('  || mug_cod_postales.codigo_postal || ')'  as desc , 
				mug_localidades.nombre 
			FROM mug_localidades, 
				mug_cod_postales 
			WHERE mug_localidades.localidad = mug_cod_postales.localidad
			AND mug_localidades.dpto_partido IN 
						(SELECT mug_dptos_partidos.dpto_partido 
							FROM mug_dptos_partidos 
							WHERE mug_dptos_partidos.provincia = {$parametros['provincia_id']}) 
			AND mug_localidades.nombre like $localidad
			ORDER BY mug_localidades.nombre";
			
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		if (!empty($datos))
		{
			return $datos;
		}
		else
		{
			return false;
		}		
	}
	
	/**
	 * cache: memoria
	 * parametros: localidad
	 * filas: 1
	 */
	function nombre_localidad ($parametros)
	{
		$sql = "SELECT 
			mug_localidades.nombre_abreviado || ', '  || mug_provincias.nombre || ', ' || mug_paises.nombre as texto
			FROM mug_localidades, mug_provincias, mug_paises, mug_dptos_partidos
			WHERE mug_localidades.dpto_partido =  mug_dptos_partidos.dpto_partido
			AND mug_provincias.provincia =  mug_dptos_partidos.provincia
			AND mug_provincias.pais =  mug_paises.pais 
			AND mug_localidades.localidad = {$parametros['localidad']}";
			
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_ASSOC);		
		if (!empty($datos))
		{
			return $datos['TEXTO'];
		}
		else
		{
			return false;
		}		
	}

	/**
	 * cache: memoria
	 *  parametros: pais
	 */
	function tipos_documento($parametros)
	{
		$where = "";
		if ($parametros['pais']) {
			$pais = kernel::db()->quote($parametros['pais']);

			$where = "where mdp_tipo_documento_pais.pais = ".  $parametros['pais'];
		}

		$sql = "SELECT mdp_tipo_documento.tipo_documento as id, 
				COALESCE(mdp_tipo_documento_pais.descripcion, mdp_tipo_documento.descripcion) as nombre ,
							COALESCE(mdp_tipo_documento_pais.desc_abreviada, mdp_tipo_documento.desc_abreviada) as abrev
				FROM mdp_tipo_documento
				join mdp_tipo_documento_pais on mdp_tipo_documento.tipo_documento = mdp_tipo_documento_pais.tipo_documento $where ORDER BY orden_principal";
		return kernel::db()->consultar($sql);
	}

}