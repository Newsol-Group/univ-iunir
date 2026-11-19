<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class cn_man_agregar_requisito_persona extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'agregar_requisito_persona';
	}
	
	function get_listado($filtro)
	{
		//Filtro por Inscripciones en estado Pendiente
		$filtro .= ($filtro != '') ? ' AND ' : '';
		$filtro .= "sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_pendiente."'";
		return toba::consulta_php('co_inscripciones')->get_inscriptos($filtro);
	}
	
}
?>