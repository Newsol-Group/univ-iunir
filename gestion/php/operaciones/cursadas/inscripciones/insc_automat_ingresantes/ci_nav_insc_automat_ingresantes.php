<?php
class ci_nav_insc_automat_ingresantes extends ci_nav_procesos_bk_wizzard
{
	//Filtros
	protected $s__filtro_propuestas;
	
	//Datos Filtros
	protected $s__datos_filtro_propuestas;
	
	// Componente Visual de Información
	protected $ev_info;
	
	// Mensajes
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $msj_confirma_inscripcion = '800MAT_confirmar_inscripcion_auto_ingresantes';
	
	// Temporales
	protected $datos_per_ubicacion_temp;
	protected $datos_comisiones_default_temp;
	protected $datos_criterios_comisiones_temp;

	function ini()
	{
		// Se maneja la navegación hasta la operación de Administrar Criterios de Prioridad de Ingresantes.
		$volvio = toba::memoria()->get_parametro('retorno');
		if ($volvio) {
			$this->set_pantalla('parametros');
		}
	}

	/*
	 * Se maneja el avance de pantalla.
	 */
	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();

		// Si clickeó el boton 'Inscribir Alumnos' se manda a ejecutar el proceso en background
		if ($this->s__pantalla_actual == 'parametros') {

			// Parámetros a pasar al proceso
			$parametros = $this->get_seleccion();
			$parametros['operacion'] = guarani::get_id_operacion();

			// Se ejecuta el proceso background	
			$this->cn()->ejecutar_proceso($parametros);
		}
	}

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$resultados = $this->cn()->get_resultado_proceso(pro_inscripcion_automatica_ingresantes::reporte_resultados);

		if (!empty($resultados['mensajes'])) {
			$respuesta->set(current($resultados['mensajes']));
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_insc_aceptadas':
				$reporte = pro_inscripcion_automatica_ingresantes::reporte_insc_aceptadas;
				guarani_datos_impresion::instancia()->set_titulo('Reporte de Inscripciones Aceptadas');
				break;
			case 'rep_insc_pendientes':
				$reporte = pro_inscripcion_automatica_ingresantes::reporte_insc_pendientes;
				guarani_datos_impresion::instancia()->set_titulo('Reporte de Inscripciones Pendientes');
				break;
			case 'rep_insc_rechazadas':
				$reporte = pro_inscripcion_automatica_ingresantes::reporte_insc_rechazadas;
				guarani_datos_impresion::instancia()->set_titulo('Reporte de Inscripciones Rechazadas');
				break;
		}

		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
		} else {
			// Se obtiene el proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}

		// Se almacenan los datos en la instancia de impresión para ser consumidos luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos($datos['mensajes']);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se crea el vínculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '37000085', null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_titulo('Filtros para la Selección de Alumnos');
			$this->ev_info->set_max_datos_por_columna(10);
			$this->ev_info->set_max_chars_por_columna(112);
			$this->ev_info->set_columnas_a_mostrar(array('periodo_inscripcion_nombre', 'ubicacion_nombre', 'propuesta_nombre', 'criterios_alumnos_nombres', 'respeta_turno_cursada', 'respeta_letra_apellido'));

			$this->ev_info->set_etiqueta('periodo_inscripcion_nombre', 'Período de Inscripción');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuestas');
			$this->ev_info->set_etiqueta('criterios_alumnos_nombres', 'Prioridad');
			$this->ev_info->set_etiqueta('respeta_turno_cursada', 'Respetar Turno de Cursada');
			$this->ev_info->set_etiqueta('respeta_letra_apellido', 'Respetar Letra del Apellido');
		}
		return $this->ev_info;
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA PROPUESTAS 
	//--------------------------------------------------------------------------
	
	function conf__propuestas(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	//--------------------------------------------------------------------------
	//---- form_per_ubic -------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_per_ubic(toba_ei_formulario $form)
	{
		if (isset($this->datos_per_ubicacion_temp)) {
			$this->set_hubo_cambios();
			$form->set_datos($this->datos_per_ubicacion_temp);
		} else {
			$form->set_datos($this->get_seleccion());
		}
	}

	function evt__form_per_ubic__modificacion($datos)
	{
		$this->datos_per_ubicacion_temp = $datos;

		// Datos a mostrar: período y ubicacion
		$datos_per = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_periodo_inscripcion($datos['periodo_inscripcion']);
		$datos_ubi = toba::consulta_php('co_ubicaciones')->get_ubicacion_info($datos['ubicacion']);

		// Array para el ev de información
		$datos['periodo_inscripcion_nombre'] = $datos_per['nombre'];
		$datos['periodo_inscripcion_anio_academico'] = $datos_per['anio_academico'];
		$datos['ubicacion_nombre'] = $datos_ubi['ubicacion_nombre'];

		// Se establecen los datos
		$this->set_seleccion($datos);
	}

	//--------------------------------------------------------------------------
	//---- filtro_propuestas ---------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro_propuestas(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_propuestas)) {
			$filtro->set_datos($this->s__datos_filtro_propuestas);
		}
	}

	function evt__filtro_propuestas__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_propuestas = $datos;
			$this->s__filtro_propuestas = $this->dep('filtro_propuestas')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_propuestas);
			$this->s__filtro_propuestas = '';
		}
	}

	function evt__filtro_propuestas__cancelar()
	{
		unset($this->s__datos_filtro_propuestas);
		$this->s__filtro_propuestas = '';
	}

	//--------------------------------------------------------------------------
	//---- cuadro_propuestas ---------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_propuestas(toba_ei_cuadro $cuadro)
	{
		$indx_msj = $this->indx_msg_eof;
		if (isset($this->s__filtro_propuestas) && !empty($this->s__filtro_propuestas)) {
			$indx_msj = $this->indx_msg_eof . '_filtrado';
			$datos = $this->cn()->get_listado($this->s__filtro_propuestas);
		} else {
			$datos = $this->cn()->get_listado();
		}

		$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
		$etiqueta = $etiqueta->get_enf('plural');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_propuestas__seleccion($seleccion)
	{
		// La selección trae tanto los ids como los nombres de las propuestas seleccionadas, 
		// para mostrar estos datos en el Ev de información de selecciones previas.
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		}
		
		$this->cn()->cargar_atributos_parametros($seleccion);
	}

	function conf_evt__cuadro_propuestas__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_propuestas', $fila));
	}

	//--------------------------------------------------------------------------
	//---- Validación de salida ------------------------------------------------
	//--------------------------------------------------------------------------

	function validar_propuestas()
	{
		// Se valida que se haya seleccionado alguna propuesta
		if ($this->falta_seleccionar('propuesta')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una propuesta'));
			throw guarani::error($mje);
		}
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA COMISIONES  
	//--------------------------------------------------------------------------

	function conf__comisiones($pantalla)
	{
		// Se resetean los valores de las matrices que cargan el ML de comisiones 
		// default y los datos pasados al proceso para no enviarlos al Ev.
		$seleccion = $this->get_seleccion();
		unset($seleccion['comisiones_default']);
		unset($seleccion['comisiones_default_seleccionadas']);
		$this->ev_info()->set_datos($seleccion);
	}

	//--------------------------------------------------------------------------
	//---- formulario_comisiones -----------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_ml_comisiones(toba_ei_formulario_ml $form)
	{
		if (isset($this->datos_comisiones_default_temp)) {
			$this->set_hubo_cambios();
			$form->set_datos($this->datos_comisiones_default_temp);
		} else {
			// Caso en que se vuelve de una pantalla siguiente y ya existe selección.
			$comisiones_seleccionadas = $this->get_seleccion_valor('comisiones_default_seleccionadas');
			if (!empty($comisiones_seleccionadas)) {
				$form->set_datos($comisiones_seleccionadas);
			} else {
				// Primera configuración del form.
				$propuestas = $this->get_seleccion('propuesta');
				$actividades = guarani::co('co_elementos')->get_actividades_propuestas_primer_anio($propuestas['propuesta']);
				$form->set_datos($actividades);
			}
		}
	}

	function evt__form_ml_comisiones__modificacion($datos)
	{
		// Se arma el arreglo con los datos de comisiones default para pasar al proceso de inscripción.
		$comisiones_default = array();
		foreach ($datos as $dato) {
			if (!is_null($dato['comision'])) {
				$actividad = $dato['actividad'];
				$plan = $dato['plan'];
				$comision = $dato['comision'];
				$comisiones_default[$actividad][$plan] = $comision;
			}
		}
		$this->set_seleccion(array('comisiones_default' => $comisiones_default));

		// Manejo de la modificación del formulario y selección del ci wizard.
		$this->datos_comisiones_default_temp = $datos;
		
		// Se establecen los datos
		$this->set_seleccion(array('comisiones_default_seleccionadas' => $datos));
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA PARÁMETROS 
	//--------------------------------------------------------------------------

	function conf__parametros(toba_ei_pantalla $pantalla)
	{
		// Datos para el ev de información. Se resetean los valores de las matrices 
		// que cargan el ML de comisiones default y los datos pasados al proceso para 
		// no enviarlos al Ev.
		$datos_seleccion = $this->get_seleccion();
		unset($datos_seleccion['comisiones_default']);
		unset($datos_seleccion['comisiones_default_seleccionadas']);
		$this->ev_info()->set_datos($datos_seleccion);
		
		// Se ocultan las columnas del ev correspondientes a la pantalla actual.
		$this->ev_info()->ocultar_columnas(array('criterios_alumnos_nombres', 'respeta_turno_cursada', 'respeta_letra_apellido'));

		// Mensaje de confirmación y etiqueta de botón
		$mensaje = guarani_mensajes::get($this->msj_confirma_inscripcion, array($datos_seleccion['periodo_inscripcion_anio_academico']));
		$this->evento('cambiar_tab__siguiente')->set_msg_confirmacion($mensaje);
		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Inscribir Alumnos');
		$this->evento('cambiar_tab__siguiente')->set_imagen('/aplicar.png');

		// Se setean los parámetros para el evento que permite navegar a la operación de Administrar Criterios.
		$this->dep('cuadro_criterios_alumnos')->evento('modificar_criterios_alumnos')->vinculo()->set_parametros(array('operacion_a_volver' => guarani::get_id_operacion_toba(), 'celda_a_restaurar' => toba::memoria()->get_celda_memoria_actual_id()));
		$this->dep('cuadro_criterios_alumnos')->evento('modificar_criterios_alumnos')->vinculo()->set_celda_memoria('celda_criterios');
	}

	//--------------------------------------------------------------------------
	//---- cuadro criterios alumnos --------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_criterios_alumnos(toba_ei_cuadro $cuadro)
	{
		$indx_msj = $this->indx_msg_eof;
		// Se recupera la etiqueta enfatizada y plural del concepto asociado a la tabla de Criterios de Prioridad de Ingresantes.
		$etiqueta = guarani_etiquetas::get_tabla('sga_criterios_prioridad_ing');
		$etiqueta = $etiqueta->get_enf('plural');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));
		$datos = toba::consulta_php('co_criterios_prioridad_ingresantes')->get_listado_criterios_activos_orden();
		if (!empty($datos)) {
			$cuadro->set_datos($datos);
			$criterios_nombres = guarani_arreglos::aplanar_matriz_sin_nulos($datos, 'nombre');
			$this->set_seleccion(array('criterios_alumnos_nombres' => $criterios_nombres));
		}
	}

	//--------------------------------------------------------------------------
	//---- form criterios comisiones -------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_criterios_comisiones(toba_ei_formulario $form)
	{
		if (isset($this->datos_criterios_comisiones_temp)) {
			$this->set_hubo_cambios();
			$form->set_datos($this->datos_criterios_comisiones_temp);
		} else {
			$form->set_datos($this->get_seleccion());
		}
	}

	function evt__form_criterios_comisiones__modificacion($datos)
	{
		$this->datos_criterios_comisiones_temp = $datos;

		// Array para setearlo en el ev de información
		if ($datos['turno']) {
			$datos['respeta_turno_cursada'] = comunes::si_desc;
		} else {
			$datos['respeta_turno_cursada'] = comunes::no_desc;
		}
		if ($datos['apellido']) {
			$datos['respeta_letra_apellido'] = comunes::si_desc;
		} else {
			$datos['respeta_letra_apellido'] = comunes::no_desc;
		}

		// Se establecen los datos
		$this->set_seleccion($datos);
	}

	//--------------------------------------------------------------------------
	//---- RESULTADOS 
	//--------------------------------------------------------------------------

	function conf__finalizar(toba_ei_pantalla $pantalla)
	{
		// Datos para el ev de información
		$datos_seleccion = $this->get_seleccion();
		unset($datos_seleccion['comisiones_default']);
		unset($datos_seleccion['comisiones_default_seleccionadas']);
		$this->ev_info()->set_datos($datos_seleccion);
		// Se oculta el botón Anterior
		$this->evento('cambiar_tab__anterior')->ocultar();
	}

	//--------------------------------------------------------------------------
	//---- form_totales --------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_totales(form_totales $form)
	{
		$form->set_solo_lectura();
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function evt__finalizar()
	{
		// Luego de mostrar los resultados, al finalizar se vuelve al comienzo de la operación.
		$this->limpiar_memoria();
		$this->set_pantalla('propuestas');
	}

	function get_nombre_comision($comision)
	{
		return toba::consulta_php('co_comisiones')->get_nombre_comision($comision);
	}

	//--------------------------------------------------------------------------
	//---- Auxiliares ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_periodos_inscripcion_por_anio_academico($anio_academico)
	{
		$filtro = "	sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN ('" . periodo_de_inscripcion::tipo_inscripcion . "', '" . periodo_de_inscripcion::tipo_todos . "') AND
					sga_periodos_inscripcion_propuesta.anio_academico = " . toba::db()->quote($anio_academico);
		return toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_propuesta_perfil_de_datos($filtro);
	}

}
?>