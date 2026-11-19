<?php
/**
 *
 * @package 	Propuestas
 * @subpackage 	Actualizaciones
 * 
 */
class cn_man_motivos_rechazo_insc_cursadas_y_examen extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'motivo_rechazo_insc';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_inscripciones')->get_listado_motivos_rechazo_inscripcion($filtro);			
	}
}
?>