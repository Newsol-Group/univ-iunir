<?php
class regla_porcentaje_actividades_regularizadas_un_anio extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$alumno = $this->get_parametro('alumno');
		$plan_version = $this->get_parametro('plan_version');

		// Parámetros.
		$porcentaje = $this->get_parametro('porcentaje') / 100;
		$anio = $this->get_parametro('anio');

		$cant_total = toba::consulta_php('co_planes')->get_cant_actividades_anio_cursada($plan_version, $anio);
		$porcentaje_regularizadas = ($cant_total > 0) ? 0 : 100; // Si no hay actividades en el año tiene el 100% cursado.
		if ($cant_total > 0) {
			$cant_regularizadas = toba::consulta_php('co_alumnos')->get_cant_regularidades_anio_cursada($alumno, $plan_version, $anio);
			$porcentaje_regularizadas = $cant_regularizadas / $cant_total;
		}

		$resultado = $this->resultado_final($porcentaje_regularizadas >= $porcentaje);
		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($porcentaje * 100, $anio));
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>