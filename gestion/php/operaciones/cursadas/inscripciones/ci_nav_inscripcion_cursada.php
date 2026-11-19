<?php
class ci_nav_inscripcion_cursada extends ci_navegacion_wizard
{
	// Componente Visual de Información
	protected $ev_info; 
	protected $s__datos_temp;
	// Flag para validación de Codigo de Comisión.
	protected $validar_codigo = false;
	// Mail
	protected $s__datos_mail;
	protected $envio_ok = false;
	protected $archivo = '';
	protected $s__subcomisiones;

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'documento_principal', 'legajo', 'propuesta_descr', 'plan_nombre', 'plan_version_nombre', 'ubicacion_nombre', 'descr'));
			$this->ev_info->set_columnas_fila(array('apellido_y_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('documento_principal'), 2);
			$this->ev_info->set_columnas_fila(array('legajo', 'propuesta_descr'), 3);
			$this->ev_info->set_columnas_fila(array('plan_nombre'), 4);
			$this->ev_info->set_columnas_fila(array('plan_version_nombre'), 5);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre'), 6);
			$this->ev_info->set_columnas_fila(array('descr'), 7);

			$this->ev_info->set_max_columnas_por_fila(4);

			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'documento_principal', 'legajo'), 0);

			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			$this->ev_info->set_etiqueta('propuesta_descr', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('plan_version_nombre', 'Versión');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('descr', 'Actividad');
		}
		return $this->ev_info;
	}

	function cargar_info($pantalla_info)
	{
		
	}

	//--------------------------------------------------------------------------
	//---- CONFIGURACION GENERAL -----------------------------------------------
	//--------------------------------------------------------------------------

	function conf()
	{
		if (!$this->usa_subcomisiones()) {
			$this->pantalla()->eliminar_tab('subcomisiones');
		}
		parent::conf();
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA ALUMNOS 
	//--------------------------------------------------------------------------

	function conf__alumno()
	{
		$this->cn()->deseleccionar_alumno();
		unset($this->s__seleccion['alumno']);
		$this->cn()->deseleccionar_actividad();
		$this->cn()->resetear_atributos_parametros();
	}

	//--------------------------------------------------------------------------
	//---- filtro_alumnos ------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro_alumnos(guarani_ei_filtro $filtro)
	{
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$filtro->set_ubicacion_obligatoria();
		}
		$filtro->set_sin_filtrado_inicial();
		$filtro->set_etiquetas($this->cn()->etiquetas('Alumnos'));
		$this->filtro_configurar('filtro_alumnos', false);
	}

	function evt__filtro_alumnos__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_alumnos', $datos);
	}

	function evt__filtro_alumnos__cancelar()
	{
		$this->filtro_limpiar('filtro_alumnos');
	}

	//--------------------------------------------------------------------------
	//---- cuadro_alumnos ------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_alumnos(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_alumnos');

		if ((isset($filtro) && $filtro != '1=1') || $this->dep('filtro_alumnos')->con_filtrado_inicial()) {
			$datos = $this->cn()->get_alumnos($filtro);
			$cuadro->set_datos($datos);
		}

		$msj_eof = $this->get_filtro_msg_eof('filtro_alumnos', 'Alumno');
		if ($msj_eof) {
			$cuadro->set_eof_mensaje($msj_eof);
		}
	}

	function evt__cuadro_alumnos__seleccion($seleccion)
	{
		$this->cn()->cargar_atributos_parametros($seleccion['propuesta'], $seleccion['plan']);
		
		$datos_alumno = current(guarani::co('co_alumnos')->get_datos_alumno($seleccion['alumno']));
		$seleccion['legajo'] = $datos_alumno['legajo'];
		$this->set_seleccion($seleccion);
	}

	//--------------------------------------------------------------------------
	//---- Validación de salida ------------------------------------------------
	//--------------------------------------------------------------------------
	
	function validar_alumno()
	{
		// Se valida que se haya seleccionado algún Alumno
		if ($this->falta_seleccionar('alumno')) {
			$mje = guarani_mensajes::get('800_no_seleccion_unica', array('un alumno'));
			throw guarani::error($mje);
		}
		$alumno = $this->get_seleccion_valor('alumno');
		$this->cn()->set_alumno($alumno);
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA ACTIVIDAD 
	//--------------------------------------------------------------------------

	function conf__actividad($pantalla)
	{
		if (!$this->cn()->es_fuera_de_termino() && !$this->cn()->hay_actividades_con_comisiones_vigentes()) {
			$pantalla->set_descripcion(guarani_mensajes::get('800CUR_sin_periodo_insc'), 'warning');
		}

		// Se limpian comisiones
		unset($this->datos_temp['comisiones']);
		$this->cn()->deseleccionar_comisiones();

		$this->controlador()->ev_info()->set_datos($this->get_seleccion());

		/* 
		 * Parametros para el popup
		 * @TODO: Ver como pasar esto dinámicamente
		 */
		$info = $this->get_seleccion();
		$info['titulo'] = $this->get_titulo();
		toba::memoria()->set_dato('info_alumno', $info);
	}

	//--------------------------------------------------------------------------
	//---- filtro_actividades --------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro_actividades(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_actividades', false);
		if (guarani::parametros()->get_valor('cur_recursar_regularizadas') == comunes::si) {
			$filtro->columna('regularizada')->set_condicion_fija('es_igual_a');
		} else {
			$filtro->eliminar_columnas(array('regularizada'));
		}
	}

	function evt__filtro_actividades__filtrar($datos)
	{
		$nombre = 'filtro_actividades';
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtros[$nombre] = $datos;

			$filtro_regularizada = '';

			if ((isset($datos['regularizada']))) {
				$filtro_regularizada = ($datos['regularizada']['valor'] == comunes::si) ? ' AND EXISTS ' : ' AND NOT EXISTS ';
				$alumno = toba::db()->quote($this->cn()->get_alumno_seleccionado());
				$filtro_regularizada .= "(	SELECT	1
										FROM	vw_regularidades_basica
										WHERE	vw_regularidades_basica.elemento = vw_actividades_plan.elemento AND
												vw_regularidades_basica.alumno = $alumno AND
												vw_regularidades_basica.resultado = '" . comunes::resultado_nota_aprobado . "' AND
												(vw_regularidades_basica.fecha_vigencia IS NULL OR	vw_regularidades_basica.fecha_vigencia >= CURRENT_DATE)
									)";
				$this->dep('filtro_actividades')->columna('regularizada')->resetear_estado();
			}

			$this->s__filtros[$nombre] = "{$this->dep($nombre)->get_sql_where()} $filtro_regularizada";
		} else {
			$this->filtro_limpiar($nombre);
			$this->s__filtros[$nombre] = '';
		}
	}

	function evt__filtro_actividades__cancelar()
	{
		$this->filtro_limpiar('filtro_actividades');
	}

	//--------------------------------------------------------------------------
	//---- cuadro_actividades --------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_actividades(toba_ei_cuadro $cuadro)
	{
		$cur_recursar_regularizadas = guarani::parametros()->get_valor('cur_recursar_regularizadas');

		$filtro = $this->get_filtro('filtro_actividades');
		if ((isset($filtro) && $filtro != '1=1') || $this->dep('filtro_actividades')->con_filtrado_inicial()) {
			$datos = $this->cn()->get_actividades_disponibles($filtro);
			if ($cur_recursar_regularizadas == comunes::si) {
				foreach ($datos as $clave => $valor) {
					$datos[$clave]['regularizada'] = ($valor['regularizada']) ? toba_recurso::imagen_toba('aplicar.png', true) : '';
				}
			} else {
				$cuadro->eliminar_columnas(array('regularizada'));
			}
			$cuadro->set_datos($datos);
		}

		$msj_eof = $this->get_filtro_msg_eof('filtro_actividades', 'Actividad');
		if ($msj_eof) {
			$cuadro->set_eof_mensaje($msj_eof);
		}
	}

	function evt__cuadro_actividades__seleccion($seleccion)
	{
		$this->set_seleccion($seleccion);
	}

	function conf_evt__cuadro_actividades__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_actividades', $fila));
	}

	//--------------------------------------------------------------------------
	//---- Validación de salida ------------------------------------------------
	//--------------------------------------------------------------------------
	
	function validar_actividad()
	{
		// Se valida que se haya seleccionado alguna Actividad
		if ($this->falta_seleccionar('id')) {
			$mje = guarani_mensajes::get('800_no_seleccion_unica', array('una actividad'));
			throw guarani::error($mje);
		}

		$this->cn()->set_actividad($this->get_seleccion_valor('id'));
	}

	function evt__actividad__salida()
	{
		if ($this->wizard_avanza()) {
			if ($this->cn()->get_cant_comisiones_disponibles() == 0) {
				toba::notificacion()->agregar('No hay comisiones disponibles para el alumno en la actividad seleccionada.');
				$this->set_pantalla('actividad');
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA COMISIONES
	//--------------------------------------------------------------------------

	function conf__comisiones($pantalla)
	{
		// Se limpian subcomisiones
		unset($this->datos_temp['subcomisiones']);
		$this->cn()->deseleccionar_subcomisiones();

		$this->controlador()->ev_info()->set_datos($this->get_seleccion());
		if (!$this->usa_subcomisiones()) {
			$this->dep('form_ml_comisiones')->desactivar_efs(array('tiene_subcomisiones'));
		}

		$msj_insc_cerrada = guarani_mensajes::get('800CUR_insc_cursada_com_cerrada');
		if ($this->cn()->maneja_alternativas()) {
			$pantalla->set_descripcion(guarani_mensajes::get('800CUR_max_comisiones_prioridad', array($this->cn()->get_alternativa_maxima())) . ' ' . $msj_insc_cerrada);
			// Se desactiva cuadro
			if ($pantalla->existe_dependencia('cuadro_comisiones')) {
				$pantalla->eliminar_dep('cuadro_comisiones');
			}
		} else {
			$pantalla->set_descripcion($msj_insc_cerrada);
			// Se desactiva form
			if ($pantalla->existe_dependencia('form_ml_comisiones')) {
				$pantalla->eliminar_dep('form_ml_comisiones');
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- cuadro_comisiones ---------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_comisiones(toba_ei_cuadro $cuadro)
	{
		$comisiones_disponibles = $this->cn()->get_comisiones_disponibles();
		foreach ($comisiones_disponibles as $key => $comision) {
			if ($this->usa_subcomisiones() && $comision['subcomisiones_cant'] > 0) {
				$comisiones_disponibles[$key]['tiene_subcomisiones'] = toba_recurso::imagen_proyecto('propuesta/aplicar.png', true);
			}
			$prioridad = $this->cn()->get_prioridad_comision($comision['comision']);
			if ($prioridad > 0) {
				$comisiones_disponibles[$key]['prioridad'] = $prioridad;
			}
		}
		$cuadro->set_datos($comisiones_disponibles);
		
		if (!$this->usa_subcomisiones()) {
			$cuadro->eliminar_columnas(array('tiene_subcomisiones'));
		}
	}

	function evt__cuadro_comisiones__seleccion($seleccion)
	{
		$seleccion['prioridad'] = 1;
		if ($seleccion['inscripcion_cerrada_desc'] == comunes::si_desc) {
			$seleccion['inscripcion_cerrada_codigo'] = $this->cn()->get_codigo_inscripcion_comision($seleccion['comision']);
			$this->validar_codigo = true;
		}
		$this->set_seleccion($seleccion);
	}

	function conf_evt__cuadro_comisiones__seleccion(toba_evento_usuario $evento, $fila)
	{
		$clave = $this->dep('cuadro_comisiones')->get_clave_fila_array($fila);
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_comisiones', $fila));
	}

	function conf_evt__cuadro_comisiones__ver_comision(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_comisiones')->get_datos();
		$datos_fila = $datos[$fila];

		if (toba::consulta_php('co_comisiones')->comision_tiene_subcomisiones($datos_fila['comision'])) {
			$evento->ocultar();
		} else {
			$evento->mostrar();
			$info = $this->get_seleccion();
			$info['comision'] = $datos_fila['comision'];
			$info['comision_nombre'] = $datos_fila['comision_nombre'];
			$info['titulo'] = 'Informacion de la Comisión';

			foreach ($info as $dato => $valor) {
				$evento->vinculo()->agregar_parametro(utf8_encode($dato), utf8_encode($valor));
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- formulario_comisiones -----------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_ml_comisiones(toba_ei_formulario_ml $form)
	{
		if (isset($this->datos_temp['comisiones'])) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp['comisiones']);
		} else {
			$comisiones_disponibles = $this->cn()->get_comisiones_disponibles();
			foreach ($comisiones_disponibles as $key => $comision) {
				if ($this->usa_subcomisiones() && $comision['subcomisiones_cant'] > 0) {
					$comisiones_disponibles[$key]['tiene_subcomisiones'] = toba_recurso::imagen_proyecto('propuesta/aplicar.png', true);
				}
				$prioridad = $this->cn()->get_prioridad_comision($comision['comision']);
				if ($prioridad > 0) {
					$comisiones_disponibles[$key]['prioridad'] = $prioridad;
					if ($comision['inscripcion_cerrada_desc'] == comunes::si_desc) {
						// Análogo a la prioridad. Para las comisiones de inscripción cerrada que ya habían
						// sido seleccionadas y se había completado su código de validación, se obtiene de los
						// datos del cn el código, para volver a mostrarlo cuando se vuelve a la pantalla.
						$codigo_validacion = $this->cn()->get_codigo_validacion_comision($comision['comision']);
						$comisiones_disponibles[$key]['codigo_validacion'] = $codigo_validacion;
					}
				}
			}
			$form->set_datos($comisiones_disponibles);
		}
	}

	function evt__form_ml_comisiones__modificacion($datos)
	{
		try {
			$this->cn()->set_comisiones($datos);
		} catch (guarani_error $e) {
			$this->datos_temp['comisiones'] = $datos;
			throw $e;
		}
	}

	function conf_evt__form_ml_comisiones__ver_docentes($evento, $fila)
	{
		$datos = $this->dep('form_ml_comisiones')->get_datos();
		$datos_fila = $datos[$fila];
		if ($this->usa_subcomisiones()) {
			$subcomisiones_cant = $datos_fila['subcomisiones_cant'];
			if ($subcomisiones_cant > 0) {
				$evento->anular();
			}
		}
		$info = array();
		$info['comision'] = $datos_fila['comision'];
		$info['comision_nombre'] = $datos_fila['comision_nombre'];
		$info['titulo'] = $this->get_titulo();
		foreach ($info as $dato => $valor) {
			$evento->vinculo()->agregar_parametro(utf8_encode($dato), utf8_encode($valor));
		}
	}

	function evt__form_ml_comisiones__ver_docentes($seleccion)
	{
		$info = $this->get_seleccion();
		toba::memoria()->set_dato('info_comision', $info);
	}

	function get_comisiones_alternativas()
	{
		$actividad = $this->get_seleccion_valor('id');
		$cant_comisiones_disponibles = $this->cn()->get_cant_comisiones_disponibles();
		$alternativas = guarani::co('co_comisiones')->get_comisiones_alternativas($actividad, $cant_comisiones_disponibles);
		return $alternativas;
	}

	//--------------------------------------------------------------------------
	//---- Validación de salida ------------------------------------------------
	//--------------------------------------------------------------------------
	
	function validar_comisiones()
	{
		// Si se selecciona mediante cuadro con radiobuttons
		if (!$this->cn()->maneja_alternativas()) {
			if ($this->falta_seleccionar('comision')) {
				$mje = guarani_mensajes::get('800_no_seleccion_unica', array('una Comisión'));
				throw guarani::error($mje);
			} 
			$this->cn()->set_comisiones(array($this->get_seleccion(array('comision', 'prioridad', 'periodo_lectivo', 'anio_academico', 'subcomisiones_cant', 'comision_nombre', 'periodo_lectivo_nombre', 'catedra_nombre', 'turno_nombre', 'ubicacion_nombre', 'inscripcion_cerrada_desc', 'inscripcion_cerrada_codigo', 'codigo_validacion'))));
		}
	}

	function evt__validar_codigo_inscripcion_cerrada($codigo)
	{
		// Se recupera el código de validación de la comisión de los datos de la selección previamente seteados
		// y de los parámetros el código ingresado por el usuario para la comisión con inscripción cerrada.
		$comision_codigo = current($this->get_seleccion('inscripcion_cerrada_codigo'));
		if (empty($codigo) || $comision_codigo != $codigo) {
			$comision_nombre = current($this->get_seleccion('comision_nombre'));
			$mje = guarani_mensajes::get('800CUR_insc_cursada_codigo_inv', array($comision_nombre));
			$this->eliminar_seleccion_previa();
			throw guarani::error($mje);
		}
		// Si es válido el código introducido lo setea también en la selección de la comisión
		$this->set_seleccion(array('codigo_validacion' => $codigo));
	}
	
	function evt__comisiones__salida()
	{
		if ($this->wizard_avanza()) {
			if (!$this->usa_subcomisiones() || !$this->cn()->hay_subcomisiones()) {
				$this->set_pantalla('finalizar');
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA SUBCOMISIONES 
	//--------------------------------------------------------------------------

	function evt__subcomisiones__entrada()
	{
		if (!$this->usa_subcomisiones() || !$this->cn()->hay_subcomisiones()) {
			$this->set_pantalla('comisiones');
		}
	}

	function conf__subcomisiones()
	{
		$this->controlador()->ev_info()->set_datos($this->get_seleccion());
		// Se realiza limpieza final
		$this->cn()->deseleccionar_final();
	}

	function usa_subcomisiones()
	{
		try {
			$valor = (guarani::parametros()->get_valor('cur_usa_subcomisiones') == comunes::si);
		} catch (guarani_error_params $e) {
			// Si todavía no se puede determinar el valor de verdad del parámetro
			// se retorna una opción por defecto
			$valor = false;
		}
		return $valor;
	}

	//--------------------------------------------------------------------------
	//---- cuadro_subcomisiones ------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_subcomisiones(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_subcomisiones_disponibles();
		$cuadro->set_datos($datos);
		$etiquetas = guarani_etiquetas::get_tabla('sga_subcomisiones');
		$msj_eof = guarani_mensajes::get('800_eof_cuadro', array($etiquetas->get_enf('plural')));
		if ($msj_eof) {
			$cuadro->set_eof_mensaje($msj_eof);
		}
	}

	function evt__cuadro_subcomisiones__seleccion($seleccion)
	{
		$this->s__subcomisiones = $seleccion;
	}

	function conf_evt__cuadro_subcomisiones__seleccion(toba_evento_usuario $evento, $fila)
	{
		$clave = $this->dep('cuadro_subcomisiones')->get_clave_fila_array($fila);
		$subcomision = $clave['subcomision'];
		if ($this->es_subcomision_elegida($subcomision)) {
			$evento->set_check_activo(true);
		} else {
			$evento->set_check_activo(false);
		}
	}

	function conf_evt__cuadro_subcomisiones__ver_docentes(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_subcomisiones')->get_datos();
		$datos_fila = $datos[$fila];

		$info = $this->get_seleccion();
		$info['comision'] = $datos_fila['comision'];
		$info['comision_nombre'] = $datos_fila['comision_nombre'];
		$info['subcomision'] = $datos_fila['subcomision'];
		$info['subcomision_nombre'] = $datos_fila['subcomision_nombre'];
		$info['titulo'] = 'Informacion de la Subcomision';
		foreach ($info as $dato => $valor) {
			$evento->vinculo()->agregar_parametro(utf8_encode($dato), utf8_encode($valor));
		}
	}

	function es_subcomision_elegida($subcomision)
	{
		if (isset($this->datos_temp['subcomisiones'])) {
			return in_array($subcomision, aplanar_matriz($this->datos_temp['subcomisiones'], 'subcomision'));
		} else {
			return $this->cn()->es_subcomision_elegida($subcomision);
		}
	}

	//--------------------------------------------------------------------------
	//---- Validación de salida ------------------------------------------------
	//--------------------------------------------------------------------------
	
	function validar_subcomisiones()
	{
		$this->cn()->set_subcomisiones($this->s__subcomisiones);
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA FINALIZAR 
	//--------------------------------------------------------------------------

	function conf__finalizar()
	{
		$this->controlador()->ev_info()->set_datos($this->get_seleccion());
		if ($this->cn()->get_estado() == 'CONFIRMADA') {
			$this->evento('confirmar')->set_en_botonera(false);
			$this->evento('cambiar_tab__anterior')->set_en_botonera(false);
			$this->preparar_datos_impresion();

			// Se genera el comprobante
			$this->generar_comprobante();

			// Se envía Mail con el comprobante
			$conf = guarani_datos_impresion::instancia()->get_conf();
			$ra = toba::consulta_php('co_propuestas')->get_ra_por_defecto($conf['propuesta']);
			toba::memoria()->set_dato('ra', $ra);
			toba::memoria()->set_dato('id_persona', $conf['persona']);
			toba::memoria()->set_dato('path_comp', $this->archivo);
			$envio_mail_autom = guarani::parametros()->get_valor('email_envio_automatico_comprobante');
			if ($envio_mail_autom == comunes::si) {
				$this->evento('mail')->ocultar();
				$this->enviar_mail($conf['persona']);
			}
		} else {
			$this->evento('nueva_inscripcion')->set_en_botonera(false);
			$this->evento('ver_comprobante')->set_en_botonera(false);
			$this->evento('mail')->ocultar();
			$msj = guarani_mensajes::get('800CUR_confirmar_alta_insc', array($this->get_seleccion_valor('codigo'), $this->get_seleccion_valor('nombre')), false);
			$this->evento('confirmar')->set_msg_confirmacion($msj);
		}

		if (!$this->usa_subcomisiones() || !$this->cn()->hay_subcomisiones()) {
			$this->pantalla()->eliminar_dep('cuadro_subcomisiones_elegidas');
		}

		if (!$this->cn()->maneja_alternativas()) {
			$this->dep('cuadro_comisiones_elegidas')->eliminar_columnas(array('prioridad'));
		}
	}

	//--------------------------------------------------------------------------
	//---- cuadro_comisiones_elegidas ------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_comisiones_elegidas(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_comisiones();
		if ($datos) {
			$cuadro->set_datos($datos);
		}
	}

	//--------------------------------------------------------------------------
	//---- cuadro_subcomisiones_elegidas ---------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_subcomisiones_elegidas(toba_ei_cuadro $cuadro)
	{
		$subcomisiones = $this->cn()->get_subcomisiones();
		$filtro = guarani_sql::armar_condicion_in($subcomisiones, 'sga_subcomisiones', 'subcomision');
		$datos = guarani::co('co_comisiones')->get_subcomisiones($filtro);
		$cuadro->set_datos($datos);
	}

	//--------------------------------------------------------------------------
	//---- DATOS COMPROBANTE ---------------------------------------------------
	//--------------------------------------------------------------------------

	function preparar_datos_impresion()
	{
		$gimp = guarani_datos_impresion::instancia();
		$gimp->limpiar_datos();

		$datos_impresion = $this->get_seleccion();
		$datos_impresion['fecha_insc'] = $this->cn()->get_fecha_inscripcion();
		$datos_impresion['estado_insc'] = $this->cn()->get_estado_inscripcion_visual();
		$datos_impresion['legajo'] = (!isset($datos_impresion['legajo'])) ? '  ' : $datos_impresion['legajo'];
		
		if ($this->cn()->maneja_alternativas()) {
			$gimp->set_titulo('Comprobante de Inscripciones a Cursada');
		} else {
			$gimp->set_titulo('Comprobante de Inscripción a Cursada');
		}
		$gimp->set_conf(array('propuesta' => $datos_impresion['propuesta'],
			'nro_transaccion' => $this->cn()->get_nro_transaccion(),
			'persona' => $datos_impresion['persona'],
			'alumno' => $datos_impresion['apellido_y_nombre'],
			'identificacion' => $datos_impresion['documento_principal'],
			'legajo' => $datos_impresion['legajo'],
			'propuesta_desc' => $datos_impresion['propuesta_descr'],
			'plan_desc' => '(' . $datos_impresion['plan_codigo'] . ') ' . $datos_impresion['plan_nombre'],
			'actividad' => $datos_impresion['descr'],
			'fecha' => $datos_impresion['fecha_insc']
				)
		);

		// Arreglo de datos
		$comisiones = $this->cn()->get_comisiones();

		$gimp->nueva_fila_datos('Alumno', $datos_impresion['apellido_y_nombre']);
		$gimp->nueva_fila_datos('Identificación', $datos_impresion['documento_principal']);
		$gimp->nueva_fila_datos('Legajo', $datos_impresion['legajo']);
		$gimp->nueva_fila_datos('Propuesta', $datos_impresion['propuesta_descr']);
		$gimp->nueva_fila_datos('Plan', '(' . $datos_impresion['plan_codigo'] . ') ' . $datos_impresion['plan_nombre']);
		$gimp->nueva_fila_datos('Actividad', $datos_impresion['descr']);
		$gimp->nueva_fila_datos('Fecha de Insc.', $datos_impresion['fecha_insc']);

		if ($this->cn()->maneja_alternativas() && guarani_arreglos::count($comisiones) > 1) {
			$tabla[0][] = array('nombre' => 'Prioridad', 'clave' => 'prioridad');
			$tabla[0][] = array('nombre' => 'Comisión', 'clave' => 'comision_nombre');
			$tabla[0][] = array('nombre' => 'Período Lectivo', 'clave' => 'periodo_lectivo_nombre');
			$tabla[0][] = array('nombre' => 'Cátedra', 'clave' => 'catedra_nombre');
			$tabla[0][] = array('nombre' => 'Turno', 'clave' => 'turno_nombre');
			$tabla[0][] = array('nombre' => 'Ubicación', 'clave' => 'ubicacion_nombre');
			if ($this->cn()->hay_subcomisiones()) {
				$tabla[0][] = array('nombre' => 'Subcomisiones', 'clave' => 'subcomisiones');
			}
			$tabla[0][] = array('nombre' => 'Inscripto a', 'clave' => 'instancias');

			$indx = 0;
			foreach ($comisiones as $comision) {
				if (!$comision['prioridad']) {
					continue;
				}
				// Si la comisión tiene más de una subcomisión
				if ($comision['subcomisiones_cant']) {
					$filtro = guarani_sql::armar_condicion_in($comision['subcomisiones'], 'sga_subcomisiones', 'subcomision');
					$subcos_datos = guarani::co('co_comisiones')->get_subcomisiones($filtro);
					$subcos = aplanar_matriz($subcos_datos, 'subcomision_nombre');
					$cont = 0;
					foreach ($subcos as $subco) {
						$indx++;
						if ($cont > 0) {
							foreach ($tabla[0] as $columna) {
								$tabla[$indx][$columna['clave']] = '';
							}
						} else {
							foreach ($tabla[0] as $columna) {
								$tabla[$indx][$columna['clave']] = $comision[$columna['clave']];
								if ($columna['clave'] == 'instancias') {
									$tabla[$indx]['instancias'] = $comision['instancias_a_inscribir'];
								}
							}
						}
						$tabla[$indx]['subcomisiones'] = $subco;
						$cont++;
					}
				} else {
					$indx++;
					foreach ($tabla[0] as $columna) {
						$tabla[$indx][$columna['clave']] = $comision[$columna['clave']];
						if ($columna['clave'] == 'instancias') {
							$tabla[$indx]['instancias'] = $comision['instancias_a_inscribir'];
						}
					}
				}
			}
			$gimp->set_datos(array('tabla' => $tabla));
		} else {
			$comision = $comisiones[0];
			$gimp->nueva_fila_datos('Comisión', $comision['comision_nombre']);
			if ($this->cn()->hay_subcomisiones() && $comision['subcomisiones_cant']) {
				$filtro = guarani_sql::armar_condicion_in($comision['subcomisiones'], 'sga_subcomisiones', 'subcomision');
				$subcos_datos = guarani::co('co_comisiones')->get_subcomisiones($filtro);
				$subcos_nombres = implode('/', aplanar_matriz($subcos_datos, 'subcomision_nombre'));
				$gimp->nueva_fila_datos('Subcomisiones', $subcos_nombres);
			}
			$gimp->nueva_fila_datos('Período lectivo', $comision['periodo_lectivo_nombre']);
			$gimp->nueva_fila_datos('Cátedra', $comision['catedra_nombre']);
			$gimp->nueva_fila_datos('Turno', $comision['turno_nombre']);
			$gimp->nueva_fila_datos('Ubicación', $comision['ubicacion_nombre']);
			$gimp->nueva_fila_datos('Inscripto a', implode('/', aplanar_matriz($comision['instancias_a_inscribir'], 'nombre')));
			$gimp->nueva_fila_datos('Estado', $datos_impresion['estado_insc']);
		}
		// Se almacenan los datos en memoria para recuperarlos luego para la impresión de comprobante
		$gimp->guardar_datos_imp();
	}

	//--------------------------------------------------------------------------
	//---- Validación de salida ------------------------------------------------
	//--------------------------------------------------------------------------

	function validar_finalizar()
	{
		
	}

	function evt__confirmar()
	{
		$this->cn()->guardar();
	}

	function evt__nueva_inscripcion()
	{
		$this->cn()->resetear();
		$this->limpiar_seleccion();
		$this->filtro_limpiar('filtro_actividades');
		$this->set_pantalla('alumno');
		$this->s__datos_mail = null;
	}

	function enviar_mail($persona)
	{
		$this->s__datos_mail = null;

		$mail = new guarani_mail($persona);
		$mail->set_html(true);

		$mail->agregar_adjunto("Comprobante.pdf", $this->archivo, 'base64', 'pdf');

		try {
			$this->s__datos_mail = $mail->get_datos_mail();
			$mail->ejecutar();
			$this->envio_ok = true;
			if (file_exists($this->archivo)) {
				unlink($this->archivo);
			}
		} catch (toba_error $e) {
			$this->envio_ok = false;
		}
	}

	function get_datos_mail()
	{
		return $this->s__datos_mail;
	}

	function envio_ok()
	{
		return $this->envio_ok;
	}

	function generar_comprobante()
	{
		// Se genera el Comprobante
		$pdf = new guarani_pdf(array($this->dep('comprobante')));
		$this->archivo = $pdf->generar(true);
	}

	//--------------------------------------------------------------------------
	//---- Selección -----------------------------------------------------------
	//--------------------------------------------------------------------------

	function eliminar_seleccion_previa($pantalla = null, $campo = null)
	{
		if ($pantalla == 'subcomisiones') {
			unset($this->s__subcomisiones);
		}
		parent::eliminar_seleccion_previa($pantalla, $campo);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->validar_codigo) {
			$validar = 'var insc_cerrada = true;';
			$this->validar_codigo = false;
			echo "
				{$this->objeto_js}.validar_codigo= function(proyecto, operacion, url, es_popup) {
					if (! es_popup || typeof(es_popup) == 'undefined') {
						var codigo = '';
						// Si la Comisión seleccionada tiene inscripción cerrada, debe pedir el Código de validación
						codigo = prompt('Código Comisión');
						this.set_evento(new evento_ei('validar_codigo_inscripcion_cerrada',true,'',[codigo]));
					}
				}
			
				$validar;
				if (insc_cerrada) {
					{$this->objeto_js}.validar_codigo();
				}
			";
		}
	}
}
?>
