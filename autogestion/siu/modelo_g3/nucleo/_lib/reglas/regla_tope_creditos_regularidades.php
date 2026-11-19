<?php
class regla_tope_creditos_regularidades extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$alumno = $this->get_parametro('alumno');
		$fecha = $this->get_parametro('fecha');
		$actividad = $this->get_parametro('elemento');

		$plan_version = guarani::alumno()->get_plan_version($alumno, $fecha);
		$tope_creditos = toba::consulta_php('co_planes')->get_tope_creditos_reg_vigentes($plan_version);

		$resultado = true;
		if ($tope_creditos != 0) {
			$creditos_regularidades = toba::consulta_php('co_alumnos')->get_creditos_regularidades_actividad($alumno, $fecha, $actividad);
			$creditos_actividad = guarani::elementos()->get_creditos_elemento_plan_version($actividad, $plan_version);
			$resultado = $this->resultado_final(!($creditos_regularidades + $creditos_actividad > $tope_creditos));
		}

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$datos_basicos_elemento = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($actividad, $plan_version);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $tope_creditos, $creditos_regularidades + $creditos_actividad, $datos_basicos_elemento['codigo_nombre']), true);
		}
		return $resultado;
	}

}
?>