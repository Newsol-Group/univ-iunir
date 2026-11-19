<?php
class regla_superposicion_horaria_subcomision extends regla
{
	/**
	 * Verifica la superposición del horario de cursada de la subcomisión respecto a las inscripciones del alumno.
	 * Tanto la subcomisión como el alumno son datos recibidos como parámetro de contexto.
	 *
	 * @return boolean false si existe superposición y true en caso contrario.
	 */
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$comision = $this->get_parametro('comision');
		$subcomision = $this->get_parametro('subcomision');
		$fecha = $this->get_parametro('fecha');

		$datos_actividad = guarani::elementos()->get_datos_basicos_elemento($actividad);

		$hay_superposicion = false;
		if ($datos_actividad['insc_cursada_por_prioridad'] == comunes::no) {
			
			// Asignaciones horarias de la subcomisión
			$asignaciones = guarani::co('co_bandas_horarias')->get_listado_asignaciones_subcomision($subcomision);
			foreach ($asignaciones as $asignacion) {
				$asignacion_origen = $asignacion['asignacion'];
				$bh_origen = $asignacion['banda_horaria'];
				
				// Asignaciones horarias de inscripciones a otras comisiones/subcomisiones
				$asignaciones_superpuestas = guarani::co('co_bandas_horarias')->get_inscripciones_superpuestas($alumno, $comision, $asignacion['dia_semana'], $asignacion['hora_inicio'], $asignacion['hora_finalizacion'], $asignacion['fecha_desde'], $asignacion['fecha_hasta']);
				if (!empty($asignaciones_superpuestas)) {
					// Para cada inscripción del alumno a otras comisiones/subcomisiones con 
					// asignaciones que se superpongan con las asignaciones de la subcomisión 
					// de la inscripción que se está dando de alta, debe verificarse si hay 
					// superposición de clases por fecha calendario, considerando así la periodicidad.
					foreach ($asignaciones_superpuestas as $asignacion_superpuesta) {
						$asignacion_otra = $asignacion_superpuesta['asignacion'];
						$bh_otra = $asignacion_superpuesta['banda_horaria'];
						
						// Solo valido si la asignación que encontré de otra comision no es la misma que estoy evaluando de la comision.
						if ($asignacion_otra <> $asignacion_origen) {
							$hay_superposicion = guarani::co('co_bandas_horarias')->hay_clases_superpuestas_en_bh_solapadas($bh_origen, $bh_otra);
							if ($hay_superposicion) {
								break;
							}
						}	
					}
				}
			}
		}

		$resultado = $this->resultado_final(! $hay_superposicion);

		if (! $resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}
}
?>