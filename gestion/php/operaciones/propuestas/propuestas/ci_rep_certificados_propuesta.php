<?php 
/**
 *
 *
 * @package Propuestas
 * @subpackage Propuestas
 */
class ci_rep_certificados_propuesta extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_certificados';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_propuestas')->get_certificados($filtro);
	}
}

?>