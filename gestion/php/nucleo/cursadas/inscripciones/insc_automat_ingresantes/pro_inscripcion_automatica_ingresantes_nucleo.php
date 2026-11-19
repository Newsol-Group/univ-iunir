<?php
class pro_inscripcion_automatica_ingresantes_nucleo extends guarani_procesos_bk
{
	// Conjunto de propuestas al cual se inscribiran los ingresantes
	protected $propuestas = array();
	
	// Conjunto de comisiones default elegidas para las actividades a inscribir
	protected $comisiones_default = array();
	
	// Datos básicos seleccionados previamente
	protected $ubicacion;
	protected $periodo_inscripcion;
	protected $anio_academico;
	
	// Datos a considerar para ordenar las comisiones de las actividades
	protected $resp_letra_apellido = 0;
	protected $resp_turno = 0;
	
	// Código interno de la operación que invocó el proceso (necesario para recuperar los controles de cada punto).
	protected $operacion;

	// Retornos de los puntos de control ejecutados.
	protected $retornos_ptos_control;
	
	// Cantidad de inscripciones por estado.
	protected $cant_insc_aceptadas = 0;
	protected $cant_insc_pendientes = 0;
	protected $cant_insc_rechazadas = 0;

	// Constantes utilizadas en los índices de los reportes generados durante el proceso
	const reporte_insc_aceptadas = 1;
	const reporte_insc_pendientes = 2;
	const reporte_insc_rechazadas = 3;
	const reporte_resultados = 4;
	
	// Nombre del Processso
	const nombre = 'pro_inscripcion_automatica_ingresantes';

	function set_datos($datos = array())
	{
		$this->propuestas = $datos['propuesta'];
		$this->periodo_inscripcion = $datos['periodo_inscripcion'];
		$this->ubicacion = $datos['ubicacion'];
		$this->anio_academico = $datos['periodo_inscripcion_anio_academico'];
		$this->comisiones_default = $datos['comisiones_default'];
		$this->resp_turno = $datos['turno'];
		$this->resp_letra_apellido = $datos['apellido'];
		$this->operacion = $datos['operacion'];
	}

	function procesar()
	{
		$aspirantes = $this->get_aspirantes();
		// Si no se procesaron aspirantes se incrementa 100% el avance de proceso para finalizar
		$cant_aspirantes = guarani_arreglos::count($aspirantes);
		if ($cant_aspirantes > 0) {
			$paso = 100 / $cant_aspirantes;
		} else {
			$this->log_estado->incrementar_progreso(100);
		}

		$cant_alumnos = 0;
		
		$comisiones_a_actualizar = array();

		// Se recorren los aspirantes obtenidos para las propuestas, ubicación y períodos de inscripción
		foreach ($aspirantes as $aspirante) {
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}
			// Incremento el contador de alumnos procesados
			$cant_alumnos++;

			// Se obtienen las actividades y comisiones de primer año del plan_version del alumno, el alumno y la modalidad. 
			// Además considera el anio academico y la ubicación pasada al proceso.	
			$actividades = toba::consulta_php('co_elementos')->get_actividades_con_comisiones_proc_insc_ingresantes($aspirante['alumno'], $aspirante['plan_version'], $aspirante['modalidad'], $this->anio_academico, $this->ubicacion);

			// Se ordenan los datos en una matriz indexada por actividad
			$actividades_index = array();
			foreach ($actividades as $actividad) {
				$actividades_index[$actividad['actividad']][] = $actividad;
			}

			// Se recorren las actividades
			foreach ($actividades_index as $activ => $act_com) {
				// Se obtiene la comisión default
				$comision_default = $this->get_comision_default($activ, $aspirante['plan']);
				$actividad = array('actividad' => $activ, 'actividad_nombre' => $act_com[0]['actividad_nombre']);
				$se_inscribio = false;

				// Se recorren las comisiones y se intenta inscribir al alumno
				foreach ($act_com as $comision) {
					// Se saltea la comisión default, se chequea que la comisión tenga cupo y respete los criterios que correponde aplicar.
					if ($comision['comision'] != $comision_default &&
						guarani::co('co_comisiones')->comision_tiene_cupo($comision['comision']) &&
						$this->respeta_criterios($comision['comision'], $aspirante['turno_preferido'], $aspirante['apellido'])) {
						$se_inscribio = $this->inscribir_aspirante($actividad, $comision, $aspirante);
						if ($se_inscribio) {
							break;
						}
					}
				}

				// Si no se pudo inscribir en ninguna comisión, existe una comisión default y la misma tiene cupo...
				if (!$se_inscribio && $comision_default && guarani::co('co_comisiones')->comision_tiene_cupo($comision_default)) {
					$datos_comision = current(guarani::co('co_comisiones')->get_datos_comision($comision_default));
					$se_inscribio = $this->inscribir_aspirante($actividad, $datos_comision, $aspirante);
				}
			}

			$this->log_estado->incrementar_progreso($paso);
		}

