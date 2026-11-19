<?php
class regla_reinscripcion_anual extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$anio_academico = $this->get_parametro('anio_academico');
		$fecha = $this->get_parametro('fecha');

		// Obtengo los datos basicos del alumno en la propuesta.
		$datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));

		// Por defecto indico que tiene la reinscripcion anual en la propuesta.
		$tiene_reinscripcion_anual = true;

		// Solo controlo la reinscripcion anual si el alumno esta Activo.
		if ($datos_alumno['calidad'] == alumno::calidad_activo) {
			// El valor del parámetro 'mat_reinscripcion_propuesta' para la propuesta del alumno debe ser 'S'.
			if (guarani::parametros()->get_valor_en_reglas('mat_reinscripcion_propuesta', $datos_alumno['propuesta']) == comunes::si) {
				// Si el alumno es ingresante, el valor del parámetro 'mat_reinscripcion_ingresante' para la propuesta debe ser 'S'.
				if (!toba::consulta_php('co_alumnos')->es_ingresante($alumno, $anio_academico) ||
					 guarani::parametros()->get_valor_en_reglas('mat_reinscripcion_ingresante', $datos_alumno['propuesta']) == comunes::si) {
					$tiene_reinscripcion_anual = guarani::co('co_alumnos')->tiene_reinscripcion_anual($alumno, $anio_academico, $fecha);
				}
			}
		}

		$resultado = $this->resultado_final($tiene_reinscripcion_anual);

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$nombre_propuesta = guarani::alumno()->get_alumno_propuesta_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $nombre_propuesta), true);
		}
		return $resultado;
	}

}
?>