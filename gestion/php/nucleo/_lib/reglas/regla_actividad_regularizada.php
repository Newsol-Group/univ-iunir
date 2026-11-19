<?php
class regla_actividad_regularizada extends regla
{
	/**
	 * Se redefine el método para considerar el caso especial
	 * de las operaciones de cierre y rectificación de trámites de equivalencia,
	 * donde se envía un parámetro adicional correspondiente al trámite de referencia.
	 */
	function set_parametros_contexto($parametros_contexto=array())
	{
		if (!isset($parametros_contexto['id_equiv_tramite_original'])) {
			$parametros_contexto['id_equiv_tramite_original'] = -1;
		}
		parent::set_parametros_contexto($parametros_contexto);
	}

	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$fecha = $this->get_parametro('fecha');
		$id_equiv_tramite_original = $this->get_parametro('id_equiv_tramite_original');

		if ($id_equiv_tramite_original == -1) {
			// Verifico si el alumno tiene la actividad regularizada y vigente. 
			$actividad_cursada = guarani::alumno()->actividad_regularizada($alumno, $actividad, $fecha);
		} else {
			$persona = guarani::alumno()->get_persona_alumno($alumno);
			$actividad_cursada = guarani::alumno()->actividad_regularizada_rectificativa_equivalencia($persona, $actividad, $id_equiv_tramite_original, $fecha);
		}

		$resultado = $this->resultado_final($actividad_cursada);
		if (! $resultado) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$plan_version = $this->existe_parametro('plan_version') ? $this->get_parametro('plan_version') : guarani::alumno()->get_plan_version($alumno, $fecha);
			$datos_basicos_elemento = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($actividad, $plan_version); 
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $datos_basicos_elemento['codigo_nombre']), true);
		}
		return $resultado;
	}

	function validar_caso_test($caso_test)
	{
		$actividad = $this->get_parametro('elemento');
		$actividad_cursada = toba::consulta_php('co_casos_testeo')->actividad_cursada_caso_testeo($actividad, $caso_test);
		return $this->resultado_final($actividad_cursada);
	}
}
?>