		// Se arma el registro para el reporte de resultados
		$mensaje = array();
		$mensaje['alumnos'] = $cant_alumnos;
		$mensaje['inscripciones'] = $this->cant_insc_aceptadas + $this->cant_insc_pendientes;
		$mensaje['insc_aceptadas'] = $this->cant_insc_aceptadas;
		$mensaje['insc_pendientes'] = $this->cant_insc_pendientes;
		$mensaje['insc_rechazadas'] = $this->cant_insc_rechazadas;
		$this->log->set($mensaje, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function get_comision_default($actividad, $plan)
	{
		return (isset($this->comisiones_default[$actividad][$plan])) ? intval($this->comisiones_default[$actividad][$plan]) : 0;
	}

	/**
	 * Indica si la comision respeta los criterios de turno y apellido establecidos
	 * @todo corregir la estructura de la tabla 'mdp_datos_personales' y quitar la conversión de turno preferido.
	 *
	 * @param integer $comision
	 * @param integer $turno_preferido
	 * @param string $apellido
	 * @return boolean
	 */
	function respeta_criterios($comision, $turno_preferido, $apellido)
	{
		$filtro = '';

		// Se debe respetar el apellido?
		if ($this->resp_letra_apellido) {
			$inicial_apellido = substr($apellido, 0, 1);
			$inicial_apellido = guarani_texto::simplificar_caracteres($inicial_apellido);
			$inicial_apellido = guarani_texto::pasar_a_mayusculas($inicial_apellido);
			$filtro .= " AND (	sga_comisiones.letra_desde IS NULL AND sga_comisiones.letra_hasta IS NULL OR
								sga_comisiones.letra_desde IS NULL AND sga_comisiones.letra_hasta IS NOT NULL AND '$inicial_apellido' <= sga_comisiones.letra_hasta OR
								sga_comisiones.letra_desde IS NOT NULL AND sga_comisiones.letra_hasta IS NULL AND '$inicial_apellido' >= sga_comisiones.letra_desde OR
								sga_comisiones.letra_desde IS NOT NULL AND sga_comisiones.letra_hasta IS NOT NULL AND '$inicial_apellido' BETWEEN sga_comisiones.letra_desde AND sga_comisiones.letra_hasta)";
		}

		// Se debe respetar el turno y la persona tiene un turno preferido?
		if ($this->resp_turno) {
			if (is_null($turno_preferido)) {
				return false;
			} else {
				$filtro .= " AND (sga_comisiones.turno IS NULL OR sga_comisiones.turno = $turno_preferido)";
			}
		}

		return guarani::co('co_comisiones')->existe_comision($comision, $filtro);
	}

	// Obtiene el listado de aspirantes de acuerdo a los datos ingresados y a los criterios establecidos
	function get_aspirantes()
	{
		// Armo el where
		$where_propuestas = "sga_propuestas_aspira.ubicacion = $this->ubicacion AND
                   sga_periodos_inscripcion_fechas.periodo_inscripcion = $this->periodo_inscripcion AND ";
		// Obtengo el IN de las propuestas
		$where_propuestas .= guarani_sql::armar_condicion_in($this->propuestas, 'sga_propuestas', 'propuesta');

		// Armo los campos y el orden a incluir en la consulta segun los criterios especificados
		$orden = '';
		$select = '';
		$criterios = toba::consulta_php('co_criterios_prioridad_ingresantes')->get_listado_criterios_activos_orden();
		for ($i = 0; $i < guarani_arreglos::count($criterios); $i++) {
			$nro = $i + 1;
			$criterio = 'criterio_' . "$nro";
			$select .= ', ' . $criterios[$i]['criterio_base'] . ' as ' . $criterio;
			$orden .= $criterio . ', ';
		}
		$orden .= 'apellido_y_nombre';

		return guarani::co('co_alumnos')->get_aspirantes($select, $where_propuestas, $orden);
	}

