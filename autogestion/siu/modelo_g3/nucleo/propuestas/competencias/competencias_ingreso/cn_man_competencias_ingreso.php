<?php
/**
 * 
 * @package Propuestas Formativas
 * @subpackage Competencias
 */
class cn_man_competencias_ingreso extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'competencia_ingreso';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_competencias')->get_listado($filtro);			
	}
}

?>