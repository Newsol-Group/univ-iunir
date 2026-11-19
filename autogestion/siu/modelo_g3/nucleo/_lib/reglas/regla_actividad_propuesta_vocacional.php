<?php
class regla_actividad_propuesta_vocacional extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$fecha = $this->get_parametro('fecha');

		// Recupera el tipo de propuesta y el id de persona
		$datos_alumno = toba::consulta_php('co_alumnos')->get_persona_proptipo_alumno($alumno);

		// Si la propuesta no es vocacional devolver OK (NO FALLA EL CONTROL)
		if ($datos_alumno['propuesta_tipo'] != g3entidad::subtipo_vocacional) {
			$resultado = true;
		} else {// Sino, hacer el siguiente control:
			// Devuelve true si la actividad se encuentra en otra propueta NO vocacional del alumno
			$resultado = (!toba::consulta_php('co_alumnos')->tiene_actividad_en_propuesta_novocacional($alumno, $datos_alumno['persona'], $actividad));
		}

		$resultado = $this->resultado_final($resultado);

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$plan_version = $this->existe_parametro('plan_version') ? $this->get_parametro('plan_version') : guarani::alumno()->get_plan_version($alumno, $fecha);
			$datos_basicos_elemento = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($actividad, $plan_version); 
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $datos_basicos_elemento['codigo_nombre']), true);
		}

		return $resultado;
	}

}
?>