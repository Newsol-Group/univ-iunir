<?php 
/**
 *
 *
 * @package Matriculas
 * @subpackage Alumnos
 */
class ci_rep_reinscriptos_a_propuestas extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_reinscripciones';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_reinscripciones')->get_listado_reporte($filtro);
	}
}
?>