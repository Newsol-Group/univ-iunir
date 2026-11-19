<?php

namespace siu\operaciones\cronograma_evaluaciones;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_listado extends pagelet
{
	
	function get_nombre()
	{
		return 'listado';
	}

	function prepare()
	{
		$persona = kernel::persona()->get_id();
		$solo_insc_cursos = kernel::persona()->es_perfil_cursos();
		$evaluaciones = catalogo::consultar('comisiones', 'get_cronograma_evaluaciones_persona', ['persona' => $persona, 'visible_al_alumno' => \comunes::si, 'solo_insc_cursos' => $solo_insc_cursos]);
		
		$datos = [];
		foreach ($evaluaciones as $evaluacion) {
			
			// Se agrupa primero por propuesta
			$corte_propuesta = $evaluacion['propuesta'];
			$datos[$corte_propuesta]['propuesta_nombre'] = $evaluacion['propuesta_nombre'];
			
			// Luego se agrupa por actividad y comisión
			$corte_actividad_comision = $evaluacion['actividad'].$evaluacion['comision'];
			$datos[$corte_propuesta]['actividades_comisiones'][$corte_actividad_comision]['actividad_codigo'] = $evaluacion['actividad_codigo'];
			$datos[$corte_propuesta]['actividades_comisiones'][$corte_actividad_comision]['actividad_nombre'] = $evaluacion['actividad_nombre'];
			$datos[$corte_propuesta]['actividades_comisiones'][$corte_actividad_comision]['comision_nombre'] = $evaluacion['comision_nombre'];
			$datos[$corte_propuesta]['actividades_comisiones'][$corte_actividad_comision]['evaluaciones'][] = [
				'fecha_evaluacion' => $evaluacion['fecha_evaluacion_visual'],
				'evaluacion_nombre' => $evaluacion['evaluacion_nombre'],
				'tipo_evaluacion_desc' => $evaluacion['tipo_evaluacion_desc'],
				'hora_inicio' => $evaluacion['hora_inicio_visual'],
				'hora_fin' => $evaluacion['hora_fin_visual'],
				'evaluacion_desc' => $evaluacion['evaluacion_desc'],
			];
		}
		kernel::log()->add_debug("=== Listado cronograma evaluaciones ===", $datos);
		$this->data['listado'] = $datos;
	}
	
}