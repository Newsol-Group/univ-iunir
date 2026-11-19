<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Actualizaciones
 */
class ci_rep_expulsados_en_otras_instituciones extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_alumnos_expulsados';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_expulsados_en_otras_instituciones')->get_listado($filtro);
	}
	
}
?>