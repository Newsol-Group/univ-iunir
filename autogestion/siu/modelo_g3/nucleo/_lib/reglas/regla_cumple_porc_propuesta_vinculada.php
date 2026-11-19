<?php
class regla_cumple_porc_propuesta_vinculada extends regla
{
	function validar()
	{
		$persona = $this->get_parametro('persona');
		$propuesta = $this->get_parametro('propuesta');

		$datos_inscripcion = toba::consulta_php('co_alumnos')->get_datos_inscripcion($persona, $propuesta);

		// Se recupera la relación entre propuestas. Este metodo solo recupera una relación en base al año y plan de ingreso del alumno.
		$relacion = toba::consulta_php('co_propuestas_relaciones')->get_relacion_por_anio_academico_propuesta_plan_version($datos_inscripcion['anio_academico'], $propuesta, $datos_inscripcion['plan_version']);
		$cumple_propuesta = empty($relacion) ? true : $this->validar_relacion($persona, $relacion);

		$resultado = $this->resultado_final($cumple_propuesta);
		if (!$resultado) {
			$nombre_persona = guarani::personas()->get_nombre_persona($persona);
			// Si no hay relación definida y el requisito está negado, el nombre de las propuestas es vacío.
			$nombre_propuestas = empty($relacion) ? '' : toba::consulta_php('co_propuestas_relaciones')->get_nombre_grupos_por_relacion($relacion);
			$this->set_parametros_mensaje_validacion(array($nombre_persona, $nombre_propuestas), true);
		}
		return $resultado;
	}

	protected function validar_relacion($persona, $relacion)
	{
		$grupos = toba::consulta_php('co_propuestas_relaciones')->get_grupos_por_relacion($relacion);
		foreach ($grupos as $grupo) {
			$grupo_cumplido = true;
			$planes = toba::consulta_php('co_propuestas_relaciones')->get_planes_por_grupo($grupo['relacion_grupo']);
			foreach ($planes as $plan) {
				$datos_otro_alumno = toba::consulta_php('co_alumnos')->get_alumno($persona, $plan['propuesta']);
				// Si el alumno no está inscripto en la propuesta o no tiene cumplido el n% de la misma...
				if (empty($datos_otro_alumno) || !$this->validar_plan($datos_otro_alumno['alumno'], $datos_otro_alumno['plan_version'])) {
					$grupo_cumplido = false;
					break;
				}
			}
			if ($grupo_cumplido) {
				return true;
			}
		}
		return false;
	}

	protected function validar_plan($alumno, $plan_version)
	{
		$n = $this->get_parametro('porcentaje');
		$modulo_raiz = toba::consulta_php('co_planes')->get_modulo_raiz($plan_version);
		$regla = guarani::regla(regla::porc_aprobadas);
		$regla->set_parametros($n);
		$regla->set_parametros_contexto(array('elemento' => $modulo_raiz['elemento'], 'alumno' => $alumno, 'plan_version' => $plan_version));
		return $regla->validar();
	}
}