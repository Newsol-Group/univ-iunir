<?php
class pro_inscribir_automaticamente_a_examen_nucleo extends guarani_procesos_bk
{
	// Variables que mantienen los datos pasados como parámetros al proceso.
	protected $comisiones_llamados;
	protected $usuario_actual;
	
	// Código interno de la operación que invocó el proceso (necesario para recuperar los controles de cada punto).
	protected $operacion;

	// Constantes utilizadas en los índices de los diferentes reportes generados por el proceso.
	const reporte_comisiones_procesadas = 1;
	const reporte_resultados = 2;

	function set_datos($datos = array())
	{
		$this->comisiones_llamados = $datos['comisiones_llamados_mesa'];
		$this->usuario_actual = $datos['usuario'];
		$this->operacion = $datos['operacion'];
	}

	function procesar()
	{
		$this->log_estado->set('Proceso de generación de inscripciones iniciado', 'aviso');
		$paso = 100 / guarani_arreglos::count($this->comisiones_llamados);

		$total_insc_aceptadas = 0;
		$total_insc_rechazadas = 0;
		$total_insc_pendientes = 0;
		$total_com_procesadas = 0;

		/**
		 * Por cada Comisión para la cual se desean realizar las inscripciones al Llamado Mesa asociado, se recuperan 
		 * los últimos detalles (originales o rectificados) para los alumnos de Actas de Regulares cerradas de la Comisión, 
		 * para alumnos que verifiquen que: aprobaron la cursada, su nota esta activa (no invalidada), en el caso de que 
		 * exista un acta de promoción, que el alumno no este promocionado, y que el alumno no este ya inscripto en el 
		 * llamado de la mesa de examen asociado a dicha comisión. 
		 * Por cada Alumno recuperado se genera la inscripción a través de una función postgres.
		 */
		foreach ($this->comisiones_llamados as $clave_act => $valor_act) {
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}
			$comision = $valor_act['comision'];
			$llamado_mesa = $valor_act['llamado_mesa'];
			$cant_procesos_com = $valor_act['cantidad'];

			//Hallo los datos de la Comisión y la Mesa que están siendo actualmente procesadas.
			$datos_comision = current(toba::consulta_php('co_comisiones')->get_datos_comision($comision));
			$datos_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_mesa_llamados($llamado_mesa);

			//Armo el registro para el reporte de Actividades
			$mensaje['comision'] = $datos_comision['comision_nombre'];
			$mensaje['periodo_lectivo'] = $datos_comision['periodo_lectivo_nombre'];
			$mensaje['elemento'] = $datos_comision['elemento'];
			$mensaje['actividad_desc'] = $datos_comision['actividad_desc'];
			$mensaje['mesa'] = $datos_mesa['nombre'];
			$mensaje['turno_examen'] = $datos_mesa['nombre_turno_examen'];
			$mensaje['llamado_mesa'] = $datos_mesa['nombre_llamado'];
			$mensaje['fecha_examen'] = $datos_mesa['fecha'];

			$com_insc_aceptadas = 0;
			$com_insc_rechazadas = 0;
			$com_insc_pendientes = 0;

			//Se recuperan los detalles de alumnos para la comisión actual con las restricciones pedidas.
			$alumnos_comision = toba::consulta_php('co_inscripciones_examenes')->get_detalles_alumnos_comision_para_inscripcion_llamado($comision, $llamado_mesa);
			$atributos = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
			/*
			 * Si el parametro exa_mesas_comisiones_pasar_desaprobados está en si, tengo que buscar esos alumnos para también inscribirlos
			 */
			if (guarani::parametros()->get_valor('exa_mesas_comisiones_pasar_desaprobados', $atributos, false) == comunes::si) {
				$not_in = aplanar_matriz($alumnos_comision, 'alumno');
				
				//Traigo los desaprobados y para que no sean los mismos alumnos se agrega la condición not in.
				$desaprobados = toba::consulta_php('co_inscripciones_examenes')->get_alumnos_desaprobados_inscripcion_automatica_examen($comision, $llamado_mesa, $not_in);
				$alumnos_comision = array_merge($alumnos_comision, $desaprobados);
			}
			foreach ($alumnos_comision as $clave => $alumno) {
				//Los campos fuera_de_termino y fecha_inscripcion toman los valores default de la base.
				$alumnos_comision[$clave]['llamado_mesa'] = $llamado_mesa;
				$alumnos_comision[$clave]['instancia'] = inscripcion_examen::inst_insc_regular;
				$propuestas = array($alumnos_comision[$clave]['propuesta']);
				$alumno_ok = true;

				if (inscripcion_examen::ejecutar_controles($propuestas)) {
					//Ejecución de los controles configurables sobre el alumno.
					$retorno_pto_ctrol = $this->validar_puntos_de_control_inscripcion_alumno($alumno['persona'], $alumno['alumno'], $alumno['propuesta'], $alumno['plan_version'], $datos_comision['elemento'], $llamado_mesa);
					//Dependiendo del código que retornen los puntos de control al ejecutarse se realiza o no la inscripción y se determina su estado.
					switch ($retorno_pto_ctrol) {
						case guarani_punto_de_control::autorizado:
							//El alumno pasó los controles y se inscribirá en estado aceptado.
							$alumnos_comision[$clave]['estado'] = inscripcion_examen::get_estado_equivalente(guarani_punto_de_control::autorizado);
							$com_insc_aceptadas++;
							break;
						case guarani_punto_de_control::advertido:
							//El alumno se inscribirá en estado de pendiente.
							$alumnos_comision[$clave]['estado'] = inscripcion_examen::get_estado_equivalente(guarani_punto_de_control::advertido);
							$com_insc_pendientes++;
							break;
						case guarani_punto_de_control::rechazado:
							//El alumno no se inscribirá ya que no pasó los controles.
							$alumno_ok = false;
							$com_insc_rechazadas++;
							break;
					}
				} else {
					/**
					 * Si no se ejecutan los puntos de control porque están deshabilitados, los alumnos se inscriben
					 * en estado de advertido.
					 */
					$alumnos_comision[$clave]['estado'] = inscripcion_examen::get_estado_equivalente(guarani_punto_de_control::advertido);
					$com_insc_pendientes++;
				}
				if ($alumno_ok) {
					guarani::act('act_inscripcion_examenes')->inscribir_alumno_automaticamente($alumnos_comision[$clave], $llamado_mesa);
				}
				if( guarani::parametros()->get_valor(inscripcion_examen::exa_notificar_estado_inscripcion) == comunes::si ) {
					$datos_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa($llamado_mesa);
					$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_alumno($alumno['alumno']);
					$parametros['estado'] = $alumnos_comision[$clave]['estado'];
					$parametros['nombre_actividad'] = $datos_mesa['actividad_nombre'];
					$parametros['fecha_mesa'] = $datos_mesa['fecha_formato_visual'];
					$parametros['persona'] = $datos_alumno[0]['persona'];
					$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas( Array($datos_alumno[0]['persona']) );
					$parametros['nombre_alumno'] = $datos_alumno[0]['nombres'];
					guarani::notificador()->enviar(notificador::inscribir_a_examenes, $parametros);
				}
			}

			$mensaje['com_insc_aceptadas'] = $com_insc_aceptadas;
			$mensaje['com_insc_rechazadas'] = $com_insc_rechazadas;
			$mensaje['com_insc_pendientes'] = $com_insc_pendientes;

			$this->log->set($mensaje, array(self::reporte_comisiones_procesadas));
			$this->log->guardar();

			$total_insc_aceptadas = $total_insc_aceptadas + $com_insc_aceptadas;
			$total_insc_pendientes = $total_insc_pendientes + $com_insc_pendientes;
			$total_insc_rechazadas = $total_insc_rechazadas + $com_insc_rechazadas;
			$total_com_procesadas++;

			$this->log_estado->set('Procesando la Comisión ' . $datos_comision['comision_nombre'] . ' para la Actividad ' . $datos_comision['actividad_desc'], 'aviso');
			$this->log_estado->guardar();
			$this->log_estado->incrementar_progreso($paso);
			$this->log->guardar();

			/**
			 * Se actualiza la cantidad de procesamientos de la comisión, así como la fecha y el usuario que realizó 
			 * la inscripción automática para la misma. 
			 */
			$datos_persona = toba::consulta_php('co_personas')->get_persona_x_usuario($this->usuario_actual);
			$id_persona = $datos_persona['persona'];
			guarani::act('act_inscripcion_examenes')->actualizar_insc_automaticas_comision($comision, $llamado_mesa, $cant_procesos_com, $id_persona);
		}

