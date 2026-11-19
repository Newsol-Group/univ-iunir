<?php
/**
 *
 * @package 	
 * @subpackage Sedes
 * 
 */
class co_condiciones_regularidad
{
	/**
	 * Retorna un listado de condiciones de regularidad, según filtro recibido
	 *
	 */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}
		$sql = "SELECT 		sga_cond_regularidad.cond_regularidad,
							sga_cond_regularidad.nombre,
							sga_cond_regularidad.descripcion,
							sga_cond_regularidad.resultado,
							sga_cond_regularidad.es_condicion_defecto,
							sga_cond_regularidad.activo
				FROM 		sga_cond_regularidad
				$where
				ORDER BY	sga_cond_regularidad.nombre"; 
      	return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene el listado de condiciones de regularidad ACTIVAS.
	 *
	 */
	function get_condiciones_activas()
	{
		$si = toba::db()->quote(comunes::si);
		$where = "sga_cond_regularidad.activo = $si";
		return $this->get_listado($where);
	}
	
	/**
     *  Valida si un nombre de Condición de Regularidad se puede usar o no.
     */
	function validar_nombre_condicion_regularidad($nombre, $cond_regularidad)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);		
		$sql = 'SELECT	count(sga_cond_regularidad.cond_regularidad) as cant
                FROM 	sga_cond_regularidad
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_cond_regularidad.nombre', $nombre).'
				';
		
		if (!is_null($cond_regularidad)) {
			$cond_regularidad = toba::db()->quote($cond_regularidad);
			$sql .= " AND sga_cond_regularidad.cond_regularidad <> $cond_regularidad ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
	/**
	 * Valida que no haya otra otra condición definida por defecto para el mismo resultado
	 *
	 */
	function validar_condicion_resultado($resultado)
	{
		$resultado = toba::db()->quote($resultado);
		
		$sql = "SELECT 
					count(1) AS cant
				FROM 
					sga_cond_regularidad
				WHERE
					sga_cond_regularidad.resultado = $resultado
				    AND sga_cond_regularidad.es_condicion_defecto = '".comunes::si."'
				    AND sga_cond_regularidad.activo = '".comunes::si."'";
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	/**
	 * Obtiene el Listado de Condiciones de Regularidad para exhibir en el reporte, de acuerdo a filtro recibido.
	 *
	 */
	function get_listado_reporte($where='')
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		
		$sql = 'SELECT 		sga_cond_regularidad.cond_regularidad,
							sga_cond_regularidad.nombre,
							sga_cond_regularidad.descripcion,
							sga_cond_regularidad.activo,
							'.guarani_sql::get_case_si_no('sga_cond_regularidad','activo','activo_si_no').',
							sga_cond_regularidad.resultado,
				            sga_escalas_notas_resultado.descripcion	AS resultado_nombre,
							sga_cond_regularidad.es_condicion_defecto,
							'.guarani_sql::get_case_si_no('sga_cond_regularidad','es_condicion_defecto','es_condicion_defecto_si_no')."

				FROM 		sga_cond_regularidad,
				            sga_escalas_notas_resultado

				WHERE		sga_cond_regularidad.resultado = sga_escalas_notas_resultado.resultado
				            $where

				ORDER BY	sga_cond_regularidad.nombre
				";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si una condición de regularidad es válida para un resultado dado.
	 */
	function condicion_valida_resultado($resultado, $cond_regularidad)
	{
		$resultado = toba::db()->quote($resultado);
		$cond_regularidad = toba::db()->quote($cond_regularidad);

		$sql = "SELECT 	COUNT(sga_cond_regularidad.cond_regularidad) as cant
				FROM 	sga_cond_regularidad
				WHERE	sga_cond_regularidad.resultado = $resultado AND
						sga_cond_regularidad.cond_regularidad = $cond_regularidad
		";

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] > 0);
	}

	/**
	 * Retorna el identificador de la condición de regularidad configurada como "por defecto" para un resultado dado.
	 * 
	 * @param char $resultado
	 * @return int
	 */
	function get_condicion_defecto_resultado($resultado)
	{
		$resultado = toba::db()->quote($resultado);
		$sql = "SELECT 	sga_cond_regularidad.cond_regularidad
				FROM 	sga_cond_regularidad
				WHERE	sga_cond_regularidad.resultado = $resultado AND
						sga_cond_regularidad.es_condicion_defecto = '".comunes::si."'
		";
		$res = guarani_db::consultar_fila($sql);
		return $res['cond_regularidad'];
	}
}
?>