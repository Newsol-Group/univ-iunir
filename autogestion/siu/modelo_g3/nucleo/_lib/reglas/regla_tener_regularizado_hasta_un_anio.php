<?php
class regla_tener_regularizado_hasta_un_anio extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$alumno = $this->get_parametro('alumno');
		$plan_version = $this->get_parametro('plan_version');

		// Parámetros.
		$anio = $this->get_parametro('anio');

		$cant_total = toba::consulta_php('co_planes')->get_cant_actividades_anio_cursada($plan_version, $anio, '<=');
		$cant_regularizadas = toba::consulta_php('co_alumnos')->get_cant_regularidades_anio_cursada($alumno, $plan_version, $anio, '<=');

		$resultado = $this->resultado_final($cant_regularizadas == $cant_total);
		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($anio));
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}
}
?>