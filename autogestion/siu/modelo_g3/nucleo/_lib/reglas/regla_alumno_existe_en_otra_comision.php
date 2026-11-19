<?php
class regla_alumno_existe_en_otra_comision extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$comision = $this->get_parametro('comision');

		$tiene_inscripciones = toba::consulta_php('co_alumnos')->tiene_inscripciones_actividad_periodo_lectivo($alumno, $actividad, $comision);
		$resultado = $this->resultado_final(!$tiene_inscripciones);

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>