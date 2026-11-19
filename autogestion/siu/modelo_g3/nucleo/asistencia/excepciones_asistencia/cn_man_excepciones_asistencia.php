<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage 
 */
class cn_man_excepciones_asistencia extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'excepcion_asistencia';
	}

	function get_listado($filtro)
	{
		$filtro .= " AND sga_insc_cursada.estado IN ('".inscripcion_cursada::estado_aceptada."', '".inscripcion_cursada::estado_pendiente."')";
		return toba::consulta_php('co_inscripciones_cursadas')->get_listado($filtro, true);
	}
	
	function get_listado_preseleccion($filtro)
	{
		$filtro .= " AND sga_alumnos.calidad = '".alumno::calidad_activo."'";
		return toba::consulta_php('co_alumnos')->get_listado($filtro);			
	}
}
?>