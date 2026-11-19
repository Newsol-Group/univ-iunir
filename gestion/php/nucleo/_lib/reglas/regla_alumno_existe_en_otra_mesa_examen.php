<?php
class regla_alumno_existe_en_otra_mesa_examen extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$llamado_mesa = $this->get_parametro('llamado_mesa');

		$tiene_inscripciones = toba::consulta_php('co_alumnos')->tiene_inscripciones_actividad_llamado($alumno, $actividad, $llamado_mesa);

		$resultado = $this->resultado_final(!$tiene_inscripciones);

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>