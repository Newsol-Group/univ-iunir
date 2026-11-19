<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Actualizaciones
 * 
 */
class pro_proc_insc_prioridades_nucleo extends guarani_procesos_bk
{
	// Código interno de la operación que invocó el proceso (necesario para recuperar los controles de cada punto).
	protected $operacion;
	protected $inscripciones;

	const reporte_aceptadas = 1;
	const reporte_fallo_controles = 2;
	const reporte_resultados = 3;
	const reporte_con_observaciones = 4;

	// Contadores de estados
	protected $cant_aceptadas;
	protected $cant_fallo_controles;

	const nombre = 'pro_proc_insc_prioridades';
	
	const control_cupo_por_comision = 'comision';
	const control_cupo_por_aula = 'aula';

	function set_datos($datos = array())
	{
		$this->operacion = $datos['operacion'];
		$this->inscripciones = $datos['inscripciones'];
	}

	function procesar()
	{
		$cant_inscripciones = guarani_arreglos::count($this->inscripciones);

		$this->cant_aceptadas = 0;
		$this->cant_fallo_controles = 0;

		if ($cant_inscripciones > 0) {
			$paso = 100 / $cant_inscripciones;

			$i = 0;
			$inscribio = false;
			while ($i < $cant_inscripciones) {
				$avanzar = true;
				$estado = $this->get_estado();
				if ((isset($estado['abortar'])) && ($estado['abortar'])) {
					$this->abortar = true;
					break;
				}
				try {
					toba::db()->abrir_transaccion();
					
					if (!$inscribio) {
						// Todavía no se inscribió al alumno en la actividad corriente
					$alumno = $this->inscripciones[$i]['alumno'];
					$elemento = $this->inscripciones[$i]['elemento'];

						/**
						 * ACLARACIÓN: no se tiene en cuenta el parámetro cur_controlar_cupo
						 * ya que este procedimiento de inscripciones por prioridad sólo tiene 
						 * sentido si hay cupos involucrados. Se chequea siempre contra el campo
						 * cupo de la tabla cabecera de la comisión. (ver Caso de Uso)
						 */
						if (empty($this->inscripciones[$i]['cupo'])) {
							// Si la comisión NO tiene definido cupo, se ejecutan controles para aceptar la preinscripción
							$inscribio = $this->procesar_inscripcion($this->inscripciones[$i]);
						} else {
							// La comisión tiene cupo definido
							if ($this->comision_tiene_cupo($this->inscripciones[$i]['comision'], $this->inscripciones[$i]['cupo'])) {
								// Si la comisión tiene cupo disponible, se ejecutan controles para aceptar la preinscripción
								$inscribio = $this->procesar_inscripcion($this->inscripciones[$i]);
							} else {
								// Se rechaza esa preinscripción 
								$this->rechazar_inscripcion($this->inscripciones[$i]['inscripcion'], inscripcion::motivo_rechazo_cupo);
							}
						}
					} else {
						/**
						 * Si ya se inscribió a un alumno en una actividad, se rechazan 
						 * las demás inscripciones del alumno en otras comisiones de esa actividad
						 */
						if (($this->inscripciones[$i]['alumno'] == $alumno) && ($this->inscripciones[$i]['elemento'] == $elemento)) {
							$this->rechazar_inscripcion($this->inscripciones[$i]['inscripcion'], inscripcion::motivo_rechazo_otra_comision);
						} else {
							$inscribio = false;
							$avanzar = false;
						}
					}

					toba::db()->cerrar_transaccion();
				} catch (toba_error $e) {
					toba::db()->abortar_transaccion();
				}

				if ($avanzar) {
					$this->log->guardar();
					$this->log_estado->incrementar_progreso($paso);
					$i++;
				}
			}
		} else {
			// No hay inscripciones para procesar
			$paso = 100;
			$this->log_estado->incrementar_progreso($paso);
		}

		$mensaje_resultado['aceptadas'] = $this->cant_aceptadas;
		$mensaje_resultado['fallo_controles'] = $this->cant_fallo_controles;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	//---- Operaciones auxiliares ----------------------------------------------

	function procesar_inscripcion($inscripcion)
	{
		$retorno = guarani_punto_de_control::autorizado;
		if (inscripcion_cursada::ejecutar_controles($inscripcion['propuesta'])) {
			$retorno = $this->validar_puntos_de_control_inscripcion_alumno($inscripcion);
			$mensajes_validacion = null;
			if (!empty($retorno['mensajes_validacion'])) {
				$mensajes_validacion = $retorno['mensajes_validacion'];
			}
			$mensajes = $retorno['mensajes'];
			$retorno = $retorno['retorno'];
		}
		$inscribio = false;

		switch ($retorno) {
			case guarani_punto_de_control::autorizado:
				try {
					$this->aceptar_inscripcion($inscripcion['inscripcion']);
					$this->log->set($inscripcion, array(self::reporte_aceptadas));
					$this->cant_aceptadas++;
					$inscribio = true;
				} catch (toba_error $e) {
					$this->log_estado->set_error();
					$inscripcion['observaciones'] = $e->get_mensaje();
					$this->log->set($inscripcion, array(self::reporte_con_observaciones));
					$this->cant_fallo_controles++;
				}
				break;
			case guarani_punto_de_control::advertido:
			case guarani_punto_de_control::rechazado:
				$this->rechazar_inscripcion($inscripcion['inscripcion'], null, $mensajes_validacion);
				$inscripcion['observaciones'] = $mensajes;
				$this->log->set($inscripcion, array(self::reporte_fallo_controles));
				$this->cant_fallo_controles++;
				break;
		}

		return $inscribio;
	}

	function comision_tiene_cupo($comision, $cupo)
	{
		$propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
		$param_cur_controlar_cupo_por = guarani::parametros()->get_valor('cur_controlar_cupo_por', $propuestas);
		
		if ($param_cur_controlar_cupo_por == self::control_cupo_por_comision) {
			$cant_inscriptos = toba::consulta_php('co_inscripciones_cursadas')->get_cant_insc_aceptadas($comision);
		} else {
			$cant_inscriptos = toba::consulta_php('co_inscripciones_cursadas')->get_cant_insc_aceptadas_bh($comision);
		}
		return $cant_inscriptos < $cupo;
	}

	function aceptar_inscripcion($inscripcion) {
		
		guarani::act('act_inscripcion_cursadas')->aceptar_inscripcion_prioridad($inscripcion);
		
		if (guarani::parametros()->get_valor(inscripcion_cursada::cur_notificar_estado_inscripcion) == comunes::si) {
			
			$datos_inscripcion = toba::consulta_php('co_inscripciones_cursadas')->get_listado_alumnos_inscripciones_a_comisiones('inscripcion = ' . $inscripcion);
			$fila = current($datos_inscripcion);
			$parametros['estado'] = comunes::resultado_inscripcion_aceptada;
			$parametros['nombre_actividad'] = (isset($fila['actividad_nombre'])) ? $fila['actividad_nombre'] : null;
			$parametros['nombre_comision'] = (isset($fila['comision_nombre'])) ? $fila['comision_nombre'] : null;

			if (isset($fila['persona'])) {
				$parametros['persona'] = $fila['persona'];
				$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas([$fila['persona']]);
			} else {
				$parametros['persona'] = null;
				$parametros['email'] = null;
			}

			$parametros['nombre_alumno'] = (isset($fila['nombres'])) ? $fila['nombres'] : null;

			guarani::notificador()->enviar(notificador::inscribir_a_actividad, $parametros);
			
		}
		
	}

	function rechazar_inscripcion($inscripcion, $motivo = null, $mensajes = null)
	{
		guarani::act('act_inscripcion_cursadas')->eliminar_insc_cursada_prioridad($inscripcion, $motivo, $mensajes);		
		if (guarani::parametros()->get_valor(inscripcion_cursada::cur_notificar_estado_inscripcion) == comunes::si) {
			$datos_inscripcion = toba::consulta_php('co_inscripciones_cursadas')->get_listado_alumnos_inscripciones_a_comisiones('inscripcion = ' . $inscripcion);
			$fila = current($datos_inscripcion);
			$parametros['estado'] = comunes::resultado_inscripcion_rechazada;
			$parametros['nombre_actividad'] = (isset($fila['actividad_nombre'])) ? $fila['actividad_nombre'] : null;
			$parametros['nombre_comision'] = (isset($fila['comision_nombre'])) ? $fila['comision_nombre'] : null;

			if (isset($fila['persona'])) {
				$parametros['persona'] = $fila['persona'];
				$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas([$fila['persona']]);
			} else {
				$parametros['persona'] = null;
				$parametros['email'] = null;
			}

			$parametros['nombre_alumno'] = (isset($fila['nombres'])) ? $fila['nombres'] : null;
			
			guarani::notificador()->enviar(notificador::inscribir_a_actividad, $parametros);
		}
	}

	//---- Puntos de control ---------------------------------------------------

	function validar_puntos_de_control_inscripcion_alumno($datos_inscripcion)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$params = array(
			'fecha' => $datos_inscripcion['fecha_inscripcion_formato_date'],
			'persona' => $datos_inscripcion['persona'],
			'alumno' => $datos_inscripcion['alumno'],
			'plan_version' => $datos_inscripcion['plan_version'],
			'elemento' => $datos_inscripcion['elemento'],
			'comision' => $datos_inscripcion['comision'],
			'propuesta' => $datos_inscripcion['propuesta'],
			'comision_origen' => -1
		);
		$instancias = toba::consulta_php('co_inscripciones_cursadas')->get_instancias_inscripcion($datos_inscripcion['inscripcion']);
		$ptos_control_instancias = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancias);
		$ptos_control_a_validar = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::alumno_actividad,
			guarani_punto_de_control::alumno_comision,
			guarani_punto_de_control::persona_propuesta
		);
		$ptos_control_a_validar = array_merge($ptos_control_a_validar, $ptos_control_instancias);
		
		$validador = guarani::validador_puntos_de_control($ptos_control_a_validar, $params);
		$validador->set_registrar_error();		
		$retorno = $validador->controlar();		

		$mensajes = guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_gestion);
		
		$subcomisiones = toba::consulta_php('co_inscripciones_cursadas')->get_subcomisiones_por_inscripcion($datos_inscripcion['inscripcion']);
		if (!empty($subcomisiones)) {
			foreach ($subcomisiones as $subcomision) {
				$params['subcomision'] = $subcomision['subcomision'];
				$retorno_subcomision = guarani::validador_puntos_de_control(guarani_punto_de_control::alumno_subcomision, $params)->controlar();
				if ($retorno == guarani_punto_de_control::autorizado ||
					$retorno == guarani_punto_de_control::advertido && 
					$retorno_subcomision == guarani_punto_de_control::rechazado) {
					$retorno = $retorno_subcomision;
				}
				$mensajes[] = current(guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_gestion));
			}
		}

		$mensajes_validacion = '';
		if (!empty($mensajes)) {
			$mensajes_validacion = 'array[';
			$primero = true;
			foreach ($mensajes as $mensaje) {
				if (!$primero) {
					$mensajes_validacion .= ',';
				}
				$mensajes_validacion .= "'{$mensaje['mensaje']}'";
				$primero = false;
			}
			$mensajes_validacion .= ']';
		}
		$mensajes = implode('||', aplanar_matriz($mensajes, 'mensaje'));

		return array('retorno' => $retorno, 'mensajes' => $mensajes, 'mensajes_validacion' => $mensajes_validacion);
	}

}
?>