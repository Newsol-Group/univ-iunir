<?php
class validador_actas_cursada_nucleo
{
	static protected $instancia;

	protected $hay_errores = false;
	protected $errores;

	protected $hay_notificaciones = false;
	protected $notificaciones;
	protected $interfaz = comunes::interfaz_gestion;

	/**
     * @return validador_actas_cursada
     */
	static function instancia()
	{
		if (! isset(self::$instancia)) {
			self::$instancia = new validador_actas_cursada();
		}
		return self::$instancia;
	}

	//----------------------------------------------------------------------------------------
	//---- Manejo de errores y notificaciones ------------------------------------------------
	//----------------------------------------------------------------------------------------	

	/**
	 * Acumulación de mensajes.
	 *
	 * @param string $descripcion_corta
	 * @param array $params
	 * @param string $tipo
	 */
	function set_error($descripcion_corta, $params=array(), $tipo=comunes::error_critico)
	{
		$this->hay_errores = true;
		$this->errores[] = array('descripcion_corta' => $descripcion_corta, 'params' => $params, 'tipo' => $tipo);
	}

	function set_notificacion($descripcion_corta, $params=null, $tipo=comunes::error_advertencia)
	{
		$this->hay_notificaciones = true;
		$this->notificaciones[] = array('descripcion_corta' => $descripcion_corta, 'params' => $params, 'tipo' => $tipo);
	}

	function get_errores()
	{
		return $this->errores;
	}

	function get_notificaciones()
	{
		return $this->notificaciones;
	}

	function hay_errores()
	{
		return $this->hay_errores;
	}

	function hay_notificaciones()
	{
		return $this->hay_notificaciones;
	}

	function get_interfaz()
	{
		return $this->interfaz;
	}
	
	function set_interfaz($interfaz)
	{
		$this->interfaz = $interfaz;
	}
	
	//----------------------------------------------------------------------------------------
	//---- Validaciones en el cierre de actas ------------------------------------------------
	//----------------------------------------------------------------------------------------

	function validar_datos_cierre($datos_acta, $alumnos, $parametros)
	{
		$this->validar_acta_vacia($alumnos);
		$this->validar_campos_completados($datos_acta, $alumnos);
		$this->validar_asistencia($datos_acta, $alumnos);
		$this->validar_inscripciones_pendientes($datos_acta, $parametros['multiples_actas_comision']);
		$this->validar_alumnos_acta($datos_acta, $parametros['multiples_actas_comision']);
		$this->validar_notas_cargadas($datos_acta, $alumnos, $parametros['asentar_notas_actas']);
		$this->validar_modificaciones($datos_acta, $alumnos);
		if (!$parametros['utiliza_acta_digital']) {
			$this->validar_impresion($datos_acta, $parametros['imprimir_ultima_version_acta']);
		}
	}
	
	/**
	 * Valida que todos los alumnos inscriptos a la instancia de una comision estén incluidos en el acta
	 * cuando solo se usa un acta por comision.
	 * 
	 * @param array $datos_acta
	 * @param string $multiples_actas_comision
	 */
	function validar_alumnos_acta($datos_acta, $multiples_actas_comision)
	{
		if ($datos_acta['tipo_acta'] != acta::tipo_rectificativa) {
			$instancia = ($datos_acta['origen'] == acta::origen_regulares) ? comision::inst_cursar : comision::inst_promocionar;
			// Si el parámetro 'cur_multiples_actas_comision' es 'NO' valido
			if ($multiples_actas_comision == comunes::no) {			
				$cant = guarani::co('co_actas')->get_cant_alumnos_sin_acta_cursada($datos_acta['comision'], $instancia);
				if ($cant > 0) {
					$this->set_error('800_alumnos_acta');
				}
			}
		}
	}

	/**
	 * Verifica que el acta no esté vacía: debe tener asociado al menos un alumno.
	 */
	function validar_acta_vacia($alumnos)
	{
		if (guarani_arreglos::count($alumnos) == 0) {
			static::set_error('800_acta_vacia');
		}
	}

