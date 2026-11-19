<?php 
/**
 *
 * @package 	Cursadas
 * @subpackage 	Reportes
 */
class ci_rep_condiciones_regularidad extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_cond_regularidad';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_condiciones_regularidad')->get_listado_reporte($filtro);
	}
	
}
?>