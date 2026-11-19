<?php
/**
 *
 *
 * @package Egresados
 * @subpackage Juramentos
 */
class co_juramentos
{
	/**
        Obtiene una lista de juramentos según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "	SELECT 	sga_juramentos.juramento,
							sga_juramentos.descripcion, 
							sga_juramentos.descripcion_abreviada
					FROM 	sga_juramentos
							$where 
					ORDER BY sga_juramentos.descripcion
							;"; 
		return guarani_db::consultar($sql);
	}

	function get_juramentos()
	{
		$sql = "SELECT		sga_juramentos.juramento as valor,
							sga_juramentos.descripcion_abreviada as descr
				FROM		sga_juramentos
				ORDER BY	sga_juramentos.descripcion
		";
		return guarani_db::consultar($sql);
	}

	/**
        Valida si un nombre de juramento se puede usar o no.
    */
	function validar_nombre_juramento($descripcion_abreviada, $juramento)
	{
		if (!isset($descripcion_abreviada)) {
			return false;
		}
		
		$descripcion_abreviada = toba::db()->quote($descripcion_abreviada);
		
		$sql = 'SELECT	count(sga_juramentos.juramento) as cant
                FROM 	sga_juramentos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_juramentos.descripcion_abreviada', $descripcion_abreviada).'
				';
		
		if (!is_null($juramento)) {
			$juramento = toba::db()->quote($juramento);
			$sql .= " AND sga_juramentos.juramento <> $juramento ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
}

?>