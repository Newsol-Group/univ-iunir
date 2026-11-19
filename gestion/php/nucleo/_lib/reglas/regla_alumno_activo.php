<?php
class regla_alumno_activo extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$fecha = $this->get_parametro('fecha');
		$es_activo = toba::consulta_php('co_alumnos')->es_alumno_activo($alumno, $fecha);
		$resultado = $this->resultado_final($es_activo);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$nombre_propuesta = guarani::alumno()->get_alumno_propuesta_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $nombre_propuesta), true);
		}
		return $resultado;
	}

}
?>
