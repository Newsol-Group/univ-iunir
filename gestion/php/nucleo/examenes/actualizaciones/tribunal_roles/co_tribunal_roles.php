<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Actualizaciones
 */
class co_tribunal_roles
{
	function get_listado($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "SELECT		sga_tribunal_roles.rol,
							sga_tribunal_roles.nombre, 
							sga_tribunal_roles.descripcion,
							sga_tribunal_roles.orden_acta
				FROM 		sga_tribunal_roles
							$where
				ORDER BY 	sga_tribunal_roles.nombre
				"; 
		
		return guarani_db::consultar($sql);
	}
	
	/**
        Valida si un nombre de un rol de docente en tribunal se puede usar o no.
    */
	function validar_nombre_tribunal_rol($nombre, $tribunal_rol)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_tribunal_roles.rol) as cant
                FROM 	sga_tribunal_roles
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_tribunal_roles.nombre', $nombre).'
				';
		
		if (!is_null($tribunal_rol)) {
			$tribunal_rol = toba::db()->quote($tribunal_rol);
			$sql .= " AND sga_tribunal_roles.rol <> $tribunal_rol ";
		}
		
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
}

?>