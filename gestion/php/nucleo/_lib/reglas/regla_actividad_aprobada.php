<?php
class regla_actividad_aprobada extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$fecha = $this->get_parametro('fecha');
		$actividad_aprobada = guarani::alumno()->actividad_aprobada($alumno, $actividad, $fecha);
		$resultado = $this->resultado_final($actividad_aprobada);
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
		$actividad_aprobada = toba::consulta_php('co_casos_testeo')->actividad_aprobada_caso_testeo($actividad, $caso_test);
		return $this->resultado_final($actividad_aprobada);
	}
}
?>