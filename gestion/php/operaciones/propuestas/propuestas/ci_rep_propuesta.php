<?php 
/**
 *
 *
 * @package Propuestas
 * @subpackage Propuestas
 */
class ci_rep_propuesta extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_propuestas';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_propuestas')->get_listado($filtro);
	}

}

?>