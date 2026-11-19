<?php
class regla_adeudar_hasta_n_actividades_turno_examen_especial extends regla
{
	function validar()
	{
		/*
		Si la mesa pertenece a un turno de examen especial, verifica que el alumno no adeude mas materias de las
		permitidas para poder rendir examen en este turno.
		*/
		
		// Parámetros de contexto.
		$alumno       = $this->get_parametro('alumno');
		$plan_version = $this->get_parametro('plan_version');
		$llamado_mesa = $this->get_parametro('llamado_mesa');
		
		$datos_llamado_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa($llamado_mesa);

		if ($datos_llamado_mesa['turno_examen_tipo'] <> 2) {
			// Si no es un turno de tipo ESPECIAL entonces no controlo este requisito. Sale sin error.
			$resultado = true;
			return $resultado;
		}	
		
		// Parámetros. Cantidad de actividades que puede adeudar el alumno en su plan de estudios.
		$cantidad_maxima_adeudada = $this->get_parametro('actividad');

		// Recupero cantidad de actividades aprobadas por el alumno y cantidad de actividades del plan de estudios.
		$cant_aprobadas     = toba::consulta_php('co_alumnos')->get_cant_actividades_aprobadas($alumno);
		$cant_plan_estudios = toba::consulta_php('co_planes')->get_cant_actividades_plan_version($plan_version);
		$cant_adeuda_plan   = $cant_plan_estudios - $cant_aprobadas;

		$resultado = $this->resultado_final($cant_adeuda_plan <= $cantidad_maxima_adeudada);
		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($cantidad));
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno), true);
		}
		return $resultado;
	}

}
?>