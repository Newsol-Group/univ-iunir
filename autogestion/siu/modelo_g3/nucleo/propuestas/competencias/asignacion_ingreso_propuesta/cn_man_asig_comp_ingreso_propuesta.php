<?php
/**
 *
 * 
 * @package 	Matrícula
 * @subpackage 	Competencias
 * 
 */
class cn_man_asig_comp_ingreso_propuesta extends cn_manejador_entidad
{
	
	function get_indice_instancia()
	{
		return 'asignacion_requisito_ingreso_propuesta';
	}

	function get_listado($filtro)
	{
		$listado = toba::consulta_php('co_propuestas')->get_listado($filtro);
		$listado = rs_ordenar_por_columna($listado, 'nombre');
		return $listado;
	}
	
	function get_listado_competencias($filtro)
	{
		return toba::consulta_php('co_competencias')->get_listado($filtro);
	}
	
}
?>