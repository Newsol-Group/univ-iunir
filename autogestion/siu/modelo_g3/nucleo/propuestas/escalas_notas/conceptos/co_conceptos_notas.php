<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Escalas de Notas
 */
class co_conceptos_notas
{
	/**
	 * Retorna el listado de conceptos de escalas de notas según el filtro especificado.
	 *
	 * @param string $where
	 * @return array
	 */
	function get_listado($where='')
	{
		if ($where) {
			$where = "WHERE $where ";
		}
		$sql = "SELECT		sga_escalas_notas_concepto.concepto,
							sga_escalas_notas_concepto.nombre,
							sga_escalas_notas_concepto.descripcion
				FROM		sga_escalas_notas_concepto
				$where
				ORDER BY	sga_escalas_notas_concepto.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_conceptos_notas_activos()
	{
		$sql = "SELECT		sga_escalas_notas_concepto.concepto as valor,
							sga_escalas_notas_concepto.nombre as descr
				FROM		sga_escalas_notas_concepto
				WHERE		sga_escalas_notas_concepto.estado = '".comunes::estado_activo."'
				ORDER BY	sga_escalas_notas_concepto.nombre
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Valida si un nombre de concepto de escala de notas se puede usar o no.
	 *
	 * @param string $nombre
	 * @param integer $concepto
	 * @return boolean
	 */
	function validar_nombre_concepto_notas($nombre, $concepto)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	COUNT(sga_escalas_notas_concepto.concepto) AS cant
                FROM 	sga_escalas_notas_concepto
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_escalas_notas_concepto.nombre', $nombre).'
				';
		
		if (!is_null($concepto)) {
			$concepto = toba::db()->quote($concepto);
			$sql .= " AND sga_escalas_notas_concepto.concepto <> $concepto";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
}
?>