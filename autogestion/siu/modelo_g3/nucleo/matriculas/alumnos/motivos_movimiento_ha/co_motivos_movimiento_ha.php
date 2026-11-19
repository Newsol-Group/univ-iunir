<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Alumnos
 */
class co_motivos_movimiento_ha
{
	/**
	 * Obtiene un listado de motivos de movimiento de historia académica según el filtro recibido
	 *
	 */
	function get_listado($where='')
	{
		if ($where) {
			$where = " WHERE $where ";
		}
		
		$motivos_mov_ha = $this->get_opciones_combo_tipo_motivo_movimiento_ha();
		
		$sql = "SELECT 		sga_movimientos_ha_motivos.motivo_movimiento,
							sga_movimientos_ha_motivos.nombre,
							sga_movimientos_ha_motivos.descripcion,
							sga_movimientos_ha_motivos.tipo,
							".guarani_sql::get_case('sga_movimientos_ha_motivos', 'tipo', 'tipo_descr', $motivos_mov_ha)."
				FROM 		sga_movimientos_ha_motivos
							$where
				ORDER BY	sga_movimientos_ha_motivos.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene el listado de motivos de invalidación
	 *
	 */
	function get_motivos_invalidacion()
	{
		$tipo_invalidar = toba::db()->quote(alumno::motivo_movimiento_ha_tipo_invalidar);
		
		$sql = "SELECT 		sga_movimientos_ha_motivos.motivo_movimiento as valor,
							sga_movimientos_ha_motivos.nombre as descr
				FROM 		sga_movimientos_ha_motivos
				WHERE		sga_movimientos_ha_motivos.tipo = $tipo_invalidar
				ORDER BY	sga_movimientos_ha_motivos.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene el listado de motivos de invalidación
	 *
	 */
	function get_motivos_convalidacion()
	{
		$tipo_convalidar = toba::db()->quote(alumno::motivo_movimiento_ha_tipo_convalidar);
		
		$sql = "SELECT 		sga_movimientos_ha_motivos.motivo_movimiento as valor,
							sga_movimientos_ha_motivos.nombre as descr
				FROM 		sga_movimientos_ha_motivos
				WHERE		sga_movimientos_ha_motivos.tipo = $tipo_convalidar
				ORDER BY	sga_movimientos_ha_motivos.nombre
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 *    Valida si un nombre de motivo de movimiento de la historia académica de un alumno se puede usar o no
	 */
	function validar_nombre_motivo_movimiento_ha($nombre, $motivo_movimiento)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	COUNT(sga_movimientos_ha_motivos.motivo_movimiento) AS cant
                FROM 	sga_movimientos_ha_motivos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_movimientos_ha_motivos.nombre', $nombre).'
				';
		
		if (!is_null($motivo_movimiento)) {
			$motivo_movimiento = toba::db()->quote($motivo_movimiento);
			$sql .= " AND sga_movimientos_ha_motivos.motivo_movimiento <> $motivo_movimiento ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Opciones para el motivo del movimiento de la historia académica --------------
	//-----------------------------------------------------------------------------------

	function get_opciones_combo_tipo_motivo_movimiento_ha()
	{
			return array(array('valor' => alumno::motivo_movimiento_ha_tipo_invalidar,	'descr' => alumno::motivo_movimiento_ha_tipo_invalidar),
						 array('valor' => alumno::motivo_movimiento_ha_tipo_convalidar,	'descr' => alumno::motivo_movimiento_ha_tipo_convalidar));
	}
	
}
?>