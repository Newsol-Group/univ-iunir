<?php
class cn_ent_acta_cursada extends cn_entidad
{
	protected $s__hay_cambios_cabecera = false;
	protected $s__eliminado = false;

	function entidad_cargada()
	{
		return $this->dep('datos')->esta_cargada();
	}

	function cargar_atributos_parametros($comision)
	{
		$operacion = guarani::get_id_operacion();

		$planes = aplanar_matriz(toba::consulta_php('co_comisiones')->get_planes_comision($comision), 'plan');
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_plan, $planes);

		$propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);

		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
	}

	function sincronizar()
	{
		// Se levanta una excepcion para que se haga un rollback de los insert/update que se hacen por fuera del DR.
		if (!$this->hay_cambios()) {
			throw guarani::error(toba::mensajes()->get('800_no_hubo_cambios'));
		}
		$this->dep('datos')->sincronizar();
	}

	function desactivar_lock_optimista()
	{
		$this->dep('datos')->persistidor()->set_lock_optimista(false);
	}

	//--------------------------------------------------------------------------
	//---- Dependencias dinámicas entre parámetros -----------------------------
	//--------------------------------------------------------------------------

	function cargar_dependencias_impresion($origen_acta, $digital)
	{
		try {
			$operacion = guarani::get_id_operacion();
			if ($origen_acta == acta::origen_regulares) {
				if ($digital == comunes::si) {
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_forma_asignacion_folio_fisico_regul');
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_carga_manual_libro_regulares');
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_agrega_folios_fisicos_extra_regul');
				} elseif (guarani::parametros()->get_valor('cur_forma_asignacion_folio_fisico_regul') != acta::forma_asignacion_folio_fisico_impresion) {
					// Si la forma de asignación de folio físico no es impresión, se marca como no usado el parámetros relacionado.
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_agrega_folios_fisicos_extra_regul');
				}
				// Se marcan los parámetros relativos a promociones como no usados en este contexto.
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_forma_asignacion_folio_fisico_promo');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_carga_manual_libro_promocion');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_actas_promocion_abiertas_cnt_ejemplares');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_actas_promocion_cerradas_cnt_ejemplares');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_agrega_folios_fisicos_extra_promo');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_inserta_alumnos_no_promocionales_en_acta');
			} else {
				if ($digital == comunes::si) {
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_forma_asignacion_folio_fisico_promo');
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_carga_manual_libro_promocion');
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_agrega_folios_fisicos_extra_promo');
				} elseif (guarani::parametros()->get_valor('cur_forma_asignacion_folio_fisico_promo') != acta::forma_asignacion_folio_fisico_impresion) {
					// Si la forma de asignación de folio físico no es impresión, se marca como no usado el parámetro relacionado.
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_agrega_folios_fisicos_extra_promo');
				}
				// Se marcan los parámetros relativos a cursadas como no usados en este contexto
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_forma_asignacion_folio_fisico_regul');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_carga_manual_libro_regulares');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_actas_regulares_abiertas_cnt_ejemplares');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_actas_regulares_cerradas_cnt_ejemplares');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_agrega_folios_fisicos_extra_regul');
			}			
		} catch (guarani_error_param_no_usado $e) {
		}
	}

	function cargar_dependencias_cierre_regul($digital)
	{
		try {
			$operacion = guarani::get_id_operacion();
			if ($digital == comunes::si) {
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_imprimir_ultima_version_acta_regulares');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_agrega_folios_fisicos_extra_regul');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_forma_asignacion_folio_fisico_regul');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_carga_manual_libro_regulares');
			} elseif (guarani::parametros()->get_valor('cur_forma_asignacion_folio_fisico_regul') != acta::forma_asignacion_folio_fisico_cierre) {
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_agrega_folios_fisicos_extra_regul');
			}
		} catch (guarani_error_param_no_usado $e) {
		}
	}

	function cargar_dependencias_cierre_promo($digital)
	{
		try {
			$operacion = guarani::get_id_operacion();
			if ($digital == comunes::si) {
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_imprimir_ultima_version_acta_promocion');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_agrega_folios_fisicos_extra_promo');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_forma_asignacion_folio_fisico_promo');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_carga_manual_libro_promocion');
			} elseif (guarani::parametros()->get_valor('cur_forma_asignacion_folio_fisico_promo') != acta::forma_asignacion_folio_fisico_cierre) {
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_agrega_folios_fisicos_extra_promo');
			}
		} catch (guarani_error_param_no_usado $e) {
		}
	}

	function cargar_dependencias_anulacion($origen_acta)
	{
		try {
			$operacion = guarani::get_id_operacion();
			if ($origen_acta == acta::origen_regulares) {
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'cur_inserta_alumnos_no_promocionales_en_acta');
			}
		} catch (guarani_error_param_no_usado $e) {
		}
	}

	//--------------------------------------------------------------------------
	//---- Control de Cambios --------------------------------------------------
	//--------------------------------------------------------------------------

	function hay_cambios()
	{
		return $this->dep('datos')->hay_cambios() || $this->s__hay_cambios_cabecera || $this->get_eliminado();
	}

	function alta_baja_detalle($id)
	{
		$altas_bajas = $this->tabla('eval_detalle_cursadas')->get_id_filas_a_sincronizar(array('d', 'i'));
		return in_array($id, $altas_bajas);
	}

	function hay_cambios_detalle($id, $columnas)
	{
		$cambios = $this->tabla('eval_detalle_cursadas')->get_cambios_fila($id);
		foreach ($columnas as $columna) {
			if (isset($cambios[$columna])) {
				return true;
			}
		}
		return false;
	}

	function hay_cambios_seleccion_alumnos()
	{
		$datos = $this->get_detalles(array('agregado' => true));
		return !empty($datos);
	}

	function set_eliminado()
	{
		$this->s__eliminado = true;
	}

	function get_eliminado()
	{
		return $this->s__eliminado;
	}

	function unset_eliminado()
	{
		$this->s__eliminado = false;
	}

	//--------------------------------------------------------------------------
	//---- Evaluación ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function set_datos_evaluacion($datos)
	{
		$this->tabla('evaluaciones')->set($datos);
	}

	function get_datos_evaluacion()
	{
		return $this->tabla('evaluaciones')->get();
	}

	function get_columna_evaluacion($columna)
	{
		return $this->tabla('evaluaciones')->get_columna($columna);
	}

	//------------------------------------------------------------------------
	//-- Detalles de la Evaluación
	//------------------------------------------------------------------------

	function get_detalles($condiciones = null, $param_ordenamiento = null)
	{
		$detalles = $this->tabla('eval_detalle_cursadas')->get_filas($condiciones);
		array_walk($detalles, function (&$alumno) {
			$alumno['apellido_y_nombre_order'] = guarani_texto::limpiar_acentos($alumno['apellido_y_nombre']);
			$alumno['legajo_order'] = (isset($alumno['legajo']))? guarani_texto::limpiar_acentos($alumno['legajo']) : "";
			$alumno['apellido_order'] = guarani_texto::limpiar_acentos($alumno['apellido']);
			$alumno['nombres_order'] = guarani_texto::limpiar_acentos($alumno['nombres']);
		});
		
		$campos_ordenamiento = $this->get_campos_orden_detalle($param_ordenamiento);
		return rs_ordenar_por_columnas($detalles, $campos_ordenamiento, SORT_STRING|SORT_FLAG_CASE);
	}

	protected function get_campos_orden_detalle($param_ordenamiento = null)
	{
		if (is_null($param_ordenamiento)) {
			// Si no se especifica nada, se ordena por apellido y nombre
			$salida = array('apellido_y_nombre_order');
		} elseif ($param_ordenamiento == 1) {
			// Ordena por legajo
			$salida = array('legajo_order');
		} elseif ($param_ordenamiento == 2) {
			// Ordena por apellido y nombre en forma separada
			$salida = array('apellido_order', 'nombres_order');
		} elseif ($param_ordenamiento == 3) {
			// Ordena por Número de documento principal del alumno. No importa el tipo de documento
			$salida = array('nro_documento');
		}
		return $salida;
	}

	function get_detalle($id)
	{
		return $this->tabla('eval_detalle_cursadas')->get_fila($id);
	}

	function get_id_detalle($condiciones)
	{
		return current($this->tabla('eval_detalle_cursadas')->get_id_fila_condicion($condiciones));
	}

	function existe_detalle($condiciones)
	{
		return $this->tabla('eval_detalle_cursadas')->existe_fila_condicion($condiciones);
	}

	function agregar_detalle($datos)
	{
		$this->tabla('eval_detalle_cursadas')->nueva_fila($datos);
	}

	function eliminar_detalle($datos)
	{
		$this->tabla('eval_detalle_cursadas')->eliminar_fila($datos);
	}

	function modificar_detalle($id, $datos)
	{
		$this->tabla('eval_detalle_cursadas')->modificar_fila($id, $datos);
	}

	function modificar_columna_detalle($id, $columna, $valor)
	{
		$this->tabla('eval_detalle_cursadas')->set_fila_columna_valor($id, $columna, $valor);
	}

	function modificar_columna_detalles($columna, $valor)
	{
		$this->tabla('eval_detalle_cursadas')->set_columna_valor($columna, $valor);
	}

	function procesar_detalles($datos)
	{
		$this->tabla('eval_detalle_cursadas')->procesar_filas($datos);
	}

	//--------------------------------------------------------------------------
	//---- Validaciones --------------------------------------------------------
	//--------------------------------------------------------------------------

	function validar_alumno($alumno, $comision, $instancia = null)
	{
		$this->validar_inscripcion($alumno, $comision, $instancia);
		$this->validar_puntos_de_control_agregar_alumno($alumno, $comision, $instancia);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Valida si un alumno puede asociarse a una comisión (sga_eval_detalle_cursadas) en alguna o todas sus instancias.
	 * Retorna los datos del alumno con las instancias y escalas de notas por instancia.
	 *
	 * @param array $alumno
	 * @param integer $comision
	 * @return array
	 */
	function validar_alumno_instancias($alumno, $comision)
	{
		$instancias = toba::consulta_php('co_comisiones')->get_instancias_comision($comision);
		$mensajes = array();
		$agregar_alumno = false;
		foreach ($instancias as $i) {
			if ($i['instancia'] == comision::inst_cursar) {
				$campo_instancia = 'instancia_cursada';
				$campo_escala_nota = 'escala_nota_cursada';
			} else {
				$campo_instancia = 'instancia_promocion';
				$campo_escala_nota = 'escala_nota_promocion';
			}
			$retorno = $this->validar_puntos_de_control_instancia($alumno['alumno'], $comision, $i['instancia']);
			if ($retorno['retorno'] == guarani_punto_de_control::autorizado) {
				$agregar_alumno = true;
				$alumno[$campo_instancia] = 1;
				$alumno[$campo_escala_nota] = $i['escala_nota'];
			} else {
				$mensajes = array_merge($mensajes, $retorno['mensajes']);
			}
		}
		if (!$agregar_alumno) {
			validador_puntos_de_control::agregar_al_notificador(toba::notificacion(), $mensajes);
			throw new guarani_error(null, null, guarani_mensajes::get('800_error_control_acta'));
		}
		return $alumno;
	}

	function validar_inscripcion($alumno, $comision, $instancia = null)
	{
		$filtro = "sga_insc_cursada.alumno = $alumno";
		$filtro .= " AND sga_insc_cursada.estado = '" . inscripcion_cursada::estado_pendiente . "'";
		if (!empty($instancia)) {
			$filtro .= " AND EXISTS (SELECT * FROM sga_insc_cursada_instancias WHERE sga_insc_cursada_instancias.inscripcion = sga_insc_cursada.inscripcion AND sga_insc_cursada_instancias.instancia = $instancia)";
		}
		$cant_inscripciones_pendientes = toba::consulta_php('co_inscripciones_cursadas')->get_cant_inscripciones_para_comision($comision, $filtro);
		if ($cant_inscripciones_pendientes > 0) {
			$this->set_error('800_insc_pendiente_comision');
		}
		// Se valida que no exista una inscripción a la comisión (ignorando el estado) para la persona en cualquier propuesta (sin importanr la calidad).
		$persona = toba::consulta_php('co_alumnos')->get_persona_alumno($alumno);
		$filtro = "sga_alumnos.persona = $persona";
		$cant_inscripciones_persona = toba::consulta_php('co_inscripciones_cursadas')->get_cant_inscripciones_para_comision($comision, $filtro);
		if ($cant_inscripciones_persona > 0) {
			$this->set_error('800SIU_insc_cursada_existe_insc');
		}
	}

	function validar_puntos_de_control_agregar_alumno($alumno, $comision, $instancia = null)
	{
		$puntos_de_control = array(
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::alumno_actividad,
			guarani_punto_de_control::alumno_comision,
			guarani_punto_de_control::persona_propuesta,
			guarani_punto_de_control::alumno_anio_academico
		);
		if (!is_null($instancia)) {
			$puntos_de_control_instancia = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
			$puntos_de_control = array_merge($puntos_de_control, $puntos_de_control_instancia);
		}

		$datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno));
		$datos_comision = toba::consulta_php('co_comisiones')->get_datos_basicos_comision($comision);
		$anio_academico = toba::consulta_php('co_comisiones')->get_anio_academico_comision($comision);
		$parametros = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $datos_alumno['persona'],
			'alumno' => $datos_alumno['alumno'],
			'plan_version' => $datos_alumno['plan_version'],
			'elemento' => $datos_comision['elemento'],
			'comision' => $datos_comision['comision'],
			'propuesta' => $datos_alumno['propuesta'],
			'anio_academico' => $anio_academico
		);

		$retorno = guarani::validador_puntos_de_control($puntos_de_control, $parametros)->controlar();
		guarani::validador_puntos_de_control()->notificar();
		if ($retorno == guarani_punto_de_control::rechazado) {
			throw new guarani_error(null, null, guarani_mensajes::get('800_error_control_acta'));
		}
	}

	function validar_puntos_de_control_instancia($alumno, $comision, $instancia)
	{
		$puntos_de_control = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);

		$datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno));
		$datos_comision = toba::consulta_php('co_comisiones')->get_datos_basicos_comision($comision);
		$parametros = array(
			'fecha' => guarani_fecha::get_hoy(),
			'persona' => $datos_alumno['persona'],
			'alumno' => $datos_alumno['alumno'],
			'plan_version' => $datos_alumno['plan_version'],
			'elemento' => $datos_comision['elemento'],
			'comision' => $datos_comision['comision']
		);

		$retorno = guarani::validador_puntos_de_control($puntos_de_control, $parametros)->controlar();
		$mensajes = guarani::validador_puntos_de_control()->get_mensajes();
		return array('retorno' => $retorno, 'mensajes' => $mensajes);
	}

	//--------------------------------------------------------------------------
	//---- Generación de Acta --------------------------------------------------
	//--------------------------------------------------------------------------
	/**
	 * Crea un nuevo acta para la comisión.
	 *
	 * @param integer	$nro_acta
	 * @param string	$observaciones
	 * @param integer	$comision	código de comisión a la cual pertenece el acta.
	 * @param integer	$instancia	instancia a la cual estará asociada (regularidad, promoción).
	 * @return integer	identificador del nuevo acta.
	 */
	function generar_acta($nro_acta, $observaciones, $comision, $instancia)
	{
		$this->desactivar_transacciones_anidadas();
		try {
			toba::db()->abrir_transaccion();

			$id_acta = $this->generar_cabecera_acta($nro_acta, $observaciones, $comision, $instancia);

			$this->generar_instancias_acta($id_acta, $comision, $instancia);
			$this->asociar_detalles_acta($id_acta, $instancia);

			toba::db()->cerrar_transaccion();

			return $id_acta;
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	/**
	 * Crea la cabecera del acta.
	 */
	function generar_cabecera_acta($nro_acta, $observaciones, $comision, $instancia, $tipo_acta = acta::tipo_normal, $documento = null, $id_acta_original = null)
	{
		// Datos por defecto para la creación de un acta.
		$evaluacion = $this->get_columna_evaluacion('evaluacion');

		if ($instancia == comision::inst_cursar) {
			$lineas_folio = guarani::parametros()->get_valor('cur_lineas_folio_acta_regulares');
			$digital = guarani::parametros()->get_valor('cur_utiliza_acta_digital_regul');
			$origen = acta::origen_regulares;
		} else {
			$lineas_folio = guarani::parametros()->get_valor('cur_lineas_folio_acta_promocion');
			$digital = guarani::parametros()->get_valor('cur_utiliza_acta_digital_promo');
			$origen = acta::origen_promociones;
		}

		// Se inserta el nuevo acta.
		guarani::act('act_actas')->insertar_acta($nro_acta, $origen, $tipo_acta, $evaluacion, $comision, $lineas_folio, $documento, $id_acta_original, $observaciones, $digital);

		return toba::db()->recuperar_secuencia('sga_actas_seq');
	}

	/**
	 * Asocia un acta a la instancia correspondiente.
	 */
	function generar_instancias_acta($id_acta, $comision, $instancia)
	{
		// Se obtiene la escala de notas correspondiente a la instancia de la comisión.
		$where = "sga_comisiones_instancias.instancia = $instancia";
		$instancia_comision = current(toba::consulta_php('co_comisiones')->get_instancias_comision($comision, $where));
		$escala_nota = $instancia_comision['escala_nota'];

		// Se asocia al acta la instancia.
		guarani::act('act_actas')->insertar_acta_instancia($id_acta, $instancia, $escala_nota);
	}

	/**
	 * Vincula detalles de la evaluación a un acta de acuerdo a la instancia.
	 */
	function asociar_detalles_acta($id_acta, $instancia)
	{
		$campo_instancia = ($instancia == comision::inst_cursar) ? 'id_acta_cursada' : 'id_acta_promocion';

		// Se reemplaza el ID de acta ficticia por el real.
		$condiciones = array($campo_instancia => acta::id_acta_ficticia);
		$detalles = $this->get_detalles($condiciones);
		foreach ($detalles as $detalle) {
			$id_detalle = $this->get_id_detalle(array('alumno' => $detalle['alumno']));
			$datos = array($campo_instancia => $id_acta);
			$this->modificar_detalle($id_detalle, $datos);
		}
		$this->sincronizar();
	}

	//--------------------------------------------------------------------------
	//---- Modificación de Acta ------------------------------------------------
	//--------------------------------------------------------------------------
	/**
	 * Actualiza la versión de un acta y sincroniza los cambios de sus detalles.
	 *
	 * @param array $datos_acta datos básicos del acta.
	 */
	function modificar_acta($datos_acta)
	{
		$this->desactivar_transacciones_anidadas();
		try {
			toba::db()->abrir_transaccion();

			$this->actualizar_version_acta($datos_acta);

			$this->sincronizar();

			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	/**
	 * Si la versión impresa del acta coincide con su versión actual, esta última se incrementa en uno.
	 * 
	 * @param integer $datos_acta datos básicos del acta.
	 */
	function actualizar_version_acta($datos_acta)
	{
		if ($datos_acta['version'] == $datos_acta['version_impresa']) {
			guarani::act('act_actas')->actualizar_version_acta($datos_acta['id_acta'], $datos_acta['version']);
		}
	}

	//--------------------------------------------------------------------------
	//---- Cierre de Acta ------------------------------------------------------
	//--------------------------------------------------------------------------
	/**
	 * Cierra un acta.
	 *
	 * @param integer	$id_acta 	código del acta a cerrar.
	 * @param array		$alumnos 	arreglo asociativo con los datos de los alumnos vinculados al acta a cerrar.
	 * @param int		$libro_tomo	id del Tomo del Libro de Acta
	 */
	function cerrar_acta($id_acta, $alumnos, $libro_tomo = null)
	{	
		$cierre_ok = false;
		$datos_acta = toba::consulta_php('co_actas')->get_datos_acta($id_acta);                
        $autoridad_firma_acta_digital = guarani::parametros()->get_valor('autoridad_firma_acta_digital');
        
        $esActaRegular = ($datos_acta['origen'] == acta::origen_regulares);
		$esActaPromocion = ($datos_acta['origen'] == acta::origen_promociones);
        $esActaRectificativa = $datos_acta['tipo_acta'] == acta::tipo_rectificativa && !empty($datos_acta['acta_referencia']);
        
		if ($esActaRegular) {
			$campo_otra_instancia = 'instancia_promocion';
			$campo_acta_cerrada = 'acta_cursada_cerrada';
			$campo_otra_instancia_cerrada = 'acta_promocion_cerrada';
			$metodo_fin_vigencia = 'get_fin_vigencia_regularidad';

            $cur_forma_asignacion_folio_fisico = acta::cur_forma_asignacion_folio_fisico_regul;
            $cur_carga_manual_libro = acta::cur_carga_manual_libro_regulares;
            $cur_agrega_folios_fisicos_extra = acta::cur_agrega_folios_fisicos_extra_regul;
            
            $cur_actas_cerradas_cnt_ejemplares = acta::cur_actas_regulares_cerradas_cnt_ejemplares;
            $cur_actas_abiertas_cnt_ejemplares = acta::cur_actas_regulares_abiertas_cnt_ejemplares;
            
            $alcance_notificador = notificador::cerrar_acta_regulares;
            
		} elseif ($esActaPromocion) {
			$campo_otra_instancia = 'instancia_cursada';
			$campo_acta_cerrada = 'acta_promocion_cerrada';
			$campo_otra_instancia_cerrada = 'acta_cursada_cerrada';
			$metodo_fin_vigencia = 'get_fin_vigencia_promocion';

            $cur_forma_asignacion_folio_fisico = acta::cur_forma_asignacion_folio_fisico_promo;
            $cur_carga_manual_libro = acta::cur_carga_manual_libro_promocion;
            $cur_agrega_folios_fisicos_extra = acta::cur_agrega_folios_fisicos_extra_promo;  
            
            $cur_actas_cerradas_cnt_ejemplares = acta::cur_actas_promocion_cerradas_cnt_ejemplares;
            $cur_actas_abiertas_cnt_ejemplares = acta::cur_actas_promocion_abiertas_cnt_ejemplares;
            
            $alcance_notificador = notificador::cerrar_acta_promociones;
		}

		$utiliza_acta_digital = ($datos_acta['digital'] == comunes::si);
        $propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($datos_acta['comision']), 'propuesta');
        $ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);

        if ($utiliza_acta_digital) {
            comunes::verificar_jasper_corriendo();
			arai_documentos::validar_cuenta_usuario_origen_arai_documentos();
            acta::validar_autorizantes_asociados_acta($id_acta, $autoridad_firma_acta_digital, $ras);
        } else {
            $forma_asignacion_folio_fisico = guarani::parametros()->get_valor($cur_forma_asignacion_folio_fisico);
            if ($forma_asignacion_folio_fisico == acta::forma_asignacion_folio_fisico_cierre) {
                $carga_manual_libro = guarani::parametros()->get_valor($cur_carga_manual_libro);
                $agrega_folios_fisicos_extra = guarani::parametros()->get_valor($cur_agrega_folios_fisicos_extra);
            }
        }
        
        $this->validar_datos_cierre($datos_acta, $alumnos);
		$this->validar_puntos_de_control_alumnos($datos_acta, $alumnos);

		$this->desactivar_transacciones_anidadas();
		try {
			toba::db()->abrir_transaccion();

			// Si corresponde, se realiza la asignación de folios físicos.
			if (!$utiliza_acta_digital && $forma_asignacion_folio_fisico == acta::forma_asignacion_folio_fisico_cierre) {
				$mensaje_error = acta::asignar_folio_fisico($datos_acta, guarani_arreglos::count($alumnos), $forma_asignacion_folio_fisico, $carga_manual_libro, $agrega_folios_fisicos_extra, $libro_tomo);
				if (!empty($mensaje_error)) {
					throw guarani::error(guarani_mensajes::get($mensaje_error));
				}
			}

			// Fechas de fin de vigencia, según los parámetros definidos por plan para regularidad y promoción.
			$params_vig_por_plan = array();

			// Se actualizan o eliminan detalles de evaluación, se insertan detalles de acta.
			foreach ($alumnos as $alumno) {
				$id_detalle = $this->get_id_detalle(array('alumno' => $alumno['alumno']));
				$detalle = $this->get_detalle($id_detalle);

				// Si la otra instancia es válida y no está cerrada, se modifica el detalle indicando que el acta para la instancia actual está cerrada.
				if ($detalle[$campo_otra_instancia] && !$detalle[$campo_otra_instancia_cerrada]) {
					$this->modificar_columna_detalle($id_detalle, $campo_acta_cerrada, 1);
				} else { // Sino, se elimina el detalle.
					$this->eliminar_detalle($detalle);
				}

				// La fecha de vigencia sólo se completa para alumnos APROBADOS.
				if ($alumno['resultado'] == escalas_notas::resultado_aprobado) {
					// Verifica si ya se obtuvo el parámetro de fin de vigencia, correspondiente a la 
					// instancia y plan de inscripción del alumno, si es así se recupera del arreglo asociado, en
					// otro caso se consulta al método apropiado que retorna la fecha desplazada de vigencia
					// según el valor parámetro correspondiente.
					$plan = $alumno['plan_inscripcion'];
					if (!isset($params_vig_por_plan[$plan])) {
						// El valor del parámetro 'cur_cant_meses_vigencia_regularidad' se obtiene para cada alumno,
						// dado que puede variar en función del plan de cada uno.
						$cant_meses_vigencia_regularidad = guarani::parametros()->get_valor('cur_cant_meses_vigencia_regularidad', $plan);

						// Se busca una excepción de vigencia de regularidad para el elemento y el plan de inscripción.
						$filtro = " sga_excep_regularidad.elemento = {$datos_acta['elemento']} AND
									sga_excep_regularidad_planes.plan = $plan";
						$excep_vigencia = current(guarani::co('co_elementos')->get_planes_excepcion_regularidad($filtro));
						if (!empty($excep_vigencia) && isset($excep_vigencia['vigencia_cursada'])) {
							$cant_meses_vigencia_regularidad = $excep_vigencia['vigencia_cursada'];					
						}

						$params_vig_por_plan[$plan] = call_user_func_array(array(toba::consulta_php('co_actas'), $metodo_fin_vigencia), array($datos_acta['comision'], $cant_meses_vigencia_regularidad));
					}
					$alumno['fecha_vigencia'] = $params_vig_por_plan[$plan];
				}

				// El plan-versión es 'sga_eval_detalle_cursadas.plan_version'.
				$alumno['plan_version'] = $detalle['plan_version'];
				guarani::act('act_actas')->insertar_acta_detalle($alumno);

				// Si el acta es de tipo 'Rectificativa', para cada alumno se marcan los detalles del acta original y sus rectificativas cerradas como rectificados.
				if ($esActaRectificativa) {
					guarani::act('act_actas')->rectificar_detalle($alumno['alumno'], $datos_acta['acta_referencia']);
					// Se elimina (si existe) el registro para el alumno en la tabla de log de no promocionales.
					if ($esActaPromocion) {
						guarani::act('act_actas')->eliminar_alumno_no_promocional($datos_acta['comision'], $alumno['alumno']);
					}
				}
			}

			if ($esActaPromocion &&
					guarani::parametros()->get_valor('cur_inserta_alumnos_no_promocionales_en_acta') == comunes::no &&
					$datos_acta['tipo_acta'] == acta::tipo_normal) {
				$this->procesar_alumnos_no_promocionales($id_acta);
			}

			// Se actualiza la cabecera del acta.
			guarani::act('act_actas')->cerrar_acta($id_acta);

			// Se sincronizan los cambios en los detalles de evaluaciones.
			$this->sincronizar();

			// Si corresponde, se generan los registros para la asignación manual de folio físico.
			if (!$utiliza_acta_digital && $forma_asignacion_folio_fisico == acta::forma_asignacion_folio_fisico_manual) {
				guarani::act('act_actas')->generar_foliado_fisico($id_acta);
			}
            
            $parametros = [];            
            $parametros['id_acta'] = $id_acta;
            
            //Se agrega el notificador dentro de la transaccion.
            guarani::notificador()->enviar($alcance_notificador, $parametros, false);            

            toba::db()->cerrar_transaccion();
			$cierre_ok = true;
		} catch (toba_error_db $e) {
			$cierre_ok = false;
			toba::db()->abortar_transaccion();
			if ($e->get_sqlstate() == 'db_23505') {
				$mensaje = guarani_mensajes::get('800_cerrar_acta_error_ff');
				throw guarani::error($mensaje);
			} else {
				throw $e;
			}
		} catch (toba_error $e) {
			$cierre_ok = false;
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
		
		if ($cierre_ok) {
			$parametros = [];
			$parametros['utiliza_acta_digital'] = $datos_acta['digital'];
			$parametros['docentes_nivel_autorizacion'] = guarani::parametros()->get_valor('docentes_nivel_autorizacion');
			$parametros['autoridad_firma_acta_digital'] = $autoridad_firma_acta_digital;
			$parametros['cur_actas_cerradas_cnt_ejemplares'] = guarani::parametros()->get_valor($cur_actas_cerradas_cnt_ejemplares);
			$parametros['cur_actas_abiertas_cnt_ejemplares'] = guarani::parametros()->get_valor($cur_actas_abiertas_cnt_ejemplares);
			$parametros['cur_inserta_alumnos_no_promocionales_en_acta'] = guarani::parametros()->get_valor(acta::cur_inserta_alumnos_no_promocionales_en_acta);
			$parametros['cur_orden_detalle_actas'] = guarani::parametros()->get_valor(acta::cur_orden_detalle_actas);
		   /**
			* Envía un acta a SIU-Araí Documentos.
			*/
			acta::enviar_acta_arai_documentos($id_acta, $parametros, $ras);
		}
	}
            
	function validar_datos_cierre($datos_acta, $alumnos)
	{
		$asentar_notas_actas = ($datos_acta['origen'] == acta::origen_regulares) ? acta::cur_asentar_notas_actas_regulares : acta::cur_asentar_notas_actas_promocion;
		$imprimir_ultima_version_acta = ($datos_acta['origen'] == acta::origen_regulares) ? acta::cur_imprimir_ultima_version_acta_regulares : acta::cur_imprimir_ultima_version_acta_promocion;
		$utiliza_acta_digital = ($datos_acta['digital'] == comunes::si);
		$imprimir_ultima_version_acta = $utiliza_acta_digital ? false : guarani::parametros()->get_valor($imprimir_ultima_version_acta);
		$parametros = array(
			'multiples_actas_comision' => guarani::parametros()->get_valor('cur_multiples_actas_comision'),
			'asentar_notas_actas' => guarani::parametros()->get_valor($asentar_notas_actas),
			'utiliza_acta_digital' => $utiliza_acta_digital,
			'imprimir_ultima_version_acta' => $imprimir_ultima_version_acta
		);
		$validador = acta::validador_actas_cursada();
		$validador->validar_datos_cierre($datos_acta, $alumnos, $parametros);
		if ($validador->hay_notificaciones()) {
			$notificaciones = $validador->get_notificaciones();
			foreach ($notificaciones as $notificacion) {
				$this->set_notificacion($notificacion['descripcion_corta'], $notificacion['params'], $notificacion['tipo']);
			}
		}
		if ($validador->hay_errores()) {
			$errores = $validador->get_errores();
			foreach ($errores as $error) {
				$this->set_error($error['descripcion_corta'], $error['params'], $error['tipo']);
			}
		}
		if ($this->hay_errores) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 * Ejecuta los controles configurables al conjunto de alumnos.
	 */
	function validar_puntos_de_control_alumnos($datos_acta, $alumnos)
	{
		if ($datos_acta['origen'] == acta::origen_regulares) {
			$instancia = comision::inst_cursar;
			$parametro_cierre = acta::cur_aplicar_controles_cierre_reg;
			$punto_control_cierre = guarani_punto_de_control::cerrar_acta_regulares;
		} else {
			$instancia = comision::inst_promocionar;
			$parametro_cierre = acta::cur_aplicar_controles_cierre_promo;
			$punto_control_cierre = guarani_punto_de_control::cerrar_acta_promocion;
		}

		$puntos_de_control = [
			guarani_punto_de_control::alumno_entidad,
			guarani_punto_de_control::persona_operacion,
			guarani_punto_de_control::alumno_operacion,
			guarani_punto_de_control::alumno_actividad,
			guarani_punto_de_control::alumno_comision,
			guarani_punto_de_control::alumno_anio_academico,
			guarani_punto_de_control::persona_propuesta,
			$punto_control_cierre
		];
		$puntos_de_control_instancia = toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
		$puntos_de_control = array_merge($puntos_de_control, $puntos_de_control_instancia);

		$aplicar_controles_cierre = guarani::parametros()->get_valor($parametro_cierre);

		foreach ($alumnos as $alumno) {
			// Si es ausente, o solo se controla a los alumnos que aprobaron la cursada y no aprobó entonces no corro los controles sobre dicho alumno.
			if (!($alumno['resultado'] == escalas_notas::resultado_ausente || ($aplicar_controles_cierre == 'APROBADOS' && $alumno['resultado'] != escalas_notas::resultado_aprobado))) {
				$parametros = [
					'alumno' => $alumno['alumno'],
					'elemento' => $datos_acta['elemento'],
					'comision' => $datos_acta['comision'],
					'nota' => $alumno['nota'],
					'resultado' => $alumno['resultado'],
					'fecha' => guarani_fecha::get_hoy(),
					'persona' => $alumno['persona'],
					'plan_version' => $alumno['plan_version_inscripcion'],
					'propuesta' => $alumno['propuesta'],
					'anio_academico' => $datos_acta['anio_academico_periodo']
				];

				if ($datos_acta['tipo_acta'] == acta::tipo_rectificativa) {
					$parametros['id_acta_original'] = $datos_acta['acta_referencia'];
				}

				// Si se trata de un Acta de Regulares se agregan parámetros de contexto faltantes para los puntos de control 14 y 15 en cierre de actas de cursada y promoción.
				if ($punto_control_cierre == guarani_punto_de_control::cerrar_acta_regulares) {
					$parametros['cond_regularidad'] = $alumno['cond_regularidad'];
				}

				$retorno = guarani::validador_puntos_de_control($puntos_de_control, $parametros)->controlar();
				guarani::validador_puntos_de_control()->notificar();
				if ($retorno == guarani_punto_de_control::rechazado) {
					throw new guarani_error(null, null, guarani_mensajes::get('800_error_control_cierre_acta'));
				}
			}
		}
	}

	function procesar_alumnos_no_promocionales($id_acta)
	{
		$alumnos = toba::consulta_php('co_actas')->get_alumnos_no_promocionales_acta($id_acta);
		foreach ($alumnos as $alumno) {
			$id_detalle = $this->get_id_detalle(array('alumno' => $alumno['alumno']));
			$detalle = $this->get_detalle($id_detalle);

			// Si la instancia cursada es válida y el acta de cursada está abierta...
			if ($detalle['instancia_cursada'] && !$detalle['acta_cursada_cerrada']) {
				// Se modifica el detalle indicando que el acta de promoción está cerrada.
				$this->modificar_columna_detalle($id_detalle, 'acta_promocion_cerrada', 1);
			} else {
				// Sino, se elimina el detalle.
				$this->eliminar_detalle($detalle);
			}

			// Se pasan los datos del alumno no promocional a la tabla de logs.
			guarani::act('act_actas')->insertar_alumno_no_promocional($id_acta, $alumno['alumno'], $alumno['plan_version'], $alumno['fecha_promocion'], $alumno['escala_nota_promocion'], $alumno['nota_promocion'], $alumno['resultado_promocion'], $alumno['observacion_promocion']);
		}
	}

	//--------------------------------------------------------------------------
	//---- Rectificación de Acta -----------------------------------------------
	//--------------------------------------------------------------------------
	/**
	 * Crea un nuevo acta rectificativa para la comisión.
	 *
	 * @param integer	$nro_acta
	 * @param integer	$documento			código de documento respaldatorio de la rectificación.
	 * @param string	$observaciones
	 * @param integer	$id_acta_original	código del acta original a rectificar.
	 * @param integer	$comision			código de comisión a la cual pertenece el acta.
	 * @param integer	$instancia			instancia a la cual estará asociada (regularidad, promoción).
	 * 
	 * @return integer	identificador del nuevo acta.
	 */
	function generar_acta_rectificativa($nro_acta, $documento = null, $observaciones, $id_acta_original, $comision, $instancia)
	{
		$this->desactivar_transacciones_anidadas();
		try {
			toba::db()->abrir_transaccion();

			$id_acta = $this->generar_cabecera_acta($nro_acta, $observaciones, $comision, $instancia, acta::tipo_rectificativa, $documento, $id_acta_original);

			$this->generar_instancias_acta_rectificativa($id_acta, $id_acta_original);
			$this->asociar_detalles_acta($id_acta, $instancia);

			toba::db()->cerrar_transaccion();

			return $id_acta;
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	/**
	 * Asocia un acta rectificativa a las instancias del acta original.
	 */
	function generar_instancias_acta_rectificativa($id_acta, $id_acta_original)
	{
		// Se obtienen las instancias y escalas de notas correspondientes al acta original.
		$instancias = toba::consulta_php('co_actas')->get_instancias_acta($id_acta_original);

		foreach ($instancias as $instancia) {
			// Se asocia al acta la instancia.
			guarani::act('act_actas')->insertar_acta_instancia($id_acta, $instancia['instancia'], $instancia['escala_nota']);
		}
	}

	//--------------------------------------------------------------------------
	//---- Anulación de Acta ---------------------------------------------------
	//--------------------------------------------------------------------------
	/**
	 * Anula un acta.
	 *
	 * @param array	$datos_acta	datos básicos del acta a anular.
	 * @param array	$alumnos	datos de los alumnos vinculados al acta a anular.
	 */
	function anular_acta($datos_acta, $alumnos)
	{
		if ($datos_acta['origen'] == acta::origen_regulares) {
			$campo_otra_instancia = 'instancia_promocion';
			$campo_instancia = 'instancia_cursada';
			$campo_acta_cerrada = 'acta_cursada_cerrada';
			$campo_otra_instancia_cerrada = 'acta_promocion_cerrada';
			$campo_id_acta = 'id_acta_cursada';
			$campo_nota = 'nota_cursada';
			$campo_escala_nota = 'escala_nota_cursada';
			$campo_resultado = 'resultado_cursada';
		} else {
			$campo_otra_instancia = 'instancia_cursada';
			$campo_instancia = 'instancia_promocion';
			$campo_acta_cerrada = 'acta_promocion_cerrada';
			$campo_otra_instancia_cerrada = 'acta_cursada_cerrada';
			$campo_id_acta = 'id_acta_promocion';
			$campo_nota = 'nota_promocion';
			$campo_escala_nota = 'escala_nota_promocion';
			$campo_resultado = 'resultado_promocion';
		}

		$this->desactivar_transacciones_anidadas();
		try {
			toba::db()->abrir_transaccion();
			if (!empty($alumnos)) {
				// Se actualizan o eliminan detalles de evaluación, se insertan detalles de acta.
				foreach ($alumnos as $alumno) {
					$id_detalle = $this->get_id_detalle(array('alumno' => $alumno['alumno']));
					$detalle = $this->get_detalle($id_detalle);

					// Si es un acta NORMAL, se desasocia al alumno del acta.
					if ($datos_acta['tipo_acta'] == acta::tipo_normal) {
						$this->modificar_columna_detalle($id_detalle, $campo_id_acta, null);
					} else { // Si es un acta RECTIFICATIVA...
						// Si la otra instancia es válida y no está cerrada, se modifica el detalle.
						if ($detalle[$campo_otra_instancia] && !$detalle[$campo_otra_instancia_cerrada]) {
							// Se busca la última nota válida del alumno.
							$nota_anterior = toba::consulta_php('co_actas')->get_detalle_alumno_comision($alumno['alumno'], $datos_acta['comision'], $datos_acta['origen']);
							// Si tiene nota en algún acta anterior de la comisión, actualizar el detalle colocando acta, nota, resultado y marcarlo como válido y cerrado.
							if (!empty($nota_anterior)) {
								$datos = array();
								$datos[$campo_nota] = $nota_anterior['nota'];
								$datos[$campo_escala_nota] = $nota_anterior['escala_nota'];
								$datos[$campo_resultado] = $nota_anterior['resultado'];
								$datos[$campo_id_acta] = $nota_anterior['id_acta'];
								$datos[$campo_instancia] = 1;
								$datos[$campo_acta_cerrada] = 1;
								$this->modificar_detalle($id_detalle, $datos);
							} else { // Sino, se marca el detalle como inválido.
								$this->modificar_columna_detalle($id_detalle, $campo_instancia, 0);
							}
						} elseif ($alumno['inscripto'] == comunes::no) { // Sino, si el alumno es NO inscripto, se elimina el detalle.
							$this->eliminar_detalle($detalle);
						} else { // Sino, si el alumno es inscripto, se desasocia del acta.
							$this->modificar_columna_detalle($id_detalle, $campo_id_acta, null);
						}
					}

					$alumno['estado'] = comunes::estado_baja;
					guarani::act('act_actas')->insertar_acta_detalle($alumno);
				}
				$this->sincronizar();
			}

			// Se actualiza la cabecera del acta.
			guarani::act('act_actas')->anular_acta($datos_acta['id_acta'], $datos_acta['observaciones']);
			/**
			 * Para el caso en que el parámetro de incluir los alumnos no promocionales en el Acta de
			 * tipo Promoción es N, se puede estar anulando la misma sin ningún alumno para el que se
			 * modifique el detalle, con lo cual debe marcarse el cambio en la cabecera para que luego
			 * se permita la sincronización.
			 */
			if ($datos_acta['origen'] == acta::origen_promociones &&
					guarani::parametros()->get_valor('cur_inserta_alumnos_no_promocionales_en_acta') == comunes::no &&
					$datos_acta['tipo_acta'] == acta::tipo_normal) {
				$this->s__hay_cambios_cabecera = true;
			}
			// Se sincronizan los cambios en los detalles de evaluaciones.

			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	//--------------------------------------------------------------------------
	//---- Carga de Notas ------------------------------------------------------
	//--------------------------------------------------------------------------

	function procesar_carga_notas($datos)
	{
		$this->validar_carga_notas($datos);
		$this->procesar_detalles($datos);
	}

	/**
	 * Sincronización en la base de datos de la carga de notas a un conjunto de alumnos.
	 */
	function sincronizar_carga_notas($condiciones)
	{
		// Se obtiene la lista de alumnos seleccionados para la carga de notas.
		$datos = $this->get_detalles($condiciones);

		foreach ($datos as $detalle) {
			if (!$this->alta_baja_detalle($detalle[apex_datos_clave_fila])) { // Si el detalle no es nuevo ni fue dado de baja...
				if ($this->hay_cambios_detalle($detalle[apex_datos_clave_fila], array('nota_cursada', 'resultado_cursada', 'cond_regularidad', 'fecha_regular', 'observacion_cursada'))) {
					// Se controla la versión del acta de cursada.
					$this->controlar_version_acta($detalle['id_acta_cursada']);
				}

				if ($this->hay_cambios_detalle($detalle[apex_datos_clave_fila], array('nota_promocion', 'resultado_promocion', 'fecha_promocion', 'observacion_promocion'))) {
					// Se controla la versión del acta de promoción.
					$this->controlar_version_acta($detalle['id_acta_promocion']);
				}
			}
		}

		$this->sincronizar();
	}

	/**
	 * Si el alumno está en un acta (de cursada o promoción) y hubo cambios en el detalle
	 * se controla que la versión impresa del acta coincida con su versión actual, en cuyo caso esta última se incrementa en uno.
	 * 
	 * @param integer $id_acta código del acta (de cursada o promoción).
	 */
	function controlar_version_acta($id_acta)
	{
		if (!empty($id_acta)) {
			$datos_acta = toba::consulta_php('co_actas')->get_datos_acta($id_acta);
			$this->actualizar_version_acta($datos_acta);
		}
	}

	function validar_carga_notas($datos)
	{
		$primer_detalle = current($datos);
		$comision = $primer_detalle['comision'];
		$datos_periodo = toba::consulta_php('co_comisiones')->get_datos_periodo_comision($comision);

		$validador = acta::validador_actas_cursada();
		foreach ($datos as $detalle) {
			$validador->validar_carga_notas($detalle, $datos_periodo['periodo_fecha_inicio']);
		}
		if ($validador->hay_errores()) {
			$errores = $validador->get_errores();
			foreach ($errores as $error) {
				$this->set_error($error['descripcion_corta'], $error['params'], $error['tipo']);
			}
		}
		if ($this->hay_errores) {
			throw guarani::error($this->errores);
		}
	}

	//--------------------------------------------------------------------------
	//---- Impresión de Acta ---------------------------------------------------
	//--------------------------------------------------------------------------

	function actualizar_datos_impresion($datos_acta, $carga_manual_libro, $forma_asignacion_folio_fisico, $libro_tomo = null, $propuestas = null)
	{
		if ($datos_acta['version'] != $datos_acta['version_impresa']) {
			try {
				toba::db()->abrir_transaccion();

				// Si corresponde, se realiza la asignación de folios físicos.
				if (($datos_acta['estado'] != acta::estado_anulada) && ($forma_asignacion_folio_fisico == acta::forma_asignacion_folio_fisico_impresion)) {
					if ($datos_acta['origen'] == acta::origen_regulares) {
						if (is_null($propuestas)) {
							$agrega_folios_fisicos_extra = guarani::parametros()->get_valor('cur_agrega_folios_fisicos_extra_regul');
						} else {
							$agrega_folios_fisicos_extra = guarani::parametros()->get_valor('cur_agrega_folios_fisicos_extra_regul', $propuestas);
						}
					} else {
						if (is_null($propuestas)) {
							$agrega_folios_fisicos_extra = guarani::parametros()->get_valor('cur_agrega_folios_fisicos_extra_promo');
						} else {
							$agrega_folios_fisicos_extra = guarani::parametros()->get_valor('cur_agrega_folios_fisicos_extra_promo', $propuestas);
						}
					}
					$mensaje_error = acta::asignar_folio_fisico($datos_acta, $datos_acta['cant_alumnos'], $forma_asignacion_folio_fisico, $carga_manual_libro, $agrega_folios_fisicos_extra, $libro_tomo);
					if (!empty($mensaje_error)) {
						throw guarani::error(guarani_mensajes::get($mensaje_error));
					}
				}

				// Se actualiza la versión impresa del acta.
				guarani::act('act_actas')->actualizar_version_impresa($datos_acta['id_acta']);

				toba::db()->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				throw $e;
			}
		}
	}

	function resetear()
	{
		unset($this->s__hay_cambios_cabecera);
		unset($this->s__eliminado);
		parent::resetear();
	}

	//--------------------------------------------------------------------------
	//---- Auxiliares ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_escala_notas_comision_instancia($comision, $instancia)
	{
		$where = "sga_comisiones_instancias.instancia = $instancia";
		$instancia_comision = current(toba::consulta_php('co_comisiones')->get_instancias_comision($comision, $where));
		return $instancia_comision['escala_nota'];
	}

}