<?php

class ci_nav_insc_propuesta extends ci_navegacion_wizard
{
	/**
	 * Componente Visual de Información
	 * @var ev_info_datos 
	 */
	protected $ev_info;
	// Mail
	protected $s__datos_mail;
	protected $envio_ok = false;
	protected $archivo = '';
	// Datos Auxiliares
	protected $s__datos_forms;
	protected $s__datos_planesvm = false;
	protected $s__datos_conv;
	protected $s__datos_impresion;
	protected $s__mail_enviado = false;
	// Navegación a Administrar Requisitos de una Persona
	protected $s__navegar;
	protected $s__vinculo_adm_req;
	
	//-----------------------------------------------------------------------------------
	//---- Inicialización ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ini()
	{
		// Se chequea si regresa de Administrar Personas
		$persona = toba::memoria()->get_parametro('id_nuevo_registro');
		if (isset($persona)) {
			if ($persona == '0') {
				// El usuario presionó "Volver" en el ABM de Personas, no dio de alta la nueva persona.
				$this->set_pantalla('sel_alumno');
			} else {
				// El usuario agregó una nueva persona
				$this->set_seleccion(array('persona' => $persona), 'sel_alumno');
				$this->validar_sel_alumno();
				$this->evt__sel_inscr__entrada();
				$this->set_pantalla('sel_inscr');
			}
		}

		// Se chequea si regresa de Administrar Requisitos de una Persona
		$propuesta_aspira = toba::memoria()->get_parametro('propuesta_aspira');
		if (isset($propuesta_aspira)) {
			$this->cn()->resetear();
			$this->cn()->nueva();
			$this->cn()->entidad()->cargar_datos(array('propuesta_aspira' => $propuesta_aspira));
			$this->set_pantalla('pant_comprobante');
		}
	}

