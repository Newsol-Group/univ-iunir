<?php
/**
 *
 *
 * @package 	Calendario
 * @subpackage 	Actualizaciones
 * 
 */
class cn_man_motivos_invalidacion_clases extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'motivo_invalidacion_clase';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_motivos_invalidacion_clases')->get_listado($filtro);			
	}
	
}
?>