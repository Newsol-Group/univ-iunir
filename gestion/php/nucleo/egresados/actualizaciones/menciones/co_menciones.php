<?php
/**
 *
 *
 * @package Egresados
 * @subpackage Actualizaciones
 */
class co_menciones
{
	function get_listado($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "SELECT 	sga_menciones.mencion,
						sga_menciones.nombre, 
						sga_menciones.descripcion
				FROM 	sga_menciones
						$where
				ORDER BY 2
				;"; 
		
		return guarani_db::consultar($sql);
	}
	
	/**
        Valida si un nombre de mención se puede usar o no.
    */
	function validar_nombre_mencion($nombre, $mencion)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_menciones.mencion) as cant
                FROM 	sga_menciones
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_menciones.nombre', $nombre).'
				';
		
		if (!is_null($mencion)) {
			$mencion = toba::db()->quote($mencion);
			$sql .= " AND sga_menciones.mencion <> $mencion ";
		}
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
}

?>