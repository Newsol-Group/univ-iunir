<?php
namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;

class atributos_params
{
	/**
	 * parametros: comision
	 * cache: memoria
	 * cache_expiracion: 300
	 * filas: n
	 * 
	 * Retorna el ID de todas las propuestas asociadas a la comisión recibida como parámetro.
	 */
	function get_propuestas_comision($parametros)
	{
		$comision = $parametros['comision'];

		$sql = "SELECT	sga_comisiones_propuestas.propuesta
				FROM	sga_comisiones_propuestas
				WHERE	sga_comisiones_propuestas.comision = $comision
				";

		$datos = kernel::db()->consultar($sql);
		return aplanar_matriz($datos, 'propuesta');
	}
	
	/**
	 * parametros: mesa_examen
	 * cache: memoria
	 * cache_expiracion: 300
	 * filas: n
	 * 
	 * Retorna el ID de todas las propuestas asociadas a la mesa de examen recibida como parámetro.
	 */
	function get_propuestas_mesa_examen($parametros)
	{
		$mesa_examen = $parametros['mesa_examen'];
		
		$sql = "SELECT	DISTINCT sga_mesas_examen_propuestas.propuesta
				FROM	sga_mesas_examen_propuestas
				WHERE	sga_mesas_examen_propuestas.mesa_examen = $mesa_examen
		";
		
		$datos = kernel::db()->consultar($sql);
		return aplanar_matriz($datos, 'propuesta');
	}
	
	/**
	 * parametros: llamado_mesa
	 * no_quote: llamado_mesa
	 * cache: memoria
	 * cache_expiracion: 300
	 * filas: n
	 * 
	 * Retorna el ID de todas las propuestas asociadas a la mesa de examen recibida como parámetro.
	 */
	function get_propuestas_llamado_mesa($parametros)
	{
		$llamado_mesa = $parametros['llamado_mesa'];
		
		$sql = "SELECT	DISTINCT sga_mesas_examen_propuestas.propuesta
				FROM	sga_mesas_examen_propuestas,
						vw_mesas_examen
				WHERE	sga_mesas_examen_propuestas.mesa_examen = vw_mesas_examen.mesa_examen
						AND vw_mesas_examen.llamado_mesa = $llamado_mesa
		";
		
		$datos = kernel::db()->consultar($sql);
		return aplanar_matriz($datos, 'propuesta');
	}
	
	/**
	 * parametros: elemento
	 * cache: memoria
	 * cache_expiracion: 300
	 * filas: n
	 * 
	 * Retorna el ID de todas las propuestas asociadas a una actividad dada.
	 * Usada para obtener las propuestas de la misma manera que la función f_mesa_examen_alta,
	 * usada en las actas fuera de calendario, cuando todavía no se cuenta con IDs de
	 * mesa_examen o llamado_mesa, y se necesitan las propuestas para resolver
	 * valores de parámetros definidos en nivel 4 (propuesta)
	 */
	function get_propuestas_elemento($parametros)
	{
		$elemento = $parametros['elemento'];
		
		$sql = "SELECT	DISTINCT propuesta
				FROM	vw_actividades_plan
				WHERE	elemento = $elemento;
		";
		
		$datos = kernel::db()->consultar($sql);
		return aplanar_matriz($datos, 'propuesta');
	}

	/**
	 * parametros: propuestas
	 * no_quote: propuestas
	 * cache: memoria
	 * cache_expiracion: 300
	 * filas: n
	 * 
	 * Retorna el ID de todas las propuestas asociadas a la comisión recibida como parámetro.
	 */
	function get_propuestas_ras($parametros)
	{
		$propuestas = $parametros['propuestas'];
		$where_in = ' sga_propuestas_ra.propuesta IN (';
		foreach ($propuestas as $key => $propuesta) {
			$propuestas[$key] = "'" . $propuesta . "'";
		}
		$where_in .= implode(',', $propuestas);
		$where_in .= ')';

		$sql = "SELECT 	DISTINCT responsable_academica
				FROM 	sga_propuestas_ra
				WHERE 	$where_in";

		$datos = kernel::db()->consultar($sql);
		return aplanar_matriz($datos, 'responsable_academica');
	}

}