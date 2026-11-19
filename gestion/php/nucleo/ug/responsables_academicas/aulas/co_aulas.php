<?php
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Aulas
 */
class co_aulas
{
	
	/**
        Obtiene una lista de aulas según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) $where = ' and '.$where.' ';
    
	   	$sql = "SELECT	sga_aulas.aula,
	   					sga_aulas.nombre,
	   					sga_aulas.codigo,
	   					sga_aulas.tipo_aula,
	   					sga_aulas.edificio,
	   					sga_aulas.piso,
	   					sga_aulas.capacidad,
	   					sga_aulas.descripcion,
	   					sga_aulas_tipos.descripcion as aula_tipo,
	   					sga_edificios.nombre as aula_edificio
				FROM 	sga_aulas,
						sga_edificios,
						sga_aulas_tipos
				WHERE   sga_aulas.edificio = sga_edificios.edificio AND
						sga_aulas.tipo_aula = sga_aulas_tipos.tipo_aula" . $where;
	  
	        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de tipos de aulas según el filtro recibido
    */
	function get_listado_tipos_aulas($where='')
	{
		if ($where) $where = ' and '.$where.' ';
    
	   	$sql = "SELECT	sga_aulas_tipos.tipo_aula,
	   					sga_aulas_tipos.descripcion,
	   					sga_aulas_tipos.descripcion_abreviada
				FROM 	sga_aulas_tipos
				WHERE   true " . $where;

			        
		return guarani_db::consultar($sql);
	}

	
	/**
        Obtiene una lista de los tipos de aulas
    */
	function get_tipos_aulas()
	{
	   	$sql = "SELECT	sga_aulas_tipos.tipo_aula as valor,
	   					sga_aulas_tipos.descripcion as descr
				FROM 	sga_aulas_tipos
		";
	   	
		return guarani_db::consultar($sql);
	}
}
?>