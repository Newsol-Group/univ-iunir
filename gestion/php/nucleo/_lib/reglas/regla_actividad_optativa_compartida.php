<?php
class regla_actividad_optativa_compartida extends regla
{
	function validar()
	{
		// Parámetros de contexto
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$fecha = $this->get_parametro('fecha');

		// Se obtiene la versión del plan del alumno.
		$plan_version = $this->existe_parametro('plan_version') ? $this->get_parametro('plan_version') : guarani::alumno()->get_plan_version($alumno, $fecha);

		// Se verifica si la actividad es optativa, está compartida entre materias genéricas y el alumno eligió para cuál de ellas se cumple.
		$es_optativa_compartida = toba::consulta_php('co_alumnos')->optativa_esta_compartida($actividad, $plan_version, $alumno);

		$resultado = $this->resultado_final(($es_optativa_compartida != -1));
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$datos_basicos_elemento = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($actividad, $plan_version);
			$this->set_parametros_mensaje_validacion(array($datos_basicos_elemento['codigo_nombre'], $nombre_alumno), true);
		}
		return $resultado;
	}

}
?>