	function inscribir_aspirante($datos_actividad, $datos_comision, $datos_aspirante)
	{
		// Armo el registro para los reportes
		$mensaje = array();
		$mensaje['ubicacion_alumno'] = $datos_aspirante['ubicacion_alumno'];
		$mensaje['ubicacion_propuesta'] = $datos_aspirante['ubicacion_propuesta'];
		$mensaje['nombre_alumno'] = $datos_aspirante['apellido_y_nombre'];
		$mensaje['doc'] = $datos_aspirante['documento_principal'];
		$mensaje['propuesta'] = $datos_aspirante['propuesta_nombre'];
		$mensaje['legajo'] = $datos_aspirante['legajo'];
		$mensaje['actividad'] = $datos_actividad['actividad'];
		$mensaje['actividad_descr'] = $datos_actividad['actividad_nombre'];
		$mensaje['comision'] = $datos_comision['comision'];
		$mensaje['comision_nombre'] = $datos_comision['comision_nombre'];
		$mensaje['turno'] = empty($datos_comision['turno_nombre']) ? 'Sin turno' : $datos_comision['turno_nombre'];

		$datos_inscripcion = array();
		$datos_inscripcion['comision'] = $datos_comision['comision'];
		$datos_inscripcion['alumno'] = $datos_aspirante['alumno'];
		$datos_inscripcion['periodo_lectivo'] = $datos_comision['periodo_lectivo'];
		$datos_inscripcion['prioridad'] = '1';
		$datos_inscripcion['plan_version'] = $datos_aspirante['plan_version'];
		$datos_inscripcion['tipo'] = inscripcion_cursada::tipo_inscripcion;
		$datos_inscripcion['fuera_de_termino'] = comunes::no;
		$datos_inscripcion['autorizacion'] = null;

		try {
			$this->validar_alumno_actividad_comision($datos_aspirante['persona'], $datos_aspirante['alumno'], $datos_aspirante['plan_version'], $datos_actividad['actividad'], $datos_comision['comision'], $datos_aspirante['propuesta']);
			$instancias = $this->validar_instancias($datos_aspirante['alumno'], $datos_aspirante['plan_version'], $datos_actividad['actividad'], $datos_comision['comision'], $datos_aspirante['propuesta']);
			$retorno = $this->get_peor_estado_retorno_ptos_control();
			$datos_inscripcion['estado'] = inscripcion_cursada::get_estado_equivalente($retorno);
			$nro_transaccion = toba::consulta_php('co_inscripciones_cursadas')->get_nuevo_nro_transaccion();
			$params_sistema = array('cur_controlar_cupo' => guarani::parametros()->get_valor('cur_controlar_cupo', $datos_aspirante['propuesta']),
									'cur_controlar_cupo_por' => guarani::parametros()->get_valor('cur_controlar_cupo_por', $datos_aspirante['propuesta']),
									'cur_insc_siempre_pendiente' => guarani::parametros()->get_valor('cur_insc_siempre_pendiente', $datos_aspirante['plan']));
			$result = guarani::act('act_inscripcion_cursadas')->guardar_inscripcion_a_comision_func($nro_transaccion, $datos_inscripcion, aplanar_matriz($instancias, 'instancia'), array(), false, comunes::interfaz_gestion, $params_sistema);
			if ($result['res'] == 1) {
				if ($retorno == guarani_punto_de_control::autorizado) {
					$this->cant_insc_aceptadas++;
					$reporte = self::reporte_insc_aceptadas;
				} else {
					$this->cant_insc_pendientes++;
					$reporte = self::reporte_insc_pendientes;
				}
			} else {
				$this->cant_insc_rechazadas++;
				$reporte = self::reporte_insc_rechazadas;
				$mensaje['error'] = guarani_mensajes::get($result['msg_indice'], $result['pars']);
			}
		} catch (toba_error $e) {
			$this->cant_insc_rechazadas++;
			$reporte = self::reporte_insc_rechazadas;
			$mensaje['error'] = $e->get_mensaje();
			$this->log_estado->set_error();
			toba::db()->abortar_transaccion();
		}

		$this->log->set($mensaje, array($reporte));
		$this->log->guardar();

		$se_inscribio = (isset($result['res']) && $result['res'] == 1);

		if ($se_inscribio && guarani::parametros()->get_valor(inscripcion_cursada::cur_notificar_estado_inscripcion) == comunes::si) {
			$parametros['estado'] = $datos_inscripcion['estado'];
			$parametros['nombre_actividad'] = $datos_actividad['actividad_nombre'];
			$parametros['nombre_comision'] = $datos_comision['comision_nombre'];
			$parametros['persona'] = $datos_aspirante['persona'];
			$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas(array($datos_aspirante['persona'])); 
			$parametros['nombre_alumno'] = $datos_aspirante['apellido_y_nombre']; 
			guarani::notificador()->enviar(notificador::inscribir_a_actividad, $parametros);
		}

		return $se_inscribio;
	}

