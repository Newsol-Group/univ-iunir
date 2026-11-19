<?php
class regla_porcentaje_actividades_aprobadas_un_anio extends regla
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
		$porcentaje_aprobadas = ($cant_total > 0) ? 0 : 100; // Si no hay actividades en el año tiene el 100% aprobado.
		if ($cant_total > 0) {
			$cant_aprobadas = toba::consulta_php('co_alumnos')->get_cant_actividades_aprobadas_anio_cursada($alumno, $plan_version, $anio);
			$porcentaje_aprobadas = $cant_aprobadas / $cant_total;
		}

		$resultado = $this->resultado_final($porcentaje_aprobadas >= $porcentaje);
		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($porcentaje * 100, $anio));
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>