	/**
	 * Verifica que todos los campos de los alumnos asociados al acta hayan sido completados: Fecha, Condición de Regularidad y Resultado.
	 */
	function validar_campos_completados($datos_acta, $alumnos)
	{
		$campos_no_nulos = ($datos_acta['origen'] == acta::origen_regulares) ? array('fecha','cond_regularidad','resultado') : array('fecha','resultado');
		foreach ($alumnos as $alumno) {
			foreach ($campos_no_nulos as $campo) {
				if (empty($alumno[$campo])) {
					static::set_error('800_acta_incompleta');
					break 2;
				}
			}
		}
	}

	/**
	 * Verifica que todos los alumnos aprobados tengan pocentaje de asistencia mayor al mínimo definido para la comisión e instancia.
	 * En caso contrario emite una notificación y no permite continuar.
	 */
	function validar_asistencia($datos_acta, $alumnos)
	{
		$params_registrar_asistencia_plan = array();
		foreach ($alumnos as $alumno) {
			if ($alumno['resultado'] == escalas_notas::resultado_aprobado) {
				if (!isset($params_registrar_asistencia_plan[$alumno['plan_inscripcion']])) {
					$params_registrar_asistencia_plan[$alumno['plan_inscripcion']] = guarani::parametros()->get_valor(comision::registrar_asistencia, $alumno['plan_inscripcion'], false);
				}
				if ($params_registrar_asistencia_plan[$alumno['plan_inscripcion']] == asistencia::asist_registra_valida_acta) {
					$pct_min = toba::consulta_php('co_asistencias')->get_asistencia_minima_comision_instancia_alumno($datos_acta['comision'], $alumno['instancia'], $alumno['alumno']);
					if ($alumno['pct_asistencia'] < $pct_min) {
						static::set_error('800CUR_error_porc_asistencia', array($alumno['apellido_y_nombre'], $pct_min.'%', $datos_acta['instancia_nombre']));
					}
				}
			}
		}
	}

	/**
	 * Verifica que la comisión no tenga inscripciones a la instancia en estado pendiente.
	 */
	function validar_inscripciones_pendientes($datos_acta, $multiples_actas_comision)
	{
		if ($multiples_actas_comision == comunes::no) {
			$instancia = ($datos_acta['origen'] == acta::origen_regulares) ? comision::inst_cursar : comision::inst_promocionar;
			$filtro = "EXISTS (SELECT * FROM sga_insc_cursada_instancias WHERE sga_insc_cursada_instancias.inscripcion = sga_insc_cursada.inscripcion AND sga_insc_cursada_instancias.instancia = $instancia)";
			$filtro .= " AND sga_insc_cursada.estado = '".inscripcion_cursada::estado_pendiente."'";
			$cant_inscripciones_pendientes = toba::consulta_php('co_inscripciones_cursadas')->get_cant_inscripciones_para_comision($datos_acta['comision'], $filtro);
			if ($cant_inscripciones_pendientes > 0) {
				static::set_error('800_inscripciones_pendientes');
			}
		}
	}

	/**
	 * Verifica que se hayan cargado las notas en los detalles del acta.
	 */
	function validar_notas_cargadas($datos_acta, $alumnos, $asentar_notas_actas)
	{
		if ($asentar_notas_actas == acta::ingreso_notas_obligatorio) {
			$resultado_aprobado = escalas_notas::resultado_aprobado;
			$resultado_reprobado = escalas_notas::resultado_reprobado;

			foreach ($alumnos as $alumno) {
				if (($alumno['resultado'] == $resultado_aprobado || $alumno['resultado'] == $resultado_reprobado) && !isset($alumno['nota'])) {
					static::set_error('800_acta_sin_notas');
					break;
				}
			}
		}		
		if ($asentar_notas_actas == acta::ingreso_notas_solo_promocion) {
			$resultado_aprobado = escalas_notas::resultado_aprobado;
			foreach ($alumnos as $alumno) {
				if (($alumno['resultado'] == $resultado_aprobado ) && !isset($alumno['nota'])) {
					static::set_error('800_promocion_sin_nota');
					break;
				}
			}
		}
	}

