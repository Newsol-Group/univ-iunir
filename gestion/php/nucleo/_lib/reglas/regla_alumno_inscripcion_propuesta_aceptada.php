<?php
class regla_alumno_inscripcion_propuesta_aceptada extends regla
{
	function validar()
	{
		// Parámetros de contexto
		$alumno = $this->get_parametro('alumno');
		$fecha = $this->get_parametro('fecha');

		$insc_aceptada = toba::consulta_php('co_alumnos')->inscripcion_propuesta_aceptada($alumno);
		$resultado = $this->resultado_final($insc_aceptada);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}
}
?>