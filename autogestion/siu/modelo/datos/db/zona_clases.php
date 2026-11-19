<?php
namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use siu\modelo\entidades;

//parametro importarlo asi entidades\parametro

class zona_clases
{
//	/**
//	 * parametros: anio_academico, docente, periodo_lectivo, actividad
//	 * param_null: anio_academico, docente, periodo_lectivo, actividad
//	 * no_quote: anio_academico, docente, periodo_lectivo, actividad
//	 * cache: no
//	 * cache_expiracion: 600
//	 * filas: n
//	 */
//	function lista_clases_docente($parametros)
//	{
//
//
//	}

	/**
	 * el id es comision si no tiene subco, o comision_subcomision en otro caso
	 * Se usa para hacer links con los derechos
	 * parametros: id
	 * cache: no
	 * filas: n
	 */
	function subcomision_entidad($parametros)
	{

		$sql = "-- Comisiones sin subcomisiones
			SELECT
			   cast(sga_comisiones.comision as text)  as id,
			   sga_comisiones.comision as comision,
			   cast(null as integer) as subcomision,
			   sga_comisiones.entidad
			FROM
			   sga_comisiones
			WHERE
			   cast(sga_comisiones.comision as text) = {$parametros['id']}
			UNION ALL
			-- Comisiones con subcomisiones.
			SELECT
			  cast(sga_comisiones.comision as text) || '_' || cast(sga_subcomisiones.subcomision as text) as id,
			   sga_comisiones.comision as comision,
			   sga_subcomisiones.subcomision as subcomision,
			   sga_comisiones.entidad
			FROM
			   sga_comisiones,
			   sga_subcomisiones
			WHERE
			      sga_subcomisiones.comision = sga_comisiones.comision
			  AND
			   cast(sga_comisiones.comision as text) || '_' || cast(sga_subcomisiones.subcomision as text) = {$parametros['id']}
			";

		$datos = kernel::db()->consultar($sql);
		return $datos;

	}
    
	/**
	 * parametros: comision
	 * cache: no
	 * filas: n
	 */
	function get_propuesta($parametros)
	{
		$sql = "SELECT propuesta 
                FROM  sga_comisiones_propuestas
                WHERE comision = {$parametros['comision']}
               ";

		$datos = kernel::db()->consultar($sql);
		return $datos;

	}


}