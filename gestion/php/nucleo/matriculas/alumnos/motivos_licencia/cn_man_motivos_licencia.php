<?php
/**
 *
 *
 * @package 	Matricula
 * @subpackage 	Alumnos
 * 
 */
class cn_man_motivos_licencia extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'motivo_licencia';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_motivos_licencia')->get_listado($filtro);			
	}
}
?>