	function resetear()
	{
		unset($this->s__datos_mail);
		unset($this->s__datos_forms);
		$this->s__datos_planesvm = false;
		unset($this->s__datos_conv);
		unset($this->s__datos_impresion);
		unset($this->s__navegar);
		unset($this->s__vinculo_adm_req);
		$this->s__mail_enviado = false;
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('alumno', 'documento', 'fecha_inicio', 'fecha_fin', 'fecha_cierre', 'propuesta', 'modalidad', 'per_anio', 'ubicacion', 'per_nombre', 'estado_insc'));
			$this->ev_info->set_columnas_fila(array('alumno'), 1);
			$this->ev_info->set_columnas_fila(array('documento'), 2);
			$this->ev_info->set_columnas_fila(array('propuesta'), 3);
			$this->ev_info->set_columnas_fila(array('modalidad'), 4);
			$this->ev_info->set_columnas_fila(array('ubicacion'), 5);
			$this->ev_info->set_columnas_fila(array('per_anio'), 6);
			$this->ev_info->set_columnas_fila(array('per_nombre'), 7);
			$this->ev_info->set_columnas_fila(array('fecha_inicio', 'fecha_fin', 'fecha_cierre'), 8);
			$this->ev_info->set_columnas_fila(array('estado_insc'), 9);
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_etiqueta('alumno', 'Apellido y Nombre');
			$this->ev_info->set_etiqueta('documento', 'Nro. Documento');
			$this->ev_info->set_etiqueta('propuesta', 'Propuesta');
			$this->ev_info->set_etiqueta('modalidad', 'Modalidad');
			$this->ev_info->set_etiqueta('per_anio', 'Período Año Académico');
			$this->ev_info->set_etiqueta('per_nombre', 'Período Nombre');
			$this->ev_info->set_etiqueta('fecha_inicio', 'Fecha Inicio');
			$this->ev_info->set_etiqueta('fecha_fin', 'Fecha Fin');
			$this->ev_info->set_etiqueta('fecha_cierre', 'Fecha Finalización');
			$this->ev_info->set_etiqueta('estado_insc', 'Estado inscripción');
			$this->ev_info->set_td_etiqueta_width('130px');
		}
		return $this->ev_info;
	}

	function get_datos_conv()
	{
		$datos = $this->get_seleccion();
		return toba::consulta_php('co_convenios')->get_convenios_propuestas_all($datos['propuesta']);
	}

	function get_datos_estudios()
	{
		$persona = $this->get_seleccion_valor('persona');
		return toba::consulta_php('co_personas')->get_datos_estudios_persona($persona);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: SELECCION PROPUESTA (sel_prop)
	//-----------------------------------------------------------------------------------

	function conf__sel_prop(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear();
		$this->cn()->nueva();
		$this->cn()->resetear_atributos_parametros();
	}

	function validar_sel_prop()
	{
		// Se valida que se haya seleccionado una propuesta
		if ($this->falta_seleccionar('propuesta')) {
			$mje = guarani_mensajes::get('800_no_seleccion_unica', array('una propuesta'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_props -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_props(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_props', false);
	}

	function evt__filtro_props__filtrar($datos)
	{
		$this->filtro_filtrar('filtro_props', $datos);
	}

	function evt__filtro_props__cancelar()
	{
		$this->filtro_limpiar('filtro_props');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_props -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_props(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('filtro_props');

		if (isset($filtro) && !empty($filtro)) {
			$datos = $this->cn()->get_datos_props($filtro);
		} else {
			$datos = $this->cn()->get_datos_props();
		}

		$cuadro->set_datos($datos);
	}

	function evt__cuadro_props__seleccion($seleccion)
	{
		$this->set_seleccion($seleccion);
		$this->cn()->cargar_atributos_parametros_propuesta($seleccion['propuesta']);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION PLAN-MODALIDAD-VERSION (sel_plan_mod_version)
	//-----------------------------------------------------------------------------------

	function conf__sel_plan_mod_version(toba_ei_pantalla $pantalla)
	{
		$prop = $this->get_seleccion();
		$datos = toba::consulta_php('co_propuestas')->get_datos_propuesta($prop['propuesta']);
		$this->s__datos_forms = array('propuesta' => $datos['nombre_abreviado']);
		$this->controlador()->ev_info()->set_datos($this->s__datos_forms);
		$this->cn()->resetear_atributos_parametros(parametro::nivel_plan);
	}

	function validar_sel_plan_mod_version()
	{
		// Se valida que se haya seleccionado plan y ubicación
		if ($this->falta_seleccionar('modalidad')) {
			$mje = guarani_mensajes::get('800_no_seleccion_unica', array('un Plan/Modalidad/Versión'));
			throw guarani::error($mje);
		} else {
			if ($this->falta_seleccionar('ubicacion')) {
				$mje = guarani_mensajes::get('800_no_seleccion_unica', array('una ubicación'));
				throw guarani::error($mje);
			}
		}

		// Se valida que exista un período de inscripción disponible
		$datos = $this->get_seleccion();
		$datos_perinsc = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_per_insc_pv($datos['plan_version'], $datos['modalidad'], $datos['ubicacion']);
		if (empty($datos_perinsc)) {
			$mje = guarani_mensajes::get('800MAT_Sin_Per_Insc');
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- plan_mod_version -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__plan_mod_version(toba_ei_cuadro $cuadro)
	{
		$prop = $this->get_seleccion();
		$params_sistema = array(
			'inscripcion_propuesta_plan' => guarani::parametros()->get_valor('inscripcion_propuesta_plan'),
			'inscripcion_propuesta_version' => guarani::parametros()->get_valor('inscripcion_propuesta_version')
		);
		if ($this->s__datos_planesvm) {
			$datos = toba::consulta_php('co_propuestas')->get_planes_para_inscripcion($prop['propuesta'], true, '', $params_sistema);
			$this->s__datos_planesvm = false;
		} else {
			$datos = toba::consulta_php('co_propuestas')->get_planes_para_inscripcion($prop['propuesta'], false, '', $params_sistema);
		}

		$cuadro->set_datos($datos);

		if ($params_sistema['inscripcion_propuesta_plan'] != comunes::todos) {
			$cuadro->eliminar_evento('todos');
		}
	}

	function evt__plan_mod_version__seleccion($seleccion)
	{
		$this->set_seleccion($seleccion);
		$this->cn()->cargar_atributos_parametros_plan($seleccion['plan']);
	}

	function evt__plan_mod_version__todos($datos)
	{
		$this->s__datos_planesvm = true;
	}

	//-----------------------------------------------------------------------------------
	//---- ubicacion --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__ubicacion(toba_ei_cuadro $cuadro)
	{
		$prop = $this->get_seleccion();
		$datos = toba::consulta_php('co_ubicaciones')->get_ubicaciones_propuestas($prop['propuesta']);
		$cuadro->set_datos($datos);
		if (guarani_arreglos::count($datos) == 1) {
			$this->set_seleccion(current($datos));
		}
	}

	function evt__ubicacion__seleccion($seleccion)
	{
		$this->set_seleccion($seleccion);
	}

	function conf_evt__ubicacion__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('ubicacion', $fila));
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION PERIODOS INSCRIPCION (sel_periodo_insc)
	//-----------------------------------------------------------------------------------

	function conf__sel_periodo_insc(toba_ei_pantalla $pantalla)
	{
		$prop = $this->get_seleccion();
		$datos_pv = toba::consulta_php('co_planes')->get_datos_plan_version($prop['plan_version']);
		$datos_mod = toba::consulta_php('co_planes')->get_datos_modalidad($prop['modalidad']);
		$datos_ubic = toba::consulta_php('co_ubicaciones')->get_ubicaciones($prop['ubicacion']);
		$this->s__datos_forms['propuesta'] .= ' - ' . $datos_pv[0]['plan_nombre'] . ' ' . $datos_pv[0]['version_nombre'];
		$this->s__datos_forms['ubicacion'] = $datos_ubic[0]['descr'];
		$this->s__datos_forms['modalidad'] = $datos_mod['modalidad_nombre'];
		$this->controlador()->ev_info()->set_datos($this->s__datos_forms);
	}

	function validar_sel_periodo_insc()
	{
		// Se valida que se haya seleccionado un Alumno
		if ($this->falta_seleccionar('periodo_insc')) {
			$mje = guarani_mensajes::get('800_no_seleccion_unica', array('un Período'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- per_insc ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__per_insc(toba_ei_cuadro $cuadro)
	{
		$sel = $this->get_seleccion();
		$datos = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_per_insc_pv($sel['plan_version'], $sel['modalidad'], $sel['ubicacion']);
		$cuadro->set_datos($datos);
	}

	function evt__per_insc__seleccion($seleccion)
	{
		$seleccion['anio_academico'] = $seleccion['anio'];
		$this->set_seleccion($seleccion);
	}

	function conf_evt__per_insc__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('per_insc', $fila));
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ALUMNO (sel_alumno)
	//-----------------------------------------------------------------------------------

	function conf__sel_alumno(toba_ei_pantalla $pantalla)
	{
		$this->dep('persona_cuadro')->evento('agregar_persona')->vinculo()->set_parametros(array('operacion_a_volver' => guarani::get_id_operacion_toba(), 'celda_a_restaurar' => toba::memoria()->get_celda_memoria_actual_id()));
		$this->dep('persona_cuadro')->evento('agregar_persona')->vinculo()->set_celda_memoria('celda_persona');
		$datos_per = $this->get_seleccion();
		$this->s__datos_forms['per_anio'] = $datos_per['anio'];
		$this->s__datos_forms['per_nombre'] = $datos_per['nombre'];
		$this->controlador()->ev_info()->set_datos($this->s__datos_forms);
	}

	function validar_sel_alumno()
	{
		// Se valida que se haya seleccionado un alumno
		$datos = $this->get_seleccion();
		$persona = $this->get_seleccion_valor('persona');
		if (!isset($persona)) {
			$mje = guarani_mensajes::get('800_no_seleccion_unica', array('un alumno'));
			throw guarani::error($mje);
		}

		/**
		 * Se valida que la persona que se está inscribiendo no esté expulsada en otra Institución. 
		 * 
		 * @todo: implementar en un punto de control
		 */
		if (toba::consulta_php('co_expulsados_en_otras_instituciones')->fue_expulsado($persona)) {
			$mje = guarani_mensajes::get('800MAT_persona_expulsada');
			throw guarani::error($mje);
		}

		// Se valida que la persona seleccionada no puede tener inscripciones pendientes o aceptadas en la propuesta (sga_situacion_aspirante.resultado_asp = A o P).
		$filtro = "sga_situacion_aspirante.resultado_asp IN ('" . propuesta::inscripcion_pendiente . "','" . propuesta::inscripcion_aceptada . "')";
		$cant_inscripciones = toba::consulta_php('co_inscripciones')->get_cant_inscripciones($persona, $datos['propuesta'], null, $filtro);
		if ($cant_inscripciones != 0) {
			$mje = guarani_mensajes::get('800MAT_persona_inscripta');
			throw guarani::error($mje);
		}

		// Se valida que la persona seleccionada no tenga inscripciones rechazadas en la propuesta para ese período de inscripción.
		$filtro = "	sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_rechazada . "' AND
					sga_propuestas_aspira.periodo_insc = {$datos['periodo_insc']}";
		$cant_inscripciones = toba::consulta_php('co_inscripciones')->get_cant_inscripciones($persona, $datos['propuesta'], null, $filtro);
		if ($cant_inscripciones != 0) {
			$datos_propuesta = toba::consulta_php('co_propuestas')->get_datos_propuesta($this->get_seleccion_valor('propuesta'));
			$mje = guarani_mensajes::get('800MAT_persona_rechazada_periodo', array($datos_propuesta['nombre_abreviado']));
			throw guarani::error($mje);
		}

		/**
		 * @todo: validar también
		 * Cuando se agrega una nueva persona, o bien cuando se continua con Siguiente a la nueva pantalla, se tienen que 
		 * validar los datos obligatorios que se cargan para las personas, los mismos van a depender del tipo de propuesta
		 * a la que se esta inscribiendo la persona.
		 */
	}

	//-----------------------------------------------------------------------------------
	//---- persona_filtro ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------		

	function conf__persona_filtro(toba_ei_filtro $filtro)
	{
		$filtro->set_sin_filtrado_inicial();
		$this->filtro_configurar('persona_filtro', false);
	}

	function evt__persona_filtro__filtrar($datos)
	{
		$this->filtro_filtrar('persona_filtro', $datos);
	}

	function evt__persona_filtro__cancelar()
	{
		$this->filtro_limpiar('persona_filtro');
	}

	//-----------------------------------------------------------------------------------
	//---- persona_cuadro ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__persona_cuadro(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro('persona_filtro');

		if (isset($filtro)) {
			$datos = $this->cn()->get_datos_pers($filtro);
		} else {
			$datos = array();
		}
		$cuadro->set_datos($datos);
	}

	function evt__persona_cuadro__seleccion($seleccion)
	{
		$this->set_seleccion($seleccion);
	}

	function conf_evt__persona_cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('persona_cuadro', $fila));
	}

	//-----------------------------------------------------------------------------------		
	//---- PANTALLA CONFIRMACIÓN INSCRIPCIÓN (sel_inscr)
	//-----------------------------------------------------------------------------------		

	function conf__sel_inscr(toba_ei_pantalla $pantalla)
	{
		$datos = $this->get_seleccion();
		$formateo = new toba_formateo('html');
		$datos_persona = current($this->cn()->get_datos_pers("mdp_personas.persona = {$datos['persona']}"));
		$this->s__datos_forms['alumno'] = $datos_persona['nombre_completo'];
		$this->s__datos_forms['documento'] = $datos_persona['documento'];
		$this->s__datos_forms['fecha_inicio'] = $formateo->formato_fecha($datos['fecha_inicio']);
		$this->s__datos_forms['fecha_fin'] = $formateo->formato_fecha($datos['fecha_fin']);
		$this->s__datos_forms['fecha_cierre'] = $formateo->formato_fecha($datos['fecha_cierre_requisitos']);
		$this->controlador()->ev_info()->set_datos($this->s__datos_forms);

		$this->evento('cambiar_tab__siguiente')->ocultar();
	}

	function evt__sel_inscr__entrada()
	{
		$datos = $this->get_seleccion();
		// Se guarda en memoria el ID persona para accederlo desde los popups de la pantalla siguiente
		toba::memoria()->set_dato('persona', $datos['persona']);
		// Se establecen los datos en el DT de propuestas_aspira
		$this->cn()->entidad()->set_datos_prop_asp($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- form_conv --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_conv(toba_ei_formulario $form)
	{
		$fecha_hoy = guarani_fecha::get_hoy();
		if (!isset($this->s__datos_conv)) {
			$this->s__datos_conv['fecha_inscripcion'] = $fecha_hoy;
		}
		$form->set_datos($this->s__datos_conv);
	}

	function evt__form_conv__modificacion($datos)
	{
		// Se preparan los valores a guardar
		$this->s__datos_conv = $datos;
		if (!isset($datos['fecha_inscripcion'])) {
			throw new guarani_error('Debe ingresar una fecha de inscripción');
		}

		$this->set_seleccion($datos);
	}

	//-----------------------------------------------------------------------------------		
	//---- PANTALLA COMPROBANTE (pant_comprobante)
	//-----------------------------------------------------------------------------------		

	function conf__pant_comprobante(toba_ei_pantalla $pantalla)
	{
		$this->evento('cambiar_tab__anterior')->ocultar();
		$this->preparar_datos_comprobante();
		$this->controlador()->ev_info()->set_datos($this->s__datos_impresion);
	}

	protected function preparar_datos_comprobante()
	{
		$this->s__datos_impresion = array();
		
		// Configuraciones para popup
		$inscripcion = current($this->cn()->entidad()->get_datos_prop_asp());

		// Datos personales
		$datos_persona = toba::consulta_php('co_personas')->get_datos_persona($inscripcion['persona']);
		$this->s__datos_impresion['alumno'] = $datos_persona['nombre_completo'];
		$this->s__datos_impresion['documento'] = $datos_persona['documento'];

		// Propuesta / Plan
		$datos_prop = toba::consulta_php('co_propuestas')->get_propuestas_datos($inscripcion['propuesta']);
		$datos_pv = toba::consulta_php('co_planes')->get_datos_plan_version($inscripcion['plan_version']);

		$this->s__datos_impresion['plan'] = $datos_pv[0]['plan_nombre'] . ' - ' . $datos_pv[0]['version_nombre'];
		$this->s__datos_impresion['propuesta'] = $datos_prop['propuesta_nombre'];

		// Fecha de inscripción
		$formateo = new toba_formateo('html');
		$this->s__datos_impresion['fecha_insc'] = $formateo->formato_fecha($inscripcion['fecha_inscripcion']);

		// Modalidad
		$modalidad = toba::consulta_php('co_planes')->get_nombres_modalidades($inscripcion['modalidad']);
		$this->s__datos_impresion['modalidad'] = $modalidad['modalidad_nombre'];

		// Ubicación
		$this->s__datos_impresion['ubicacion'] = toba::consulta_php('co_ubicaciones')->get_ubicacion_nombre($inscripcion['ubicacion']);

		// Estado de Inscripción
		$this->s__datos_impresion['estado_insc'] = toba::consulta_php('co_inscripciones')->get_descr_estado_propuesta_aspira($inscripcion['propuesta_aspira']);

		// Tipo de Ingreso
		if (!empty($inscripcion['tipo_ingreso'])) {
			$tipo_ingreso = toba::db()->quote($inscripcion['tipo_ingreso']);
			$ing = current(toba::consulta_php('co_ingresos')->get_listado("sga_tipos_ingreso.tipo_ingreso = $tipo_ingreso"));
			$this->s__datos_impresion['tipo_ingreso'] = $ing['nombre'];
		} else {
			$this->s__datos_impresion['tipo_ingreso'] = ' - ';
		}

		// Convenio
		if (!empty($inscripcion['convenio'])) {
			$convenio = toba::db()->quote($inscripcion['convenio']);
			$conv = current(toba::consulta_php('co_convenios')->get_listado("sga_convenios.convenio = $convenio"));
			$this->s__datos_impresion['convenio'] = $conv['nombre'];
		} else {
			$this->s__datos_impresion['convenio'] = ' - ';
		}

		// Se preparan los datos a pasar
		$gimp = new guarani_datos_impresion();
		$titulo = 'Comprobante de Inscripción a Propuesta';
		$conf = array('propuesta' => $inscripcion['propuesta'], 'nro_transaccion' => $inscripcion['nro_transaccion'], 'id_persona' => $inscripcion['persona']);
		$gimp->set_titulo($titulo);
		$gimp->set_conf($conf);

		// Se arma el arreglo de datos
		$gimp->nueva_fila_datos('Apellido y Nombre', $this->s__datos_impresion['alumno']);
		$gimp->nueva_fila_datos('Identificación', $this->s__datos_impresion['documento']);
		$gimp->nueva_fila_datos('Propuesta', $this->s__datos_impresion['propuesta']);
		$gimp->nueva_fila_datos('Plan de Estudios', $this->s__datos_impresion['plan']);
		$gimp->nueva_fila_datos('Modalidad de Cursada', $this->s__datos_impresion['modalidad']);
		$gimp->nueva_fila_datos('Ubicación', $this->s__datos_impresion['ubicacion']);
		$gimp->nueva_fila_datos('Tipo de Ingreso', $this->s__datos_impresion['tipo_ingreso']);
		$gimp->nueva_fila_datos('Convenio', $this->s__datos_impresion['convenio']);
		$gimp->nueva_fila_datos('Estado de Inscripción', $this->s__datos_impresion['estado_insc']);
		$gimp->nueva_fila_datos('Fecha de Inscripción', $this->s__datos_impresion['fecha_insc']);

		// Se almacenan los datos en memoria para recuperarlos luego para la impresión de comprobante
		$gimp->guardar_datos_imp();

		// Envío de Mail con el comprobante
		$this->generar_comprobante();
		$ra = toba::consulta_php('co_propuestas')->get_ra_por_defecto($inscripcion['propuesta']);
		toba::memoria()->set_dato('ra', $ra);
		toba::memoria()->set_dato('id_persona', $inscripcion['persona']);
		if (!is_null($this->archivo)) {
			toba::memoria()->set_dato('path_comp', $this->archivo);
		}
		$envio_mail_autom = guarani::parametros()->get_valor('email_envio_automatico_comprobante');
		if (($envio_mail_autom == comunes::si) && (!$this->s__mail_enviado)) {
			$this->s__mail_enviado = true;
			$this->evento('mail')->ocultar();
			$this->enviar_mail($inscripcion['persona']);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		$this->cn()->desactivar_transacciones_anidadas();

		try {
			toba::db()->abrir_transaccion();

			// Se establece el registro propuesta aspira.
			$datos = $this->get_seleccion();
			$datos['situacion_asp'] = propuesta::situacion_asp_insc_aspirante_a_prop;
			$this->cn()->entidad()->set_datos_prop_asp($datos);

			// Se establecen los requisitos de aspirante
			$this->cn()->entidad()->set_requisitos_aspirante();

			// Sincronización global con el resto de los datos.
			$this->cn()->entidad()->sincronizar();

			$situacion_asp = propuesta::situacion_asp_insc_aspirante_a_prop;
			$persona = $datos['persona'];

			// Si no existen inscripciones previas (rechazadas) se inserta un nuevo alumno, sino se actualizan sus datos.
			preinscripcion::procesar_alumno($persona, $datos['propuesta'], $datos['plan_version'], $datos['modalidad'], $datos['ubicacion'], $situacion_asp, $datos['fecha_inscripcion']);

			// Inscripción a propuestas vinculadas
			preinscripcion::inscribir_a_propuestas_vinculadas($persona, $datos['periodo_inscripcion'], $datos['periodo_insc'], $datos['anio_academico'], $datos['propuesta'], $datos['plan_version'], $datos['modalidad'], $datos['ubicacion'], $datos['fecha_inscripcion'], $datos['tipo_ingreso'], $datos['convenio']);
			
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');

			$this->set_pantalla('pant_comprobante');

			// Navegación a la operación "Administrar Requisitos de una Persona"
			$datos_aspirante = current($this->cn()->entidad()->get_datos_prop_asp());

			if (toba::proyecto()->puede_grupo_acceder_item('5000111')) {
				$params = array();
				$params['operacion_a_volver'] = guarani::get_id_operacion_toba();
				$params['celda_a_restaurar'] = toba::memoria()->get_celda_memoria_actual_id();
				$params['persona'] = $persona;
				$params['propuesta_aspira'] = $datos_aspirante['propuesta_aspira'];
				$this->s__vinculo_adm_req = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '5000111', $params);
				$this->s__navegar = true;
			}

			toba::db()->cerrar_transaccion();
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			$this->cn()->resetear();
			$this->cn()->nueva();
			$this->cn()->entidad()->set_datos_prop_asp($this->get_seleccion());
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}

	function evt__finalizar()
	{
		// Se regresa a la pantalla inicial si se guardo ok
		try {
			$this->limpiar_memoria();
			$this->resetear();
			$this->set_pantalla('sel_prop');
		} catch (guarani_error $e) {
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Envío de Mail ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

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
		} catch (toba_error $e) {
			$this->envio_ok = false;
		}
		if (file_exists($this->archivo)) {
			unlink($this->archivo);
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
		try {
			// Se genera el comprobante
			$pdf = new guarani_pdf(array($this->dep('comprobante')));
			$this->archivo = $pdf->generar(true);
		} catch (toba_error $e) {
			$this->archivo = null;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();

		$msj_administrar = 'Inscripción procesada con éxito. ¿Desea gestionar requisitos de la persona?';
		if (!empty($this->s__navegar)) {
			unset($this->s__navegar);
			echo "
				resp = confirm('$msj_administrar');
				if (resp) {
					document.location.href='$this->s__vinculo_adm_req';
				}
			";
		}
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__get_requiere_documento_ingreso($params, toba_ajax_respuesta $respuesta) 
	{
		$tipo_ingreso = $params['tipo_ingreso'];
		$requiere_res_exp = toba::consulta_php('co_ingresos')->get_requiere_res_exp_tipo_ingreso($tipo_ingreso);
		if (!empty($requiere_res_exp)) {
			$salida = array('requiere_res_exp' => $requiere_res_exp);
			$respuesta->set($salida);
		} else {
			$respuesta->set(false);
		}
	}

	function ajax__datos_estudio_anterior($params, toba_ajax_respuesta $respuesta)
	{
		$dato_estudio = current($params);
		$respuesta->set(array('institucion_anterior' => toba::consulta_php('co_personas')->get_institucion_dato_estudio($dato_estudio)));
	}
}