<?php
class regla_turno_horario_cursada extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$alumno = $this->get_parametro('alumno');
		$elemento = $this->get_parametro('elemento');
		$comision = $this->get_parametro('comision');
		$fecha = $this->get_parametro('fecha');

		// Parámetros.
		$anio_desde = $this->get_parametro('anio_desde');
		$anio_hasta = $this->get_parametro('anio_hasta');

		$plan_version = guarani::alumno()->get_plan_version($alumno, $fecha);
		$anio_cursada = guarani::co('co_elementos')->get_anio_de_cursada($elemento, $plan_version, $anio_desde, $anio_hasta);

		$resultado = true;
		if (!empty($anio_cursada)) {
			$turno_preferido = toba::consulta_php('co_alumnos')->get_turno_preferido($alumno);

			if (!empty($turno_preferido)) {
				$datos_comision = guarani::co('co_comisiones')->get_datos_basicos_comision($comision);
				$turno_comision = $datos_comision['turno'];

				if (!empty($turno_comision)) {
					$resultado = $turno_comision == $turno_preferido;
				}
			}
		}

		$resultado = $this->resultado_final($resultado);

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$nombre_turno_preferido = toba::consulta_php('co_comisiones')->get_nombre_turno_cursada($turno_preferido);
			$nombre_turno_comision = toba::consulta_php('co_comisiones')->get_nombre_turno_cursada($turno_comision);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $nombre_turno_comision, $nombre_turno_preferido), true);
		}

		return $resultado;
	}

}
?>