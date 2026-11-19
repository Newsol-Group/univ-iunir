<?php
/**
 *
 * @package 	Cursadas
 * @subpackage 	Actualizaciones
 * 
 */
class cn_man_motivos_baja_insc_propuesta_nsg extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'motivo_baja_insc';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_inscripciones')->get_listado_motivos_baja_inscripcion($filtro);			
	}
}
?>