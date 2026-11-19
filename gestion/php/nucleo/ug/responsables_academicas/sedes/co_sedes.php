<?php
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Sedes
 */
class co_sedes
{
	/**
        Retorna las sedes de acuerdo al filtro recibido
    */

	function get_listado($where='')
	{
		if ($where) $where = ' where '.$where.' ';
		$sql = "SELECT 	sga_sedes.sede,
						sga_sedes.localidad, 
						mug_localidades.nombre as localidad_nombre,	
						sga_sedes.nombre, 
						sga_sedes.calle, 
						sga_sedes.numero, 
						sga_sedes.telefono,	
						sga_sedes.telefono2, 
						sga_sedes.fax, 
						sga_sedes.email, 
						sga_sedes.codigo_postal 
			FROM sga_sedes 
				LEFT JOIN mug_localidades ON mug_localidades.localidad = sga_sedes.localidad 
				".$where; 
      	return guarani_db::consultar($sql);
	}
}

?>