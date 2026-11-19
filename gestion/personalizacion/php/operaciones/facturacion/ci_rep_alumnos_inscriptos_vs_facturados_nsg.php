<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Actualizaciones
 */
class ci_rep_alumnos_inscriptos_vs_facturados_nsg extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'nsg_datos_facturacion';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_alumnos_inscriptos_vs_facturados_perfil_de_datos($filtro);
	}
}
?>