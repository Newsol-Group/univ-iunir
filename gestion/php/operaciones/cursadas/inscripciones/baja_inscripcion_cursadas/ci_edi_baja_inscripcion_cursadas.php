<?php
class ci_edi_baja_inscripcion_cursadas extends guarani_pers_ci
{
	protected $popup = false;

	//-----------------------------------------------------------------------------------
	//---- cuadro_inscripciones ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_inscripciones(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_insc_cursada');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta->get_enf('plural'))));

		$filtro = 'sga_alumnos.alumno = ' . toba::db()->quote($this->controlador()->get_seleccion());
		$datos = toba::consulta_php('co_inscripciones_cursadas')->get_listado_baja($filtro);
		
		foreach ($datos as $key => $dato) 
		{
			
			// Se obtienen las Responsables Académicas de esas propuestas
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($dato['propuesta']);
			// Se determina si se usa Sanavirón-Quilmes como módulo de pago para esas RAs
			$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron', $ras);
			$comision_cobrable = $dato['cobrable'];
			
			$datos[$key]['modo_devolucion'] = cobro::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_NO_CORRESPONDE;
			$datos[$key]['mostrar_btn_eliminar'] = true;
			$datos[$key]['etiqueta_btn_eliminar'] = guarani_mensajes::get('800CUR_btn_eliminar');
			$datos[$key]['mostrar_btn_eliminar2'] = false;
			$datos[$key]['etiqueta_btn_eliminar2'] = guarani_mensajes::get('800CUR_btn_eliminar');
			$datos[$key]['mostrar_btn_eliminar3'] = false;
			$datos[$key]['etiqueta_btn_eliminar3'] = guarani_mensajes::get('800CUR_btn_eliminar');
			
			// Si la comisión es cobrable y usa SQ
			if (($usa_sq == comunes::si) && ($comision_cobrable == comunes::si)) 
			{

				$estado_consumo_externo = cobro::get_estado_consumo_externo(cobro::TIPO_CONSUMO_SUSCRIPCION, $dato['nro_transaccion']);

				// Si el estado de la inscripción es 'cobrada' (ya se cobro)
				if ($estado_consumo_externo['estado'] == cobro::ESTADO_CONSUMO_PAGADO)
				{
					
					// Se pone $datos[$key]['modo_devolucion'] = null; para que el modo de devolución se decide por lo botones
					$datos[$key]['modo_devolucion'] = null;
					$datos[$key]['mostrar_btn_eliminar'] = false;
					
					$modos_devolucion_posibles = array_column($estado_consumo_externo['opciones_devolucion'], 'valor');
					
					if (in_array(cobro::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_AGENTE_COBRANZA, $modos_devolucion_posibles)) {
						$datos[$key]['mostrar_btn_eliminar'] = true;
						$datos[$key]['etiqueta_btn_eliminar'] = guarani_mensajes::get('800CUR_btn_eliminar_con_devolucion');
					}
					
					if (in_array(cobro::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_CREDITO, $modos_devolucion_posibles)) {
						$datos[$key]['mostrar_btn_eliminar2'] = true;
						$datos[$key]['etiqueta_btn_eliminar2'] = guarani_mensajes::get('800CUR_btn_eliminar_con_credito');
					}
					
					if (in_array(cobro::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_NO_CORRESPONDE, $modos_devolucion_posibles)) {
						$datos[$key]['mostrar_btn_eliminar3'] = true;
						$datos[$key]['etiqueta_btn_eliminar3'] = guarani_mensajes::get('800CUR_btn_eliminar_sin_devolucion');
					}
					
				}

			}
			
		}
		
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_inscripciones__eliminar($seleccion) {
		$this->eliminar_inscripcion($seleccion, cobro::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_AGENTE_COBRANZA);
	}

	function evt__cuadro_inscripciones__eliminar2($seleccion) {
		$this->eliminar_inscripcion($seleccion, cobro::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_CREDITO);
	}

	function evt__cuadro_inscripciones__eliminar3($seleccion) {
		$this->eliminar_inscripcion($seleccion, cobro::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_NO_CORRESPONDE);
	}
	
