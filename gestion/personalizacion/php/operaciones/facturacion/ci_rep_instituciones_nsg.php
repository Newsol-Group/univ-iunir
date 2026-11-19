<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Actualizaciones
 */
class ci_rep_instituciones_nsg extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_instituciones';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_instituciones')->get_reporte_instituciones($filtro);

	}
}
?>