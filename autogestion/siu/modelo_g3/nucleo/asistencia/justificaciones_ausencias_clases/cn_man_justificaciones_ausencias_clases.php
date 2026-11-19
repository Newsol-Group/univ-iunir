<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage -
 */
class cn_man_justificaciones_ausencias_clases extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'justificacion_ausencia_clase';
	}

	// Recupera los Alumnos en calidad de Activos según el filtro dado.
	function get_listado_preseleccion($filtro)
	{
		$filtro .= " AND sga_alumnos.calidad = '" . alumno::calidad_activo . "'";
		return toba::consulta_php('co_alumnos')->get_listado($filtro);			
	}
	
	// Recupera las Comisiones con Período Lectivo en Curso para las cuales el Alumno está inscripto.
	function get_listado_comisiones_alumno($alumno)
	{
		return toba::consulta_php('co_inscripciones_cursadas')->get_datos_comisiones_insc_alumno($alumno);
	}
	
	// Recupera las Clases de la Comisión dada, para las cuales el Alumno dado estuvo ausente.
	function get_listado_ausencias_clases($alumno, $comision)
	{
		$comision = toba::db()->quote($comision);
		$where = "sga_comisiones.comision = $comision";
		return toba::consulta_php('co_asistencias')->get_ausencias_alumno($alumno, $where);
	}
}
?>