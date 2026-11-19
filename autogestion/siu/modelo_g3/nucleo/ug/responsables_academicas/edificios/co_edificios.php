<?php
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Edificios
 */
class co_edificios
{
	/**
        Obtiene una lista de edificios según el filtro recibido
    */
	function get_listado($where='')
	{
	   if ($where) $where = ' and '.$where.' ';
    
	   	$sql = "SELECT	sga_edificios.edificio, 
						sga_edificios.nombre, 
						sga_edificios.responsable_academica, 
						sga_edificios.localidad, 
						sga_edificios.calle || ' ' || COALESCE(sga_edificios.numero,' ') as direccion, 
						sga_edificios.codigo_postal, 
						mug_localidades.nombre as localidad,
						sga_responsables_academicas.nombre as nombre_responsable_academica
				FROM 	sga_edificios,
				        mug_localidades,
				        sga_responsables_academicas
				WHERE   sga_edificios.localidad = mug_localidades.localidad AND
						sga_edificios.responsable_academica = sga_responsables_academicas.responsable_academica" . $where;
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los nombres de los edificios en la base de datos
    */
	function get_nombres()
	{
	   	$sql = "SELECT	sga_edificios.edificio,
	   					sga_edificios.nombre
				FROM 	sga_edificios
		";
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de edificios y aulas, agrupadas según el edificio, a partir del filtro recibido
    */
	function get_listado_reporte($where='')
	{
	    if ($where) $where = ' and '.$where.' ';
    
	   	$sql = "SELECT	sga_edificios.edificio, 
						sga_edificios.nombre as edificio_nombre,  
						mug_localidades.nombre as localidad_nombre,
						sga_aulas.aula,
						sga_aulas.nombre as aula_nombre,
						sga_aulas.codigo,
						sga_aulas.capacidad,
						sga_aulas.piso,
						sga_aulas.descripcion,
						sga_aulas_tipos.descripcion as tipo_descripcion
				FROM 	sga_edificios,
						mug_localidades,
				        sga_aulas,
				        sga_aulas_tipos
				WHERE   sga_edificios.localidad = mug_localidades.localidad AND
						sga_edificios.edificio = sga_aulas.edificio AND
						sga_aulas.tipo_aula = sga_aulas_tipos.tipo_aula
		" . $where;
	   	
			        
		return guarani_db::consultar($sql);
	}
}
?>