	function validar_alumno_actividad_comision($persona, $alumno, $plan_version, $elemento, $comision, $propuesta)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$params = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $persona,
			'alumno' => $alumno,
			'plan_version' => $plan_version,
			'elemento' => $elemento,
			'comision' => $comision,
			'propuesta' => $propuesta,
			'anio_academico' => $this->anio_academico
		);
		$ptos_control_a_validar = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::persona_propuesta,
			guarani_punto_de_control::alumno_actividad,
			guarani_punto_de_control::alumno_comision,
			guarani_punto_de_control::alumno_anio_academico
		);
		$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);
		if ($retorno == guarani_punto_de_control::rechazado) {
			$mensajes = aplanar_matriz(guarani::validador_puntos_de_control()->get_mensajes(), 'mensaje');
			var_dump($mensajes);
			$mensaje = implode(' | ', $mensajes);
			throw guarani::error($mensaje);
		}
		$this->guardar_retorno_ptos_control('alumno_actividad_comision', $retorno);
	}

	protected function validar_instancias($alumno, $plan_version, $elemento, $comision, $propuesta)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$estado_inscripcion = guarani_punto_de_control::autorizado;
		$instancias = toba::consulta_php('co_comisiones')->get_instancias_comision($comision);
		$instancias_a_inscribir = array();
		foreach ($instancias as $instancia) {
			$ptos_control_a_validar = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia['instancia']);
			$params = array(
				'alumno' => $alumno,
				'elemento' => $elemento,
				'fecha' => guarani_fecha::get_hoy(),
				'propuesta' => $propuesta,
				'plan_version' => $plan_version
			);
			$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);
			if ($retorno == guarani_punto_de_control::rechazado) {
				if ($instancia['instancia'] == guarani::parametros()->get_valor('cur_instancia_mandatoria', $propuesta)) {
					$mensajes = aplanar_matriz(guarani::validador_puntos_de_control()->get_mensajes(), 'mensaje');
					$mensaje = implode(' | ', $mensajes);
					throw guarani::error($mensaje);
				}
			} else {
				$instancias_a_inscribir[] = $instancia;
			}
			if ($retorno == guarani_punto_de_control::advertido) {
				$estado_inscripcion = $retorno;
			}
		}
		$this->guardar_retorno_ptos_control('final', $estado_inscripcion);
		return $instancias_a_inscribir;
	}

	protected function validar_puntos_de_control($pto_control_a_validar, $params)
	{
		if (!inscripcion_cursada::ejecutar_controles(array($params['propuesta']))) {
			$retorno = guarani_punto_de_control::advertido;
		} else {
			$retorno = guarani::validador_puntos_de_control($pto_control_a_validar, $params)->controlar();
			guarani::validador_puntos_de_control()->notificar();
		}
		return $retorno;
	}

	protected function guardar_retorno_ptos_control($momento_orden, $retorno)
	{
		$this->retornos_ptos_control[$momento_orden] = $retorno;
	}

	protected function get_peor_estado_retorno_ptos_control()
	{
		foreach ($this->retornos_ptos_control as $retorno) {
			if ($retorno == guarani_punto_de_control::advertido) {
				return guarani_punto_de_control::advertido;
			}
		}
		return guarani_punto_de_control::autorizado;
	}
}
?>