	/**
	 * Verifica que cada alumno tenga al menos un dato modificado respecto del acta original.
	 */
	function validar_modificaciones($datos_acta, $alumnos)
	{
		if ($datos_acta['tipo_acta'] == acta::tipo_rectificativa) {
			$campos_modificados = ($datos_acta['origen'] == acta::origen_regulares) ? array('fecha','cond_regularidad','nota','resultado') : array('fecha','nota','resultado');
			foreach ($alumnos as $alumno) {
				$detalle_original = toba::consulta_php('co_actas')->get_detalle_alumno_comision($alumno['alumno'], $datos_acta['comision'], $datos_acta['origen']);

				$hay_modificaciones = false;
				$i = 0;
				$cant_campos = guarani_arreglos::count($campos_modificados);
				while ($i < $cant_campos && !$hay_modificaciones) {
					$campo = $campos_modificados[$i];
					if ($alumno[$campo] != $detalle_original[$campo]) {
						$hay_modificaciones = true;
					}
					$i++;
				}

				if (!$hay_modificaciones) {
					static::set_error('800_rectif_sin_modif');
					break;
				}
			}
		}
	}

	/**
	 * Se verifica si la versíón impresa del acta corresponde con la última versión de la misma.
	 */
	function validar_impresion($datos_acta, $imprimir_ultima_version_acta)
	{
		if ($imprimir_ultima_version_acta == comunes::si) {
			if ($datos_acta['version'] != $datos_acta['version_impresa']) {
				static::set_error('800_imprimir_ultima_version_acta');
			}
		} else {
			if (($this->interfaz == comunes::interfaz_gestion) && ($datos_acta['version_impresa'] == 0)) {
				static::set_error('800_acta_no_impresa');
			}
		}
	}

	//----------------------------------------------------------------------------------------
	//---- Validaciones en la carga de notas -------------------------------------------------
	//----------------------------------------------------------------------------------------

	function validar_carga_notas($detalle, $periodo_fecha_inicio)
	{		
		static::validar_carga_notas_cursada($detalle, $periodo_fecha_inicio);
		static::validar_carga_notas_promocion($detalle, $periodo_fecha_inicio);
		static::validar_resultados($detalle['resultado_cursada'], $detalle['resultado_promocion'], $detalle['apellido_y_nombre']);
	}

	/**
	 * Valida la fecha, nota y resultado de cursada y la condición de regularidad.
	 */
	function validar_carga_notas_cursada($detalle, $periodo_fecha_inicio)
	{
		static::validar_fecha($detalle['fecha_regular'], comision::inst_cursar, $periodo_fecha_inicio, $detalle['apellido_y_nombre']);
		if (static::validar_nota($detalle['nota_cursada'], $detalle['escala_nota_cursada'], $detalle['apellido_y_nombre'])) {
			static::validar_resultado($detalle['nota_cursada'], $detalle['escala_nota_cursada'], $detalle['resultado_cursada'], $detalle['apellido_y_nombre']);
			static::validar_condicion($detalle['resultado_cursada'], $detalle['cond_regularidad'], $detalle['apellido_y_nombre']);
		}
	}

