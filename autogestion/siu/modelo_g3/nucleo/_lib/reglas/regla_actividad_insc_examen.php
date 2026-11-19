<?php
class regla_actividad_insc_examen extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$actividad_insc_examen = toba::consulta_php('co_alumnos')->actividad_inscripto_examen($alumno, $actividad, true);
		$resultado = $this->resultado_final($actividad_insc_examen);
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