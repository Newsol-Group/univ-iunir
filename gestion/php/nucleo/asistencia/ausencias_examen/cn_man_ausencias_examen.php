<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class cn_man_ausencias_examen extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'ausencia_examen';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_actas')->get_detalles_ausencia_alumno($filtro);
	}
	
	function get_listado_preseleccion($filtro)
	{
		$filtro .= " AND sga_alumnos.calidad = '".alumno::calidad_activo."'";
		return toba::consulta_php('co_alumnos')->get_listado($filtro);			
	}
}
?>