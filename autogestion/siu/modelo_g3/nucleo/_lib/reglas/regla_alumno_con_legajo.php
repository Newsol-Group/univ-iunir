<?php
class regla_alumno_con_legajo extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));
		$tiene_legajo_generado = !is_null($datos_alumno['legajo']);
		$resultado = $this->resultado_final($tiene_legajo_generado);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$nombre_propuesta = guarani::alumno()->get_alumno_propuesta_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $nombre_propuesta), true);
		}
		return $resultado;
	}

}
?>