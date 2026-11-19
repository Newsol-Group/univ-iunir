<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 * 
 */
class pro_inscripcion_cursadas_masiva_nucleo extends guarani_procesos_bk
{
	protected $propuesta;
	protected $plan;
	protected $plan_version;
	protected $comision;
	protected $subcomisiones;
	protected $alumnos;
	protected $actividad;
	protected $ubicacion;
	protected $operacion;
	protected $retornos_ptos_control;
	
	// Parámetros del sistema
	protected $param_cant_max_inscripciones;
	protected $param_instancia_mandatoria;
	protected $param_cur_controlar_cupo;
	protected $param_cur_controlar_cupo_por;
	protected $param_cur_insc_siempre_pendiente;
	protected $ejecutar_controles;
	protected $detener_ejecucion_en_rechazo;

	const reporte_inscriptos = 1;
	const reporte_no_inscriptos = 2;
	const reporte_resultados = 3;
	const nombre = 'pro_inscripcion_cursadas_masiva';

	function set_datos($datos = array())
	{
		$this->propuesta = $datos['propuesta'];
		$this->plan = $datos['plan'];
		$this->plan_version = $datos['plan_version'];
		$this->comision = $datos['comision'];
		$this->subcomisiones = $datos['subcomisiones'];
		$this->alumnos = $datos['alumnos'];
		$this->actividad = $datos['actividad'];
		$this->ubicacion = $datos['ubicacion'];
		$this->operacion = $datos['operacion'];
		
		// Parámetros del sistema
		$this->param_cant_max_inscripciones = guarani::parametros()->get_valor('cur_cant_max_insc_igual_actividad_periodo_lectivo', $this->propuesta);
		$this->param_instancia_mandatoria = guarani::parametros()->get_valor('cur_instancia_mandatoria', $this->propuesta);
		$this->param_cur_controlar_cupo = guarani::parametros()->get_valor('cur_controlar_cupo', $this->propuesta);
		$this->param_cur_controlar_cupo_por = guarani::parametros()->get_valor('cur_controlar_cupo_por', $this->propuesta);
		$this->param_cur_insc_siempre_pendiente = guarani::parametros()->get_valor('cur_insc_siempre_pendiente', $this->plan);
		
		$this->ejecutar_controles = guarani::parametros()->get_valor('cur_ejecuta_controles', $this->propuesta) == comunes::si;
		$this->detener_ejecucion_en_rechazo = guarani::parametros()->get_default('par_detener_ejecucion_en_rechazo') == comunes::si;

		if (!isset($this->comision['instancias'])) {
			$this->comision['instancias'] = toba::consulta_php('co_comisiones')->get_instancias_comision($this->comision['comision']);
		}
	}

