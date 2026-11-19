<?php 
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class ci_rep_actividad_materia_generica extends ci_reporte
{
	function get_tabla_principal()
	{
		return 'sga_elementos';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_elementos')->get_materias_genericas_con_sus_componentes($filtro);
	}
}
?>