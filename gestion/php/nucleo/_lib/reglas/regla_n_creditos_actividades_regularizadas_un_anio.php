<?php
class regla_n_creditos_actividades_regularizadas_un_anio extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$alumno = $this->get_parametro('alumno');
		$plan_version = $this->get_parametro('plan_version');

		// Parámetros.
		$creditos = $this->get_parametro('creditos');
		$anio = $this->get_parametro('anio');

		$creditos_regularidades = toba::consulta_php('co_alumnos')->get_creditos_regularidades_anio_cursada($alumno, $plan_version, $anio);

		$resultado = $this->resultado_final($creditos_regularidades >= $creditos);
		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($creditos, $anio, $creditos_regularidades));
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>