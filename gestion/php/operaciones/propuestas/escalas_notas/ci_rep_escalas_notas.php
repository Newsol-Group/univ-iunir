<?php 
/**
 *
 *
 * @package Propuestas
 * @subpackage EscalaDeNotas
 */
class ci_rep_escalas_notas extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_escalas_notas';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_escalas_notas')->get_listado_reporte($filtro);
	}
}
?>