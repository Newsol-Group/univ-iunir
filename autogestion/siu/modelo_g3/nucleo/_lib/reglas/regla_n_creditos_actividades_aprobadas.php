<?php
class regla_n_creditos_actividades_aprobadas extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$alumno = $this->get_parametro('alumno');

		// Parámetros.
		$creditos = $this->get_parametro('creditos');

		$creditos_actividades_aprobadas = toba::consulta_php('co_alumnos')->get_creditos_actividades_aprobadas($alumno);

		$resultado = $this->resultado_final($creditos_actividades_aprobadas >= $creditos);
		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($creditos, $creditos_actividades_aprobadas));
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>