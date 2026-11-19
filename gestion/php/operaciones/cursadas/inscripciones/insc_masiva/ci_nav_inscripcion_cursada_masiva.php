<?php
class ci_nav_inscripcion_cursada_masiva extends ci_nav_inscripcion_cursada
{
	function ev_info()
	{
		return $this->controlador()->ev_info();
	}
	
	function get_seleccion($claves = null)
	{
		if (!is_null($claves)) {
			return parent::get_seleccion($claves);
		}
		$salida = array_merge(parent::get_seleccion($claves), $this->cn()->get_selecciones_descr());
		return $salida;
	}
	
	//--------------------------------------------------------------------------
	//---- PANTALLA: actividad
	//--------------------------------------------------------------------------

	function conf__actividad($pantalla)
	{
		if (!$this->cn()->hay_filtro_actividad()) {
			$this->cn()->resetear_atributos_parametros();
		}
		$this->cn()->deseleccionar_actividad();
	}
	
	function conf_evt__cuadro_actividades__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_actividades')->get_datos();
		if ($datos[$fila]['insc_cursada_por_prioridad'] == comunes::si) {
			$evento->anular();
		}
	}

	//--------------------------------------------------------------------------
	//---- filtro_actividades --------------------------------------------------
	//--------------------------------------------------------------------------

	function get_ubicaciones_propuesta($propuesta)
	{
		$filtro = 'sga_propuestas_oferta.propuesta = ' . toba::db()->quote($propuesta);
		return toba::consulta_php('co_propuestas')->get_ubicaciones_propuestas($filtro);
	}

	function conf__filtro_actividades(guarani_ei_filtro $filtro)
	{
		$filtro->set_sin_filtrado_inicial();
		$this->filtro_configurar('filtro_actividades', false);
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
		$filtro->columna('plan')->set_condicion_fija('es_igual_a');
		$filtro->columna('plan_version')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro_actividades__filtrar($datos)
	{
		// Se establecen en variables propias las selecciones relacionadas con la propuesta
		$this->cn()->set_selecciones_propuesta($datos);

		// Se resetean los filtros que tienen que ver con propuesta (ya fueron salvados
		// en la acción anterior)
		$columnas_a_ignorar = array('propuesta', 'plan', 'plan_version', 'ubicacion');
		foreach ($columnas_a_ignorar as $columna) {
			if ((isset($datos[$columna]))) {
				$this->dep('filtro_actividades')->columna($columna)->resetear_estado();
			}
		}

		parent::evt__filtro_actividades__filtrar($datos);
	}

	//--------------------------------------------------------------------------
	//---- cuadro_actividades --------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_actividades(toba_ei_cuadro $cuadro)
	{
		if ($this->cn()->hay_filtro_actividad()) {
			$cuadro->eliminar_columnas(array('regularizada'));
			$filtro = $this->get_filtro('filtro_actividades');
			$datos = $this->cn()->get_actividades_disponibles($filtro);
			$cuadro->set_datos($datos);
		}

		$msj_eof = $this->get_filtro_msg_eof('filtro_actividades', 'Actividad');
		if ($msj_eof) {
			$cuadro->set_eof_mensaje($msj_eof);
		}

		if ($cuadro->existe_evento('ver_inscripciones')) {
			$cuadro->eliminar_evento('ver_inscripciones');
		}
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: comisión
	//--------------------------------------------------------------------------

	function evt__comisiones__salida()
	{
		if ($this->wizard_avanza()) {
			if (!$this->usa_subcomisiones() || !$this->cn()->hay_subcomisiones()) {
				$this->set_pantalla('alumnos');
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: alumnos
	//--------------------------------------------------------------------------

	function evt__alumnos__entrada()
	{
		$this->cn()->resetear_alumnos_seleccionados();
	}

	function conf__alumnos(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->ev_info()->set_datos($this->get_seleccion());
		if ($pantalla->existe_evento('cambiar_tab__siguiente')) {
			$pantalla->eliminar_evento('cambiar_tab__siguiente');
		}
		
		$cant = $this->cn()->get_cant_alumnos_seleccionados();
		if ($cant > 0) {
			$alumnos = ($cant > 1) ? 'alumnos' : 'alumno';
			$confirm = "Se procesarán inscripciones de $cant $alumnos. ¿Desea continuar?";
			$pantalla->evento('procesar')->set_msg_confirmacion($confirm);
		}
	}

	//--------------------------------------------------------------------------
	//---- cuadro_alumnos_disp -------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_alumnos_disp(guarani_pers_ei_cuadro $cuadro)
	{
		$filtro = '';
		$datos = $this->cn()->get_alumnos_disponibles($filtro);
		$cuadro->set_datos($datos);
	}
	
	//--------------------------------------------------------------------------
	//---- PANTALLA: procesar
	//--------------------------------------------------------------------------
	
	function conf__procesar(toba_ei_pantalla $pantalla)
	{	
		if ($pantalla->existe_evento('cambiar_tab__anterior')) {
			$pantalla->eliminar_evento('cambiar_tab__anterior');	
		}
		
		if ($pantalla->existe_evento('cambiar_tab__siguiente')) {
			$pantalla->eliminar_evento('cambiar_tab__siguiente');	
		}
		
		$confirm = 'Esta acción abortará la ejecución del procesamiento restante. Lo procesado hasta el momento no será revertido. ¿Desea continuar?';
		$pantalla->evento('abortar')->set_msg_confirmacion($confirm);
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__procesar()
	{
		if (!$this->cn()->hay_alumnos_seleccionados()) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un alumno'));
			throw guarani::error($mje);
		}
		$this->cn()->ejecutar_proceso();
		$this->set_pantalla('procesar');
	}

	function evt__finalizar()
	{
		$this->limpiar_seleccion();
		$this->filtro_limpiar('filtro_actividades');
		$this->dep('ci_sel_alumnos')->resetear();
		$this->cn()->resetear();
		$this->set_pantalla('actividad');
	}

	//--------------------------------------------------------------------------
	//---- Procesamiento -------------------------------------------------------
	//--------------------------------------------------------------------------

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_resultado_proceso(pro_inscripcion_cursadas_masiva::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_inscriptos':
				$reporte = pro_inscripcion_cursadas_masiva::reporte_inscriptos;
				$operacion = '41000159';
				$titulo = 'Resultados de inscripciones múltiples - Inscriptos';
				break;
			case 'rep_no_inscriptos':
				$reporte = pro_inscripcion_cursadas_masiva::reporte_no_inscriptos;
				$operacion = '41000159';
				$titulo = 'Resultados de inscripciones múltiples - No inscriptos';
				break;
		}

		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
		} else {
			// Se obtiene el Proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}

		// Se almacenan los datos en la instancia de impresión para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array(
			'datos' => $datos['mensajes'],
			'reporte' => $reporte,
			'titulo' => $titulo
		));

		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se crea el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	//--------------------------------------------------------------------------
	//---- Eventos de ci_nav_procesos_bk_wizzard
	//--------------------------------------------------------------------------
	
	function evt__result_anteriores()
	{
		$this->controlador()->set_pantalla('resultados_seleccion');
	}
	
	function evt__abortar()
	{
		$this->controlador()->evt__abortar();
	}
	
	function modo_visualizacion()
	{
		return $this->controlador()->modo_visualizacion();
	}

}
?>