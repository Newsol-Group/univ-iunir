<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 * 
 */
class cn_man_licencias extends cn_manejador_entidad
{	
	function get_indice_instancia()
	{
		return 'licencia';
	}
	
	function get_listado_preseleccion($where='')
	{
		return toba::consulta_php('co_alumnos')->get_alumnos_una_persona($where);
	}
	
	function get_listado_licencias($persona)
	{
		return toba::consulta_php('co_licencias')->get_licencias_persona($persona);
	}
	
	function es_alumno_activo($persona)
	{
		$where = 'sga_alumnos.calidad = '.toba::db()->quote(alumno::calidad_activo);
		return toba::consulta_php('co_personas')->es_alumno($persona, $where);
	}
}	
?>