<?php
class regla_regularidad_vencida extends regla
{
	function validar()
	{
		// Verifica que la última cursada de la actividad esté aprobada y vencida a la fecha del examen.
		// Tambien tiene en cuenta equivalencias de regularidad internas (cambio de plan, version de plan o cursada simultanea).
		// Busca cursada de la actividad en la misma propuesta u otras propuestas del alumno (actividad comun entre propuestas)
		// Usado en Actas de Examen - Instancia Reválida de Cursada.
		$alumno = $this->get_parametro('alumno');
		$elemento = $this->get_parametro('elemento');
		$fecha = $this->get_parametro('fecha');

		// Verifica la ultima cursada/equivalencia de regularidad de la actividad con fecha anterior a la fecha del examen y que este aprobada y vencida.
		$resultado_regularidad = toba::consulta_php('co_alumnos')->get_id_ultima_regularidad_vencida($alumno, $elemento, $fecha, false);

		// Si se obtuvo id de acta y fecha de fin de vigencia entonces tiene la regularidad aprobada y vencida.
		$resultado = $this->resultado_final(!empty($resultado_regularidad));
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$plan_version = $this->existe_parametro('plan_version') ? $this->get_parametro('plan_version') : guarani::alumno()->get_plan_version($alumno, $fecha);
			$datos_basicos_elemento = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($elemento, $plan_version); 
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $datos_basicos_elemento['codigo_nombre']), true);
		}
		return $resultado;
	}

}
?>