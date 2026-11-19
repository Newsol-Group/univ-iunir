<?php
class regla_modulo_horas_aprobadas extends regla
{
	/**
	 * Deja las horas del módulo en la clave de cache 'horas_obtenidas'.
	 * Cuenta las horas de las actividades aprobadas por examen final, promocion, equivalencia, resolución.
	 */
	function validar()
	{
		// Parámetros. Obtengo las horas a cumplir de actividades aprobadas del modulo a evaluar
		$cant_horas_a_cumplir = $this->get_parametro('horas');

		// Parámetros de contexto.
		$modulo       = $this->get_parametro('elemento');
		$plan_version = $this->get_parametro('plan_version');
		$alumno       = $this->get_parametro('alumno');
		$total_horas_obtenidas = 0;  // Cantidad de horas de las actividades aprobadas del módulo
		
		// Verifico si el modulo debe informar creditos de actividades a su modulo padre.
		$origen_creditos_modulo = guarani::elementos()->get_origen_creditos_elemento_plan_version($modulo, $plan_version);
		$this->set_cache('creditos_obtenidos', 0);
		
		// Verifico si el módulo esta aprobado por resolucion en su totalidad..
		$aprobacion_por_resolucion = false;
		$reconocimiento = toba::consulta_php('co_aprobacion_por_resolucion')->get_reconocimiento_alumno_modulo($alumno, $modulo);
		if (!empty($reconocimiento)) {
			$this->set_cache('creditos_obtenidos', $reconocimiento['creditos']);
			// El modulo se reconoció en su totalidad.
			if ($reconocimiento['reconocimiento_total'] == comunes::si) {
				$aprobacion_por_resolucion = true;
			}
		}

		// El módulo no fue reconocido en su totalidad por resolución.
		if (!$aprobacion_por_resolucion) {
			$filtro = 'sga_g3entidades_subtipos.entidad_tipo = ' . g3entidad::tipo_actividad;
			$actividades_modulo = planes::get_componentes_modulo($modulo, $plan_version, $alumno, $filtro);
			// Recorro las actividades del modulo.
			foreach ($actividades_modulo as $actividad) {
				list($actividad_aprobada, $creditos) = toba::consulta_php('co_alumnos')->get_creditos_actividad_aprobada($alumno, $actividad['elemento']);
				if ($actividad_aprobada) {
					$total_horas_obtenidas = $total_horas_obtenidas + $actividad['horas'];
					// Sumo los creditos de la actividad aprobada
					$this->set_cache('creditos_obtenidos', $this->get_cache('creditos_obtenidos') + $creditos);
					
					// Si no debe informar creditos de actividades aprobadas a su modulo padre y ya llego a cumplir las horas que necesitaba entonces salgo.
					if ($origen_creditos_modulo != elemento::creditos_componentes && $total_horas_obtenidas >= $cant_horas_a_cumplir) {
						break;
					}
				}
			}
		}
		
		// Verifico si la cantidad de horas de actividades regularizadas es mayor o igual a las horas a cumplir.
		// O aprobó el módulo por resolución en forma total
		$resultado = $this->resultado_final($aprobacion_por_resolucion || $total_horas_obtenidas >= $cant_horas_a_cumplir);

		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($cant_horas_a_cumplir));
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$datos_basicos_elemento = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($modulo, $plan_version);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $datos_basicos_elemento['nombre'], $total_horas_obtenidas), true);
		}
		return $resultado;
	}

	/**
	 * Deja los créditos del módulo en la clave de cache 'creditos_obtenidos'.
	 */
	function validar_con_config_ha($excluidas)
	{
		// Parámetros.
		$cant_horas_a_cumplir = $this->get_parametro('horas');

		// Parámetros de contexto.
		$modulo = $this->get_parametro('elemento');
		$plan_version = $this->get_parametro('plan_version');
		$alumno = $this->get_parametro('alumno');

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
			$origen_creditos_modulo = guarani::elementos()->get_origen_creditos_elemento_plan_version($modulo, $plan_version);
			$filtro = 'sga_g3entidades_subtipos.entidad_tipo = ' . g3entidad::tipo_actividad;
			$actividades_modulo = planes::get_componentes_modulo($modulo, $plan_version, $alumno, $filtro);
			$total_horas_obtenidas = 0;
			foreach ($actividades_modulo as $actividad) {
				$excluida = in_array($actividad['elemento'], $excluidas);
				list($actividad_aprobada, $creditos) = toba::consulta_php('co_alumnos')->get_creditos_actividad_aprobada($alumno, $actividad['elemento']);
				$actividad_aprobada = !$excluida && $actividad_aprobada;
				if ($actividad_aprobada) {
					$this->set_cache('creditos_obtenidos', $this->get_cache('creditos_obtenidos') + $creditos);
					$total_horas_obtenidas = $total_horas_obtenidas + $actividad['horas'];
					if ($origen_creditos_modulo != elemento::creditos_componentes && $total_horas_obtenidas >= $cant_horas_a_cumplir) {
						break;
					}
				}
			}
		}

		$resultado = $this->resultado_final($aprobacion_por_resolucion || $total_horas_obtenidas >= $cant_horas_a_cumplir);

		if (!$resultado) {
			$this->set_parametros_mensaje_validacion(array($cant_horas_a_cumplir));
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$datos_basicos_elemento = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($modulo, $plan_version);
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $datos_basicos_elemento['nombre'], $total_horas_obtenidas), true);
		}
		return $resultado;
	}

	function validar_caso_test($caso_test)
	{
		// Parámetros.
		$cant_horas_a_cumplir  = $this->get_parametro('horas');

		$total_horas_obtenidas = 0;
		$this->set_cache('creditos_obtenidos', 0);

		// Parámetros de contexto.
		$modulo       = $this->get_parametro('elemento');
		$plan_version = $this->get_parametro('plan_version');

		$origen_creditos_modulo = guarani::elementos()->get_origen_creditos_elemento_plan_version($modulo, $plan_version);
		$filtro = 'sga_g3entidades_subtipos.entidad_tipo = ' . g3entidad::tipo_actividad;
		$actividades_modulo = toba::consulta_php('co_planes')->get_componentes_modulo($modulo, $plan_version, $filtro);
		
		// Recorro las actividades del modulo
		foreach ($actividades_modulo as $actividad) {
			$actividad_aprobada = toba::consulta_php('co_casos_testeo')->actividad_aprobada_caso_testeo($actividad['elemento'], $caso_test);
			if ($actividad_aprobada) {
				$total_horas_obtenidas = $total_horas_obtenidas + $actividad['horas'];
				$this->set_cache('creditos_obtenidos', $this->get_cache('creditos_obtenidos') + $actividad['creditos']);
				if ($origen_creditos_modulo != elemento::creditos_componentes && $total_horas_obtenidas >= $cant_horas_a_cumplir) {
					break;
				}
			}
		}
		return $this->resultado_final($total_horas_obtenidas >= $cant_horas_a_cumplir);
	}

	function validar_cache($componentes_modulo, $historia_academica, $regularidades, $aprobaciones_por_resolucion)
	{
		// Parámetros.
		$cant_horas_a_cumplir = $this->get_parametro('horas');

		$total_horas_obtenidas = 0;
		$this->set_cache('creditos_obtenidos', 0);
		
		
		// Parámetros de contexto.
		$modulo       = $this->get_parametro('elemento');
		$plan_version = $this->get_parametro('plan_version');

		$origen_creditos_modulo = guarani::elementos()->get_origen_creditos_elemento_plan_version($modulo, $plan_version);

		// Verifico si el modulo esta cumplido en su totalidad. Obtengo los creditos reconocidos.
		$aprobacion_por_resolucion = false;
		if (isset($aprobaciones_por_resolucion[$modulo])) {
			$this->set_cache('creditos_obtenidos', $aprobaciones_por_resolucion[$modulo]['creditos']);
			if ($aprobaciones_por_resolucion[$modulo]['reconocimiento_total'] == comunes::si) {
				$aprobacion_por_resolucion = true;
			}
		}

		if (!$aprobacion_por_resolucion) {
			foreach ($componentes_modulo as $componente) {
				if ($componente['entidad_tipo'] == g3entidad::tipo_actividad && isset($historia_academica[$componente['elemento']])) {
					// Obtengo creditos de cada actividad aprobada.
					$creditos = $historia_academica[$componente['elemento']]['creditos'];
					$this->set_cache('creditos_obtenidos', $this->get_cache('creditos_obtenidos') + $creditos);
					
					// Obtengo horas de cada actividad aprobada	 
					$total_horas_obtenidas = $total_horas_obtenidas + $historia_academica[$componente['elemento']]['horas'];
					
					if ($origen_horas_modulo != elemento::creditos_componentes && $total_horas_obtenidas >= $cant_horas_a_cumplir) {
						break;
					}
				}
			}
		}

		return $this->resultado_final($aprobacion_por_resolucion || $total_horas_obtenidas >= $cant_horas_a_cumplir);
	}
}
?>