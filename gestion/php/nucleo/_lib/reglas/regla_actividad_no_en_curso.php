<?php
class regla_actividad_no_en_curso extends regla
{
	function validar()
	{
		/*
		  Verifica que el alumno no este cursando la actividad en un periodo lectivo vigente	
		*/
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		
		// Busco si esta inscripto para cursar en la actividad en un periodo lectivo vigente o futuro (no finalizado)
		$actividad_insc_cursada = toba::consulta_php('co_alumnos')->actividad_inscripto_cursada($alumno, $actividad, true);
		
		// Le paso al resultado que lo válido es que el alumno NO este inscripto para cursar
		$actividad_no_esta_cursandose = !$actividad_insc_cursada;
		
		$resultado = $this->resultado_final($actividad_no_esta_cursandose);
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
