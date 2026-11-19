<?php
class regla_superposicion_horaria_comision extends regla
{
	/**
	 * Se redefine el set de parámetros de contexto de regla_nucleo.php para considerar el caso especial de la operación
	 * Distribuir Comisiones, donde se pasa un parámetro adicional, correspondiente a la Comisión Origen, que no es 
	 * necesario considerar en el resto de las operaciones al evaluar esta regla.
	 */
	function set_parametros_contexto($parametros_contexto = array())
	{
		if (!isset($parametros_contexto['comision_origen'])) {
			$parametros_contexto['comision_origen'] = $parametros_contexto['comision'];
		}
		parent::set_parametros_contexto($parametros_contexto);
	}

	/**
	 * Verifica la superposición del horario de cursada de la comisión respecto a las inscripciones del alumno.
	 * Tanto la comisión como el alumno son datos recibidos como parámetro de contexto.
	 *
	 * @return boolean false si existe superposición y true en caso contrario.
	 */
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$comision = $this->get_parametro('comision');
		$fecha = $this->get_parametro('fecha');

		// En la operación de Distribuir Comisiones se excluye la comisión origen de la comparación.
		$comision_origen = $this->get_parametro('comision_origen');
		$filtro = '';
		if ($comision != $comision_origen) {
			$filtro = "sga_comisiones_bh.comision <> $comision_origen";
		}

		$datos_actividad = guarani::elementos()->get_datos_basicos_elemento($actividad);

		$hay_superposicion = false;
		if ($datos_actividad['insc_cursada_por_prioridad'] == comunes::no || $comision != $comision_origen) {
			$subcomisiones = guarani::co('co_comisiones')->get_subcomisiones_comision($comision);
			if (guarani_arreglos::count($subcomisiones) == 0) { // Si la comision tiene subcomsiones, este control no se realiza (se asume que no hay superposición).
				$asignaciones = guarani::co('co_bandas_horarias')->get_listado_asignaciones_comision($comision);
				
				// Recorro las asignaciones horarias de la comision.
				foreach ($asignaciones as $asignacion) {
					$asignacion_origen = $asignacion['asignacion'];
					$bh_origen = $asignacion['banda_horaria'];
					
					// Recupero asignaciones horarias de inscripciones a otras comisiones.
					$asignaciones_superpuestas = guarani::co('co_bandas_horarias')->get_inscripciones_superpuestas($alumno, $comision, $asignacion['dia_semana'], $asignacion['hora_inicio'], $asignacion['hora_finalizacion'], $asignacion['fecha_desde'], $asignacion['fecha_hasta'], $filtro);
					
					if (!empty($asignaciones_superpuestas)) {
						// Para cada inscripción del alumno a otras comisiones/subcomisiones con 
						// asignaciones que se superpongan con las asignaciones de la comisión de 
						// la inscripción que se está dando de alta, debe verificarse si hay 
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
		}
		$resultado = $this->resultado_final(!$hay_superposicion);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}

		return $resultado;
	}

}
?>