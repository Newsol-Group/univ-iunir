<?php
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Departamentos
 */
class co_departamentos
{
	function get_listado($where=' ')
	{
		$from = ' from sga_departamentos ';	
		if (trim($where) != '' ) $where = ' where '.$where.' ';
		
		$sql = "SELECT sga_departamentos.departamento,
						sga_departamentos.nombre, 
						".guarani_sql::get_case('sga_departamentos','estado')."
            	FROM	sga_departamentos
            	$where "; 
      	return guarani_db::consultar($sql);
	}
}
?>