<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Reportes
 * 
 */
class ci_rep_tramites_certificacion extends ci_reporte 
{
	function get_tabla_principal()
	{
		return 'sga_certificados_otorg';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_tramites_certificacion')->get_tramites_por_estado($filtro);
	}
}
?>