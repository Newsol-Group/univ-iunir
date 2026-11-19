<?php
class regla_n_actividades_regularizadas extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$alumno = $this->get_parametro('alumno');

		// Parámetros.
		$cantidad = $this->get_parametro('cantidad');

		$cant_regularizadas = toba::consulta_php('co_alumnos')->get_cant_regularidades($alumno);

		$resultado = $this->resultado_final($cant_regularizadas >= $cantidad);
		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($cantidad));
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>