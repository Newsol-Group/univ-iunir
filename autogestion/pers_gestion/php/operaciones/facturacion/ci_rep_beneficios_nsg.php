<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Actualizaciones
 */
class ci_rep_beneficios_nsg extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'nsg_datos_beneficios';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_beneficios_perfil_de_datos($filtro);	}
}
?>