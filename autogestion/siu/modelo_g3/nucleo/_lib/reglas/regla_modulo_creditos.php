<?php
class regla_modulo_creditos extends regla
{
	/**
	 * Deja los créditos del módulo en la clave de cache 'creditos_obtenidos'.
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
			$componentes_modulo = planes::get_componentes_modulo($modulo, $plan_version, $alumno);
			foreach ($componentes_modulo as $componente) {
				$creditos_componente = 0;
				if ($componente['entidad_tipo'] == g3entidad::tipo_actividad) {
					list($componente_aprobado, $creditos_componente) = toba::consulta_php('co_alumnos')->get_creditos_actividad_aprobada($alumno, $componente['elemento']);
				} else {
					$regla = guarani::regla($componente['regla']);
					$regla->set_parametros($componente['parametros']);

					$parametros_contexto = $this->get_parametros_contexto();
					$parametros_contexto['elemento'] = $componente['elemento'];
					$regla->set_parametros_contexto($parametros_contexto);

					$componente_aprobado = $regla->validar();
					if ($componente['origen_creditos'] == elemento::creditos_componentes) {
						$creditos_componente = $regla->get_cache('creditos_obtenidos');
					} elseif ($componente['origen_creditos'] == elemento::creditos_modulo) {
						$creditos_componente = $componente['creditos'];
					}
				}
				if ($componente_aprobado) {
					$this->set_cache('creditos_obtenidos', $this->get_cache('creditos_obtenidos') + $creditos_componente);
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

	/**
	 * Deja los créditos del módulo en la clave de cache 'creditos_obtenidos'.
	 */
	function validar_con_config_ha($excluidas)
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
			$componentes_modulo = planes::get_componentes_modulo($modulo, $plan_version, $alumno);
			foreach ($componentes_modulo as $componente) {
				$creditos_componente = 0;
				if ($componente['entidad_tipo'] == g3entidad::tipo_actividad) {
					$excluida = in_array($componente['elemento'], $excluidas);
					list($componente_aprobado, $creditos_componente) = toba::consulta_php('co_alumnos')->get_creditos_actividad_aprobada($alumno, $componente['elemento']);
					$componente_aprobado = !$excluida && $componente_aprobado;
				} else {
					$regla = guarani::regla($componente['regla']);
					$regla->set_parametros($componente['parametros']);

					$parametros_contexto = $this->get_parametros_contexto();
					$parametros_contexto['elemento'] = $componente['elemento'];
					$regla->set_parametros_contexto($parametros_contexto);

					$componente_aprobado = $regla->validar_con_config_ha($excluidas);
					if ($componente['origen_creditos'] == elemento::creditos_componentes) {
						$creditos_componente = $regla->get_cache('creditos_obtenidos');
					} elseif ($componente['origen_creditos'] == elemento::creditos_modulo) {
						$creditos_componente = $componente['creditos'];
					}
				}
				if ($componente_aprobado) {
					$this->set_cache('creditos_obtenidos', $this->get_cache('creditos_obtenidos') + $creditos_componente);
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
		$componentes_modulo = toba::consulta_php('co_planes')->get_componentes_modulo($modulo, $plan_version);

		$this->set_cache('creditos_obtenidos', 0);
		foreach ($componentes_modulo as $componente) {
			$creditos_componente = 0;
			if ($componente['entidad_tipo'] == g3entidad::tipo_actividad) {
				$componente_aprobado = toba::consulta_php('co_casos_testeo')->actividad_aprobada_caso_testeo($componente['elemento'], $caso_test);
				$creditos_componente = $componente['creditos'];
			} else {
				$regla = guarani::regla($componente['regla']);
				$regla->set_parametros($componente['parametros']);

				$parametros_contexto = $this->get_parametros_contexto();
				$parametros_contexto['elemento'] = $componente['elemento'];
				$regla->set_parametros_contexto($parametros_contexto);

				$componente_aprobado = $regla->validar_caso_test($caso_test);
				if ($componente['origen_creditos'] == elemento::creditos_componentes) {
					$creditos_componente = $regla->get_cache('creditos_obtenidos');
				} elseif ($componente['origen_creditos'] == elemento::creditos_modulo) {
					$creditos_componente = $componente['creditos'];
				}
			}
			if ($componente_aprobado) {
				$this->set_cache('creditos_obtenidos', $this->get_cache('creditos_obtenidos') + $creditos_componente);
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
				$creditos_componente = 0;
				if ($componente['entidad_tipo'] == g3entidad::tipo_actividad) {
					$componente_aprobado = isset($historia_academica[$componente['elemento']]);
					$creditos_componente = isset($historia_academica[$componente['elemento']]) ? $historia_academica[$componente['elemento']]['creditos'] : $componente['creditos'];
				} else {
					$regla = guarani::regla($componente['regla']);
					$regla->set_parametros($componente['parametros']);

					$parametros_contexto = $this->get_parametros_contexto();
					$parametros_contexto['elemento'] = $componente['elemento'];
					$regla->set_parametros_contexto($parametros_contexto);

					$componente_aprobado = $regla->validar_cache($componente['componentes'], $historia_academica, $regularidades, $aprobaciones_por_resolucion);
					if ($componente['origen_creditos'] == elemento::creditos_componentes) {
						$creditos_componente = $regla->get_cache('creditos_obtenidos');
					} elseif ($componente['origen_creditos'] == elemento::creditos_modulo) {
						$creditos_componente = $componente['creditos'];
					}
				}
				if ($componente_aprobado) {
					$this->set_cache('creditos_obtenidos', $this->get_cache('creditos_obtenidos') + $creditos_componente);
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