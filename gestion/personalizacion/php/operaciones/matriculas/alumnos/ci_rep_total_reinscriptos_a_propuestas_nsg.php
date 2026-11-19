<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Alumnos
 */
class ci_rep_total_reinscriptos_a_propuestas_nsg extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_reinscripciones';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_inscripciones')->get_reporte_total_reinscriptos($filtro);
	}
}
?>