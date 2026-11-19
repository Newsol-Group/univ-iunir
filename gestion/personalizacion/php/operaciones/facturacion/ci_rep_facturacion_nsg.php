<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Actualizaciones
 */
class ci_rep_facturacion_nsg extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'nsg_datos_facturacion';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_facturacion_perfil_de_datos($filtro);
	}
}
?>