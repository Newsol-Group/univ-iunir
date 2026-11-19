<?php
class regla_alumno_regular extends regla
{
	/**
	 * Controla la regularidad de un alumno. 
	 */
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));
		$es_regular = $datos_alumno['regular'] == comunes::si;
		$resultado = $this->resultado_final($es_regular);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$nombre_propuesta = $datos_alumno['propuesta_nombre'];
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $nombre_propuesta), true);
		}
		return $resultado;
	}

}
?>