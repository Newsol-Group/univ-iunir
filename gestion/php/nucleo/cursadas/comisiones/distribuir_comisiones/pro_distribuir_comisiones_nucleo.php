<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class pro_distribuir_comisiones_nucleo extends guarani_procesos_bk
{
	// Código interno de la operación que invocó el proceso (necesario para recuperar los controles de cada punto).
	protected $operacion;
	protected $tipo_distribucion;
	protected $datos_origen;
	protected $alumnos_distribuidos;
	protected $atributos_propuestas;

	// Constantes utilizadas en los índices de los reportes generados durante el proceso
	const reporte_alumnos_distribuidos = 1;
	const reporte_alumnos_no_distribuidos = 2;
	const reporte_resultados = 3;
	const nombre = 'pro_distribuir_comisiones';

	function set_datos($datos = array())
	{
		$this->tipo_distribucion = $datos['tipo_distribucion'];
		$this->datos_origen = $datos['datos_origen'];
		$this->alumnos_distribuidos = $datos['alumnos_distribuidos'];
		$this->operacion = $datos['operacion'];
	}

	function procesar()
	{
		// Se inicializan los contadores 
		$cant_alumnos_distribuidos = 0;
		$cant_alumnos_no_distribuidos = 0;
		$paso = 100 / guarani_arreglos::count($this->alumnos_distribuidos);

		// Para cada Alumno que se redistribuye debe considerarse la Comisión o Subcomisiones destino (según el tipo de
		// Distribución) y las Instancias de la Inscripción Original y la Comisión Destino.
		foreach ($this->alumnos_distribuidos as $alumno) {
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}
			$insc_original = $alumno['inscripcion'];

			// Si la distribucion era hacia subcomisiones, se recuperan los ids de las subcomisiones destino 
			// además de la comisiones.
			$origen = null;
			if ($this->tipo_distribucion != comision::dist_entre_com) {
				$comision_destino = $alumno['destino'][0];
				$subcomisiones_destino = $alumno['destino'][1];
				if ($this->tipo_distribucion == comision::dist_com_subcom && $this->datos_origen['alumnos'] == comision::alumno_con_subcomision) {
					$origen = $alumno['origen'];
					$subcomisiones_destino = current($alumno['destino'][1]);
				}
			} else {
				$comision_destino = $alumno['destino'];
				$subcomisiones_destino = array();
			}

			$filtro = "vw_insc_cursada.inscripcion = $insc_original";
			$datos_insc_origen = current(toba::consulta_php('co_inscripciones_cursadas')->get_datos_basicos_inscripciones($filtro));

			$instancias_destino = toba::consulta_php('co_comisiones')->get_instancias_comision($comision_destino);

			if ($datos_insc_origen['estado'] == inscripcion_cursada::estado_pendiente) {
				// Si la inscripción en la comisión origen está en estado Pendiente, entonces se lo debe inscribir 
				// en todas las instancias de la comisión destino en estado Pendiente.
				$resultado = $this->mover_inscripcion($insc_original, $comision_destino, inscripcion_cursada::estado_pendiente, $instancias_destino, $subcomisiones_destino, $origen);
			} elseif ($datos_insc_origen['estado'] == inscripcion_cursada::estado_aceptada) {
				// Si la inscripción en la comisión origen está en estado Aceptado.
				$propuesta = toba::consulta_php('co_inscripciones_cursadas')->get_propuestas_inscripciones($alumno);
				if (!inscripcion_cursada::ejecutar_controles($propuesta)) {
					// Si el parámetro del sistema cur_ejecuta_controles es igual a N, NO se ejecutan controles 
					// configurables inscribiéndose al alumno con estado Pendiente y a todas las instancias de la 
					// comisión destino.
					$resultado = $this->mover_inscripcion($insc_original, $comision_destino, inscripcion_cursada::estado_pendiente, $instancias_destino, $subcomisiones_destino, $origen);
				} else {
					// Si el parámetro del sistema cur_ejecuta_controles es igual a S.
					// Validación de puntos de control genéricos para el alumno y comisión para obtener el estado de la inscripción.
					$retorno = $this->validar_puntos_de_control_distribucion_alumno($datos_insc_origen, $comision_destino);
					$mensajes = $retorno['mensajes'];
					$ret = $retorno['retorno'];

					switch ($ret) {
						case guarani_punto_de_control::autorizado:
							$estado_insc = inscripcion_cursada::estado_aceptada;
							break;
						case guarani_punto_de_control::advertido:
							$estado_insc = inscripcion_cursada::estado_pendiente;
							break;
						case guarani_punto_de_control::rechazado:
							// No se puede inscribir al alumno.
							$resultado = -1;
							$datos_insc_origen['observaciones'] = $mensajes;
							break;
					}

					// Si validó los puntos de control genéricos se ejecutan los puntos de control sobre cada instancia
					// para obtener las instancias de la comisión destino en las que se inscribirá al alumno.
					if (isset($estado_insc)) {
						$instancias_aceptadas = array();
						$instancias_rechazadas = array();

						foreach ($instancias_destino as $instancia) {
							if ($this->existe_instancia_destino($instancia['instancia'], $datos_insc_origen['comision'])) {
								// Si la instancia de la comisión destino existe en la comisión origen, entonces al alumno se lo 
								// inscribe en esa instancia en estado Aceptado.
								array_push($instancias_aceptadas, array('instancia' => $instancia['instancia']));
							} else {
								/**
								 * Si la instancia de la comisión destino no existe en la comisión origen se deberán correr 
								 * los siguientes controles y en el caso que no cumpla con algún control no se lo inscribe en 
								 * la instancia evaluada:

								  ++ Puntos de control asociados a la instancia asociada a la comisión destino
								  (sga_instancias_puntos_ctrl) que no se encuentra en la comisión origen.

								  -> Si el resultado de la ejecución de todos los controles es A o P, inscribe al alumno en la instancia.
								  -> Si el resultado es R, indica que el control estaba establecido como estricto y ha fallado. No se inscribe
								  al alumno en la instancia.
								 * 
								 * En el caso que no se lo pueda inscribir a ninguna instancia, no se realizará el cambio 
								 * de comisión. 
								 *
								 */
								$retorno_instancias = $this->validar_puntos_de_control_instancia($datos_insc_origen, $instancia);
								$mensajes_inst = $retorno_instancias['mensajes'];
								$ret_inst = $retorno_instancias['retorno'];

								if ($ret_inst == guarani_punto_de_control::autorizado || $ret_inst == guarani_punto_de_control::advertido) {
									array_push($instancias_aceptadas, array('instancia' => $instancia['instancia']));
								} else {
									array_push($instancias_rechazadas, $instancia);
									$datos_insc_origen['observaciones'] = $mensajes_inst;
								}
							}
						}

						// Si validó alguna instancia para inscribir al alumno.
						if (!empty($instancias_aceptadas)) {
							$resultado = $this->mover_inscripcion($insc_original, $comision_destino, $estado_insc, $instancias_aceptadas, $subcomisiones_destino, $origen);
						} else {
							$resultado = -1;
						}
					} else {
						$resultado = -1;
					}
				}
			}

			// Si el alumno se inscribe a al menos una instancia, ya se puede considerar como inscripto a la (sub)comisión destino.
			// Datos para el reporte
			$datos_comision_destino = current(toba::consulta_php('co_comisiones')->get_datos_comision($comision_destino));
			$mensaje = $datos_insc_origen;
			$mensaje['comision_destino'] = $datos_comision_destino['comision_nombre'];
			$mensaje['periodo_lectivo_destino'] = $datos_comision_destino['periodo_lectivo_nombre'];

			if (is_array($resultado)) {
				// Parámetros devueltos
				$codigo_retorno = $resultado['res'];
				$mje = guarani_mensajes::get($resultado['msg_indice'], $resultado['params']);
				$mensaje['observaciones'] = $mje;
			} else {
				$codigo_retorno = $resultado;
			}

			// Dependiendo de si el alumno pudo inscribirse, se setean los datos en 
			// el reporte que corresponda (retorno de la función).
			if ($codigo_retorno == 1) {
				// Inscripción distribuida correctamente,
				$this->log->set($mensaje, array(self::reporte_alumnos_distribuidos));
				$cant_alumnos_distribuidos++;
				if( guarani::parametros()->get_valor(inscripcion_cursada::cur_notificar_estado_inscripcion) == comunes::si ) { 
					$parametros['nombre_actividad'] = toba::consulta_php('co_elementos')->get_nombre_actividad($datos_insc_origen['elemento']);
					$parametros['nombre_comision_origen'] = $datos_insc_origen['comision_nombre'];
					$parametros['nombre_comision_destino'] = $datos_comision_destino['comision_nombre'];
					$parametros['persona'] = $datos_insc_origen['persona'];
					$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas( Array($datos_insc_origen['persona']) );  
					$datos_persona = toba::consulta_php('co_personas')->get_datos_persona($datos_insc_origen['persona']);  
					$parametros['nombre_alumno'] = $datos_persona['nombres'];
					guarani::notificador()->enviar(notificador::distribuir_comisiones, $parametros); 
				}
			} else {
				// Hubo error.
				$this->log->set($mensaje, array(self::reporte_alumnos_no_distribuidos));
				$cant_alumnos_no_distribuidos++;
			}

			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
		}

		// Registro para el reporte de resultados
		$mensaje = array();
		$mensaje['alumnos_distribuidos'] = $cant_alumnos_distribuidos;
		$mensaje['alumnos_no_distribuidos'] = $cant_alumnos_no_distribuidos;
		$this->log->set($mensaje, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function mover_inscripcion($insc_origen, $com_destino, $insc_estado, $inst_destino, $subcom_destino, $origen = null)
	{
		try {
			toba::db()->abrir_transaccion();
			$instancias = aplanar_matriz($inst_destino, 'instancia');
			$resultado = guarani::act('act_inscripcion_cursadas')->mover_insc_a_comision($insc_origen, $com_destino, $insc_estado, $instancias, $subcom_destino, $origen);
			toba::db()->cerrar_transaccion();
			return $resultado;
		} catch (toba_error $e) {
			$this->log_estado->set_error();
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	/**
	 * Verifica si la instancia de la Comisión Destino donde se desea mover la inscripción, existe en la Comisión Origen.
	 * 
	 * @param integer $instancia
	 * @param integer $comision_destino
	 */
	function existe_instancia_destino($instancia, $comision_origen)
	{
		return toba::consulta_php('co_comisiones')->existe_instancia_para_comision($instancia, $comision_origen);
	}

	function validar_puntos_de_control_distribucion_alumno($datos_insc_origen, $comision_destino)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$fecha_insc = guarani_fecha::separar_fecha_hora($datos_insc_origen['fecha_inscripcion']);
		$params = array(
			'fecha' => $fecha_insc[0],
			'persona' => $datos_insc_origen['persona'],
			'alumno' => $datos_insc_origen['alumno'],
			'plan_version' => $datos_insc_origen['plan_version'],
			'elemento' => $datos_insc_origen['elemento'],
			'comision' => $comision_destino,
			'propuesta' => $datos_insc_origen['propuesta'],
			'comision_origen' => $datos_insc_origen['comision']
		);

		$ptos_control_a_validar = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_comision,
			guarani_punto_de_control::persona_propuesta
		);
		$retorno = guarani::validador_puntos_de_control($ptos_control_a_validar, $params, true)->controlar();
		$mensajes = guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_autogestion);
		$mensajes = implode('||', aplanar_matriz($mensajes, 'mensaje'));
		return array('retorno' => $retorno, 'mensajes' => $mensajes);
	}

	function validar_puntos_de_control_instancia($datos_insc_origen, $instancia)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$fecha_insc = guarani_fecha::separar_fecha_hora($datos_insc_origen['fecha_inscripcion']);
		$params = array(
			'fecha' => $fecha_insc[0],
			'alumno' => $datos_insc_origen['alumno'],
			'plan_version' => $datos_insc_origen['plan_version'],
			'elemento' => $datos_insc_origen['elemento']
		);
		$ptos_control_instancia = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
		$retorno = guarani::validador_puntos_de_control($ptos_control_instancia, $params, true)->controlar();
		$mensajes = guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_autogestion);
		$mensajes = implode('||', aplanar_matriz($mensajes, 'mensaje'));
		return array('retorno' => $retorno, 'mensajes' => $mensajes);
	}

}
?>