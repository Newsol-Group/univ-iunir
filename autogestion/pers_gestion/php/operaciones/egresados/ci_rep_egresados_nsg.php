<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Reportes
 * 
 */
class ci_rep_egresados_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_certificados_otorg';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_tramites_certificacion')->get_listado_egresados($filtro);
	}
}
?>