		/**
		 * Se setean las cantidades totales resultantes de la suma de inscripciones aceptadas, rechazadas y 
		 * pendientes para cada comisión.
		 */
		$mensaje_resultado['cant_insc_aceptadas'] = $total_insc_aceptadas;
		$mensaje_resultado['cant_insc_pendientes'] = $total_insc_pendientes;
		$mensaje_resultado['cant_insc_rechazadas'] = $total_insc_rechazadas;
		$mensaje_resultado['cant_insc_procesadas'] = $total_insc_aceptadas + $total_insc_pendientes + $total_insc_rechazadas;
		$mensaje_resultado['cant_com_procesadas'] = $total_com_procesadas;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
		$this->log_estado->set('Proceso de generación de inscripciones terminado', 'aviso');
		$this->log_estado->guardar();
	}

	function validar_puntos_de_control_inscripcion_alumno($persona, $alumno, $propuesta, $plan_version, $elemento, $llamado_mesa)
	{
		toba::solicitud()->set_datos_item(array('item_parametro_a' => $this->operacion));
		$params = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $persona,
			'alumno' => $alumno,
			'plan_version' => $plan_version,
			'elemento' => $elemento,
			'llamado_mesa' => $llamado_mesa,
			'propuesta' => $propuesta
		);
		$ptos_control_instancia = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia(inscripcion_examen::inst_insc_regular);
		$ptos_control_a_validar = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::elegir_actividad_examen,
			guarani_punto_de_control::alumno_mesa_examen,
			guarani_punto_de_control::persona_propuesta
		);
		$ptos_control_a_validar = array_merge($ptos_control_a_validar, $ptos_control_instancia);
		return guarani::validador_puntos_de_control($ptos_control_a_validar, $params)->controlar();
	}

	/*
	 * Verifica que desaprobados estan en los alumnos.
	 */
	function verificar_desaprobados($alumnos_comision, $desaprobados)
	{
		$a = aplanar_matriz($alumnos_comision, 'alumno');
		$b = aplanar_matriz($desaprobados, 'alumno');
		
		//Recorro el array aplanado de desaprobados, si el valor esta en a lo quito del principal de desaprobados
		foreach (array_keys($b) as $key) {
			if (in_array($b[$key], $a)) {
				unset($desaprobados[$key]);
			}
		}
		return array_merge($alumnos_comision, $desaprobados);
	}
}
?>