<?php
/**
 * 
 * 
 * @package 	Cursadas
 * @subpackage 	Inscripciones
 * 
 */
class cn_man_modificaciones_prioridad_insc_actividades extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'modificacion_prioridad_insc_actividad';
	}

	/**
	 * Devuelve un listado de alumnos activos
	 */
	function get_listado_preseleccion($filtro = '')
	{
		$where = " 	sga_alumnos.calidad = '" . alumno::calidad_activo . "'
				 	AND	sga_planes_versiones.estado IN ('" . planes::estado_vigente . "','" . planes::estado_activo . "')
					";

		if ($filtro) {
			$where .= ' AND ' . $filtro;
		}

		return guarani::co('co_alumnos')->get_listado($where);
	}

	/**
	 * Obtiene un listado de actividades factibles de aplicar cambios de prioridad
	 */
	function get_listado_actividades_disp_cambio($alumno)
	{
		return guarani::co('co_inscripciones_cursadas')->get_actividades_disp_cambio_prioridad($alumno);
	}

}
?>