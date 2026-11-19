<?php
class regla_actividad_no_aprobada extends regla
{
	/**
	 * Se redefine el método para considerar el caso especial
	 * de las operaciones de generación, cierre y agregado de alumnos a actas rectificativas
	 * donde se envía un parámetro adicional correspondiente al acta de referencia.
	 */
	function set_parametros_contexto($parametros_contexto=array())
	{
		if (!isset($parametros_contexto['id_acta_original'])) {
			$parametros_contexto['id_acta_original'] = -1;
		}
		if (!isset($parametros_contexto['id_equiv_tramite_original'])) {
			$parametros_contexto['id_equiv_tramite_original'] = -1;
		}
		if (!isset($parametros_contexto['comision'])) {
			$parametros_contexto['comision'] = -1;
		}
		if (!isset($parametros_contexto['cond_regularidad'])) {
			$parametros_contexto['cond_regularidad'] = -1;
		}
		parent::set_parametros_contexto($parametros_contexto);
	}

	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$fecha = $this->get_parametro('fecha');
		
		// Los siguientes datos son pasados en los cierres de rectificativas de actas y equivalencias.
		$id_acta_original = $this->get_parametro('id_acta_original');
		$id_equiv_tramite_original = $this->get_parametro('id_equiv_tramite_original');
		$comision = $this->get_parametro('comision');
		$cond_regularidad = $this->get_parametro('cond_regularidad');

		if ($id_acta_original == -1 && $id_equiv_tramite_original == -1) { 
			// Acta original.
			// La condición de regularidad tiene valor sólo para actas de regulares.
			$actividad_no_aprobada = ($cond_regularidad == -1) ? !guarani::alumno()->actividad_aprobada($alumno, $actividad, $fecha) : !guarani::alumno()->actividad_aprobada_comision($alumno, $actividad, $comision, $fecha);
		} else { 
			// Acta rectificativa.
			// $persona = guarani::alumno()->get_persona_alumno($alumno);
			if ($id_acta_original <> -1) { 
				// Rectificativa de regulares, promoción o examen.
				$comision = ($cond_regularidad <> -1) ? $comision : null;
				$actividad_no_aprobada = !guarani::alumno()->actividad_aprobada_rectificativa($alumno, $actividad, $id_acta_original, $fecha, $comision);
			} else { 
				// Rectificativa de equivalencia.
				$actividad_no_aprobada = !guarani::alumno()->actividad_aprobada_rectificativa_equivalencia($alumno, $actividad, $id_equiv_tramite_original, $fecha);
			}
		}

		$resultado = $this->resultado_final($actividad_no_aprobada);
		if (!$resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$plan_version = $this->existe_parametro('plan_version') ? $this->get_parametro('plan_version') : guarani::alumno()->get_plan_version($alumno, $fecha);
			$datos_basicos_elemento = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($actividad, $plan_version);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $datos_basicos_elemento['codigo_nombre']), true);
		}
		return $resultado;
	}
}
?>
