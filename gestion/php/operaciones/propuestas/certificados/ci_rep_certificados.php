<?php 
/**
 *
 *
 * @package Propuestas
 * @subpackage Certificados
 */
class ci_rep_certificados extends ci_reporte
{

	function get_tabla_principal()
	{
		return 'sga_certificados';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_certificados')->get_listado($filtro);
	}
	
}
?>