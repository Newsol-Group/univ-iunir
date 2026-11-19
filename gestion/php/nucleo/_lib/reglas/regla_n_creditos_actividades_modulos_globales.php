<?php
class regla_n_creditos_actividades_modulos_globales extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$alumno = $this->get_parametro('alumno');

		// Parámetros.
		$creditos = $this->get_parametro('creditos');

		// Recupero los creditos obtenidos por el alumno en el plan por actividades aprobadas, por módulos reconocidos y créditos globales.
		$creditos_obtenidos = toba::consulta_php('co_alumnos')->get_creditos_alumno($alumno, true, true, true);

		// Cumple la regla si los créditos obtenidos son mayores o iguales a los créditos necesarios para cumplir el requisito.
		$resultado = $this->resultado_final($creditos_obtenidos >= $creditos);
		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($creditos, $creditos_obtenidos));
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}
}