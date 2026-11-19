<?php
/**
 *
 * 
 * 
 * @package 	Cursadas
 * @subpackage 	Inscripciones
 * 
 */
class pro_proc_insc_act_pendientes_nucleo extends guarani_procesos_bk
{
	protected $inscripciones;
	
	// Código interno de la operación que invocó el proceso (necesario para recuperar los controles de cada punto).
	protected $operacion;
	protected $cant_aceptadas;
	protected $cant_a_rechazar;
	protected $cant_con_observaciones;

	const reporte_aceptadas = 1;
	const reporte_a_rechazar = 2;
	const reporte_con_observaciones = 3;
	const reporte_resultados = 4;
	
	const nombre = 'pro_proc_insc_act_pendientes';

	function set_datos($datos = array())
	{
		$this->inscripciones = $datos['inscripciones'];
		$this->operacion = $datos['operacion'];
	}

	function procesar()
	{
		$paso = 100 / guarani_arreglos::count($this->inscripciones);

		$this->cant_aceptadas = 0;
		$this->cant_a_rechazar = 0;
		$this->cant_con_observaciones = 0;

		$filtro = guarani_sql::armar_condicion_in($this->inscripciones, 'sga_insc_cursada', 'inscripcion');
		$datos_inscripciones = guarani::co('co_inscripciones_cursadas')->get_listado($filtro);
		$datos_inscripciones = rs_convertir_asociativo_matriz($datos_inscripciones, array('inscripcion'));
		$notificador_cant_aceptadas = 0;

		foreach ($datos_inscripciones as $inscripcion) {
			// Si desde la aplicación se mandó señal de corte, no se continúa con 
			// el procesamiento (lo procesado hasta el momento no se revierte).
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}

			// Se verifican los puntos que no involucran instancias de inscripción. 
			// Esos se evalúan por separado en caso que el resto de los controles 
			// hayan sido autorizados, ya que implican una lógica de decisión especial.
			$retorno = $this->validar_puntos_de_control_inscripcion_alumno($inscripcion);
			$mensajes = $retorno['mensajes'];
			$requisitos = $retorno['requisitos'];
			$retorno = $retorno['retorno'];
			switch ($retorno) {
				case guarani_punto_de_control::autorizado:
					// Si los puntos de control generales resultaron autorizados,
					// se evalúan los relativos a las instancias de inscripcón y
					// se actúa según el resultado
					$this->controles_instancias($inscripcion);
					break;
				case guarani_punto_de_control::advertido:
					$inscripcion['observaciones'] = $mensajes;
					$inscripcion['requisitos'] = $requisitos;
					$this->log->set($inscripcion, array(self::reporte_con_observaciones));
					$this->cant_con_observaciones++;
					break;
				case guarani_punto_de_control::rechazado:
					$inscripcion['observaciones'] = $mensajes;
					$inscripcion['requisitos'] = $requisitos;
					$this->log->set($inscripcion, array(self::reporte_a_rechazar));
					$this->cant_a_rechazar++;
					break;
			}

			if (guarani::parametros()->get_valor(inscripcion_cursada::cur_notificar_estado_inscripcion) == comunes::si && $notificador_cant_aceptadas + 1 == $this->cant_aceptadas) {
				$parametros['estado'] = comunes::resultado_inscripcion_aceptada;
				$notificador_cant_aceptadas = $this->cant_aceptadas;
				$parametros['nombre_actividad'] = $inscripcion['actividad'];
				$parametros['nombre_comision'] = $inscripcion['comision'];
				$parametros['persona'] = $inscripcion['persona'];
				$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas(array($inscripcion['persona'])); 
				$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_alumno($inscripcion['alumno']); 
				$parametros['nombre_alumno'] = $datos_alumno[0]['nombres']; 
				guarani::notificador()->enviar(notificador::inscribir_a_actividad, $parametros);
			}

			$this->log->guardar();

			if (!$this->abortar) {
				$this->log_estado->incrementar_progreso($paso);
			}
		}

