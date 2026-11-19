<?php
class regla_actividad_insc_cursada extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		
		// Busco si esta inscripto para cursar en la actividad en un periodo lectivo vigente o futuro (no finalizado)
		$actividad_insc_cursada = toba::consulta_php('co_alumnos')->actividad_inscripto_cursada($alumno, $actividad, true);
		
		$resultado = $this->resultado_final($actividad_insc_cursada);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$plan_version = $this->existe_parametro('plan_version') ? $this->get_parametro('plan_version') : guarani::alumno()->get_plan_version($alumno, $fecha);
			$datos_basicos_elemento = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($actividad, $plan_version);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $datos_basicos_elemento['codigo_nombre']), true);
		}
		return $resultado;
	}

	function validar_caso_test($caso_test)
	{
		$actividad = $this->get_parametro('elemento');
		$actividad_insc_cursada = toba::consulta_php('co_casos_testeo')->actividad_inscripto_cursada_caso_testeo($actividad, $caso_test);
		return $this->resultado_final($actividad_insc_cursada);
	}

}
?>