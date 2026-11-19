<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class cn_man_motivos_de_excepcion_de_requisitos extends cn_manejador_entidad
{
	
	function get_indice_instancia()
	{
		return 'motivo_ex_req';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_motivos_de_excepcion_de_requisitos')->get_listado($filtro);			
	}
	
}
?>