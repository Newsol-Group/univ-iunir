<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class ci_nav_copiar_estructura_actividades extends ci_nav_procesos_bk_wizzard
{
	protected $indx_msg_eof = '800_eof_cuadro';
	
	// Filtros
	protected $s__filtro_comisiones;
	protected $s__filtro_actividades;
	protected $s__filtro_actividades_destino;
	
	// Datos Filtros
	protected $s__datos_filtro_actividades;
	protected $s__datos_filtro_actividades_destino;
	
	// Selección de actividades destino.
	protected $seleccion_actividades_filtrada;
	
	// Componentes Visuales de información
	protected $ev_info_origen;
	protected $ev_info_destino;
	
	protected $mje_confirma_copiado = '800CUR_copiar_estructura';

	/**
	 * Se resetean todas las variables de sesión de la clase. Utilizado en el evento volver al inicio de la operación.
	 */
	function resetear_variables()
	{
		$this->s__filtro_comisiones = '';
		$this->s__filtro_actividades = '';
		$this->s__filtro_actividades_destino = '';
		unset($this->s__datos_filtro_actividades);
		unset($this->s__datos_filtro_actividades_destino);
		// Elimina las selecciones realizadas en todas las pantallas del wizard.
		$this->eliminar_seleccion_completa();
	}

	/*
	 * Se maneja el avance de pantalla.
	 */
	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();

		if ($this->s__pantalla_actual == 'finalizar') {
			// Información de origen
			$origen = $this->get_seleccion('info_origen');
			$comisiones = $this->get_seleccion('comision');
			$destino = $this->get_seleccion('info_destino');
			$actividades_destino = $this->get_seleccion('actividades');

			// Parámetros para la la creación del proceso
			$parametros = array_merge($origen, $destino, $comisiones, $actividades_destino);

			// Se ejecuta el proceso background	
			$this->cn()->ejecutar_proceso($parametros);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ACTIVIDAD ORIGEN 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- formulario de filtro sobre comisiones y actividades --------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_filtro_comisiones_actividades(toba_ei_formulario $form)
	{
		$ra = $form->ef('responsable_academica')->get_etiqueta();
		$form->ef('propuesta')->set_descripcion(guarani_mensajes::get('800_filtro_combo_cascada', array($ra)));
		$prop = $form->ef('propuesta')->get_etiqueta();
		$form->ef('plan')->set_descripcion(guarani_mensajes::get('800_filtro_combo_cascada', array($prop)));
		if (isset($this->s__datos_filtro_actividades)) {
			$form->set_datos_defecto($this->s__datos_filtro_actividades);
		}
	}

	function evt__form_filtro_comisiones_actividades__filtrar($datos)
	{
		$this->s__filtro_comisiones = '';
		$this->s__filtro_actividades = "vw_actividades.disponible_para IN ('" . elemento::para_mesas_de_examen_y_comisiones . "', '" . elemento::solo_para_creacion_comisiones . "') AND
										vw_actividades.estado = '" . elemento::estado_activo . "'";

		if (isset($datos)) {
			// Se arma la condición where para los filtros sobre Comisiones y Actividades según 
			// los datos ingresados en el formulario y las tablas a las que los mismos afectan.
			$this->s__datos_filtro_actividades = $datos;
			$this->s__datos_filtro_actividades['periodo_nombre'] = toba::consulta_php('co_periodos_lectivos')->get_nombre_periodo_lectivo($this->s__datos_filtro_actividades['periodo_lectivo']);
			
			// Filtros sobre las Comisiones. El año académico no se considera porque filtra a los 
			// períodos lectivos que se muestran y ambos campos son obligatorios.
			$periodo_lectivo = toba::db()->quote($datos['periodo_lectivo']);
			$this->s__filtro_comisiones .= "sga_comisiones.periodo_lectivo = $periodo_lectivo";
			if (isset($datos['ubicacion'])) {
				$ubicacion = toba::db()->quote($datos['ubicacion']);
				$this->s__filtro_comisiones .= " AND sga_comisiones.ubicacion = $ubicacion";
			}
			
			// Filtros sobre las Actividades.
			if (isset($datos['responsable_academica'])) {
				$ra = toba::db()->quote($datos['responsable_academica']);
				$cond = "sga_elementos_ra.responsable_academica = $ra";
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
			if (isset($datos['propuesta'])) {
				$propuesta = toba::db()->quote($datos['propuesta']);
				$cond = "sga_propuestas.propuesta = $propuesta";
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
			if (isset($datos['plan'])) {
				$plan = toba::db()->quote($datos['plan']);
				$cond = "sga_planes.plan = $plan";
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
			if (isset($datos['anio_de_cursada'])) {
				$anio = toba::db()->quote($datos['anio_de_cursada']);
				$cond = "sga_elementos_plan.anio_de_cursada = $anio";
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
			if (isset($datos['actividad'])) {
				$actividad = guarani_texto::limpiar_acentos($datos['actividad']);
				$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
				$cond = '('.$filtro_condicion->get_sql('f_limpiar_acentos(vw_actividades.nombre::varchar)', $actividad);
				$cond .= ' OR '.$filtro_condicion->get_sql('f_limpiar_acentos(vw_actividades.codigo::varchar)', $actividad).')';
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
			if (isset($ubicacion)) {
				$cond = "sga_propuestas_oferta.ubicacion = $ubicacion";
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
		} else {
			unset($this->s__datos_filtro_actividades);
		}
	}

	function evt__form_filtro_comisiones_actividades__cancelar()
	{
		// Se limpian los datos y el filtro (condición where) del formulario con datos sobre Comisiones y Actividades.
		unset($this->s__datos_filtro_actividades);
		unset($this->s__filtro_comisiones);
		unset($this->s__filtro_actividades);
	}

	function get_ubicaciones()
	{
		$datos = guarani::co('co_ubicaciones')->get_ubicaciones();
		if (guarani_arreglos::count($datos) == 1) {
			return $datos;
		} else {
			$no_seleccion = array(array('valor' => apex_ef_no_seteado, 'descr' => '-- Seleccione --'));
			return array_merge($no_seleccion, $datos);
		}
	}

	//---- cuadro_actividades_origen ----------------------------------------------------

	function conf__cuadro_actividades_origen(toba_ei_cuadro $cuadro)
	{
		/**
		 * Si no están seteados los filtros (sobre comisiones o actividades), entonces es la carga inicial del cuadro 
		 * (el usuario no presionó filtrar o presionó limpiar). No se traen datos (no se ejecuta la consulta) y se pone 
		 * un mensaje en el cuadro que indica al usuario que debe filtrar para obtener datos.
		 */
		
		// Setea el mensaje eof del cuadro de actividades.
		// Recupera el índice del mensaje de eof del cuadro del ci de navegación.
		$indx_msj = $this->indx_msg_eof;
		if (!isset($this->s__filtro_comisiones) || !isset($this->s__filtro_actividades)) {
			$indx_msj .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj));
		} else {
			// El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$indx_msj = $this->indx_msg_eof . '_filtrado';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array('Actividades')));
			$actividades = toba::consulta_php('co_elementos')->get_actividades_propuestas_con_comisiones($this->s__filtro_comisiones, $this->s__filtro_actividades);
			// Se setea la imagen con el color correspondiente a si la actividad tiene o no comisiones asociadas.
			foreach ($actividades as $clave => $valor) {
				if ($actividades[$clave]['cantidad_comisiones'] > 0) {
					$actividades[$clave]['tiene_comisiones'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true);
				} else {
					$actividades[$clave]['tiene_comisiones'] = toba_recurso::imagen_proyecto('iconos_estados/rojo.png', true);
				}
			}
			$cuadro->set_datos($actividades);
		}
	}

	function evt__cuadro_actividades_origen__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
			// Datos a mostrar: Año, período, actividad y ubicacion
			$datos_per = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($this->s__datos_filtro_actividades['periodo_lectivo']);
			$datos_ubi = toba::consulta_php('co_ubicaciones')->get_ubicacion_info($this->s__datos_filtro_actividades['ubicacion']);

			// Se arma el array para setearlo en el ev de información
			$seleccion['periodo_lectivo'] = $this->s__datos_filtro_actividades['periodo_lectivo'];
			$seleccion['ubicacion'] = $this->s__datos_filtro_actividades['ubicacion'];
			$seleccion['periodo_lectivo_nombre'] = $datos_per['periodo_nombre'];
			$seleccion['ubicacion_nombre'] = $datos_ubi['ubicacion_nombre'];
			$seleccion['actividad_descr'] = ' (' . $seleccion['codigo'] . ') ' . $seleccion['nombre'];
			$seleccion['anio_academico'] = $this->s__datos_filtro_actividades['anio_academico'];

			// Se establecen los datos
			$this->set_seleccion(array('info_origen' => $seleccion));
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_actividades_origen__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_actividades_origen', $fila));
	}

	function validar_actividad_origen()
	{
		// Se valida que se haya seleccionado alguna Responsable Académica
		if ($this->falta_seleccionar('info_origen')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Actividad'));
			throw guarani::error($mje);
		} else {
			$datos = $this->get_seleccion_valor('info_origen');
			$cant_com_activ = $datos['cantidad_comisiones'];

			// Se valida que la Actividad seleccionada tenga comisiones para el Período, Ubicación y Años seleccionados.
			if ($cant_com_activ == 0) {
				$mje_sin_comisiones = guarani_mensajes::get('800CUR_sin_comisiones');
				throw guarani::error($mje_sin_comisiones);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- COMISIONES 
	//-----------------------------------------------------------------------------------

	function conf__comisiones(toba_ei_pantalla $pantalla)
	{
		// Datos para el ev de información
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$this->ev_info_origen()->set_datos($datos_origen);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_comisiones ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_comisiones(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array('Comisiones para la Actividad y Ubicación seleccionadas')));
		$datos = $this->get_seleccion_valor('info_origen');
		$elemento = $datos['elemento'];
		$ubicacion = $datos['ubicacion'];
		$periodo_lectivo = $datos['periodo_lectivo'];
		$comisiones = toba::consulta_php('co_comisiones')->get_listado("sga_comisiones.elemento = $elemento AND sga_comisiones.periodo_lectivo = $periodo_lectivo AND sga_comisiones.ubicacion = $ubicacion");
		$cuadro->set_datos($comisiones);
	}

	function evt__cuadro_comisiones__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_comisiones__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_comisiones', $fila));
	}

	function conf_evt__cuadro_comisiones__ver_detalle_comision($evento, $fila)
	{
		$datos = $this->dep('cuadro_comisiones')->get_datos(true);
		$datos_fila = $datos[$fila];

		$info['comision'] = $datos_fila['comision'];
		$info['comision_nombre'] = $datos_fila['nombre'];

		$datos_origen = $this->get_seleccion_valor('info_origen');
		$info['ubicacion_nombre'] = $datos_origen['ubicacion_nombre'];
		$info['descr'] = $datos_origen['actividad_descr'];
		$info['anio_academico'] = $datos_origen['anio_academico'];
		$info['periodo_lectivo_nombre'] = $datos_origen['periodo_lectivo_nombre'];

		foreach ($info as $dato => $valor) {
			$evento->vinculo()->agregar_parametro(utf8_encode($dato), utf8_encode($valor));
		}
	}

	function validar_comisiones()
	{
		// Se valida que se haya seleccionado alguna Responsable Académica
		if ($this->falta_seleccionar('comision')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('al menos una Comisión'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- DATOS COMISIONES 
	//-----------------------------------------------------------------------------------

	function conf__datos_comisiones(toba_ei_pantalla $pantalla)
	{
		// Datos para el ev de información
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$this->ev_info_origen()->set_datos($datos_origen);
	}

	//-----------------------------------------------------------------------------------
	//---- form_datos_a_copiar ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_datos_a_copiar(toba_ei_formulario $form)
	{
		$comisiones = $this->get_seleccion_valor('comision');

		// Verifica si alguna de las Comisiones seleccionadas tiene alguna Subcomisión asociada para activar el checkbox.
		if (!$this->comisiones_con_subcomisiones($comisiones)) {
			$form->ef('copiar_subcomisiones')->set_solo_lectura();
		}
		// Verifica si alguna de las Comisiones seleccionadas tiene algún Docente asociado para activar el checkbox.
		if (!$this->comisiones_con_docentes($comisiones)) {
			$form->ef('docentes_comisiones')->set_solo_lectura();
		}
		// Verifica si alguna de las Comisiones seleccionadas tiene alguna Banda Horaria asociada para activar el checkbox.
		if (!$this->comisiones_con_bandas_horarias($comisiones)) {
			$form->ef('bh_comisiones')->set_solo_lectura();
		}
		// Verifica si alguna de las Comisiones seleccionadas tiene alguna División asociada para activar el checkbox.
		if (!$this->comisiones_con_divisiones($comisiones)) {
			$form->ef('copiar_division')->set_solo_lectura();
		}
		if (($this->existe_seleccion('info_destino'))) {
			$datos = $this->get_seleccion_valor('info_destino');
			$form->set_datos($datos);
		}
	}

	function evt__form_datos_a_copiar__modificacion($datos)
	{
		$this->set_seleccion(array('info_destino' => $datos));
	}

	function comisiones_con_subcomisiones($comisiones)
	{
		$condicion_in = guarani_sql::armar_condicion_in($comisiones, 'sga_comisiones', 'comision', 'comision');
		$subcomisiones = toba::consulta_php('co_comisiones')->get_subcomisiones($condicion_in);
		return !empty($subcomisiones);
	}

	function comisiones_con_docentes($comisiones)
	{
		$condicion_in = guarani_sql::armar_condicion_in($comisiones, 'sga_docentes_comision', 'comision', 'comision');
		$docentes = toba::consulta_php('co_comisiones')->get_docentes_comision(null, $condicion_in);
		return !empty($docentes);
	}

	function comisiones_con_bandas_horarias($comisiones)
	{
		$bandas_horarias = toba::consulta_php('co_bandas_horarias')->get_listado_asignaciones_comision($comisiones);
		return !empty($bandas_horarias);
	}

	function comisiones_con_divisiones($comisiones)
	{
		$condicion_in = guarani_sql::armar_condicion_in($comisiones, 'sga_comisiones', 'comision', 'comision');
		$divisiones = toba::consulta_php('co_comisiones')->get_comisiones_con_divisiones($condicion_in);
		return !empty($divisiones);
	}

	//-----------------------------------------------------------------------------------
	//---- ACTIVIDADES DESTINO 
	//-----------------------------------------------------------------------------------

	function conf__actividades_destino(toba_ei_pantalla $pantalla)
	{
		// Datos para el ev de información
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$this->ev_info_origen()->set_datos($datos_origen);
		$periodo_nombre = $datos_origen['periodo_lectivo_nombre'];
		$ubicacion_nombre = $datos_origen['ubicacion_nombre'];
		$pantalla->set_descripcion("Solo se podrá copiar la estructura a aquellas actividades que no tengan comisiones generadas para el Período Lectivo: $periodo_nombre y Ubicación: $ubicacion_nombre, y cuyas propuestas se ofertan en dicha Ubicación");
		// Si se filtró por propuestas se limpia la selección de las mismas.
		if ($this->seleccion_actividades_filtrada) {
			$this->eliminar_seleccion_previa();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_actividades -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_actividades_destino(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_actividades_destino)) {
			$filtro->set_datos($this->s__datos_filtro_actividades_destino);
		} else {
			$filtro->colapsar();
		}
	}

	function evt__filtro_actividades_destino__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_actividades_destino = $datos;
			$this->s__filtro_actividades_destino = $this->dep('filtro_actividades_destino')->get_sql_where();
			//Se setea el flag para eliminar la selección del cuadro al filtrar.
			$this->seleccion_actividades_filtrada = true;
		} else {
			unset($this->s__datos_filtro_actividades_destino);
			$this->s__filtro_actividades_destino = '';
		}
	}

	function evt__filtro_actividades_destino__cancelar()
	{
		unset($this->s__datos_filtro_actividades_destino);
		$this->s__filtro_actividades_destino = '';
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_actividades_destino ---------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_actividades_destino(toba_ei_cuadro $cuadro)
	{
		// Se excluyen las actividades que tengan comisiones en el período lectivo y ubicación seleccionados.
		$info_origen = $this->get_seleccion_valor('info_origen');
		$periodo_lectivo = $info_origen['periodo_lectivo'];
		$ubicacion = $info_origen['ubicacion'];
		$filtro = "vw_actividades_plan.elemento NOT IN (SELECT	sga_comisiones.elemento
														FROM	sga_comisiones
														WHERE	sga_comisiones.periodo_lectivo = $periodo_lectivo AND
																sga_comisiones.ubicacion = $ubicacion)
				";

		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro_actividades_destino) && !empty($this->s__filtro_actividades_destino)) {
			$indx_msg .= '_filtrado';
			$filtro .= ' AND ' . $this->s__filtro_actividades_destino;
		} else {
			$indx_msg .= '_sinfiltro';
		}
		$msj = guarani_mensajes::get($indx_msg, array('Actividades Destino'));
		$cuadro->set_eof_mensaje($msj);

		$datos = toba::consulta_php('co_elementos')->get_actividades_plan_en_ubicacion($ubicacion, $filtro);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_actividades_destino__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion(array('actividades' => $seleccion));
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_actividades_destino__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_actividades_destino')->get_datos();
		$elemento = $datos[$fila]['elemento'];
		$evento->set_check_activo($this->es_clave_seleccionada($elemento, 'actividades'));
	}

	function validar_actividades_destino()
	{
		// Se valida que se haya seleccionado alguna Actividad Destino.
		if ($this->falta_seleccionar('actividades')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('al menos una Actividad Destino'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- FINALIZAR 
	//-----------------------------------------------------------------------------------

	function conf__finalizar(toba_ei_pantalla $pantalla)
	{
		// Datos para los evs de información
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$datos_destino = $this->get_seleccion_valor('actividades');
		$nombres_activ = aplanar_matriz($datos_destino, 'actividad_descr');
		$this->ev_info_origen()->set_datos($datos_origen);
		$this->ev_info_destino()->set_datos(array('actividad_descr' => $nombres_activ));

		//Mensaje de confirmación y etiqueta de boton
		$actividad = $datos_origen['actividad_descr'];
		$año = $datos_origen['anio_academico'];
		$per_lect = $datos_origen['periodo_lectivo_nombre'];
		$mensaje = guarani_mensajes::get($this->mje_confirma_copiado, array($actividad, $año, $per_lect));
		$this->evento('cambiar_tab__siguiente')->set_msg_confirmacion($mensaje);
		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Copiar Estructura');
		$this->evento('cambiar_tab__siguiente')->set_imagen('/generar.png');
	}

	//-----------------------------------------------------------------------------------
	//---- RESULTADOS 
	//-----------------------------------------------------------------------------------

	function conf__resultados(toba_ei_pantalla $pantalla)
	{
		// Datos para los evs de información
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$datos_destino = $this->get_seleccion_valor('actividades');
		$nombres_activ = aplanar_matriz($datos_destino, 'actividad_descr');
		$this->ev_info_origen()->set_datos($datos_origen);
		$this->ev_info_destino()->set_datos(array('actividad_descr' => $nombres_activ));

		// Se oculta el botón Anterior
		$this->evento('cambiar_tab__anterior')->ocultar();
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__finalizar()
	{
		$this->limpiar_memoria();
		$this->set_pantalla('actividad_origen');
	}

	//-----------------------------------------------------------------------------------
	//---- form_totales -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_totales(form_totales $form)
	{
		$form->set_solo_lectura();
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares el manejo del avance del proceso ----------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$resultados = $this->cn()->get_resultado_proceso(pro_copiar_estructura_actividades::reporte_resultados);

		if (!empty($resultados['mensajes'])) {
			$respuesta->set(current($resultados['mensajes']));
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_comisiones':
				$reporte = pro_copiar_estructura_actividades::reporte_comisiones;
				$operacion = '38000079';
				$parametro_popup = 'comisiones';
				break;
			case 'rep_actividades_con_comisiones':
				$reporte = pro_copiar_estructura_actividades::reporte_actividades_con_comisiones;
				$operacion = '38000101';
				$parametro_popup = 'actividades_con_com';
				break;
		}
		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
		} else {
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}

		$origen = $this->get_seleccion_valor('info_origen');
		$datos_conf = array('anio' => $origen['anio_academico'],
			'periodo_lectivo' => $origen['periodo_lectivo_nombre']);

		guarani_datos_impresion::instancia()->set_datos(array($parametro_popup => $datos['mensajes']));
		guarani_datos_impresion::instancia()->set_conf($datos_conf);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares para la visualización de las selecciones previas ------------------
	//-----------------------------------------------------------------------------------

	function ev_info_origen()
	{
		if (!isset($this->ev_info_origen)) {
			$this->ev_info_origen = guarani::ev_info_datos();
			$this->ev_info_origen->set_controlador($this);
			$this->ev_info_origen->set_titulo('Origen');
			$this->ev_info_origen->set_td_etiqueta_width(120);
			$this->ev_info_origen->set_columnas_a_mostrar(array('anio_academico', 'periodo_lectivo_nombre', 'ubicacion_nombre', 'actividad_descr'));
			$this->ev_info_origen->set_columnas_fila(array('anio_academico', 'periodo_lectivo_nombre'), 1);
			$this->ev_info_origen->set_columnas_fila(array('actividad_descr', 'ubicacion_nombre'), 2);
			$this->ev_info_origen->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info_origen->set_etiqueta('periodo_lectivo_nombre', 'Período Lectivo');
			$this->ev_info_origen->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info_origen->set_etiqueta('actividad_descr', 'Actividad');
		}
		return $this->ev_info_origen;
	}

	function ev_info_destino()
	{
		if (!isset($this->ev_info_destino)) {
			$this->ev_info_destino = guarani::ev_info_datos();
			$this->ev_info_destino->set_controlador($this);
			$this->ev_info_destino->set_titulo('Destino');
			$this->ev_info_destino->set_td_etiqueta_width(250);
			$this->ev_info_destino->set_max_chars_por_columna(250);
			$this->ev_info_destino->set_columnas_a_mostrar(array('actividad_descr'));
			$this->ev_info_destino->set_columnas_fila(array('actividad_descr'), 1);
			$this->ev_info_destino->set_etiqueta('actividad_descr', 'Actividades a las que se copia la estructura');
		}
		return $this->ev_info_destino;
	}

}
?>