	function procesar()
	{
		$cant = guarani_arreglos::count($this->alumnos);
		$cant_inscriptos = 0;
		$cant_no_inscriptos = 0;

		if ($cant != 0) {
			$paso = 100 / $cant;
			$this->comision['tipo'] = inscripcion_cursada::tipo_inscripcion;
			foreach ($this->alumnos as $alumno) {
				// Control de aborto
				$estado = $this->get_estado();
				if ((isset($estado['abortar'])) && ($estado['abortar'])) {
					$this->abortar = true;
					break;
				}

				$datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno['alumno']));

				try {
					toba::db('guarani')->abrir_transaccion();

					$alumno['comision_nombre'] = $this->comision['comision_nombre'];
					$alumno['periodo_lectivo_nombre'] = $this->comision['periodo_lectivo_nombre'];

					$datos_alumno['comision_nombre'] = $alumno['comision_nombre'];
					$datos_alumno['periodo_lectivo_nombre'] = $alumno['periodo_lectivo_nombre'];

					//----------------------------------------------------------
					//---- Validaciones ----------------------------------------
					
					// Cantidad máxima de inscripciones por período
					$this->controlar_cant_inscripciones($alumno['alumno'], $this->comision['periodo_lectivo']);
					
					// Puntos de control generales
					$this->puntos_de_control($alumno['alumno']);

					// Puntos de control de subcomisión
					$this->puntos_de_control_subcomisiones($alumno['alumno']);

					// Control de instancias
					$this->validar_instancias($alumno['alumno']);

					// Estado final de la inscripción
					$this->comision['estado_inscripcion'] = inscripcion_cursada::get_estado_equivalente($this->get_peor_estado_retorno_ptos_control());

					//---- Fin validaciones ------------------------------------
					//----------------------------------------------------------
					
					// Se obtiene nuevo nro. de transacción
					$nro_transaccion = $this->get_nuevo_nro_transaccion();


                                         if( guarani::parametros()->get_valor(inscripcion_cursada::cur_notificar_estado_inscripcion) == comunes::si ) { 
                                            $parametros['estado'] = $this->comision['estado_inscripcion']; 
                                            $parametros['nombre_actividad'] = toba::consulta_php('co_elementos')->get_nombre_actividad($this->actividad);
                                            $parametros['nombre_comision'] = $this->comision['comision_nombre'];
                                            $parametros['persona'] = $datos_alumno['persona'];
                                            $parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas( Array($datos_alumno['persona']) );  
                                            $parametros['nombre_alumno'] = $datos_alumno['nombres'];
                                            error_log('**************');
											error_log(print_r($parametros, true));
											error_log('**************');
                                            guarani::notificador()->enviar(notificador::inscribir_a_actividad, $parametros); 
                                        } 
					// Se intenta guardar la inscripción
					$result = $this->guardar_inscripcion($alumno, $this->comision, $nro_transaccion);

					error_log(print_r($result,true));
					error_log('**************');
					
					
					// Se controla resultado de la acción anterior
					if ($result['res'] == 1) {
						$datos_alumno['nro_transaccion'] = $nro_transaccion;
						$this->log->set($datos_alumno, array(self::reporte_inscriptos));
						$cant_inscriptos++;
					} else {
						$datos_alumno['error'] = guarani_mensajes::get($result['msg_indice'], $result['pars']);
						$this->log->set($datos_alumno, array(self::reporte_no_inscriptos));
						$cant_no_inscriptos++;
					}

					toba::db('guarani')->cerrar_transaccion();
				} catch (guarani_error $e) {
					toba::db('guarani')->abortar_transaccion();
					$datos_alumno['error'] = $e->get_mensaje();
					$this->log->set($datos_alumno, array(self::reporte_no_inscriptos));
					$cant_no_inscriptos++;
				} catch (toba_error $e) {
					toba::db('guarani')->abortar_transaccion();
					$datos_alumno['error'] = $e->get_mensaje();
					$this->log->set($datos_alumno, array(self::reporte_no_inscriptos));
					$cant_no_inscriptos++;
				}

				$this->log->guardar();
				$this->log_estado->incrementar_progreso($paso);
			}
		} else {
			$this->log_estado->incrementar_progreso(100);
		}

		$mensaje_resultado['inscriptos'] = $cant_inscriptos;
		$mensaje_resultado['no_inscriptos'] = $cant_no_inscriptos;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function get_nuevo_nro_transaccion()
	{
		return toba::consulta_php('co_inscripciones_cursadas')->get_nuevo_nro_transaccion();
	}

	function guardar_inscripcion($alumno, $comision, $nro_transaccion)
	{
		// Se sincroniza inscripción
		$datos_inscripcion = array();
		$datos_inscripcion['comision'] = $comision['comision'];
		$datos_inscripcion['periodo_lectivo'] = $comision['periodo_lectivo'];
		$datos_inscripcion['alumno'] = $alumno['alumno'];
		$datos_inscripcion['prioridad'] = $comision['prioridad'];
		$datos_inscripcion['tipo'] = inscripcion_cursada::tipo_inscripcion;
		$datos_inscripcion['plan_version'] = $this->plan_version;
		$datos_inscripcion['estado'] = $comision['estado_inscripcion'];
		$datos_inscripcion['fuera_de_termino'] = comunes::no;
		$datos_inscripcion['autorizacion'] = null;
		$datos_inscripcion['propuesta'] = $this->propuesta;
		$instancias = aplanar_matriz($comision['instancias_a_inscribir'], 'instancia');
		$subcomisiones = isset($comision['subcomisiones']) ? $comision['subcomisiones'] : array();

		$params_sistema = array(
			'cur_controlar_cupo' => $this->param_cur_controlar_cupo,
			'cur_controlar_cupo_por' => $this->param_cur_controlar_cupo_por,
			'cur_insc_siempre_pendiente' => $this->param_cur_insc_siempre_pendiente,
		);
                
		return guarani::act('act_inscripcion_cursadas')->guardar_inscripcion_a_comision_func($nro_transaccion, $datos_inscripcion, $instancias, $subcomisiones, false, comunes::interfaz_gestion, $params_sistema);
	}

	//--------------------------------------------------------------------------
	//---- VALIDACIONES --------------------------------------------------------
	//--------------------------------------------------------------------------

	protected function puntos_de_control($alumno)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno));

		$param = array(
			'alumno' => $datos_alumno['alumno'],
			'persona' => $datos_alumno['persona'],
			'fecha' => guarani_fecha::get_hoy(),
			'plan_version' => $this->plan_version,
			'propuesta' => $this->propuesta,
			'elemento' => $this->actividad,
			'comision' => $this->comision['comision'],
			'anio_academico' => $this->comision['anio_academico']
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

		$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $param);
		
		if ($retorno == guarani_punto_de_control::rechazado) {
			$mensajes = aplanar_matriz(guarani::validador_puntos_de_control()->get_mensajes(), 'mensaje');
			$mensaje = implode(' | ', $mensajes);
			throw guarani::error($mensaje);
		}

		$this->guardar_retorno_ptos_control('alumno', $retorno);
	}

	function puntos_de_control_subcomisiones($alumno)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$pto_control_a_validar = guarani_punto_de_control::alumno_subcomision;
		$retorno_subc = guarani_punto_de_control::autorizado;
		if (!empty($this->subcomisiones)) {
			$params = array(
				'alumno' => $alumno,
				'elemento' => $this->actividad,
				'fecha' => guarani_fecha::get_hoy(),
				'comision' => $this->comision,
				'plan_version' => $this->plan_version
			);

			foreach ($this->subcomisiones as $subcomision) {
				$params['subcomision'] = $subcomision;
				$retorno = $this->validar_puntos_de_control($pto_control_a_validar, $params);
				if ($retorno == guarani_punto_de_control::rechazado) {
					$mensajes = aplanar_matriz(guarani::validador_puntos_de_control()->get_mensajes(), 'mensaje');
					$mensaje = implode(' | ', $mensajes);
					throw guarani::error($mensaje);
				} elseif ($retorno == guarani_punto_de_control::advertido) {
					$retorno_subc = guarani_punto_de_control::advertido;
				}
			}
		}

		$this->guardar_retorno_ptos_control('subcomisiones', $retorno_subc);
	}

	/**
	 * Controla que se respete la cantidad máxima de inscripciones por actividad y período lectivo.
	 * 
	 * @param int $alumno
	 * @param int $periodo_lectivo
	 * @throws guarani_error
	 */
	function controlar_cant_inscripciones($alumno, $periodo_lectivo)
	{
		$cant_inscripciones = toba::consulta_php('co_inscripciones_cursadas')->get_cant_inscripciones_actividad_periodo_lectivo($alumno, $this->actividad, $periodo_lectivo);
		if ($cant_inscripciones >= $this->param_cant_max_inscripciones) {
			$nombre_actividad = toba::consulta_php('co_elementos')->get_nombre_actividad($this->actividad);
			$mje = guarani_mensajes::get('800CUR_max_inscripcion_excedida', array($nombre_actividad), false);
			throw guarani::error($mje);
		}
	}

	protected function validar_instancias($alumno)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		// Validación de Instancias. Determina a qué instancias se inscribe y con qué estado
		$this->comision['instancias_a_inscribir'] = array();

		$estado_inscripcion = guarani_punto_de_control::autorizado;

		foreach ($this->comision['instancias'] as $instancia) {
			$ptos_control_a_validar = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia['instancia']);
			$params = array(
				'alumno' => $alumno,
				'elemento' => $this->actividad,
				'fecha' => guarani_fecha::get_hoy(),
				'plan_version' => $this->plan_version
			);

			$retorno = $this->validar_puntos_de_control($ptos_control_a_validar, $params);
			if ($retorno == guarani_punto_de_control::rechazado) {
				if ($instancia['instancia'] == $this->param_instancia_mandatoria) {
					// Si es mandatoria aborta inscripcion
					$mensajes = aplanar_matriz(guarani::validador_puntos_de_control()->get_mensajes(), 'mensaje');
					$mensaje = implode(' | ', $mensajes);
					throw guarani::error($mensaje);
				}
			} else {
				$this->comision['instancias_a_inscribir'][] = $instancia;
			}

			// Se guarda el peor estado acumulado de las no rechazadas
			if ($retorno == guarani_punto_de_control::advertido) {
				$estado_inscripcion = $retorno;
			}
		}
		$this->guardar_retorno_ptos_control('final', $estado_inscripcion);
	}

	protected function validar_puntos_de_control($pto_control_a_validar, $params)
	{
		if (!$this->ejecutar_controles) {
			$retorno = guarani_punto_de_control::advertido;
		} else {
			$retorno = guarani::validador_puntos_de_control($pto_control_a_validar, $params, $this->detener_ejecucion_en_rechazo)->controlar();
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