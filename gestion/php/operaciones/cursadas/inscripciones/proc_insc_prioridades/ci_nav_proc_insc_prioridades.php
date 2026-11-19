<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Inscripciones
 * 
 */
class ci_nav_proc_insc_prioridades extends ci_nav_procesos_bk_wizzard
{
	protected $s__actividades;
	protected $s__criterios_prioridad;
	protected $s__cont_criterios;
	
	protected $s__datos_ev_info;
	
	// Componente visual de información.
	protected $ev_info;
	
	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial();
	}
	
	//--------------------------------------------------------------------------
	//---- Pantalla: seleccion
	//--------------------------------------------------------------------------
	
	function evt__seleccion__entrada()
	{
		unset($this->s__actividades);
		unset($this->s__criterios_prioridad);
		unset($this->s__cont_criterios);
	}
	
	function evt__seleccion__salida()
	{
		if (empty($this->s__actividades)) {
			$mensaje = guarani_mensajes::get('800_no_seleccion', array('una Actividad'));
			throw guarani::error($mensaje);
		}
	}
	
	//--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodo')->set_condicion_fija('es_igual_a');
	}
	
	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_datos()
	{
		$periodo_lectivo = $this->s__datos['periodo']['valor'];
		$this->dep('filtro')->columna('anio_academico')->resetear_estado();
		$this->dep('filtro')->columna('periodo')->resetear_estado();
		$this->s__filtro = $this->dep('filtro')->get_sql_where();
		$this->dep('filtro')->set_datos($this->s__datos);
		return $this->cn()->get_listado($periodo_lectivo, $this->s__filtro);
	}
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro) && !$this->con_listado_inicial()) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			$datos = $this->get_datos();
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}
	
	function evt__cuadro__seleccion($datos)
	{
		if (!empty($datos)) {
			$this->s__actividades = aplanar_matriz($datos, 'elemento');
		}
	}

	//--------------------------------------------------------------------------
	//---- Pantalla: prioridades
	//--------------------------------------------------------------------------

	function conf__pant_prioridades(toba_ei_pantalla $pantalla)
	{
		$pantalla->eliminar_evento('cambiar_tab__siguiente');

		$mensaje_confirmacion = guarani_mensajes::get('800CUR_confirmar_proc_inscr');
		$pantalla->evento('procesar')->set_msg_confirmacion($mensaje_confirmacion);
	}

	//--------------------------------------------------------------------------
	//---- prioridades ---------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__prioridades(guarani_pers_ei_formulario_ml $form_ml)
	{
		$datos = toba::consulta_php('co_inscripciones_cursadas')->get_criterios_prioridad_insc_cursada();
		$form_ml->set_datos($datos);
	}

	function evt__prioridades__modificacion($datos)
	{
		$this->s__criterios_prioridad = '';
		$this->s__cont_criterios = 0;
		foreach ($datos as $dato) {
			if ($dato['seleccion']) {
				$this->s__criterios_prioridad .= $dato['criterio_base'] . ', ';
				$this->s__cont_criterios++;
			}
		}
	}
	
	//--------------------------------------------------------------------------
	//---- Pantalla: procesamiento
	//--------------------------------------------------------------------------

	function conf__procesamiento(toba_ei_pantalla $pantalla)
	{
		$pantalla->eliminar_evento('cambiar_tab__anterior');
		$this->ev_info()->set_datos($this->get_datos_ev_info());
	}
	
	//--------------------------------------------------------------------------
	//---- ev_info -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function ev_info()
	{
		if (! isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'periodo_descr', 'actividades'));
			$this->ev_info->set_max_columnas_por_fila(2);
			$this->ev_info->set_max_chars_por_columna(80);
			
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico:');			
			$this->ev_info->set_etiqueta('periodo_descr', 'Período Lectivo:');
			$this->ev_info->set_etiqueta('actividades', 'Actividades:');
			
			$this->ev_info->set_columnas_fila(array('anio_academico', 'periodo_descr'), 1);
			$this->ev_info->set_columnas_fila(array('actividades'), 2);
			
			$this->ev_info->set_style('width: 100%');
			$this->ev_info->set_td_etiqueta_width(100);
		}
		return $this->ev_info;
	}
	
	function get_datos_ev_info()
	{
		if (empty($this->s__datos_ev_info)) {
			$this->s__datos_ev_info = array();
			// Año Académico
			$this->s__datos_ev_info['anio_academico'] = $this->s__datos['anio_academico']['valor'];
			// Período Lectivo
			$periodo_lectivo =  $this->s__datos['periodo']['valor'];
			$this->s__datos_ev_info['periodo_descr'] = toba::consulta_php('co_periodos_lectivos')->get_nombre_periodo_lectivo($periodo_lectivo);
			// Actividades
			$where = 'sga_elementos.elemento IN (' . implode(',', $this->s__actividades) .')';
			$this->s__datos_ev_info['actividades'] = aplanar_matriz($this->cn()->get_listado($periodo_lectivo, $where), 'actividad_codigo_y_nombre');
		}
		return $this->s__datos_ev_info;
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function evt__procesar()
	{	
		$parametros = array();
		$parametros['operacion'] = guarani::get_id_operacion();
		$periodo_lectivo =  $this->s__datos['periodo']['valor'];
		$parametros['inscripciones'] = $this->cn()->get_inscripciones_a_procesar($periodo_lectivo, $this->s__actividades, $this->s__criterios_prioridad, $this->s__cont_criterios);
		$this->cn()->ejecutar_proceso($parametros);

		$this->set_pantalla('procesamiento');
	}

	function evt__cancelar()
	{
		unset($this->s__datos_ev_info);
		$this->set_pantalla('seleccion');
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
		$contenido = $this->cn()->get_resultado_proceso(pro_proc_insc_prioridades::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_aceptadas':
				$reporte = pro_proc_insc_prioridades::reporte_aceptadas;
				$operacion = '41000145';
				$titulo = 'Inscripciones Aceptadas';
				break;
			case 'rep_fallo_controles':
				$reporte = pro_proc_insc_prioridades::reporte_fallo_controles;
				$operacion = '41000145';
				$titulo = 'Inscripciones que no pasaron los controles';
				break;
		}
		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
		} else {
			// Se obtiene el Proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);			
		}

		// Se obtiene el Proceso para pedirle el log de resultados
		$inscripciones = array();
		foreach ($datos['mensajes'] as $dato) {
			$inscripciones[] = $dato;
		}
		
		// Se almacenan los datos en la instancia de impresión para ser consumidos luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array(	'inscripciones' => $inscripciones,
																'reporte' => $reporte,
																'titulo' => $titulo));
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se crea el vínculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria'=>'popup'));
		$respuesta->set($vinculo);		
	}
	
	//--------------------------------------------------------------------------
	//---- Javascript ----------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function js_evt_cancelar() 
	{
		return '';
	}
	
}
?>