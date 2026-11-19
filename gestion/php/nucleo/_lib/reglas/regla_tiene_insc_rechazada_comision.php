<?php
class regla_tiene_insc_rechazada_comision extends regla
{
	function validar()
	{
		// Se verifica si el alumno tiene una inscripcion a cursada rechazada en la comision por cualquiera de sus propuestas.
		$alumno = $this->get_parametro('alumno');
		$comision = $this->get_parametro('comision');

		$tiene_inscripcion_rechazada = toba::consulta_php('co_alumnos')->tiene_inscripcion_rechazada_comision($alumno, $comision);

		$resultado = $this->resultado_final(!$tiene_inscripcion_rechazada);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$nombre_comision = toba::consulta_php('co_comisiones')->get_nombre_comision($comision);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $nombre_comision), true);
		}
		return $resultado;
	}

}
?>