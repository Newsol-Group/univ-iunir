<?php
class regla_n_creditos_actividades_regularizadas extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$alumno = $this->get_parametro('alumno');

		// Parámetros.
		$creditos = $this->get_parametro('creditos');

		$creditos_regularidades = toba::consulta_php('co_alumnos')->get_creditos_regularidades($alumno);

		$resultado = $this->resultado_final($creditos_regularidades >= $creditos);
		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($creditos, $creditos_regularidades));
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>