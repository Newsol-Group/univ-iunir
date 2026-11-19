<?php
class regla_alumno_no_sancionado extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$fecha = $this->get_parametro('fecha');
		$esta_sancionado = guarani::co('co_alumnos')->es_alumno_sancionado($alumno, $fecha);
		$resultado = $this->resultado_final(!$esta_sancionado);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>