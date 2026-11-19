<?php 
class regla_modulo_siempre_cumplido extends regla
{
	/**
	 * Deja los créditos del módulo en la clave de cache 'creditos_obtenidos'.
	 */
	function validar()
	{
		$modulo = $this->get_parametro('elemento');
		$plan_version = $this->get_parametro('plan_version');
		$alumno = $this->get_parametro('alumno');

		$origen_creditos_modulo = guarani::elementos()->get_origen_creditos_elemento_plan_version($modulo, $plan_version);

		if ($origen_creditos_modulo == elemento::creditos_componentes) {
			$this->set_cache('creditos_obtenidos', 0);

			$aprobacion_por_resolucion = false;
			$reconocimiento = toba::consulta_php('co_aprobacion_por_resolucion')->get_reconocimiento_alumno_modulo($alumno, $modulo);
			if (!empty($reconocimiento)) {
				$this->set_cache('creditos_obtenidos', $reconocimiento['creditos']);
				if ($reconocimiento['reconocimiento_total'] == comunes::si) {
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
					}
				}
			}
		}

		$resultado = $this->resultado_final(true);
		return $resultado;
	}
	
	/**
	 * Deja los créditos del módulo en la clave de cache 'creditos_obtenidos'.
	 */
	function validar_con_config_ha($excluidas)
	{
		$modulo = $this->get_parametro('elemento');
		$plan_version = $this->get_parametro('plan_version');
		$alumno = $this->get_parametro('alumno');

		$origen_creditos_modulo = guarani::elementos()->get_origen_creditos_elemento_plan_version($modulo, $plan_version);

		if ($origen_creditos_modulo == elemento::creditos_componentes) {
			$componentes_modulo = planes::get_componentes_modulo($modulo, $plan_version, $alumno);

			$this->set_cache('creditos_obtenidos', 0);
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
				}
			}
		}

		$resultado = $this->resultado_final(true);
		return $resultado;
	}

	function validar_caso_test($caso_test)
	{
		$modulo = $this->get_parametro('elemento');
		$plan_version = $this->get_parametro('plan_version');

		$origen_creditos_modulo = guarani::elementos()->get_origen_creditos_elemento_plan_version($modulo, $plan_version);

		if ($origen_creditos_modulo == elemento::creditos_componentes) {
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
				}
			}
		}
		return $this->resultado_final(true);
	}

	function validar_cache($componentes_modulo, $historia_academica, $regularidades, $aprobaciones_por_resolucion)
	{
		$modulo = $this->get_parametro('elemento');
		$plan_version = $this->get_parametro('plan_version');

		$origen_creditos_modulo = guarani::elementos()->get_origen_creditos_elemento_plan_version($modulo, $plan_version);

		if ($origen_creditos_modulo == elemento::creditos_componentes) {
			$this->set_cache('creditos_obtenidos', 0);
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
				}
			}
		}
		return $this->resultado_final(true);
	}
}
?>