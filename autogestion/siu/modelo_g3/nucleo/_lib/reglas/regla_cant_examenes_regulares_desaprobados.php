<?php
class regla_cant_examenes_regulares_desaprobados extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$fecha = $this->get_parametro('fecha');

		// Se obtiene el plan actual del alumno.
		$datos_alumno = current(guarani::alumno()->get_datos_alumno($alumno));
		$plan = $datos_alumno['plan'];

		$resultado = true;
		$cant_examenes_desap = guarani::co('co_alumnos')->get_cant_examenes_regulares_desaprobados($alumno, $actividad, $fecha);
		if ($cant_examenes_desap > 0) { // Sólo se controla si existen exámenes rendidos como regular desaprobados.
			// Se obtiene la regularidad vigente del alumno a la fecha.
			$regularidad_vigente_actividad = toba::consulta_php('co_alumnos')->get_regularidad_vigente_actividad($alumno, $actividad, $fecha);
			$cant_veces_permitido = (empty($regularidad_vigente_actividad) || $regularidad_vigente_actividad['origen'] != 'C') ? guarani::parametros()->get_valor_en_reglas('exa_cant_veces_rendir_examen_regular', $plan) : guarani::parametros()->get_valor_en_reglas('exa_cant_veces_rendir_examen_regular_equiv', $plan);
			$resultado = $cant_examenes_desap < $cant_veces_permitido;
		}

		// Evaluo la cantidad de veces que el alumno desaprobÃ³ la actividad y la cantidad de veces que puede desaprobar segun como la regularizÃ³ (cursada o equiv de regularidad)
		$resultado = $this->resultado_final($resultado);

		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $cant_veces_permitido), true);
		}
		return $resultado;
	}

}
?>