<?php
class regla_actividad_no_regularizada extends regla
{

	/*
	  Se valida que el alumno NO tenga la actividad regularizada y vigente a la fecha de control.
	*/
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$fecha = $this->get_parametro('fecha');

		// Verifico si el alumno tiene la actividad regularizada y vigente. 
		$actividad_cursada = guarani::alumno()->actividad_regularizada($alumno, $actividad, $fecha);

		// Falla si el alumno tiene la actividad cursada y vigente.
		$resultado = $this->resultado_final(! $actividad_cursada);
		if (! $resultado) {
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
		$actividad_cursada = toba::consulta_php('co_casos_testeo')->actividad_cursada_caso_testeo($actividad, $caso_test);
		return $this->resultado_final(! $actividad_cursada);
	}
}
?>