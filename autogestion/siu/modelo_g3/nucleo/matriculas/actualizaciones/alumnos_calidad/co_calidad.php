<?php
/**
 *
 *
 * @package Matricula
 * @subpackage Actualizaciones
 */
class co_calidad
{
	/**
        Retorna las calidades
    */
    function get_calidades($where='')
    {
    	if ($where) $where = 'WHERE '.$where.' ';

		$sql = "	SELECT 	sga_alumnos_calidad.calidad as valor,
							sga_alumnos_calidad.nombre as descr
					FROM 	sga_alumnos_calidad
							$where
					ORDER BY 2
							;"; 
		
		return guarani_db::consultar($sql);
    }	
}

?>