<?php
/**
 *
 *
 * @package		Matrícula
 * @subpackage 	Actualizaciones
 * 
 */
class ci_nav_generacion_legajos extends ci_nav_procesos_bk_wizzard
{
	// Componente Visual de Información.
	protected $ev_info;
	
	// Datos de las inscripciones.
	protected $s__inscripciones;
	protected $s__datos_form_inscriptos;
	
	// Parámetros
	protected $s__param_legajo_unico;
	
	protected $s__mostrar_inscriptos_gen_automatica = false;
	protected $s__forma_generacion;
	
	// Datos para autocompletado.
	protected $autocompletar;
	protected $datos_a_completar;

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'nombre', 'entidad_descripcion', 'ubicacion_nombre', 'modalidad_nombre', 'propuesta_nombre', 'inscriptos'));
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_max_chars_por_columna(80);

			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico:');
			$this->ev_info->set_etiqueta('nombre', 'Período de Inscripción:');
			$this->ev_info->set_etiqueta('entidad_descripcion', 'Responsables Académicas:');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicaciones:');
			$this->ev_info->set_etiqueta('modalidad_nombre', 'Modalidades:');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuestas:');
			$this->ev_info->set_etiqueta('inscriptos', 'Inscriptos:');
			$this->ev_info->set_style('width: 100%');
			$this->ev_info->set_td_etiqueta_width(130);
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

	/**
	 * Redefinición de la forma de obtener las selecciones, porque el año académico
	 * siempre será el mismo (depende de un filtro obligatorio).
	 */
	function get_seleccion_info($claves = null)
	{
		$seleccion = parent::get_seleccion($claves);
		if (isset($seleccion['anio_academico'])) {
			$seleccion['anio_academico'] = array($seleccion['anio_academico'][0]);
		}
		if (isset($seleccion['propuesta_nombre'])) {
			$seleccion['propuesta_nombre'] = array_unique($seleccion['propuesta_nombre']);
		}
		return $seleccion;
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->indx_msg_eof = '800_eof_cuadro';
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Períodos 
	//-----------------------------------------------------------------------------------
	
	function conf__periodo_insc(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro_periodo_insc ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_periodo_insc(guarani_ei_filtro $filtro)
	{
		$filtro->set_sin_filtrado_inicial();
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
		if ($this->filtro_esta_cargado('filtro_periodo_insc')) {
			$datos = guarani::co('co_periodos_de_inscripcion')->get_periodos_inscripcion_para_gen_legajos($this->get_filtro('filtro_periodo_insc'));
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_filtro_msg_eof('filtro_periodo_insc', 'sga_periodos_inscripcion'));
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
	//---- PANTALLA Resp. Académicas 
	//-----------------------------------------------------------------------------------

	function conf__resp_acad(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_seleccion_info());
		
		$this->cn()->resetear_atributos_ras();
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
			$ras = aplanar_matriz($seleccion, 'responsable_academica');
			$this->cn()->cargar_atributos_parametros_ras($ras);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function validar_resp_acad()
	{
		if (guarani_arreglos::count($this->get_seleccion('responsable_academica')) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_responsables_academicas');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Ubicaciones 
	//-----------------------------------------------------------------------------------

	function conf__ubicaciones(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_seleccion_info());
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

	function conf__cuadro_ubicaciones($cuadro)
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

	//-----------------------------------------------------------------------------------
	//---- cuadro_modalidades -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_modalidades(toba_ei_cuadro $cuadro)
	{
		$datos = guarani::co('co_planes')->get_modalidades();
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

	function validar_ubicaciones()
	{
		if (guarani_arreglos::count($this->get_seleccion('ubicacion')) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_ubicaciones');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
		if (guarani_arreglos::count($this->get_seleccion('modalidad')) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_modalidad_cursada');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Propuestas 
	//-----------------------------------------------------------------------------------

	function conf__propuestas(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_seleccion_info());
		$descr = "Se listan propuestas para las cuales, además de respetar los criterios seleccionados anteriormente, el parámetro <strong>mat_utiliza_legajo</strong> está definido en <strong>'A los alumnos de la propuesta se les genera un número de legajo.'</strong><br/><br/>
				  Para pasar a la siguiente etapa, deben seleccionarse propuestas con idéntica forma de generación de legajo (manual o automática).";
		$pantalla->set_descripcion($descr);
		$this->cn()->resetear_atributos_propuestas();
		$this->s__mostrar_inscriptos_gen_automatica = false;
		unset($this->s__forma_generacion);
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
		$modalidades = $this->get_seleccion('modalidad');
		$filtro_modalidad = ' AND sga_planes.plan IN (	SELECT	sga_planes_modalidad.plan
														FROM	sga_planes_modalidad
														WHERE	' . guarani_sql::armar_condicion_in($modalidades['modalidad'], 'sga_planes_modalidad', 'modalidad') . ')';
		$filtro = empty($filtro) ? 'true' : $filtro;
		$filtro .= $filtro_ra . $filtro_ubicacion . $filtro_modalidad;

		$datos = guarani::co('co_propuestas')->get_propuestas($filtro);
		$datos_cuadro = array();
		foreach ($datos as $dato) {
			// El valor del parámetro 'mat_utiliza_legajo' para la propuesta del alumno debe ser 'S'.
			if (guarani::parametros()->get_valor('mat_utiliza_legajo', array($dato['propuesta'])) == comunes::si) {
				$dato['forma_generacion'] = guarani::parametros()->get_valor('mat_legajo_alumno_generacion', array($dato['propuesta']));
				$dato['forma_generacion_descr'] = legajos::get_descripcion_forma_generacion($dato['forma_generacion']);				
				$datos_cuadro[] = $dato;
			}
		}
		$cuadro->set_datos($datos_cuadro);

		$cuadro->set_eof_mensaje($this->get_filtro_msg_eof('filtro_propuestas', 'sga_propuestas'));
	}

	function evt__cuadro_propuestas__seleccion($seleccion)
	{
		$this->validar_seleccion_forma_generacion($seleccion);
		if (!empty($seleccion)) {
			$this->s__forma_generacion = $seleccion[0]['forma_generacion'];
		}
		
		if ($seleccion) {
			$this->set_seleccion($seleccion);
			$propuestas = aplanar_matriz($seleccion, 'propuesta');
			$this->cn()->cargar_atributos_parametros_propuestas($propuestas);
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
	
	protected function validar_seleccion_forma_generacion($seleccion)
	{
		$hay_manual = false;
		$hay_automatico = false;
		foreach($seleccion as $propuesta) {
			if ($propuesta['forma_generacion'] == legajos::generacion_manual) {
				$hay_manual = true;
			} elseif ($propuesta['forma_generacion'] == legajos::generacion_automatica) {
				$hay_automatico = true;
			}
			if ($hay_manual && $hay_automatico) {
				throw guarani::error('Deben seleccionarse propuestas con idéntica forma de generación de legajo (manual o automática).');
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Inscriptos 
	//-----------------------------------------------------------------------------------

	function conf__inscriptos(toba_ei_pantalla $pantalla)
	{
		if (!isset($this->s__param_legajo_unico)) {
			$this->s__param_legajo_unico = guarani::parametros()->get_valor('mat_legajo_alumno_unico');
		}
		
		$this->ev_info()->set_datos($this->get_seleccion_info());
		$pantalla->eliminar_evento('cambiar_tab__siguiente');
				
		// Se obtiene el listado de inscripciones acorde a las selecciones previas
		$ubicacion = $this->get_seleccion('ubicacion');
		$where = guarani_sql::armar_condicion_in($ubicacion['ubicacion'], 'sga_alumnos', 'ubicacion');

		$modalidad = $this->get_seleccion('modalidad');
		$where .= ' AND ' . guarani_sql::armar_condicion_in($modalidad['modalidad'], 'sga_alumnos', 'modalidad');

		$plan_version = $this->get_seleccion('plan_version');
		$where .= ' AND ' . guarani_sql::armar_condicion_in($plan_version['plan_version'], 'sga_alumnos', 'plan_version');

		$periodos_inscripcion = $this->get_seleccion('periodo_inscripcion');
		$where .= ' AND ' . guarani_sql::armar_condicion_in($periodos_inscripcion['periodo_inscripcion'], 'sga_periodos_inscripcion_fechas', 'periodo_inscripcion');
		
		$propuestas = $this->get_seleccion('propuesta');
		$where .= ' AND ' . guarani_sql::armar_condicion_in($propuestas['propuesta'], 'sga_propuestas', 'propuesta');
		
		$inscripciones = guarani::co('co_alumnos')->get_alumnos_inscriptos_gen_legajo($where);
		$this->s__inscripciones = array();
		
		foreach ($inscripciones as $key => $dato) {
			if ($this->s__param_legajo_unico == comunes::si) {
				$dato['legajo'] = $dato['legajo_otra_propuesta'];
			}
			
			$dato['requerido'] = 1;
			
			// Se obtiene el parámetro correspondiente a la propuesta de cada inscripción
			$param_inscripciones_a_generar = guarani::parametros()->get_valor('mat_inscripciones_a_generar_legajo', array($dato['propuesta']));
			switch ($param_inscripciones_a_generar) {
				case 'A': // Se genera para todas las inscripciones aceptadas
					if ($dato['propuesta_inscripcion'] == propuesta::inscripcion_aceptada) {
						$this->s__inscripciones[] = $dato;
					}
				break;
				case 'T': // Se genera para todas las inscripciones (aceptadas y pendientes)
					if (($dato['propuesta_inscripcion'] == propuesta::inscripcion_aceptada) || ($dato['propuesta_inscripcion'] == propuesta::inscripcion_pendiente)) {
						$this->s__inscripciones[] = $dato;
					}
				break;
				case 'R': // Se genera para las inscripciones que tengan cumplidos los requisitos restrictivos
					if (($dato['propuesta_inscripcion'] == propuesta::inscripcion_aceptada || $dato['propuesta_inscripcion'] == propuesta::inscripcion_pendiente) &&
							inscripcion::cumple_requisitos_ingreso($dato['propuesta_aspira'], true)) {
						$this->s__inscripciones[] = $dato;
					}
				break;
			}
		}
		
		$this->s__datos_form_inscriptos = $this->s__inscripciones;
		
		if (guarani_arreglos::count($this->s__datos_form_inscriptos) > 0) {
			// Dinámica de pantalla
			if (($this->s__forma_generacion == legajos::generacion_manual) || 
				(($this->s__forma_generacion == legajos::generacion_automatica) && ($this->s__mostrar_inscriptos_gen_automatica))) {
				$pantalla->eliminar_dep('cuadro_msg_legajo_autom');
			} else {
				$pantalla->eliminar_dep('form_inscriptos');
			}

			if ($this->s__forma_generacion == legajos::generacion_automatica) {
				$pantalla->eliminar_dep('form_autocompletar');
			}
		} else {
			$pantalla->eliminar_dep('form_inscriptos');
			$pantalla->eliminar_dep('form_autocompletar');
		}
		
		
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_msg_legajo_autom ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_msg_legajo_autom(guarani_pers_ei_cuadro $cuadro)
	{
		if ($this->s__param_legajo_unico == comunes::si) {
			$clave_seleccion = 'persona';
			$seleccion = rs_convertir_asociativo_matriz($this->s__inscripciones, array($clave_seleccion), array('legajo'));
		} else {
			$clave_seleccion = 'alumno';
			$seleccion = rs_convertir_asociativo_matriz($this->s__inscripciones, array($clave_seleccion), array('legajo'));
		}
		
		$cant = guarani_arreglos::count($seleccion);
		if ($cant > 0) {
			$mensaje = "Hay $cant inscripciones listas para generar legajo automáticamente, a menos que se las deseé seleccionar individualmente, presionando el siguiente botón.";
		} else {
			$mensaje = "No hay inscripciones con generación de legajo pendiente para los filtros seleccionados.";
			$cuadro->eliminar_evento('ver_inscriptos');
			$this->pantalla()->evento('generar')->desactivar();
		}
		$cuadro->set_eof_mensaje($mensaje);
	}

	function evt__cuadro_msg_legajo_autom__ver_inscriptos($datos)
	{
		$this->s__mostrar_inscriptos_gen_automatica = true;
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_autocompletar -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_autocompletar($form)
	{
		if (isset($this->datos_a_completar)) {
			$form->set_datos($this->datos_a_completar);
		}
	}

	function evt__form_autocompletar__completar($datos)
	{
		$this->datos_a_completar = $datos;
		$this->autocompletar = 1;
	}

	//-----------------------------------------------------------------------------------
	//---- form_inscriptos --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_inscriptos(toba_ei_formulario_ml $form_ml)
	{
		$form_ml->set_columna_ordenamiento('apellido_y_nombres');
		
		// Se procede a autocompletar si corresponde.
		if ((isset($this->autocompletar)) && (guarani_arreglos::count($this->s__datos_form_inscriptos) > 0)) {
			$valor = $this->datos_a_completar['comp_valor'];
			$criterio = $this->datos_a_completar['comp_para'];
			foreach ($this->s__datos_form_inscriptos as $key => $dato) {
				if ($this->s__param_legajo_unico == comunes::no || empty($dato['legajo_otra_propuesta'])) {
					if ($criterio == legajos::combo_autocompletar_valor_vacios) {
						if (empty($dato['legajo'])) {
							$this->s__datos_form_inscriptos[$key]['legajo'] = $valor;
						}
					} else {
						$this->s__datos_form_inscriptos[$key]['legajo'] = $valor;
					}
				}
			}
			unset($this->autocompletar);
		}

		// Si el legajo es único para todas las propuestas cada persona se visualiza una única vez.
		if ($this->s__param_legajo_unico == comunes::si) {
			$form_ml->desactivar_efs(array('fecha_formato_visual', 'propuesta_nombre_abreviado', 'plan_nombre', 'modalidad_nombre', 'ubicacion_nombre'));
			$personas = array();
			foreach ($this->s__datos_form_inscriptos as $clave => $valor) {
				if (in_array($valor['persona'], $personas)) {
					unset($this->s__datos_form_inscriptos[$clave]);
				} else {
					$personas[] = $valor['persona'];
				}
			}
		}
		
		if ($this->s__forma_generacion == legajos::generacion_automatica) {
			$form_ml->desactivar_efs(array('legajo'));
		}

		// Se establecen los datos.
		if (!empty($this->s__datos_form_inscriptos)) {
			$form_ml->set_datos($this->s__datos_form_inscriptos);
		} else {
			$this->pantalla()->evento('generar')->desactivar();
		}
	}

	function conf_fila__form_inscriptos($fila)
	{
		$datos = $this->dep('form_inscriptos')->get_datos(true);
		if (($this->s__param_legajo_unico == comunes::si && !empty($datos[$fila]['legajo_otra_propuesta'])) ||
			($this->s__forma_generacion == legajos::generacion_automatica)) {
			$this->dep('form_inscriptos')->set_solo_lectura('legajo');
		} else {
			$this->dep('form_inscriptos')->set_solo_lectura('legajo', false);
		}
	}

	function evt__form_inscriptos__modificacion($datos)
	{
		$this->s__datos_form_inscriptos = $datos;
	}

	function evt__inscriptos__salida()
	{
		unset($this->s__datos_form_inscriptos);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Procesar 
	//-----------------------------------------------------------------------------------

	function conf__procesar(toba_ei_pantalla $pantalla)
	{
		parent::conf__procesar($pantalla);

		$this->ev_info()->set_datos($this->get_seleccion_info());

		if ($pantalla->existe_evento('cambiar_tab__anterior')) {
			$pantalla->eliminar_evento('cambiar_tab__anterior');
		}

		if ($pantalla->existe_evento('cambiar_tab__siguiente')) {
			$pantalla->eliminar_evento('cambiar_tab__siguiente');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Reportes 
	//-----------------------------------------------------------------------------------

	function conf__reporte(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_seleccion_info());

		if ($pantalla->existe_evento('cambiar_tab__anterior')) {
			$pantalla->eliminar_evento('cambiar_tab__anterior');
		}

		if ($pantalla->existe_evento('cambiar_tab__siguiente')) {
			$pantalla->eliminar_evento('cambiar_tab__siguiente');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- resultados -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__resultados(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__generar()
	{
		if ($this->s__param_legajo_unico == comunes::si) {
			$clave_seleccion = 'persona';
			$seleccion = rs_convertir_asociativo_matriz($this->s__datos_form_inscriptos, array($clave_seleccion), array('requerido', 'legajo'));
		} else {
			$clave_seleccion = 'alumno';
			$seleccion = rs_convertir_asociativo_matriz($this->s__datos_form_inscriptos, array($clave_seleccion), array('requerido', 'legajo'));
		}
		$inscripciones_seleccionadas = array();
		foreach ($this->s__inscripciones as $clave => $valor) {
			if ($seleccion[$valor[$clave_seleccion]]['requerido'] == 1) {
				$valor['legajo'] = $seleccion[$valor[$clave_seleccion]]['legajo'];
				$inscripciones_seleccionadas[] = $valor;
			}
		}

		if (empty($inscripciones_seleccionadas)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_alumnos');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('un')));
			throw guarani::error($mje);
		}

		// Si la generación es manual, se valida que no se ingresen valores repetidos entre sí.
		if ($this->s__forma_generacion == legajos::generacion_manual) {
			$this->cn()->validar_entrada_legajos($this->s__datos_form_inscriptos, $this->s__param_legajo_unico);
		}

		$parametros = array();
		$parametros['operacion'] = guarani::get_id_operacion();
		$parametros['unico_legajo'] = $this->s__param_legajo_unico;
		$parametros['generacion'] = $this->s__forma_generacion;
		$parametros['datos'] = $inscripciones_seleccionadas;
		$this->cn()->ejecutar_proceso($parametros);

		$this->set_pantalla('procesar');
	}

	function evt__finalizar()
	{
		$this->limpiar_seleccion();

		$this->filtro_limpiar('filtro_periodo_insc');
		$this->filtro_limpiar('filtro_resp_acad');
		$this->filtro_limpiar('filtro_ubicaciones');
		$this->filtro_limpiar('filtro_propuestas');

		$this->set_pantalla('periodo_insc');
	}

	//-----------------------------------------------------------------------------------
	//---- Procesamiento ----------------------------------------------------------------
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
		$contenido = $this->cn()->get_resultado_proceso(pro_generacion_legajos::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_generados':
				$reporte = pro_generacion_legajos::reporte_generados;
				$operacion = '41000071';
				$titulo = 'Legajos Generados';
				break;
			case 'rep_no_generados':
				$reporte = pro_generacion_legajos::reporte_no_generados;
				$operacion = '41000071';
				$titulo = 'Legajos no Generados';
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
			'ev_info_datos' => $this->get_seleccion_info(),
			'reporte' => $reporte,
			'titulo' => $titulo
		));

		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se crea el vínculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$mensaje_confirmacion = '¿Confirma que desea generar legajos para las inscripciones seleccionadas?';
		echo "
		//---- Eventos ---------------------------------------------				
		{$this->objeto_js}.evt__generar = function()
		{
			resp = confirm('$mensaje_confirmacion');
			return resp;
		}
		";
	}

}
?>