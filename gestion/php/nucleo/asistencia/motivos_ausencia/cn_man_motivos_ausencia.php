<?php
/**
 *
 *
 * @package 	Asistencia
 * @subpackage 	-
 * 
 */
class cn_man_motivos_ausencia extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'motivo_ausencia';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_motivos_ausencia')->get_listado($filtro);			
	}
}
?>