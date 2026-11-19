<?php
/**
 *
 *
 * @package Exámenes
 * @subpackage Mesas de Examen
 */
class ci_nav_generacion_masiva_mesas extends ci_nav_procesos_bk_wizzard
{
	//Filtros y Datos Filtros
	protected $s__filtro_ra;
	protected $s__filtro_propuesta;
	protected $s__datos_filtro_ra;
	protected $s__datos_filtro_propuesta;
	
	//Actividades a generarles las mesas
	protected $s__actividades;
	
	//Temporales
	protected $datos_turno_destino_temp;
	protected $datos_mesas_temp;
	protected $datos_param_temp;
	
	//Elementos Visuales de Información
	protected $ev_info_destino;
	protected $ev_info_filtros;
	
	protected $mje_confirma_generacion = '800exa_generar_mesa';
	
	//Flags para eliminar la selección al filtrar
	protected $seleccion_ras_filtrada = false;
	protected $seleccion_props_filtrada = false;

	function conf()
	{
		$this->indx_msg_eof = '800_eof_cuadro';
        toba_ef_radio::set_maximo_descripcion(100);		
		parent::conf();
	}
	
	function get_descripciones_parametros()
	{
		$datos_descr = array();
		$datos_descr['catedras'] = $this->get_descripcion_catedras();
		$datos_descr['criterio_generacion'] = $this->get_descripcion_criterio_generacion();
		return $datos_descr;
	}
	
	function get_descripciones_asignacion()
	{
		$datos_descr = $this->get_descripciones_parametros();
		$datos_descr['fecha_mesa'] = $this->get_descripcion_fecha_mesa();
		$datos_descr['docentes'] = $this->get_descripcion_docentes();
		return $datos_descr;
	}
	
	function get_descripcion_catedras()
	{
		switch ($this->get_seleccion_valor('mesa_por_catedra')) {
			case mesa_de_examen::no_considerar_catedras:
				return mesa_de_examen::no_considerar_catedras_desc;
			case mesa_de_examen::una_mesa_por_catedra:
				return mesa_de_examen::una_mesa_por_catedra_desc;
			case mesa_de_examen::una_mesa_por_catedra_si_existe:
				return mesa_de_examen::una_mesa_por_catedra_si_existe_desc;
		}
	}
	
	function get_descripcion_criterio_generacion()
	{
		return $this->get_seleccion_valor('criterio_generacion') == mesa_de_examen::generar_mesa_siempre ? mesa_de_examen::generar_mesa_siempre_desc : mesa_de_examen::no_generar_mesa_si_existe_desc;
	}
	
	function get_descripcion_fecha_mesa()
	{
		switch ($this->get_seleccion_valor('fecha')) {
			case mesa_de_examen::fecha_inicio_llamado:
				return mesa_de_examen::fecha_inicio_llamado_desc;
			case mesa_de_examen::fecha_definida_actividad:
				return mesa_de_examen::fecha_definida_actividad_desc;
			case mesa_de_examen::fecha_definida_catedra:
				return mesa_de_examen::fecha_definida_catedra_desc;
		}
	}
	
	function get_descripcion_docentes()
	{
		switch ($this->get_seleccion_valor('docentes')) {
			case mesa_de_examen::docentes_no_asignar:
				return mesa_de_examen::docentes_no_asignar_desc;
			case mesa_de_examen::docentes_tribunal:
				return mesa_de_examen::docentes_tribunal_desc;
			case mesa_de_examen::docentes_catedra:
				return mesa_de_examen::docentes_catedra_desc;
		}
	}
	
	/*
	 * Se maneja el avance de pantalla.
	 */
	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();
		