		$mensaje_resultado['aceptadas'] = $this->cant_aceptadas;
		$mensaje_resultado['con_observaciones'] = $this->cant_con_observaciones;
		$mensaje_resultado['a_rechazar'] = $this->cant_a_rechazar;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	/**
	 * Validación de puntos de control generales (sin instancias)
	 * 
	 * @param array $datos_inscripcion
	 * @return array
	 */
	function validar_puntos_de_control_inscripcion_alumno($datos_inscripcion)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$params = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $datos_inscripcion['persona'],
			'alumno' => $datos_inscripcion['alumno'],
			'plan_version' => $datos_inscripcion['plan_version'],
			'elemento' => $datos_inscripcion['elemento'],
			'comision' => $datos_inscripcion['id_comision'],
			'propuesta' => $datos_inscripcion['propuesta']
		);

		$ptos_control_a_validar = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::alumno_actividad,
			guarani_punto_de_control::alumno_comision,
			guarani_punto_de_control::persona_propuesta
		);

		$retorno = guarani::validador_puntos_de_control($ptos_control_a_validar, $params)->controlar();
		$mensajes = guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_gestion);

		$subcomisiones = toba::consulta_php('co_inscripciones_cursadas')->get_subcomisiones_por_inscripcion($datos_inscripcion['inscripcion']);
		if (!empty($subcomisiones)) {
			foreach ($subcomisiones as $subcomision) {
				$params['subcomision'] = $subcomision['subcomision'];
				$retorno_subcomision = guarani::validador_puntos_de_control(guarani_punto_de_control::alumno_subcomision, $params)->controlar();
				if ($retorno == guarani_punto_de_control::autorizado ||
					$retorno == guarani_punto_de_control::advertido && $retorno_subcomision == guarani_punto_de_control::rechazado) {
					$retorno = $retorno_subcomision;
				}
				$mensajes[] = current(guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_gestion));
			}
		}
		$requisitos = implode('||', aplanar_matriz($mensajes, 'requisito'));
		$mensajes = implode('||', aplanar_matriz($mensajes, 'mensaje'));
		
		return array('retorno' => $retorno, 'mensajes' => $mensajes, 'requisitos'=>$requisitos);
	}

	//--------------------------------------------------------------------------
	//---- Controles de instancias ---------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Ejecuta las validaciones de los puntos de control relativos a las instancias 
	 * de inscripción y clasifica la inscripción en el estado que corresponda
	 * 
	 * @param array $inscripcion
	 */
	function controles_instancias($inscripcion)
	{
		$resultados = $this->validar_puntos_de_control_instancias($inscripcion);

		$cant_autorizado = 0;
		$cant_advertido = 0;
		$cant_rechazado = 0;
		foreach ($resultados as $instancia => $resultado) {
			if ($resultado['retorno'] == guarani_punto_de_control::autorizado) {
				$cant_autorizado++;
			} elseif ($resultado['retorno'] == guarani_punto_de_control::advertido) {
				$cant_advertido++;
			} elseif ($resultado['retorno'] == guarani_punto_de_control::rechazado) {
				$cant_rechazado++;
			}
		}

		if ($cant_autorizado == guarani_arreglos::count($resultados)) {
			// Aceptado en todas las instancias -> se aceptan las inscripciones
			guarani::act('act_inscripcion_cursadas')->aceptar_inscripciones($inscripcion['inscripcion']);
			$this->log->set($inscripcion, array(self::reporte_aceptadas));
			$this->cant_aceptadas++;
		} elseif (($cant_advertido == guarani_arreglos::count($resultados)) || (($cant_autorizado == 1) && ($cant_advertido == 1))) {
			// Todas las instancias advertidas o 
			// una instancia autorizada y una advertida -> reporte con observaciones
			$inscripcion['observaciones'] = implode('||', aplanar_matriz($resultados, 'mensajes'));
			$this->log->set($inscripcion, array(self::reporte_con_observaciones));
			$this->cant_con_observaciones++;
		} elseif ($cant_rechazado == guarani_arreglos::count($resultados)) {
			// Todas las instancias rechazadas -> reporte a rechazar
			$inscripcion['observaciones'] = implode('||', aplanar_matriz($resultados, 'mensajes'));
			$inscripcion['requisitos'] = implode('||', aplanar_matriz($resultados, 'requisitos'));
			$this->log->set($inscripcion, array(self::reporte_a_rechazar));
			$this->cant_a_rechazar++;
		} elseif (($cant_autorizado == 1) && ($cant_rechazado == 1)) {
			// Una instancia autorizada y una rechazada -> se elimina la instancia rechazada, y se acepta la inscripción
			foreach ($resultados as $instancia => $resultado) {
				if ($resultado['retorno'] == guarani_punto_de_control::rechazado) {
					guarani::act('act_inscripcion_cursadas')->eliminar_insc_cursada_instancia($inscripcion['inscripcion'], $instancia);
				}
			}
			guarani::act('act_inscripcion_cursadas')->aceptar_inscripciones($inscripcion['inscripcion']);
			// Se refrescan instancias a mostrar en reporte
			$inscripcion['instancias'] = toba::consulta_php('co_inscripciones_cursadas')->get_instancias_nombre_inscripcion($inscripcion['inscripcion']);
			$this->log->set($inscripcion, array(self::reporte_aceptadas));
			$this->cant_aceptadas++;
		} elseif (($cant_advertido == 1) && ($cant_rechazado == 1)) {
			// Una instancia advertida y una a rechazar -> se elimina la instancia rechazada y se agrega a reporte con observaciones
			$mensaje = '';
			foreach ($resultados as $instancia => $resultado) {
				if ($resultado['retorno'] == guarani_punto_de_control::rechazado) {
					guarani::act('act_inscripcion_cursadas')->eliminar_insc_cursada_instancia($inscripcion['inscripcion'], $instancia);
				} else {
					$mensaje .= $resultado['mensajes'];
				}
			}
			// Se refrescan instancias a mostrar en reporte
			$inscripcion['instancias'] = toba::consulta_php('co_inscripciones_cursadas')->get_instancias_nombre_inscripcion($inscripcion['inscripcion']);
			$inscripcion['observaciones'] = $mensaje;
			$this->log->set($inscripcion, array(self::reporte_con_observaciones));
			$this->cant_con_observaciones++;
		}
	}

	/**
	 * Validación de los puntos de control relativos a las instancias de inscripción
	 * 
	 * @param array $datos_inscripcion
	 * @return array
	 */
	function validar_puntos_de_control_instancias($datos_inscripcion)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$params = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $datos_inscripcion['persona'],
			'alumno' => $datos_inscripcion['alumno'],
			'plan_version' => $datos_inscripcion['plan_version'],
			'elemento' => $datos_inscripcion['elemento'],
			'comision' => $datos_inscripcion['id_comision'],
			'propuesta' => $datos_inscripcion['propuesta']
		);

		$instancias = toba::consulta_php('co_inscripciones_cursadas')->get_instancias_inscripcion($datos_inscripcion['inscripcion']);

		$salida = array();
		foreach ($instancias as $dato_instancia) {
			$instancia = $dato_instancia['instancia'];
			$pto_ctrl = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
			$retorno = guarani::validador_puntos_de_control($pto_ctrl, $params)->controlar();
			$mensajes = guarani::validador_puntos_de_control()->get_mensajes(comunes::interfaz_gestion);

			$requisitos = implode('||', aplanar_matriz($mensajes, 'requisito'));
			$mensajes = $this->agregar_prefijo_instancia($mensajes, $instancia);
			$mensajes = implode('||', aplanar_matriz($mensajes, 'mensaje'));

			$salida[$instancia] = array(
				'retorno' => $retorno,
				'mensajes' => $mensajes,
				'requisitos' => $requisitos
			);
		}

		return $salida;
	}

	function agregar_prefijo_instancia($mensajes, $instancia)
	{
		$prefijo = '';
		if ($instancia == comision::inst_cursar) {
			$prefijo = 'Para regularizar: ';
		} elseif ($instancia == comision::inst_promocionar) {
			$prefijo = 'Para promocionar: ';
		}
		foreach ($mensajes as $clave => $mensaje) {
			$mensajes[$clave]['mensaje'] = $prefijo.$mensaje['mensaje'];
		}
		return $mensajes;
	}
}
?>