	/**
	 * Validan la fecha, nota y resultado de promoción.
	 */
	function validar_carga_notas_promocion($detalle, $periodo_fecha_inicio)
	{		
		static::validar_fecha($detalle['fecha_promocion'], comision::inst_promocionar, $periodo_fecha_inicio, $detalle['apellido_y_nombre']);
		if (static::validar_nota($detalle['nota_promocion'], $detalle['escala_nota_promocion'], $detalle['apellido_y_nombre']) && $detalle['resultado_promocion'] != escalas_notas::resultado_reprobado) {
			static::validar_resultado($detalle['nota_promocion'], $detalle['escala_nota_promocion'], $detalle['resultado_promocion'], $detalle['apellido_y_nombre']);
		}
		
		$alumno = $detalle['alumno'];
		$propuesta = toba::consulta_php('co_alumnos')->get_alumno_propuesta($alumno);
		$propuestas = [$propuesta];
		
		$asentar_notas_actas = guarani::parametros()->get_valor('cur_asentar_notas_actas_promocion', $propuestas, false);
		if ($asentar_notas_actas == acta::ingreso_notas_solo_promocion) {
			$resultado_aprobado = escalas_notas::resultado_aprobado;

			if (($detalle['resultado_promocion'] == $resultado_aprobado ) && !isset($detalle['nota_promocion'])) {
				static::set_error('800_promocion_sin_nota');
			}
		}
	}

	/**
	 * Verifica que la fecha sea menor o igual a la fecha actual y mayor
	 * o igual a la fecha de inicio del período lectivo de la comisión.
	 */
	function validar_fecha($fecha, $instancia, $periodo_fecha_inicio, $apellido_nombre_alumno)
	{
		if (guarani_fecha::es_mayor($fecha, guarani_fecha::get_hoy())) {
			$etiq_instancia = ($instancia == comision::inst_cursar) ? 'de regular' : 'de promoción';
			static::set_error('800CUR_control_fechas', array($etiq_instancia, 'mayor', 'actual', $apellido_nombre_alumno));
		}

		if (guarani_fecha::es_menor($fecha, $periodo_fecha_inicio)) {
			$etiq_instancia = ($instancia == comision::inst_cursar) ? 'de regular' : 'de promoción';
			static::set_error('800CUR_control_fechas', array($etiq_instancia, 'menor', 'inicio del período lectivo de la comisión', $apellido_nombre_alumno));
		}
	}

	/**
	 * Verifica que la nota ingresada sea válida de acuerdo a la escala de notas.
	 */
	function validar_nota($nota, $escala_nota, $apellido_nombre_alumno)
	{
		$validador = escalas_notas::validador_escalas_notas();
		if (! $validador->es_nota_valida($nota, $escala_nota)) {
			static::set_error('800_nota_invalida', array("$apellido_nombre_alumno."));
			return false;
		}
		return true;
	}

	/**
	 * Verifica que el resultado ingresado sea válido para la nota en la escala de notas.
	 */
	function validar_resultado($nota, $escala_nota, $resultado, $apellido_nombre_alumno)
	{
		$validador = escalas_notas::validador_escalas_notas();
		if (! empty($resultado) && ! $validador->es_resultado_valido($nota, $escala_nota, $resultado)) {
			static::set_error('800CUR_error_nota_resultado', array($apellido_nombre_alumno));
		}
	}

	/**
	 * Verifica que la condicion de regularidad ingresada sea válida para el resultado.
	 */
	function validar_condicion($resultado, $condicion, $apellido_nombre_alumno)
	{
		if (!empty($resultado) && !empty($condicion) &&	!toba::consulta_php('co_condiciones_regularidad')->condicion_valida_resultado($resultado, $condicion)) {
			static::set_error('800CUR_error_resultado_condicion', array($apellido_nombre_alumno));
		}
	}

	/**
	 * Verifica la correspondencia entre los resultados de cursada y promoción.
	 */
	function validar_resultados($resultado_cursada, $resultado_promocion, $apellido_nombre_alumno)
	{
		if (!empty($resultado_cursada) && !empty($resultado_promocion) &&
			$resultado_cursada == escalas_notas::resultado_reprobado &&
			$resultado_promocion == escalas_notas::resultado_aprobado) {
			static::set_error('800CUR_error_resultado_instancia', array($apellido_nombre_alumno));
		}
	}
}
?>