		//Si clickeó el boton 'Generar Mesas' entonces creo masivamente las Mesas
		if ($this->s__pantalla_actual == 'asignacion') {
			
			//Valores
			$destino = $this->get_seleccion_valor('info_destino');
			$planes	 = $this->get_seleccion_valor('plan');
			$fecha_mesas	= $this->get_seleccion_valor('fecha');
			$docentes		= $this->get_seleccion_valor('docentes');
			$criterio_gen	= $this->get_seleccion_valor('criterio_generacion');
			$instancias		= $this->get_seleccion_valor('instancia');
			$escalas_notas	= $this->get_seleccion_valor('escala_nota');
			$modalidades	= $this->get_seleccion_valor('modalidad');
			$mesa_x_catedra = $this->get_seleccion_valor('mesa_por_catedra');
			
			//Armo el array con los parámetros a mandar en la creación del proceso
			$parametros = array_merge(	array('anio' 		=> $destino['anio_academico']),
										array('turno' 		=> $destino['turno_examen']),
										array('ubicacion' 	=> $destino['ubicacion']),
										array('planes'		=> $planes),
										array('fecha_mesas' => $fecha_mesas),
										array('docentes' 	=> $docentes),
										array('criterio_gen'	=> $criterio_gen),
										array('instancias' 		=> $instancias),
										array('escalas_notas' 	=> $escalas_notas),
										array('modalidades'		=> $modalidades),
										array('mesa_x_catedra' 	=> $mesa_x_catedra),
										array('actividades'		=> $this->s__actividades));
			
            //Ejecuto el proceso en background
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
		//Se retorna el array de resultados con las cantidades totales como respuesta
		$resultados = $this->cn()->get_resultado_proceso(pro_generacion_mesas::reporte_resultados);
		if (!empty($resultados['mensajes'])) {
			$respuesta->set(current($resultados['mensajes']));
		}
	}
	
	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		$mostrar_cant = false;
		switch ($params['evento']) {
			case 'rep_actividades':
				$reporte				= pro_generacion_mesas::reporte_actividades;
				$operacion				= '38000081';
				$ind_datos_impresion	= mesa_de_examen::ind_actividades_mesas;
				$mostrar_cant 			= true;
			break;
			case 'rep_mesas':
				$reporte				= pro_generacion_mesas::reporte_mesas;
				$operacion				= '38000097';
				$ind_datos_impresion	= mesa_de_examen::ind_mesas_llamados;
				$mostrar_obs			= false;
			break;
			case 'rep_mesas_con_obs':
				$reporte				= pro_generacion_mesas::reporte_mesas_con_obs;
				$operacion				= '38000097';
				$ind_datos_impresion	= mesa_de_examen::ind_mesas_llamados;
				$mostrar_obs			= true;
			break;
			case 'rep_actividades_sin_mesas':
				$reporte				= pro_generacion_mesas::reporte_actividades_sin_mesas;
				$operacion				= '38000081';
				$ind_datos_impresion	= mesa_de_examen::ind_actividades_mesas;
				$mostrar_cant 			= false;
		}

        if ($this->modo_visualizacion()) {
            $datos = $this->controlador()->get_resultado_reporte($reporte);
        } else {
            //Obtengo el Proceso para pedirle el log de resultados
            $datos = $this->cn()->get_resultado_proceso($reporte);
        }
		
		//Obtengo los datos correspondientes al encabezado del reporte.
		$destino = $this->get_seleccion_valor('info_destino');
		$conf	 = array(mesa_de_examen::ind_encab_anio	 => $destino['anio_academico'],
						 mesa_de_examen::ind_encab_turno => $destino['turno_examen_nombre']);
		
		//Almaceno los datos en la instasncia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array($ind_datos_impresion => $datos['mensajes'], mesa_de_examen::mostrar_cant => $mostrar_cant));
		guarani_datos_impresion::instancia()->set_conf($conf);
		guarani_datos_impresion::instancia()->guardar_datos_imp();
		
