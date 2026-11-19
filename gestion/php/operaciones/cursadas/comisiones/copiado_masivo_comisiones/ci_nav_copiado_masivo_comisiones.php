<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class ci_nav_copiado_masivo_comisiones extends ci_nav_procesos_bk_wizzard
{
	// Filtros
	protected $s__filtro_ra;
	protected $s__filtro_propuesta;
	
	// Datos Filtros
	protected $s__datos_filtro_ra;
	protected $s__datos_filtro_propuesta;
	
	// Actividades a las cuales copiarle las comisiones
	protected $s__actividades;
	
	// Temporales
	protected $datos_per_origen_temp;
	protected $datos_per_destino_temp;
	protected $datos_anios_plan_temp;
	protected $datos_comisiones_temp;


	// Componentes Visuales de información
	protected $ev_info_origen;
	protected $ev_info_filtros;
	protected $ev_info_destino;

	// Constantes
	const maximo_anio_cursado = 10;

	protected $mje_confirma_copiado = '800cur_copiar_comision';

	function conf()
	{
		$this->indx_msg_eof = '800_eof_cuadro';
		parent::conf();
	}

	/*
	 * Se maneja el avance de pantalla.
	 */
	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();

		// Ejecución del proceso background
		if ($this->s__pantalla_actual == 'comisiones') {
			$origen = $this->get_seleccion_valor('info_origen');
			$destino = $this->get_seleccion_valor('info_destino');

			// Parámetros a enviar en la creación del proceso
			$parametros = array_merge(array('comisiones' => $this->get_seleccion_valor('comision')), $origen, $destino);

			// Ejecución del proceso background	
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
		$resultados = $this->cn()->get_resultado_proceso(pro_copia_comisiones::reporte_resultados);

		if (!empty($resultados['mensajes'])) {
			$respuesta->set(current($resultados['mensajes']));
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_comisiones':
				$reporte = pro_copia_comisiones::reporte_comisiones;
				$operacion = '38000079';
				$parametro_popup = 'comisiones';
				break;
			case 'rep_comisiones_con_obs':
				$reporte = pro_copia_comisiones::reporte_comisiones_con_obs;
				$operacion = '38000079';
				$parametro_popup = 'comisiones_con_obs';
				break;
			case 'rep_comisiones_no_copiadas':
				$reporte = pro_copia_comisiones::reporte_comisiones_no_copiadas;
				$operacion = '38000079';
				$parametro_popup = 'comisiones_no_copiadas';
				break;
			case 'rep_actividades_con_comisiones':
				$reporte = pro_copia_comisiones::reporte_actividades_con_comisiones;
				$operacion = '38000101';
				$parametro_popup = 'actividades_con_com';
				break;
		}


		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
		} else {
			// Se obtiene el Proceso para pedirle el log de resultados
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}
		// Se obtienen los datos correspondientes al encabezado del reporte.
		$destino = $this->get_seleccion_valor('info_destino');
		$datos_conf = array('anio' => $destino['anio_academico_destino'],
			'periodo_lectivo' => $destino['periodo_lectivo_destino_nombre']);

		// Se almacenan los datos en la instancia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array($parametro_popup => $datos['mensajes']));
		guarani_datos_impresion::instancia()->set_conf($datos_conf);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se crea el vínculo para que se abra en el popup		
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
			$this->ev_info_origen->set_style('width:55%;');
			$this->ev_info_origen->set_columnas_a_mostrar(array('anio_academico_origen', 'periodo_lectivo_nombre', 'ubicacion_nombre'));
			$this->ev_info_origen->set_columnas_fila(array('anio_academico_origen', 'periodo_lectivo_nombre'), 1);
			$this->ev_info_origen->set_etiqueta('anio_academico_origen', 'Año Académico');
			$this->ev_info_origen->set_etiqueta('periodo_lectivo_nombre', 'Período Lectivo Origen');
			$this->ev_info_origen->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info_origen;
	}

	function ev_info_filtros()
	{
		if (!isset($this->ev_info_filtros)) {
			$this->ev_info_filtros = guarani::ev_info_datos();
			$this->ev_info_filtros->set_controlador($this);
			$this->ev_info_filtros->set_titulo('Filtros para las Comisiones');
			$this->ev_info_filtros->set_max_datos_por_columna(10);
			$this->ev_info_filtros->set_max_chars_por_columna(130);
			$this->ev_info_filtros->set_columnas_a_mostrar(array('resp_acad_nombre', 'nombre', 'nombre_periodo_generico', 'todas_algunas_descr'));
			$this->ev_info_filtros->set_etiqueta('resp_acad_nombre', 'Responsables Académicas');
			$this->ev_info_filtros->set_etiqueta('nombre', 'Propuestas');
			$this->ev_info_filtros->set_etiqueta('nombre_periodo_generico', 'Actividades de');
			$this->ev_info_filtros->set_etiqueta('todas_algunas_descr', 'Considerar Comisiones');
		}
		return $this->ev_info_filtros;
	}

	function ev_info_destino()
	{
		if (!isset($this->ev_info_destino)) {
			$this->ev_info_destino = guarani::ev_info_datos();
			$this->ev_info_destino->set_controlador($this);
			$this->ev_info_destino->set_titulo('Destino');
			$this->ev_info_destino->set_style('width:55%;');
			$this->ev_info_destino->set_columnas_a_mostrar(array('anio_academico_destino', 'periodo_lectivo_destino_nombre'));
			$this->ev_info_destino->set_columnas_fila(array('anio_academico_destino', 'periodo_lectivo_destino_nombre'), 1);
			$this->ev_info_destino->set_etiqueta('anio_academico_destino', 'Año Académico');
			$this->ev_info_destino->set_etiqueta('periodo_lectivo_destino_nombre', 'Período Lectivo Destino');
		}
		return $this->ev_info_destino;
	}

	//-----------------------------------------------------------------------------------
	//---- PERIODO LECTIVO ORIGEN 
	//-----------------------------------------------------------------------------------
	
	function conf__periodo_lectivo_origen(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_periodo_origen ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_periodo_origen(toba_ei_formulario $form)
	{
		if (isset($this->datos_per_origen_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_per_origen_temp);
		} elseif (($this->existe_seleccion('info_origen'))) {
			$datos = $this->get_seleccion_valor('info_origen');
			$form->set_datos($datos);
		}
	}

	function evt__form_periodo_origen__modificacion($datos)
	{
		$this->datos_per_origen_temp = $datos;

		// Datos a mostrar: período y ubicacion
		$datos_per = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($datos['periodo_lectivo_origen']);
		$datos_ubi = toba::consulta_php('co_ubicaciones')->get_ubicacion_info($datos['ubicacion']);

		// Datos para el ev de información
		$datos['periodo_lectivo_nombre'] = $datos_per['periodo_nombre'];
		$datos['ubicacion_nombre'] = $datos_ubi['ubicacion_nombre'];

		$this->set_seleccion(array('info_origen' => $datos));
		$this->validar_existencia_comisiones();
	}

	function validar_existencia_comisiones()
	{
		$ubicacion = $this->dep('form_periodo_origen')->ef('ubicacion')->get_estado();
		$p_lectivo = $this->dep('form_periodo_origen')->ef('periodo_lectivo_origen')->get_estado();
		$anio_acad = $this->dep('form_periodo_origen')->ef('anio_academico_origen')->get_estado();

		// Se valida que la Ubicación seleccionada tenga Comisiones definidas en el Período Lectivo Origen
		if ($ubicacion && $p_lectivo && !toba::consulta_php('co_comisiones')->tiene_comisiones($ubicacion, $p_lectivo)) {

			// Datos a mostrar
			$datos_per = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($p_lectivo);
			$datos_ubi = toba::consulta_php('co_ubicaciones')->get_ubicacion_info($ubicacion);

			$mje_sin_comisiones = guarani_mensajes::get('800cur_copiar_ubicasincom', array($anio_acad, $datos_per['periodo_nombre'], $datos_ubi['ubicacion_nombre']));
			throw guarani::error($mje_sin_comisiones);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_ras -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_ras(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_ra)) {
			$filtro->set_datos($this->s__datos_filtro_ra);
		} else {
			$filtro->colapsar();
		}
	}

	function evt__filtro_ras__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_ra = $datos;
			$this->s__filtro_ra = $this->dep('filtro_ras')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_ra);
			$this->s__filtro_ra = '';
		}
	}

	function evt__filtro_ras__cancelar()
	{
		unset($this->s__datos_filtro_ra);
		$this->s__filtro_ra = '';
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_ras -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_ras(toba_ei_cuadro $cuadro)
	{
		$filtro = empty($this->s__filtro_ra) ? 'true' : $this->s__filtro_ra;
		$institucion_instalacion = toba::db()->quote(guarani_configuracion::instancia()->get_valor_config('institucion_instalacion'));
		$filtro .= " AND sga_responsables_academicas.institucion = $institucion_instalacion";
		$datos = guarani::co('co_responsables_academicas')->get_listado($filtro);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_ras__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_ras__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_ras', $fila));
	}

	/**
	 * Validación de salida
	 */
	function validar_periodo_lectivo_origen()
	{
		// Se valida que se haya seleccionado alguna Responsable Académica
		if ($this->falta_seleccionar('responsable_academica')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Responsable Académica'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PROPUESTAS 
	//-----------------------------------------------------------------------------------

	function conf__propuestas(toba_ei_pantalla $pantalla)
	{
		// Datos para los evs de información
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$datos_filtros = $this->get_seleccion('resp_acad_nombre');
		$this->ev_info_origen()->set_datos($datos_origen);
		$this->ev_info_filtros()->set_datos($datos_filtros);
		
		$this->cn()->resetear_atributos_parametros();
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_propuestas ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_propuestas(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_propuesta)) {
			$filtro->set_datos($this->s__datos_filtro_propuesta);
		} else {
			$filtro->colapsar();
		}
	}

	function evt__filtro_propuestas__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_propuesta = $datos;
			$this->s__filtro_propuesta = $this->dep('filtro_propuestas')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_propuesta);
			$this->s__filtro_propuesta = '';
		}
	}

	function evt__filtro_propuestas__cancelar()
	{
		unset($this->s__datos_filtro_propuesta);
		$this->s__filtro_propuesta = '';
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_propuestas ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_propuestas(toba_ei_cuadro $cuadro)
	{
		$info_origen = $this->get_seleccion_valor('info_origen');
		$ras = $this->get_seleccion_valor('responsable_academica');
		$cond_ubicacion = "sga_propuestas.propuesta IN (SELECT	sga_propuestas_oferta.propuesta 
														FROM	sga_propuestas_oferta 
														WHERE	sga_propuestas_oferta.ubicacion = {$info_origen['ubicacion']})";

		$filtro_ras = guarani_sql::armar_condicion_in($ras, 'sga_propuestas_ra', 'responsable_academica', 'responsable_academica');
		$cond_ras = "sga_propuestas.propuesta IN 
							(SELECT sga_propuestas_ra.propuesta 
							 FROM sga_propuestas_ra 
							 WHERE $filtro_ras)";

		$cond_ubic_ra = $cond_ubicacion . ' AND ' . $cond_ras;
		$filtro_prop = ($this->s__filtro_propuesta == '') ? $cond_ubic_ra : $cond_ubic_ra . ' AND ' . $this->s__filtro_propuesta;

		if ($this->s__filtro_propuesta == '' || !isset($this->s__filtro_propuesta)) {
			$indx_msj = $this->indx_msg_eof;
		} else {
			$indx_msj = $this->indx_msg_eof . '_filtrado';
		}

		$datos = toba::consulta_php('co_propuestas')->get_planes_activos_con_propuestas_en_versiones_activas($filtro_prop);
		$msj = guarani_mensajes::get($indx_msj, array('Propuestas'));
		$cuadro->set_eof_mensaje($msj);
		$cuadro->set_datos($datos);

		// Si no hay propuestas seleccionadas se eligen todas por defecto
		if (!$this->existe_seleccion('plan')) {
			$this->set_seleccion($datos);
		}
	}

	function evt__cuadro_propuestas__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
			$propuestas = aplanar_matriz($seleccion, 'propuesta');
			$this->cn()->cargar_atributos_parametros($propuestas);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	/**
	 * Validación de salida
	 */
	function validar_propuestas()
	{
		// Se valida que se haya seleccionado alguna Propuesta
		if ($this->falta_seleccionar('plan')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Propuesta'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- ACTIVIDADES 
	//-----------------------------------------------------------------------------------

	function conf__actividades(toba_ei_pantalla $pantalla)
	{
		// Datos para los evs de información
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$datos_filtros = $this->get_seleccion(array('resp_acad_nombre', 'nombre'));
		$this->ev_info_origen()->set_datos($datos_origen);
		$this->ev_info_filtros()->set_datos($datos_filtros);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_actividades -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_actividades(toba_ei_cuadro $cuadro)
	{
		$datos = toba::consulta_php('co_periodos_lectivos')->get_periodos_genericos_activos();
		$cuadro->set_datos($datos);

		// Si no hay períodos lectivos seleccionados se eligen todos por defecto
		if (!$this->existe_seleccion('periodo_generico')) {
			$this->set_seleccion($datos);
		}
	}

	function evt__cuadro_actividades__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_actividades__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_actividades', $fila));
	}

	//-----------------------------------------------------------------------------------
	//---- form_anios_plan --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_anios_plan(toba_ei_formulario $form)
	{
		// Configuraciones para el "Año según Plan"
		$opciones = $this->generar_array_opciones();
		$cant_col = ceil(guarani_arreglos::count($opciones) / 11);
		$form->ef('anio_segun_plan')->set_opciones($opciones);
		$form->ef('anio_segun_plan')->set_cantidad_columnas($cant_col);

		if (isset($this->datos_anios_plan_temp)) {
			// Si hubo algún error al querer ir al paso siguiente
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_anios_plan_temp);
		} elseif (($this->existe_seleccion('anio_segun_plan'))) {
			// Si se regresa del paso siguiente del wizzard, se recupera lo seleccionado anteriormente
			$datos = $this->get_seleccion('anio_segun_plan');
			$form->set_datos($datos);
		}
	}

	function generar_array_opciones()
	{
		// Opciones para los años
		$opciones = array(0 => 'Indefinido');
		$valor_opcion = 1;

		while ($valor_opcion <= self::maximo_anio_cursado) {
			array_push($opciones, $valor_opcion);
			++$valor_opcion;
		}

		// Arreglo de datos con valores: Indefinido, 1, 2, ... , maximo_anio_cursado
		return $opciones;
	}

	function evt__form_anios_plan__modificacion($datos)
	{
		$this->set_seleccion($datos);
		$this->datos_anios_plan_temp = $datos;
	}

	/**
	 * Validación de salida
	 */
	function validar_actividades()
	{
		// Se valida que se haya seleccionado alguna Actividad
		if ($this->falta_seleccionar('periodo_generico')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un Período Lectivo'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PERIODO LECTIVO DESTINO 
	//-----------------------------------------------------------------------------------

	function conf__periodo_lectivo_destino(toba_ei_pantalla $pantalla)
	{
		// Datos para los evs de información
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$datos_filtros = $this->get_seleccion(array('resp_acad_nombre', 'nombre', 'nombre_periodo_generico'));
		$this->ev_info_origen()->set_datos($datos_origen);
		$this->ev_info_filtros()->set_datos($datos_filtros);
	}

	//-----------------------------------------------------------------------------------
	//---- form_periodo_destino ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_periodo_destino(toba_ei_formulario $form)
	{
		// Seteo de datos seleccionados previamente
		$origen = $this->get_seleccion_valor('info_origen');
		$form->set_datos(array('ubicacion' => $origen['ubicacion_nombre'], 'anio_academico_origen' => $origen['anio_academico_origen']));
		$form->set_solo_lectura(array('ubicacion'));
                
		if (isset($this->datos_per_destino_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_per_destino_temp);
		} elseif (($this->existe_seleccion('info_destino'))) {
			$datos = $this->get_seleccion_valor('info_destino');
			$form->set_datos($datos);
		}
	}

	function evt__form_periodo_destino__modificacion($datos)
	{
		unset($datos['ubicacion']);
		$this->datos_per_destino_temp = $datos;

		// Se obtienen datos a mostrar: período y comisiones a considerar
		$datos_per = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($datos['periodo_lectivo_destino']);
		$datos['periodo_lectivo_destino_nombre'] = $datos_per['periodo_nombre'];
		$comisiones = array();
		$comisiones['todas_algunas'] = $datos['todas_algunas'];
		$comisiones['todas_algunas_descr'] = $datos['todas_algunas'] == 'T' ? 'Todas' : 'Sin comisión generada en destino';

		// Seteo de los datos
		$seleccion = array_merge(array('info_destino' => $datos), $comisiones);
		$this->set_seleccion($seleccion);
	}

	/**
	 * Validación de salida
	 */
	function validar_periodo_lectivo_destino()
	{
		// Valores
		$origen = $this->get_seleccion_valor('info_origen');
		$destino = $this->get_seleccion_valor('info_destino');
		$ras = $this->get_seleccion_valor('responsable_academica');
		$planes = $this->get_seleccion_valor('plan');
		$acts = $this->get_seleccion_valor('periodo_generico');
		$sel_activ = $this->get_seleccion_valor('todas_algunas');
		$sel_anios = $this->get_seleccion_valor('anio_segun_plan');

		// Se valida que se el Año Académico Destino sea mayor o igual al de Origen
		if ($origen['anio_academico_origen'] == $destino['anio_academico_destino']) {
			if ($origen['periodo_lectivo_origen'] == $destino['periodo_lectivo_destino']) {
				$mje = guarani_mensajes::get('800cur_copiar_perlectdest');
				throw guarani::error($mje);
			}
		} elseif ($origen['anio_academico_origen'] > $destino['anio_academico_destino']) {
			$mje = guarani_mensajes::get('800_control_anio', array('académico destino', 'menor', 'académico origen'));
			throw guarani::error($mje);
		}

		// Filtro para los elementos
		$filtro_ras = guarani_sql::armar_condicion_in($ras, 'sga_elementos_ra', 'responsable_academica', 'responsable_academica');
		$filtro_ras = "sga_elementos.elemento IN (SELECT sga_elementos_ra.elemento FROM sga_elementos_ra WHERE $filtro_ras)";
		$filtro_planes = guarani_sql::armar_condicion_in($planes, 'sga_planes_versiones', 'plan');
		$filtro_planes = "sga_elementos_plan.plan_version IN ( SELECT	sga_planes_versiones.plan_version 
																FROM	sga_planes_versiones
																WHERE	sga_planes_versiones.estado IN ('" . planes::estado_activo . "','" . planes::estado_activo_vigente . "') 
																AND		$filtro_planes)";
		$filtro_anios = '(' . guarani_sql::armar_condicion_in($sel_anios, 'sga_elementos_plan', 'anio_de_cursada');
		$filtro_anios .= in_array(0, $sel_anios) ? 'OR sga_elementos_plan.anio_de_cursada IS NULL)' : ')'; //Si se chequeó la opción Indefinido en el Año del Plan
		$filtro_acts = '(' . guarani_sql::armar_condicion_in($acts, 'sga_elementos_plan', 'periodo_de_cursada');
		$filtro_acts .= 'OR sga_elementos_plan.periodo_de_cursada IS NULL)';
		$filtro_algunas = '';

		if ($sel_activ == 'S') {
			$filtro_algunas = ' AND NOT EXISTS 
										(	SELECT 1 
											FROM 	sga_comisiones
											WHERE 	sga_comisiones.elemento			= sga_elementos.elemento
											AND		sga_comisiones.periodo_lectivo	= ' . $destino['periodo_lectivo_destino'] . '
										)';
		}

		$filtro_actividad_estado = " AND sga_elementos.estado = '" . elemento::estado_activo . "'";
		$filtro_disponible_para = " AND sga_elementos.disponible_para IN ('" . elemento::para_mesas_de_examen_y_comisiones . "', '" . elemento::solo_para_creacion_comisiones . "')";

		$filtro = "$filtro_ras AND $filtro_planes AND $filtro_anios AND $filtro_acts $filtro_algunas $filtro_actividad_estado $filtro_disponible_para";

		// Se obtienen los elementos
		$elementos = toba::consulta_php('co_elementos')->get_elementos($filtro);

		// Se valida que existan elementos a los cuales copiarles las comisiones
		if (empty($elementos)) {
			$mje_sin_elem = guarani_mensajes::get('800cur_copiar_no_hay_actividades');
			throw guarani::error($mje_sin_elem);
		}

		// Se guardan las Actividades a las que se les copiarán sus Comisiones
		$this->s__actividades = $elementos;
	}

	//-----------------------------------------------------------------------------------
	//---- COMISIONES 
	//-----------------------------------------------------------------------------------

	function conf__comisiones(toba_ei_pantalla $pantalla)
	{
		// Datos para los evs de información
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$datos_filtros = $this->get_seleccion(array('resp_acad_nombre', 'nombre', 'nombre_periodo_generico', 'todas_algunas_descr'));
		$datos_destino = $this->get_seleccion_valor('info_destino');
		$this->ev_info_origen()->set_datos($datos_origen);
		$this->ev_info_filtros()->set_datos($datos_filtros);
		$this->ev_info_destino()->set_datos($datos_destino);

		// Mensaje de confirmación y etiqueta de botón
		$mensaje = guarani_mensajes::get($this->mje_confirma_copiado);
		$this->evento('cambiar_tab__siguiente')->set_msg_confirmacion($mensaje);
		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Generar');
		$this->evento('cambiar_tab__siguiente')->set_imagen('/generar.png');
	}

	//-----------------------------------------------------------------------------------
	//---- form_comisiones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_comisiones(toba_ei_formulario $form)
	{
		$form->set_datos_defecto(array('comisiones_a_copiar' => comunes::todas));
		if (isset($this->datos_comisiones_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_comisiones_temp);
		}
	}

	function evt__form_comisiones__modificacion($datos)
	{
		$this->datos_comisiones_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_comisiones ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_comisiones(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));

		$filtro = '';
		// Copiar todas las comisiones, sólo las cobrables o sólo las no cobrables.
		$info_destino = $this->get_seleccion_valor('info_destino');
		if ($info_destino['copiar_comisiones'] != comunes::todos) {
			$filtro .= "sga_comisiones.cobrable = '{$info_destino['copiar_comisiones']}' AND ";
		}
		// Comisiones de las actividades a copiar.
		$filtro .= guarani_sql::armar_condicion_in($this->s__actividades, 'sga_comisiones', 'elemento', 'elemento') . ' AND ';
		// Comisiones del período lectivo origen.
		$info_origen = $this->get_seleccion_valor('info_origen');
		$filtro .= "sga_comisiones.periodo_lectivo = {$info_origen['periodo_lectivo_origen']} AND ";
		// Comisiones de la ubicación origen.
		$filtro .= "sga_comisiones.ubicacion = {$info_origen['ubicacion']} AND ";
		// Comisiones activas.
		$filtro .= "sga_comisiones.estado = '".comunes::estado_activo."'";
		$select = 'f_horario_comision(sga_comisiones.comision) AS horario';
		$order = 'nombre_actividad, sga_comisiones.nombre';

		$datos = toba::consulta_php('co_comisiones')->get_listado($filtro, $select, $order);

		$cuadro->set_datos($datos);
	}

	function evt__cuadro_comisiones__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	// Validación de la salida.
	function validar_comisiones()
	{
		// Se valida que se haya seleccionado alguna comisión.
		if ($this->falta_seleccionar('comision')) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones');
			throw guarani::error(guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una'))));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- RESULTADOS 
	//-----------------------------------------------------------------------------------

	function conf__resultados(toba_ei_pantalla $pantalla)
	{
		// Datos para los evs de información
		$datos_origen = $this->get_seleccion_valor('info_origen');
		$datos_filtros = $this->get_seleccion(array('resp_acad_nombre', 'nombre', 'nombre_periodo_generico', 'todas_algunas_descr'));
		$datos_destino = $this->get_seleccion_valor('info_destino');
		$this->ev_info_origen()->set_datos($datos_origen);
		$this->ev_info_filtros()->set_datos($datos_filtros);
		$this->ev_info_destino()->set_datos($datos_destino);

		// Se oculta el botón Anterior
		$this->evento('cambiar_tab__anterior')->ocultar();
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__finalizar()
	{
		$this->limpiar_memoria();
		$this->set_pantalla('periodo_lectivo_origen');
	}

	//-----------------------------------------------------------------------------------
	//---- form_totales -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_totales(form_totales $form)
	{
		$form->set_solo_lectura();
	}

}