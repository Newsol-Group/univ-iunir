<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class ci_nav_generacion_masiva_comisiones extends ci_nav_procesos_bk_wizzard
{
	// Filtros
	protected $s__filtro_ra;
	protected $s__filtro_propuesta;
	
	// Datos Filtros
	protected $s__datos_filtro_ra;
	protected $s__datos_filtro_propuesta;
	
	// Actividades a generarles las comisiones
	protected $s__actividades;
      protected $s__actividades_seleccionadas;

      // Temporales
	protected $datos_per_destino_temp;
	protected $datos_comisiones_temp;
	
	// Componente Visual de información
	protected $ev_info;
	protected $ev_info_destino;

	// Constantes
	const maximo_anio_cursado = 10;

	protected $mje_confirma_generacion = '800cur_generar_comision';

	/*
	 * Se maneja el avance de pantalla.
	 */
	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();            
        // Si clickeó el boton 'Generar' entonces creo las Comisiones
		if ($this->s__pantalla_actual == 'actividades') {
			// Valores
			$destino = $this->get_seleccion_valor('info_destino');
			$param_com = $this->get_seleccion_valor('param_comisiones');
			$comisiones = $this->obtener_comisiones_definidas($this->get_seleccion_valor('comisiones'));
			$planes = $this->get_seleccion_valor('plan');
			$instancias = $this->get_seleccion_valor('instancias');
			$modalidades = $this->get_seleccion_valor('modalidad');
			$sel_activ = $this->get_seleccion_valor('todas_algunas');
			$habilitar_inscripcion = $this->get_seleccion_valor('inscripcion');

			// Se arma el array con los parámetros a mandar en la creación del proceso
			$parametros = array_merge(
					array('actividades' => $this->s__actividades_seleccionadas), 
					array('destino' => $destino), 
					array('parametros' => $param_com), 
					array('planes' => $planes), 
					array('turnos' => $comisiones), 
					array('instancias' => $instancias), 
					array('modalidades' => $modalidades), 
					array('gen_com_siempre' => $sel_activ != comunes::si), 
					array('habilitar_inscripcion' => $habilitar_inscripcion),
					array('asist_computa_inasis_por_horas' => guarani::parametros()->get_valor('asist_computa_inasis_por_horas')),
					array('asist_indicar_cantidad_inasistencias' => guarani::parametros()->get_valor('asist_indicar_cantidad_inasistencias'))
			);
            
            // Se ejecuta el proceso background	
			$this->cn()->ejecutar_proceso($parametros);
		}
	}

	function obtener_comisiones_definidas($comisiones)
	{
		foreach ($comisiones as $clave => $valor) {
			if (is_null($valor['cantidad']) || ($valor['cantidad'] == 0)) {
				unset($comisiones[$clave]);
			}
		}
		return $comisiones;
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
		$resultados = $this->cn()->get_resultado_proceso(pro_generacion_comisiones::reporte_resultados);

		if (!empty($resultados['mensajes'])) {
			$respuesta->set(current($resultados['mensajes']));
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_comisiones':
				$reporte = pro_generacion_comisiones::reporte_comisiones;
				$operacion = '38000079';
				$parametro_popup = 'comisiones';
				break;
			case 'rep_comisiones_con_obs':
				$reporte = pro_generacion_comisiones::reporte_comisiones_con_obs;
				$operacion = '38000079';
				$parametro_popup = 'comisiones_con_obs';
				break;
			case 'rep_actividades':
				$reporte = pro_generacion_comisiones::reporte_actividades_con_comisiones;
				$operacion = '38000101';
				$parametro_popup = 'actividades_con_com';
				break;
			case 'rep_actividades_sin_comisiones':
				$reporte = pro_generacion_comisiones::reporte_actividades_sin_comisiones;
				$operacion = '38000101';
				$parametro_popup = 'actividades';
				break;
		}

		// Se obtienen los datos correspondientes al encabezado del reporte.
		$destino = $this->get_seleccion_valor('info_destino');
		$datos_conf = array(
			'anio' => $destino['anio_academico_destino'],
			'periodo_lectivo' => $destino['periodo_lectivo_nombre']
		);

		// Se obtiene el Proceso para pedirle el log de resultados
		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
		} else {
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}

		// Se almacenan los datos en la instancia de impresión para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array($parametro_popup => $datos['mensajes']));
		guarani_datos_impresion::instancia()->set_conf($datos_conf);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se crea el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares para la visualización de las selecciones previas ------------------
	//-----------------------------------------------------------------------------------

	function ev_info_destino()
	{
		if (!isset($this->ev_info_destino)) {
			$this->ev_info_destino = guarani::ev_info_datos();
			$this->ev_info_destino->set_controlador($this);
			$this->ev_info_destino->set_titulo('Filtros para las Comisiones');
			$this->ev_info_destino->set_td_etiqueta_width('207px');
			$this->ev_info_destino->set_columnas_a_mostrar(array('anio_academico_destino', 'periodo_lectivo_nombre', 'ubicacion_nombre'));
			$this->ev_info_destino->set_columnas_fila(array('anio_academico_destino', 'periodo_lectivo_nombre'));
			$this->ev_info_destino->set_etiqueta('anio_academico_destino', 'Año Académico');
			$this->ev_info_destino->set_etiqueta('periodo_lectivo_nombre', 'Período Lectivo');
			$this->ev_info_destino->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info_destino;
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_max_datos_por_columna(10);
			$this->ev_info->set_max_chars_por_columna(110);
			$this->ev_info->set_td_etiqueta_width('207px');
			$this->ev_info->set_columnas_a_mostrar(array('resp_acad_nombre', 'propuesta_nombre', 'anio_segun_plan_lista', 'nombre_periodo_generico', 'inscripcion_habilitada', 'instancia_nombre', 'modalidad_nombre', 'criterio_gen_descr'));
			$this->ev_info->set_etiqueta('resp_acad_nombre', 'Responsables Académicas');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuestas');
			$this->ev_info->set_etiqueta('anio_segun_plan_lista', 'Año de Cursado en Planes');
			$this->ev_info->set_etiqueta('nombre_periodo_generico', 'Períodos lectivos de las Actividades');
			$this->ev_info->set_etiqueta('inscripcion_habilitada', 'Habilitar Inscripción');
			$this->ev_info->set_etiqueta('instancia_nombre', 'Instancias');
			$this->ev_info->set_etiqueta('modalidad_nombre', 'Modalidades');
			$this->ev_info->set_etiqueta('criterio_gen_descr', 'Criterio para la Generación');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: PERIODO LECTIVO DESTINO 
	//-----------------------------------------------------------------------------------

	function conf__periodo_lectivo_destino(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_periodo_destino ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_periodo_destino(toba_ei_formulario $form)
	{
		if (isset($this->datos_per_destino_temp)) {
			$this->set_hubo_cambios();
			$form->set_datos($this->datos_per_destino_temp);
		} elseif (($this->existe_seleccion('info_destino'))) {
			$datos = $this->get_seleccion_valor('info_destino');
			$form->set_datos($datos);
		}
	}

	function evt__form_periodo_destino__modificacion($datos)
	{
		$this->datos_per_destino_temp = $datos;

		// Se buscan datos a mostrar: período y ubicación
		$datos_per = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($datos['periodo_lectivo_destino']);
		$datos_ubi = toba::consulta_php('co_ubicaciones')->get_ubicacion_info($datos['ubicacion']);

		// Se arma el array para setearlo en el ev de información
		$datos['periodo_lectivo_nombre'] = $datos_per['periodo_nombre'];
		$datos['ubicacion_nombre'] = $datos_ubi['ubicacion_nombre'];

		// Se setean los datos
		$this->set_seleccion(array('info_destino' => $datos));
	}

	function tiene_comisiones($ubicacion, $p_lectivo)
	{
		return toba::consulta_php('co_comisiones')->tiene_comisiones($ubicacion, $p_lectivo);
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
	function validar_periodo_lectivo_destino()
	{
		// Se valida que se haya seleccionado alguna Responsable Académica
		if ($this->falta_seleccionar('responsable_academica')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Responsable Académica'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: PROPUESTAS 
	//-----------------------------------------------------------------------------------

	function conf__propuestas(toba_ei_pantalla $pantalla)
	{
		// Datos para el ev de información
		$datos_destino = $this->get_seleccion_valor('info_destino');
		$datos_filtro = $this->get_seleccion('resp_acad_nombre');
		$this->ev_info_destino()->set_datos($datos_destino);
		$this->ev_info()->set_datos($datos_filtro);
		
		$this->cn()->resetear_atributos_parametros();
		
		$this->eliminar_seleccion_previa('propuestas', 'propuesta');
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
		$info_destino = $this->get_seleccion_valor('info_destino');
		$ras = $this->get_seleccion_valor('responsable_academica');
		$cond_ubicacion = "sga_propuestas.propuesta IN (SELECT sga_propuestas_oferta.propuesta FROM sga_propuestas_oferta WHERE sga_propuestas_oferta.ubicacion = {$info_destino['ubicacion']})";
		$filtro_ras = guarani_sql::armar_condicion_in($ras, 'sga_propuestas_ra', 'responsable_academica', 'responsable_academica');
		$cond_ras = "sga_propuestas.propuesta IN 
							(SELECT sga_propuestas_ra.propuesta 
							 FROM sga_propuestas_ra 
							 WHERE $filtro_ras)";

		$cond_ubic_ra = $cond_ubicacion . ' AND ' . $cond_ras;
		$filtro_prop = ($this->s__filtro_propuesta == '') ? $cond_ubic_ra : $cond_ubic_ra . ' AND ' . $this->s__filtro_propuesta;

		$datos = toba::consulta_php('co_propuestas')->get_planes_activos_con_propuestas_en_versiones_activas($filtro_prop);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_propuestas__seleccion($seleccion)
	{
		if (empty($seleccion)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Propuesta'));
			throw guarani::error($mje);
		}
		$this->set_seleccion($seleccion);
		$propuestas = aplanar_matriz($seleccion, 'propuesta');
		$planes = aplanar_matriz($seleccion, 'plan');
		$this->cn()->cargar_atributos_parametros($propuestas, $planes);
	}

	/**
	 * Validación de salida
	 */
	function validar_propuestas()
	{
		// Se valida que se haya seleccionado alguna Propuesta
		if ($this->falta_seleccionar('propuesta')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Propuesta'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: ACTIVIDADES 
	//-----------------------------------------------------------------------------------

	function conf__actividades_param(toba_ei_pantalla $pantalla)
	{
		// Datos para el ev de información
		$datos_destino = $this->get_seleccion_valor('info_destino');
		$datos_filtro = $this->get_seleccion(array('resp_acad_nombre', 'propuesta_nombre'));
		$this->ev_info_destino()->set_datos($datos_destino);
		$this->ev_info()->set_datos($datos_filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_actividades_param -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_actividades_param(toba_ei_cuadro $cuadro)
	{
		$datos = toba::consulta_php('co_periodos_lectivos')->get_periodos_genericos_activos();
		$cuadro->set_datos($datos);

		// Si no hay periodos lectivos seleccionados se eligen todos por defecto
		if (!$this->existe_seleccion('periodo_generico')) {
			$this->set_seleccion($datos);
		}
	}

	function evt__cuadro_actividades_param__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_actividades_param__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_actividades_param', $fila));
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

		if (($this->existe_seleccion('anio_segun_plan'))) {
			// Si vuelvo del paso siguiente del wizzard se recupera lo seleccionado anteriormente
			$datos = $this->get_seleccion('anio_segun_plan');
			$form->set_datos($datos);
		} else {
			// Si se accede al paso por primera vez setean los valores por defecto
			$anios = array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10);
			$form->set_datos(array('anio_segun_plan' => $anios));
		}
	}

	function generar_array_opciones()
	{
		// Creación de array de opciones para los años
		$opciones = array(0 => 'Indefinido');
		$valor_opcion = 1;

		while ($valor_opcion <= self::maximo_anio_cursado) {
			array_push($opciones, $valor_opcion);
			++$valor_opcion;
		}

		// Array con valores: Indefinido, 1, 2, ... , maximo_anio_cursado
		return $opciones;
	}

	function evt__form_anios_plan__modificacion($datos)
	{
		$array = $datos['anio_segun_plan'];
		$lista = array();

		if (in_array(0, $array)) {
			unset($array[0]);
			array_push($lista, 'Indefinido');
		}

		array_push($lista, guarani_texto::armar_lista($array));
		$datos['anio_segun_plan_lista'] = $lista;
		$this->set_seleccion($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- form_comisiones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_comisiones(form_comisiones $form)
	{
		if ($this->existe_seleccion('param_comisiones')) {
			$form->set_datos($this->get_seleccion_valor('param_comisiones'));
		}
	}

	function evt__form_comisiones__modificacion($datos)
	{
		$this->set_seleccion(array('param_comisiones' => $datos));
	}

	//-----------------------------------------------------------------------------------
	//---- form_inscripcion -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_inscripcion(toba_ei_formulario $form)
	{
		if (!$this->existe_seleccion('inscripcion')) {
			$form->set_datos(array('inscripcion' => guarani::parametros()->get_valor('cur_comision_habilitada') == comunes::si ? 1 : 0));
		} else {
			$form->set_datos($this->get_seleccion_valor('inscripcion'));
		}
	}

	function evt__form_inscripcion__modificacion($datos)
	{
		$datos['inscripcion_habilitada'] = $datos['inscripcion'] ? comunes::si_desc : comunes::no_desc;
		$this->set_seleccion($datos);
	}

	/**
	 * Validación de salida
	 */
	function validar_actividades_param()
	{
		// Se valida que se haya seleccionado alguna Actividad
		if ($this->falta_seleccionar('periodo_generico')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un Período Lectivo'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: COMISIONES 
	//-----------------------------------------------------------------------------------

	function conf__comisiones(toba_ei_pantalla $pantalla)
	{
		// Datos para el ev de información
		$datos_destino = $this->get_seleccion_valor('info_destino');
		$datos_filtro = $this->get_seleccion(array('resp_acad_nombre', 'propuesta_nombre', 'anio_segun_plan_lista', 'nombre_periodo_generico', 'inscripcion_habilitada'));
		$this->ev_info_destino()->set_datos($datos_destino);
		$this->ev_info()->set_datos($datos_filtro);

    }
      
	//-----------------------------------------------------------------------------------
	//---- ml_comisiones ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__ml_comisiones(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_comisiones_temp)) {
			// Si hubo algún error
			$this->set_hubo_cambios();
			$datos = $this->datos_comisiones_temp;
		} elseif ($this->existe_seleccion('comisiones')) {
			// Si ya había algo seleccionado
			$datos = $this->get_seleccion_valor('comisiones');
		} else {
			// Si accedo al paso por primera vez
			$datos = toba::consulta_php('co_comisiones')->get_listado_turnos_cursadas();
			$datos[] = array('valor' => 'null', 'descr' => 'Sin Turno');
		}

		$form_ml->set_datos($datos);
	}

	function evt__ml_comisiones__modificacion($datos)
	{
		// Se valida que exista algún Turno con Comisiones definidas
		if (!$this->existe_turno_con_comisiones($datos)) {
			$this->datos_comisiones_temp = $datos;
			$mje = guarani_mensajes::get('800_no_seleccion', array('la cantidad de Comisiones de un Turno'));
			throw guarani::error($mje);
		}

		$this->set_seleccion(array('comisiones' => $datos));
	}

	function existe_turno_con_comisiones($comisiones)
	{
		$existe = false;
		foreach ($comisiones as $comision) {
			if ((!is_null($comision['cantidad'])) && ($comision['cantidad'] != 0)) {
				$existe = true;
				break;
			}
		}
		return $existe;
	}

	//-----------------------------------------------------------------------------------
	//---- ml_instancias ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__ml_instancias(toba_ei_formulario_ml $form_ml)
	{
		if ($this->existe_seleccion('instancias')) {
			$datos = $this->get_seleccion_valor('instancias');
		} else {
			$datos = toba::consulta_php('co_comisiones')->get_listado_instancias_comisiones();
			foreach ($datos as $clave => $valor) {
				if ($valor['instancia'] == comision::inst_cursar) {
					$datos[$clave]['porc_asistencia'] = guarani::parametros()->get_valor('asistencia_porcentaje_regulares');
				} elseif ($valor['instancia'] == comision::inst_promocionar) {
					$datos[$clave]['porc_asistencia'] = guarani::parametros()->get_valor('asistencia_porcentaje_promociones');
				}
			}
		}

		$icono_informacion = new icono_info_escala_nota();
		$form_ml->ef('escala_nota')->agregar_icono_utileria($icono_informacion);
		$form_ml->set_datos($datos);
	}

	function evt__ml_instancias__modificacion($datos)
	{
		$instancias_descr = guarani_texto::armar_lista(guarani_arreglos::aplanar_matriz_sin_nulos($datos, 'instancia_nombre'));
		$this->set_seleccion(array('instancias' => $datos));
		$this->set_seleccion(array('instancia_nombre' => $instancias_descr));
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_modalidades -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_modalidades(toba_ei_cuadro $cuadro)
	{
		$datos = toba::consulta_php('co_alumnos')->get_modalidades_cursadas_alumnos();
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_modalidades__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_modalidades__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_modalidades', $fila));
	}

	//-----------------------------------------------------------------------------------
	//---- form_actividades_param -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_actividades_param(toba_ei_formulario $form)
	{
		$form->ef('todas_algunas')->set_maximo_descripcion(200);
		if (($this->existe_seleccion('todas_algunas'))) {
			// Si vuelvo del paso siguiente del wizzard se recupera lo seleccionado anteriormente
			$datos = $this->get_seleccion_valor('todas_algunas');
			$form->set_datos($datos);
		}
	}

	function evt__form_actividades_param__modificacion($datos)
	{
		if ($datos['todas_algunas'] == comunes::si) {
			$descr = 'NO generar comisión si ya existe al menos una comisión para la actividad en el turno de cursada elegido';
		} else {
			$descr = 'Generar comisión aún si ya existe alguna comisión de la actividad en el turno de cursada elegido';
		}

		$datos['criterio_gen_descr'] = $descr;
		$this->set_seleccion($datos);
	}

	/**
	 * Validación de salida
	 */
    function validar_comisiones()
    {
        // Se valida que se haya seleccionado algún Tipo de Modalidad
        if ($this->falta_seleccionar('modalidad')) {
            $mje = guarani_mensajes::get('800_no_seleccion', array('un Tipo de Modalidad'));
            throw guarani::error($mje);
        }

        // Se valida que existan elementos a los cuales generarles las comisiones
        if (!$this->existen_elementos()) {
            $mje_sin_elem = guarani_mensajes::get('800cur_copiar_no_hay_actividades');
            throw guarani::error($mje_sin_elem);
        }
    }

        /*
	 * Verifica si existen Actividades con los filtros seleccionados.
	 * Además, setea la variable de sesión que las guarda.
	 */
	function existen_elementos()
	{
		// Valores
		$ras = $this->get_seleccion_valor('responsable_academica');
		$planes = $this->get_seleccion_valor('plan');
		$acts = $this->get_seleccion_valor('periodo_generico');
		$sel_activ = $this->get_seleccion_valor('todas_algunas');
		$sel_anios = $this->get_seleccion_valor('anio_segun_plan');
		$modalidades = $this->get_seleccion_valor('modalidad');
		$comisiones = $this->obtener_comisiones_definidas($this->get_seleccion_valor('comisiones'));

		// Se arma el filtro para los elementos
		$filtro_ras = guarani_sql::armar_condicion_in($ras, 'sga_elementos_ra', 'responsable_academica', 'responsable_academica');
		$filtro_ras = "sga_elementos.elemento IN (SELECT sga_elementos_ra.elemento FROM sga_elementos_ra WHERE $filtro_ras)";
		$filtro_planes = guarani_sql::armar_condicion_in($planes, 'sga_planes_versiones', 'plan');
		$filtro_planes = "sga_elementos_plan.plan_version IN ( SELECT	sga_planes_versiones.plan_version 
																FROM	sga_planes_versiones 
																WHERE	sga_planes_versiones.estado IN ('" . planes::estado_activo . "','" . planes::estado_activo_vigente . "') 
																AND		$filtro_planes)";
		$filtro_anios = '(' . guarani_sql::armar_condicion_in($sel_anios, 'sga_elementos_plan', 'anio_de_cursada');
		$filtro_anios .= 'OR sga_elementos_plan.anio_de_cursada IS NULL)';
		$filtro_acts = '(' . guarani_sql::armar_condicion_in($acts, 'sga_elementos_plan', 'periodo_de_cursada');
		$filtro_acts .= 'OR sga_elementos_plan.periodo_de_cursada IS NULL)';

		$in_plan = guarani_sql::armar_condicion_in($planes, 'vw_actividades_plan', 'plan');
		$in_mod = guarani_sql::armar_condicion_in($modalidades, 'sga_elementos_plan_modalidad', 'modalidad');
		$filtro_acts_mod = " AND EXISTS (	SELECT 	1 
											FROM 	vw_actividades_plan, sga_elementos_plan_modalidad
							                WHERE 	vw_actividades_plan.elemento = sga_elementos.elemento
							                AND 	$in_plan 
							                AND  	vw_actividades_plan.elemento_plan = sga_elementos_plan_modalidad.elemento_plan
							                AND  	$in_mod)";

		$filtro_actividad_estado = " AND sga_elementos.estado = '" . elemento::estado_activo . "'";
		$filtro_disponible_para = " AND sga_elementos.disponible_para IN ('" . elemento::para_mesas_de_examen_y_comisiones . "', '" . elemento::solo_para_creacion_comisiones . "')";

		$filtro = "$filtro_ras AND $filtro_planes AND $filtro_anios AND $filtro_acts $filtro_acts_mod $filtro_actividad_estado $filtro_disponible_para";

		// Se obtienen los elementos
            $elementos = toba::consulta_php('co_elementos')->get_elementos($filtro);

            if (empty($elementos)) {
			// No existen actividades
			return false;
		}
            
		// Existen Actividades -> se almacenan para posteriormente generarles las Comisiones
		$this->s__actividades = $elementos;
		return true;
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Método descripción del ef popup info_escala, que sólo se utiliza para mostrar una imagen.
	 */
	function metodo($input)
	{
		return '';
	}

    //-----------------------------------------------------------------------------------
	//---- PANTALLA: Actividades 
	//-----------------------------------------------------------------------------------

	function conf__actividades(toba_ei_pantalla $pantalla)
	{
          // Datos para el ev de información
		$datos_destino = $this->get_seleccion_valor('info_destino');
		$datos_filtro = $this->get_seleccion(array('resp_acad_nombre', 'propuesta_nombre', 'anio_segun_plan_lista', 'nombre_periodo_generico', 'inscripcion_habilitada', 'instancia_nombre', 'modalidad_nombre', 'criterio_gen_descr'));
		
		// Datos para el ev de información		
		$this->ev_info_destino()->set_datos($datos_destino);
		$this->ev_info()->set_datos($datos_filtro);
        
		// Mensaje de confirmación y etiqueta de botón
		$anio = $datos_destino['anio_academico_destino'];
		$periodo = $datos_destino['periodo_lectivo_nombre'];
		$mensaje = guarani_mensajes::get($this->mje_confirma_generacion, array($anio, $periodo));
		$this->evento('cambiar_tab__siguiente')->set_msg_confirmacion(toba::escaper()->escapeHtml($mensaje));
		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Generar');
		$this->evento('cambiar_tab__siguiente')->set_imagen('/generar.png');
	}
    
	//-----------------------------------------------------------------------------------
	//---- cuadro_actividades -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
        
	function conf__cuadro_actividades(cuadro_seleccion_multiple $cuadro)
	{
        return $this->s__actividades;
	}

	function evt__cuadro_actividades__seleccion($seleccion)
	{
        $this->s__actividades_seleccionadas = $seleccion;
	}

	function conf_evt__cuadro_actividades__seleccion(toba_evento_usuario $evento, $fila)
	{
		if (!empty($this->s__actividades_seleccionadas)) {
			$key_seleccionadas = aplanar_matriz($this->s__actividades_seleccionadas, 'elemento');
			$datos_cuadro = $this->dep('cuadro_actividades')->get_datos();
			$evento->set_check_activo(in_array($datos_cuadro[$fila]['elemento'], $key_seleccionadas));
		}
	}
    
    function validar_actividades()
    {
        if (empty($this->s__actividades_seleccionadas)) {
            $mje = guarani_mensajes::get('800_no_seleccion', array(' una actividad'));
            throw guarani::error($mje);
        }
    }


    //-----------------------------------------------------------------------------------
	//---- PANTALLA: FINALIZAR 
	//-----------------------------------------------------------------------------------

	function conf__finalizar(toba_ei_pantalla $pantalla)
	{
		// Datos para el ev de información
		$datos_destino = $this->get_seleccion_valor('info_destino');
		$datos_filtro = $this->get_seleccion(array('resp_acad_nombre', 'propuesta_nombre', 'anio_segun_plan_lista', 'nombre_periodo_generico', 'inscripcion_habilitada', 'instancia_nombre', 'modalidad_nombre', 'criterio_gen_descr'));
		$this->ev_info_destino()->set_datos($datos_destino);
		$this->ev_info()->set_datos($datos_filtro);

		// Se oculta el botón Anterior
		$this->evento('cambiar_tab__anterior')->ocultar();
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__finalizar()
	{
		$this->limpiar_memoria();
		$this->set_pantalla('periodo_lectivo_destino');
	}

	//-----------------------------------------------------------------------------------
	//---- form_totales -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_totales(form_totales $form)
	{
		$form->set_solo_lectura();
	}

	function extender_objeto_js()
	{
		echo "
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.set_evento = function(evento, hacer_submit, input)
		{
			if (typeof hacer_submit == 'undefined') {
				hacer_submit = true;
			}
			ei.prototype.set_evento.call(this, evento, hacer_submit);
		}";
	}
}
?>