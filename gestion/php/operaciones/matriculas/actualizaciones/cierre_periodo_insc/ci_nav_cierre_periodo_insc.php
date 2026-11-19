<?php
/**
 *
 *
 * @package		Matrícula
 * @subpackage 	Actualizaciones
 * 
 */
class ci_nav_cierre_periodo_insc extends ci_nav_procesos_bk_wizzard
{
	// Componente Visual de Información.
	protected $ev_info;

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'fecha_cierre_requisitos_formato_visual', 'nombre', 'entidad_descripcion', 'ubicacion_nombre', 'plan_nombre', 'inscriptos'));
			$this->ev_info->set_max_columnas_por_fila(2);

			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('fecha_cierre_requisitos_formato_visual', 'Cierre de Requisitos');
			$this->ev_info->set_etiqueta('nombre', 'Período de Inscripción');
			$this->ev_info->set_etiqueta('entidad_descripcion', 'Responsables Académicas');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicaciones');
			$this->ev_info->set_etiqueta('plan_nombre', 'Propuestas');
			$this->ev_info->set_style('width: 100%');

			$this->ev_info->set_td_etiqueta_width('150px');
			$this->ev_info->set_td_valores_width('220px');
		}
		return $this->ev_info;
	}

	function vista_xml($inicial = false, $xmlns = null)
	{
		if ($xmlns) {
			$this->xml_set_ns($xmlns);
		}
		$xml = $this->xml_get_tag_inicio();
		$xml .= $this->ev_info()->vista_xml();
		foreach ($this->get_dependencias() as $dep) {
			if (method_exists($dep, 'vista_xml')) {
				$xml .= $dep->vista_xml(false, $xmlns);
			}
		}
		$xml .= $this->xml_get_tag_fin();
		return $xml;
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->indx_msg_eof = '800_eof_cuadro';
		if ($this->get_id_pantalla() != 'procesar') {
			$this->cn()->resetear_atributos_parametros();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Períodos 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- filtro_periodo_insc ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_periodo_insc(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_periodo_insc', false);
	}

	function evt__filtro_periodo_insc__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_periodo_insc', $datos);
	}

	function evt__filtro_periodo_insc__cancelar()
	{
		$this->filtro_limpiar('filtro_periodo_insc');
	}

	function validar_periodo_insc()
	{
		if (guarani_arreglos::count($this->get_seleccion('periodo_inscripcion')) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_periodos_inscripcion');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('un')));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_periodos_insc ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_periodos_insc(toba_ei_cuadro $cuadro)
	{
		/**
		 *    Si no est? seteado el filtro no se traen datos y se setea un mensaje en el cuadro que indica al usuario
		 *    que debe filtrar para obtener datos.
		 */
		$filtro = $this->get_filtro('filtro_periodo_insc');
		
		if (!isset($filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			$datos = guarani::co('co_periodos_de_inscripcion')->get_periodos_inscripcion_genericos_inscriptos($this->get_filtro('filtro_periodo_insc'));

			// Listar los períodos de Inscripción que aún tengan inscripciones en estado pendiente.
			$datos_mostrar = array();
			foreach ($datos as $dato) {
				if ($dato['cant_pendientes'] > 0) {
					$datos_mostrar[] = $dato;
				}
			}

			$cuadro->set_datos($datos_mostrar);
			$cuadro->set_eof_mensaje($this->get_filtro_msg_eof('filtro_periodo_insc', 'sga_periodos_inscripcion'));
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

	function evt__cuadro_periodos_insc__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_periodos_insc__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_periodos_insc', $fila));
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Resp. Académicas / Ubicaciones
	//-----------------------------------------------------------------------------------

	function conf__ra_ubicaciones(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_seleccion());
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_resp_acad -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_resp_acad(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_resp_acad', false);
	}

	function evt__filtro_resp_acad__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_resp_acad', $datos);
	}

	function evt__filtro_resp_acad__cancelar()
	{
		$this->filtro_limpiar('filtro_resp_acad');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_resp_acad -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_resp_acad(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_resp_acad');
		$filtro = empty($filtro) ? 'true' : $filtro;
		$institucion_instalacion = toba::db()->quote(guarani_configuracion::instancia()->get_valor_config('institucion_instalacion'));
		$filtro .= " AND sga_responsables_academicas.institucion = $institucion_instalacion";
		$datos = guarani::co('co_responsables_academicas')->get_listado($filtro);

		$cuadro->set_datos($datos);

		$cuadro->set_eof_mensaje($this->get_filtro_msg_eof('filtro_resp_acad', 'sga_responsables_academicas'));
	}

	function evt__cuadro_resp_acad__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_resp_acad__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_resp_acad', $fila));
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_ubicaciones -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_ubicaciones(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_ubicaciones', false);
	}

	function evt__filtro_ubicaciones__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_ubicaciones', $datos);
	}

	function evt__filtro_ubicaciones__cancelar()
	{
		$this->filtro_limpiar('filtro_ubicaciones');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_ubicaciones -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_ubicaciones(toba_ei_cuadro $cuadro)
	{
		$datos = guarani::co('co_ubicaciones')->get_listado($this->get_filtro('filtro_ubicaciones'));
		$cuadro->set_datos($datos);

		if (guarani_arreglos::count($datos) == 1) {
			$this->set_seleccion($datos);
		}

		$cuadro->set_eof_mensaje($this->get_filtro_msg_eof('filtro_ubicaciones', 'sga_ubicaciones'));
	}

	function evt__cuadro_ubicaciones__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_ubicaciones__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_ubicaciones', $fila));
	}

	function validar_ra_ubicaciones()
	{
		if (guarani_arreglos::count($this->get_seleccion('responsable_academica')) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_responsables_academicas');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
		if (guarani_arreglos::count($this->get_seleccion('ubicacion')) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_ubicaciones');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Propuestas 
	//-----------------------------------------------------------------------------------

	function conf__propuestas(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_seleccion());
		$pantalla->eliminar_evento('cambiar_tab__siguiente');
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_propuestas ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_propuestas(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_propuestas', false);
	}

	function evt__filtro_propuestas__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_propuestas', $datos);
	}

	function evt__filtro_propuestas__cancelar()
	{
		$this->filtro_limpiar('filtro_propuestas');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_propuestas ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_propuestas(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_propuestas');
		$ras = $this->get_seleccion('responsable_academica');
		$filtro_ra = ' AND sga_propuestas.propuesta IN (SELECT	sga_propuestas_ra.propuesta
														FROM	sga_propuestas_ra
														WHERE	' . guarani_sql::armar_condicion_in($ras['responsable_academica'], 'sga_propuestas_ra', 'responsable_academica') . ')';
		$ubicaciones = $this->get_seleccion('ubicacion');
		$filtro_ubicacion = ' AND sga_propuestas.propuesta IN (	SELECT	sga_propuestas_oferta.propuesta
																FROM	sga_propuestas_oferta
																WHERE	' . guarani_sql::armar_condicion_in($ubicaciones['ubicacion'], 'sga_propuestas_oferta', 'ubicacion') . ')';
		$filtro = empty($filtro) ? 'true' : $filtro;
		$filtro .= $filtro_ra . $filtro_ubicacion;

		$datos = guarani::co('co_propuestas')->get_propuestas($filtro);
		$cuadro->set_datos($datos);

		$cuadro->set_eof_mensaje($this->get_filtro_msg_eof('filtro_propuestas', 'sga_propuestas'));
	}

	function evt__cuadro_propuestas__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
			$propuestas = aplanar_matriz($seleccion, 'propuesta');
			$this->cn()->cargar_atributos_parametros_propuesta($propuestas);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function validar_propuestas()
	{
		if (guarani_arreglos::count($this->get_seleccion('plan_version')) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Procesar 
	//-----------------------------------------------------------------------------------

	function conf__procesar(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_seleccion());

		if ($pantalla->existe_evento('cambiar_tab__anterior')) {
			$pantalla->eliminar_evento('cambiar_tab__anterior');
		}

		if ($pantalla->existe_evento('cambiar_tab__siguiente')) {
			$pantalla->eliminar_evento('cambiar_tab__siguiente');
		}
	}

	function conf__resultados(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();
	}

	//-----------------------------------------------------------------------------------
	//---- Procesamiento
	//-----------------------------------------------------------------------------------

	function procesar()
	{
		// Se recuperan las inscripciones de acuerdo a filtros especificados a lo largo de la operación.
		$where = " sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_pendiente . "'";

		$periodo_inscripcion = $this->get_seleccion('periodo_inscripcion');
		$where .= " AND sga_periodos_inscripcion_fechas.periodo_inscripcion = '{$periodo_inscripcion['periodo_inscripcion']}'";

		$anio_acad = $this->get_seleccion('anio_academico');
		$where .= " AND sga_propuestas_aspira.anio_academico = '{$anio_acad['anio_academico']}'";

		$ubicacion = $this->get_seleccion('ubicacion');
		$where .= ' AND ' . guarani_sql::armar_condicion_in($ubicacion['ubicacion'], 'sga_alumnos', 'ubicacion');

		$plan_version = $this->get_seleccion('plan_version');
		$where .= ' AND ' . guarani_sql::armar_condicion_in($plan_version['plan_version'], 'sga_alumnos', 'plan_version');

		$propuesta = $this->get_seleccion('propuesta');
		$where .= ' AND ' . guarani_sql::armar_condicion_in($propuesta['propuesta'], 'sga_propuestas_aspira', 'propuesta');

		$inscripciones = guarani::co('co_periodos_de_inscripcion')->get_aspirantes_propuesta($where);

		if (empty($inscripciones)) {
			throw guarani::error(guarani_mensajes::get('800_eof_cuadro_filtrado', array('inscripciones pendientes')));
		}

		$parametros = array();
		$parametros['usuario'] = toba::usuario()->get_id();
		$parametros['datos'] = $inscripciones;

		$this->cn()->ejecutar_proceso($parametros);
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
		$contenido = $this->cn()->get_resultado_proceso(pro_cierre_periodo_insc::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_aceptadas':
				$reporte = pro_cierre_periodo_insc::reporte_aceptadas;
				$operacion = '41000068';
				$titulo = 'Alumnos Aceptados';
				break;
			case 'rep_rechazadas':
				$reporte = pro_cierre_periodo_insc::reporte_rechazadas;
				$operacion = '41000068';
				$titulo = 'Alumnos Rechazados';
				break;
			case 'rep_pendientes':
				$reporte = pro_cierre_periodo_insc::reporte_pendientes;
				$operacion = '41000068';
				$titulo = 'Alumnos Pendientes';
				break;
		}

		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
		} else {
			// Se obtiene el Proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}		
		// Se obtiene el resto de los parámetros
		$anio_acad = $this->get_seleccion('anio_academico');
		$periodo_inscripcion = $this->get_seleccion('nombre');
		$ev_info_datos = array('anio_academico' => $anio_acad['anio_academico'], 'periodo_inscripcion_nombre' => $periodo_inscripcion['nombre']);

		// Se almacenan los datos en la instasncia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array('inscripciones' => $datos['mensajes'],
			'ev_info_datos' => $ev_info_datos,
			'titulo' => $titulo));
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se crea el vínculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		$this->validar_propuestas();
		$this->procesar();
		$this->set_pantalla('procesar');
	}

	function evt__volver()
	{
		$this->limpiar_seleccion();
		$this->set_pantalla('periodo_insc');
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$seleccion = $this->get_seleccion('nombre');
		$mensaje_confirmacion = guarani_mensajes::get('800MAT_conf_cierre_periodo', [$seleccion['nombre'] ?? '']);
		echo "
		//---- Eventos ---------------------------------------------				
		{$this->objeto_js}.evt__procesar = function()
		{
			resp = confirm('$mensaje_confirmacion');
			return resp;
		}
		";
	}

}