	function eliminar_inscripcion($seleccion, $modo_devolucion = cobro::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_NO_CORRESPONDE) {

		$inscripcion = toba::db()->quote($seleccion['inscripcion']);
		$filtro = "sga_insc_cursada.inscripcion = $inscripcion";
		$datos = current(toba::consulta_php('co_inscripciones_cursadas')->get_listado_baja($filtro));

		// Si $seleccion['modo_devolucion'] es null es porque el 'modo_devolucion' se decide por los botones
		$datos['modo_devolucion'] = (!is_null($seleccion['modo_devolucion']))? $seleccion['modo_devolucion'] : $modo_devolucion;

		try {
			$result = $this->cn()->eliminar_inscripcion_cursada($datos);
			if ($result['res'] < 1) {
				$mje = guarani_mensajes::get($result['msg_indice'], array($result['pars']));
				throw guarani::error($mje);
			} else {
				$mje = guarani_mensajes::get($result['msg_indice'], array($result['pars']));
				toba::notificacion()->agregar($mje, comunes::error_informacion);
				$nro_transaccion_log = $result['pars'];

                                $where = "sga_insc_cursada_log.nro_transaccion_log = $nro_transaccion_log";
                                $insc_cursadas_log = toba::consulta_php('co_inscripciones_cursadas')->get_insc_cursadas_log($where);
                                // Recupero la fecha de la baja
                                if (!empty($insc_cursadas_log)) {
                                    $datos['fecha_operacion'] = $insc_cursadas_log[0]['fecha_operacion'];
                                // En caso de no poder recuperarala tomo la fecha de la DB
                                } else {
                                    $datos['fecha_operacion'] = guarani_fecha::get_ahora(false);
                                }

				if( guarani::parametros()->get_valor(inscripcion_cursada::cur_notificar_estado_inscripcion) == comunes::si ) { 
					$parametros['nombre_actividad'] = toba::consulta_php('co_elementos')->get_nombre_actividad($datos['elemento']);
					$parametros['nombre_comision'] = $datos['comision_nombre'];
					$parametros['persona'] = $datos['persona'];
					$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas( Array($datos['persona']) );  
					$datos_persona = toba::consulta_php('co_personas')->get_datos_persona($datos['persona']);  
					$parametros['nombre_alumno'] = $datos_persona['nombres'];
					guarani::notificador()->enviar(notificador::eliminar_inscripcion_cursada, $parametros); 
				}

			}
		} catch (guarani_error $e) {
			throw guarani::error($e->getMessage());
		}

		$formateo = new toba_formateo('html');
		// Se preparan los datos para la impresión
		guarani_datos_impresion::instancia()->set_titulo('Comprobante de Baja de Inscripción a Cursada');
		guarani_datos_impresion::instancia()->set_conf(array('propuesta' => $datos['propuesta'], 'nro_transaccion' => $nro_transaccion_log, 'id_persona' => $datos['persona'], 'envio_autom' => true));

		// Se prepera el arreglo de datos
		guarani_datos_impresion::instancia()->nueva_fila_datos('Apellido y Nombre', $datos['apellido_y_nombres']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Identificación', $datos['nro_identificacion']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Legajo', $datos['legajo']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Propuesta', $datos['propuesta_nombre']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Fecha de Insc.', $formateo->formato_fecha_hora($datos['fecha_inscripcion']));
		guarani_datos_impresion::instancia()->nueva_fila_datos('Fecha de Baja', $formateo->formato_fecha_hora($datos['fecha_operacion']));
		guarani_datos_impresion::instancia()->nueva_fila_datos('Actividad', $datos['actividad_descripcion']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Año Académico', $datos['anio_academico']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Período Lectivo', $datos['periodo_nombre']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Cátedra', $datos['catedra_nombre']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Comisión', $datos['comision_nombre']);
		guarani_datos_impresion::instancia()->nueva_fila_datos('Turno', $datos['turno_nombre']);

		// Almaceno los datos en memoria para recuperarlos luego para la impresión de comprobante
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Indico que se debe abrir el Popup
		$this->popup = true;

	}

	function conf_evt__cuadro_inscripciones__eliminar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_inscripciones')->get_datos();
		$mensaje = guarani_mensajes::get('800CUR_confirmar_baja_insc', array($datos[$fila]['actividad_codigo'], $datos[$fila]['actividad_nombre']), false);
		$evento->set_msg_confirmacion($mensaje);
		
		if ($datos[$fila]['mostrar_btn_eliminar']) {
			$evento->set_etiqueta($datos[$fila]['etiqueta_btn_eliminar']);
		} else {//sino
			$evento->anular();
		}
	}

	function conf_evt__cuadro_inscripciones__eliminar2(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_inscripciones')->get_datos();
		$mensaje = guarani_mensajes::get('800CUR_confirmar_baja_insc', array($datos[$fila]['actividad_codigo'], $datos[$fila]['actividad_nombre']), false);
		$evento->set_msg_confirmacion($mensaje);

		if ($datos[$fila]['mostrar_btn_eliminar2']) {
			$evento->set_etiqueta($datos[$fila]['etiqueta_btn_eliminar2']);
		} else {//sino
			$evento->anular();
		}
	}
	
	function conf_evt__cuadro_inscripciones__eliminar3(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_inscripciones')->get_datos();
		$mensaje = guarani_mensajes::get('800CUR_confirmar_baja_insc', array($datos[$fila]['actividad_codigo'], $datos[$fila]['actividad_nombre']), false);
		$evento->set_msg_confirmacion($mensaje);

		if ($datos[$fila]['mostrar_btn_eliminar3']) {
			$evento->set_etiqueta($datos[$fila]['etiqueta_btn_eliminar3']);
		} else {//sino
			$evento->anular();
		}
	}

	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->popup) {
			$url = toba::vinculador()->get_url(toba::proyecto()->get_id(), '34000020', array(), array('celda_memoria' => 'celda_impresion'));
			echo "
				abrir_popup('impresion', '$url', {'width': 750, 'height': 550, 'resizable': 1, 'scrollbars':'1'});
			";
			$this->popup = false;
		}
	}

}