		//Creo el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, [], array('celda_memoria'=>'popup'));
		$respuesta->set($vinculo);
	}
	
	//-----------------------------------------------------------------------------------
	//---- TURNO DESTINO ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- form_periodo_destino ----------------------------------------------------------
	
	function conf__form_destino(toba_ei_formulario $form)
	{
		if (isset($this->datos_turno_destino_temp)) {
			$this->set_hubo_cambios();
			$form->set_datos($this->datos_turno_destino_temp);
		} elseif (($this->existe_seleccion('info_destino'))) {
			$datos = $this->get_seleccion_valor('info_destino');
			$form->set_datos($datos);
		}
	}

	function evt__form_destino__modificacion($datos)
	{
		$this->datos_turno_destino_temp = $datos;
		
		//Hallo datos a mostrar: turno/ubicacion
		$turno_exa	= toba::consulta_php('co_turnos_de_examen')->get_nombre_turno_examen($datos['turno_examen']);
		$datos_ubi	= toba::consulta_php('co_ubicaciones')->get_ubicacion_info($datos['ubicacion']);
		$ubicacion	= $datos_ubi['ubicacion_nombre'];
		
		//Armo el array para setearlo en el ev de información
		$datos['turno_examen_nombre'] = $turno_exa;
		$datos['ubicacion_nombre'] = $ubicacion;
		
		//Seteo los datos
		$this->set_seleccion(array('info_destino' => $datos));
	}
	
	//---- filtro_ras -------------------------------------------------------------------
	
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
			$this->seleccion_ras_filtrada = true;
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
	
	//---- cuadro_ras -------------------------------------------------------------------
	
	function conf__cuadro_ras(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_responsables_academicas');
		$indx_msj = (isset($this->s__filtro_ra) && $this->s__filtro_ra != '') ? $this->indx_msg_eof.'_filtrado' : $this->indx_msg_eof;
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta->get_enf('plural'))));

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
	
	//Validación de salida
	function validar_turno_destino()
	{
		//Valido que se haya seleccionado alguna Responsable Académica
		if ($this->falta_seleccionar('responsable_academica')) {
			$mje_sin_ra = guarani_mensajes::get('800_no_seleccion', array('una Responsable Académica'));
			throw guarani::error($mje_sin_ra);
		}
		
		//Valido que el Turno destino seleccionado tenga Llamados definidos
		if (!$this->turno_con_llamados()) {
			$mje_turno_sin_llamado = guarani_mensajes::get('800EXA_turno_sin_llamados');
			throw guarani::error($mje_turno_sin_llamado);
		}
	}
	
	function turno_con_llamados()
	{
		$turno_examen = $this->get_seleccion_valor('turno_examen');
		return toba::consulta_php('co_turnos_de_examen')->es_turno_con_llamados($turno_examen);
	}
	
	//-----------------------------------------------------------------------------------
	//---- PROPUESTAS -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__propuestas(toba_ei_pantalla $pantalla)
	{
		//Datos para el ev de información
		$datos_destino = $this->get_seleccion_valor('info_destino');
		$datos_filtro = $this->get_seleccion('resp_acad_nombre');
		
		//Seteo de datos en ev
		$this->ev_info_destino()->set_datos($datos_destino);
		$this->ev_info_filtros()->set_datos($datos_filtro);
		
		//Si se filtró por propuestas entonces se limpia la selección previa.
		if ($this->seleccion_props_filtrada) {
			$this->eliminar_seleccion_previa();
		}
	}
	
	//---- filtro_propuestas ------------------------------------------------------------
	
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
			$this->seleccion_props_filtrada = true;
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
	
	//---- cuadro_propuestas ------------------------------------------------------------
	
	function conf__cuadro_propuestas(toba_ei_cuadro $cuadro)
	{
		$info_destino = $this->get_seleccion_valor('info_destino');
		$ras		= $this->get_seleccion_valor('responsable_academica');
		$cond_ubicacion	= "sga_propuestas.propuesta IN (SELECT sga_propuestas_oferta.propuesta FROM sga_propuestas_oferta WHERE sga_propuestas_oferta.ubicacion = {$info_destino['ubicacion']})";
		$filtro_ras = guarani_sql::armar_condicion_in($ras, 'sga_propuestas_ra', 'responsable_academica', 'responsable_academica');
		$cond_ras	= "sga_propuestas.propuesta IN 
							(SELECT sga_propuestas_ra.propuesta 
							 FROM sga_propuestas_ra 
							 WHERE $filtro_ras)";

		$cond_ubic_ra = $cond_ubicacion . ' AND ' . $cond_ras;
		$filtro_prop = ($this->s__filtro_propuesta == '') ? $cond_ubic_ra : $cond_ubic_ra . ' AND ' . $this->s__filtro_propuesta;

		if ($this->s__filtro_propuesta == '' || !isset($this->s__filtro_propuesta)) {
			$indx_msj = $this->indx_msg_eof;
		} else {
			$indx_msj = $this->indx_msg_eof.'_filtrado';
		}

		//Seteo de datos y mensaje
		$datos = toba::consulta_php('co_propuestas')->get_planes_activos_con_propuestas_en_versiones_activas($filtro_prop);
		$msj = guarani_mensajes::get($indx_msj, array('Propuestas'));
		$cuadro->set_eof_mensaje($msj);
		$cuadro->set_datos($datos);

		//Si no hay propuestas seleccionadas se eligen todas por defecto
		if (!$this->existe_seleccion('plan')) {
			$this->set_seleccion($datos);
		}
	}

	function evt__cuadro_propuestas__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_propuestas__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_propuestas', $fila));
	}
	
	//Validación de salida
	function validar_propuestas()
	{
		//Valido que se haya seleccionado alguna Propuesta
		if ($this->falta_seleccionar('plan')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Propuesta'));
			throw guarani::error($mje);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PARÁMETROS GENERALES ---------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__parametros(toba_ei_pantalla $pantalla)
	{
		//Datos para el ev de información
		$datos_destino = $this->get_seleccion_valor('info_destino');
		$datos_filtro = $this->get_seleccion(array('resp_acad_nombre', 'propuesta_plan'));
		
		//Seteo de datos en ev
		$this->ev_info_destino()->set_datos($datos_destino);
		$this->ev_info_filtros()->set_datos($datos_filtro);
	}
	
	//---- ml_instancias ----------------------------------------------------------------
	
	function conf__ml_instancias(toba_ei_formulario_ml $form_ml)
	{
		if ($this->existe_seleccion('insts')) {
			$datos = current($this->get_seleccion('insts'));
		} else {
			$datos = toba::consulta_php('co_mesas_de_examen')->get_instancias_aplicables_a_mesa();
		}
		
		$icono_informacion = new icono_info_escala_nota();
		$form_ml->ef('escala_nota')->agregar_icono_utileria($icono_informacion);
		$form_ml->set_datos($datos);
	}
	
	function evt__ml_instancias__modificacion($datos)
	{
		$instancias = array_filter($datos, array('ci_nav_generacion_masiva_mesas', 'es_requerido'));
		
		if (!empty($instancias)) {
			
			//Guardo el arreglo entero para setearlo al volver (Requeridos y no requeridos)
			$this->set_seleccion(array('insts' => $datos));
			
			//Seteo de datos para el ev y para mandar como parámetro al proceso
			$this->set_seleccion($instancias);
		} else {
			$this->eliminar_seleccion_previa('parametros', 'insts');
		}
	}
	
	function es_requerido($valor)
	{
		return $valor['requerido'];
	}
	
	//---- ml_modalidades ---------------------------------------------------------------
	
	function conf__ml_modalidades(toba_ei_formulario_ml $form_ml)
	{
		if ($this->existe_seleccion('mods')) {
			$datos = current($this->get_seleccion('mods'));
		} else {
			$datos = toba::consulta_php('co_alumnos')->get_modalidades_cursadas_alumnos();
		}
		$form_ml->set_datos($datos);
	}

	function evt__ml_modalidades__modificacion($datos)
	{
		$modalidades = array_filter($datos, array('ci_nav_generacion_masiva_mesas', 'es_requerido'));
		
		if (!empty($modalidades)) {
			
			//Guardo el arreglo entero para setearlo al volver (Requeridos y no requeridos)
			$this->set_seleccion(array('mods' => $datos));
			
			//Seteo de datos para el ev y para mandar como parámetro al proceso
			$this->set_seleccion($modalidades);
		} else {
			$this->eliminar_seleccion_previa('parametros', 'mods');
		}
	}
	
	//---- form_parametros --------------------------------------------------------------
	
	function conf__form_parametros(form_parametros $form)
	{
		if (isset($this->datos_param_temp)) {
			//Seteo de datos temporales por un error
			$this->set_hubo_cambios();
			$form->set_datos($this->datos_param_temp);
		} else {
			//Selecciones previas
			$sel_por_catedras = $this->existe_seleccion('mesa_por_catedra');
			$sel_criterio_gen = $this->existe_seleccion('criterio_generacion');
			$sel_versiones_no_vigentes = $this->existe_seleccion('versiones_no_vigentes');
			if ($sel_por_catedras && $sel_criterio_gen && $sel_versiones_no_vigentes) {
				//Seteo de datos previamente guardados por un retroceso en la pantalla
				$datos = $this->get_seleccion(array('mesa_por_catedra', 'criterio_generacion', 'versiones_no_vigentes'));
				$this->eliminar_seleccion_previa();
				$form->set_datos($datos);
			}
		}
	}

	function evt__form_parametros__modificacion($datos)
	{
        if ($datos) {
			$this->datos_param_temp = $datos;
			$this->set_seleccion($datos);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}
	
	//Validación de salida
	function validar_parametros()
	{
		//Valido que existan Instancias y Modalidades seleccionadas
		if ($this->falta_seleccionar('insts') || $this->falta_seleccionar('mods')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Instancia y una Modalidad'));
			throw guarani::error($mje);
		}
		
		//Valido que existan actividades a los cuales generarles las Mesas
		if (!$this->existen_actividades()) {
			$mje_sin_elem = guarani_mensajes::get('800EXA_copiar_no_hay_actividades');
			throw guarani::error($mje_sin_elem);
		}
	}
	
	/*
	 * Verifica si existen Actividades con los filtros seleccionados.
	 * Como efecto colateral setea la variable de sesión que las guarda.
	 */
	function existen_actividades()
	{
		//Obtengo las Actividades a las que se les generarán las Mesas
		$responsables_academicas = $this->get_seleccion_valor('responsable_academica');
		$modalidades = $this->get_seleccion_valor('modalidad');
		$planes = $this->get_seleccion_valor('plan');
		$versiones_no_vigentes = $this->get_seleccion_valor('versiones_no_vigentes');
		$actividades = toba::consulta_php('co_mesas_de_examen')->get_actividades_de_planes_versiones($responsables_academicas, $modalidades, $planes, $versiones_no_vigentes);
		
		if (empty($actividades)) {
			//No existen Actividades
			return false;
		}
		
		//Existen Actividades, las guardo para posteriormente generarles las Mesas
		$this->s__actividades = $actividades;
		return true;
	}
	
	//-----------------------------------------------------------------------------------
	//---- ASIGNACIÓN -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__asignacion(toba_ei_pantalla $pantalla)
	{
		//Datos para el ev de información
		$datos_destino = $this->get_seleccion_valor('info_destino');
		$datos_filtro = $this->get_seleccion(array('resp_acad_nombre', 'propuesta_plan', 'instancia_nombre', 'modalidad_nombre'));
		$datos_param = $this->get_descripciones_parametros();
		
		//Seteo de datos en el ev
		$this->ev_info_destino()->set_datos($datos_destino);
		$this->ev_info_filtros()->set_datos(array_merge($datos_filtro, $datos_param));
		
		//Seteo del mensaje de confirmación de la generación y etiqueta del botón
		$anio = $datos_destino['anio_academico'];
		$turno = $datos_destino['turno_examen_nombre'];
		$mensaje = guarani_mensajes::get($this->mje_confirma_generacion, array($anio, $turno));        
		$this->evento('cambiar_tab__siguiente')->set_msg_confirmacion(toba::escaper()->escapeHtml($mensaje));
		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Generar Mesas');
		$this->evento('cambiar_tab__siguiente')->set_imagen('/generar.png');
	}
	
	//---- form_asignacion --------------------------------------------------------------
	
	function conf__form_asignacion(form_asignacion $form)
	{
		//Opciones para los radio buttons, dependen de si se consideran a las Cátedras o no
		$mesa_x_catedra = $this->get_seleccion_valor('mesa_por_catedra');
		$opciones_fecha = toba::consulta_php('co_mesas_de_examen')->get_opciones_fecha_hora_mesa($mesa_x_catedra);
		$opciones_docentes = toba::consulta_php('co_mesas_de_examen')->get_opciones_docentes($mesa_x_catedra);
		
		//Seteo de opciones para los efs radio fecha y docentes
		$form->ef('fecha')->set_opciones($opciones_fecha);
		$form->ef('docentes')->set_opciones($opciones_docentes);
	}

	function evt__form_asignacion__modificacion($datos)
	{
		$this->set_seleccion($datos);
	}
	
	//-----------------------------------------------------------------------------------
	//---- RESULTADOS -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__resultados(toba_ei_pantalla $pantalla)
	{
		//Oculto el boton Anterior e inhabilito la edición del formulario
		$this->evento('cambiar_tab__anterior')->ocultar();
		
		//Datos para el ev de información
		$datos_destino = array_slice($this->get_seleccion(), 0, 5);
		$datos_filtro = $this->get_seleccion(array('resp_acad_nombre', 'nombre', 'instancia_nombre', 'modalidad_nombre'));
		$datos_asigna = $this->get_descripciones_asignacion();//Recupera también las descripciones de los parámetros generales
		
		//Seteo de datos en el ev
		$this->ev_info_destino()->set_datos($datos_destino);
		$this->ev_info_filtros()->set_datos(array_merge($datos_filtro, $datos_asigna));
	}
	
	//---- form_totales -----------------------------------------------------------------
	
	function conf__form_totales(toba_ei_formulario $form)
	{
		//Se inhabilita la edición del formulario que se utiliza solo para mostrar los totales resultantes del proceso.
		$form->set_solo_lectura();
		/**
		 * Para los evento de Reporte de Mesas creadas con éxito, creadas con Observaciones y de Actividades con y sin 
		 * cantidad de Mesas, se setean parámetros que le indican a cada item de popup al que se invoca si mostrar o no 
		 * las Observaciones y la cantidad de Mesas.
		 */
		/*$form->evento('rep_mesas')->vinculo()->set_parametros(array(mesa_de_examen::mostrar_observaciones=>false));
		$form->evento('rep_mesas_con_obs')->vinculo()->set_parametros(array(mesa_de_examen::mostrar_observaciones=>true));
		$form->evento('rep_actividades')->vinculo()->set_parametros(array(mesa_de_examen::mostrar_cant=>true));	
		$form->evento('rep_actividades_sin_mesas')->vinculo()->set_parametros(array(mesa_de_examen::mostrar_cant=>false));*/
	}
	
	//---- Eventos ----------------------------------------------------------------------
	
	function evt__volver()
	{
		$this->limpiar_memoria();
		$this->set_pantalla('turno_destino');
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_destino()
	{
		if (!isset($this->ev_info_destino)) {
			$this->ev_info_destino = guarani::ev_info_datos();
			$this->ev_info_destino->set_controlador($this);
			$this->ev_info_destino->set_titulo('Filtro para las Mesas');
			$this->ev_info_destino->set_td_etiqueta_width('207px');
			$this->ev_info_destino->set_columnas_a_mostrar(array('anio_academico', 'turno_examen_nombre', 'ubicacion_nombre'));
			$this->ev_info_destino->set_columnas_fila(array('anio_academico', 'turno_examen_nombre'), 1);
			$this->ev_info_destino->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info_destino->set_etiqueta('turno_examen_nombre', 'Turno');
			$this->ev_info_destino->set_etiqueta('ubicacion_nombre', 'Ubicación');
		}
		return $this->ev_info_destino;
	}
	
	function ev_info_filtros()
	{
		if (!isset($this->ev_info_filtros)) {
			$this->ev_info_filtros = guarani::ev_info_datos();
			$this->ev_info_filtros->set_controlador($this);
			$this->ev_info_filtros->set_max_datos_por_columna(10);
			$this->ev_info_filtros->set_max_chars_por_columna(110);
			$this->ev_info_filtros->set_td_etiqueta_width('207px');
			$this->ev_info_filtros->set_columnas_a_mostrar(array('resp_acad_nombre', 'propuesta_plan', 'instancia_nombre', 'modalidad_nombre', 'catedras', 'criterio_generacion', 'fecha_mesa', 'docentes'));
			$this->ev_info_filtros->set_etiqueta('resp_acad_nombre', 'Responsables Académicas');
			$this->ev_info_filtros->set_etiqueta('propuesta_plan', 'Propuestas');
			$this->ev_info_filtros->set_etiqueta('instancia_nombre', 'Instancias');
			$this->ev_info_filtros->set_etiqueta('modalidad_nombre', 'Modalidades');
			$this->ev_info_filtros->set_etiqueta('catedras', 'Cátedras');
			$this->ev_info_filtros->set_etiqueta('criterio_generacion', 'Criterio para la Generación');
			$this->ev_info_filtros->set_etiqueta('fecha_mesa', 'Fecha y Hora de la Mesa');
			$this->ev_info_filtros->set_etiqueta('docentes', 'Docentes');
		}
		return $this->ev_info_filtros;
	}
	
	//Método descripción del ef popup escala, que sólo se utiliza para mostrar una imagen.
	function metodo ($input)
	{
		return '';
	}

	function get_planes_estados_activos()
	{
		$filtro = "sga_planes_estados.estado IN ('".planes::estado_activo_vigente."','".planes::estado_activo_no_vigente."')";
		return toba::consulta_php('co_planes')->get_planes_estados($filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		$msj_confirm_filtrar = guarani_mensajes::get('800_confirmar_filtro_pierde_seleccion');
		$id_pantalla = $this->get_id_pantalla();
		$generar_js_filtro  = false;
		
		if ($id_pantalla == 'propuestas') {
			
			//Se debe generar el codigo js para el evento de filtrar las propuestas
			$generar_js_filtro = true;
			$filtro = 'filtro_propuestas';
			$cuadro = 'cuadro_propuestas';
		} elseif (($id_pantalla == 'turno_destino')) {
			
			//Se debe generar el codigo js para el evento de filtrar las responsables académicas
			$generar_js_filtro = true;
			$filtro = 'filtro_ras';
			$cuadro = 'cuadro_ras';
		}
		
		if ($generar_js_filtro) {
			
			$id_js_filtro = $this->dependencia($filtro)->get_id_objeto_js();
			$id_js_cuadro = $this->dependencia($cuadro)->get_id_objeto_js();
			
			echo "
				{$id_js_filtro}.evt__filtrar = function() {
					var seleccion = {$id_js_cuadro}.get_ids_seleccionados('seleccion');
					if (seleccion.length != 0) {
						return confirmar_filtrado();
					}
				}
				
				function confirmar_filtrado(proyecto, operacion, url, es_popup) {
					if (!es_popup || typeof(es_popup) == 'undefined') {
						resp = confirm('$msj_confirm_filtrar');
						if (resp) {
							return true;
						} else {
							return false;
						}
					}
					return true;
				}
			";
		}
	}

}
?>