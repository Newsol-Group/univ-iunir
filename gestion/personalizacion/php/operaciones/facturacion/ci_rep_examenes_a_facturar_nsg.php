<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Actualizaciones
 */
class ci_rep_examenes_a_facturar_nsg extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_facturacion')->get_reporte_examenes_a_facturar_perfil_de_datos($filtro);
	}
}
?>