<?php
class regla_modulo_creditos_regularizadas extends regla
{
	/**
	 * Deja los créditos del módulo en la clave de cache 'creditos_obtenidos'.
	 * Cuenta los créditos de las actividades regularizadas vigentes o actividades aprobadas por examen final, promocion, equivalencia, resolución.
	 * Si el módulo esta reconocido por resolución se recuperan los créditos asignados y se suman a los créditos de las actividades regularizadas del módulo
	 */
	function validar()
	{
		// Parámetros.
		$n = $this->get_parametro('creditos');

		// Parámetros de contexto.
		$modulo = $this->get_parametro('elemento');
		$plan_version = $this->get_parametro('plan_version');
		$alumno = $this->get_parametro('alumno');

		$origen_creditos_modulo = guarani::elementos()->get_origen_creditos_elemento_plan_version($modulo, $plan_version);

		$this->set_cache('creditos_obtenidos', 0);

		$aprobacion_por_resolucion = false;
		$reconocimiento = toba::consulta_php('co_aprobacion_por_resolucion')->get_reconocimiento_alumno_modulo($alumno, $modulo);
		if (!empty($reconocimiento)) {
			$this->set_cache('creditos_obtenidos', $reconocimiento['creditos']);
			if ($reconocimiento['reconocimiento_total'] == comunes::si || ($origen_creditos_modulo != elemento::creditos_componentes && $reconocimiento['creditos'] >= $n)) {
				$aprobacion_por_resolucion = true;
			}
		}

		if (!$aprobacion_por_resolucion) {
			$filtro = 'sga_g3entidades_subtipos.entidad_tipo = ' . g3entidad::tipo_actividad;
			$actividades_modulo = planes::get_componentes_modulo($modulo, $plan_version, $alumno, $filtro);
			foreach ($actividades_modulo as $actividad) {
				list($actividad_cursada, $creditos) = toba::consulta_php('co_alumnos')->get_creditos_actividad_regularizada($alumno, $actividad['elemento']);
				if ($actividad_cursada) {
					$this->set_cache('creditos_obtenidos', $this->get_cache('creditos_obtenidos') + $creditos);
					if ($origen_creditos_modulo != elemento::creditos_componentes && $this->get_cache('creditos_obtenidos') >= $n) {
						break;
					}
				}
			}
		}

		$resultado = $this->resultado_final($aprobacion_por_resolucion || $this->get_cache('creditos_obtenidos') >= $n);

		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($n));
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$datos_basicos_elemento = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($modulo, $plan_version);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $datos_basicos_elemento['nombre'], $this->get_cache('creditos_obtenidos')), true);
		}
		return $resultado;
	}

	function validar_caso_test($caso_test)
	{
		// Parámetros.
		$n = $this->get_parametro('creditos');

		// Parámetros de contexto.
		$modulo = $this->get_parametro('elemento');
		$plan_version = $this->get_parametro('plan_version');

		$origen_creditos_modulo = guarani::elementos()->get_origen_creditos_elemento_plan_version($modulo, $plan_version);
		$filtro = 'sga_g3entidades_subtipos.entidad_tipo = ' . g3entidad::tipo_actividad;
		$actividades_modulo = toba::consulta_php('co_planes')->get_componentes_modulo($modulo, $plan_version, $filtro);

		$this->set_cache('creditos_obtenidos', 0);
		foreach ($actividades_modulo as $actividad) {
			$actividad_cursada = toba::consulta_php('co_casos_testeo')->actividad_cursada_caso_testeo($actividad['elemento'], $caso_test);
			if ($actividad_cursada) {
				$this->set_cache('creditos_obtenidos', $this->get_cache('creditos_obtenidos') + $actividad['creditos']);
				if ($origen_creditos_modulo != elemento::creditos_componentes && $this->get_cache('creditos_obtenidos') >= $n) {
					break;
				}
			}
		}
		return $this->resultado_final($this->get_cache('creditos_obtenidos') >= $n);
	}

	function validar_cache($componentes_modulo, $historia_academica, $regularidades, $aprobaciones_por_resolucion)
	{
		// Parámetros.
		$n = $this->get_parametro('creditos');

		// Parámetros de contexto.
		$modulo = $this->get_parametro('elemento');
		$plan_version = $this->get_parametro('plan_version');

		$origen_creditos_modulo = guarani::elementos()->get_origen_creditos_elemento_plan_version($modulo, $plan_version);

		$this->set_cache('creditos_obtenidos', 0);

		$aprobacion_por_resolucion = false;
		if (isset($aprobaciones_por_resolucion[$modulo])) {
			$this->set_cache('creditos_obtenidos', $aprobaciones_por_resolucion[$modulo]['creditos']);
			if ($aprobaciones_por_resolucion[$modulo]['reconocimiento_total'] == comunes::si || ($origen_creditos_modulo != elemento::creditos_componentes && $aprobaciones_por_resolucion[$modulo]['creditos'] >= $n)) {
				$aprobacion_por_resolucion = true;
			}
		}

		if (!$aprobacion_por_resolucion) {
			foreach ($componentes_modulo as $componente) {
				if ($componente['entidad_tipo'] == g3entidad::tipo_actividad && (isset($regularidades[$componente['elemento']]) || isset($historia_academica[$componente['elemento']]))) {
					$creditos = isset($historia_academica[$componente['elemento']]) ? $historia_academica[$componente['elemento']]['creditos'] : $regularidades[$componente['elemento']]['creditos'];
					$this->set_cache('creditos_obtenidos', $this->get_cache('creditos_obtenidos') + $creditos);
					if ($origen_creditos_modulo != elemento::creditos_componentes && $this->get_cache('creditos_obtenidos') >= $n) {
						break;
					}
				}
			}
		}

		return $this->resultado_final($aprobacion_por_resolucion || $this->get_cache('creditos_obtenidos') >= $n);
	}

}
?>