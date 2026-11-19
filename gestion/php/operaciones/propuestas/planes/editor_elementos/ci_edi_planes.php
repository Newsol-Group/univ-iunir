<?php
class ci_edi_planes extends guarani_pers_ci
{
	protected $cambio_tipo;
	protected $datos_plan_temp;
	protected $datos_version_temp;
	protected $datos_tipo_plan_temp;
	protected $datos_plan_personalizado_temp;
	protected $datos_requisitos_temp;

	function set_estado_inicial()
	{
		if ($this->pantalla()->existe_dependencia('editor_elementos')) {
			$this->dep('editor_elementos')->set_estado_inicial();
		}
	}

	function evt__elementos__salida()
	{
		$this->dep('editor_elementos')->set_estado_inicial();
	}

	/**
	 * Se traen los datos del DT directamente, ya que en caso de Alta al modificar el combo se dispara la modificación 
	 * del form y se setean estos datos en la tabla de planes, y en caso de edición tambíén estarán allí y este ef
	 * será no editable.
	 */
	function hay_tipo_plan_seleccionado()
	{
		$datos = $this->cn()->entidad()->get_datos_plan();
		return isset($datos['tipo_plan']);
	}

	function get_tipo_plan()
	{
		$datos = $this->cn()->entidad()->get_datos_plan();
		if (isset($datos['tipo_plan'])) {
			return $datos['tipo_plan'];
		}
	}

	/**
	 * Permite eliminar todas las dependencias relacionadas con la selección de Tipo de Plan, y habilitar 
	 * solo las correspondientes según el tipo actual. Es necesario sólo si el parámetro indica que se utilizan
	 * planes personalizados, en otro caso el formulario de Tipo Plan no estará visible.
	 * 
	 */
	function conf__datos_plan(guarani_ei_pantalla $pantalla)
	{
		if (!$this->controlador()->usa_planes_personalizados()) {
			if ($pantalla->existe_dependencia('form_tipo_plan')) {
				$pantalla->eliminar_dep('form_tipo_plan');
			}
			$pantalla->agregar_dep('form_plan');
			$pantalla->agregar_dep('form_plan_version');
		} else {
			if ($this->pantalla()->existe_dependencia('form_plan')) {
				$this->pantalla()->eliminar_dep('form_plan');
			}
			if ($this->pantalla()->existe_dependencia('form_plan_version')) {
				$this->pantalla()->eliminar_dep('form_plan_version');
			}
			if ($this->pantalla()->existe_dependencia('form_plan_personalizado')) {
				$this->pantalla()->eliminar_dep('form_plan_personalizado');
			}

			// Se realiza el procesamiento de las variables y dependencias relacionadas con las 
			// pantallas de configuración del Requisito o Entidad-Estado seleccionado o agregado.
			if ($this->hay_tipo_plan_seleccionado()) {
				// Se habilitan dependencias necesarias
				switch ($this->get_tipo_plan()) {
					case planes::tipo_personalizado:
					case planes::tipo_convenio:
						$this->pantalla()->agregar_dep('form_plan_personalizado');
						break;
					case planes::tipo_estructurado:
						$this->pantalla()->agregar_dep('form_plan');
						$this->pantalla()->agregar_dep('form_plan_version');
						break;
				}
			}
		}
		parent::conf();
	}

	/**
	 * Método de carga pedido para el esquema master-slave de los efs Propuesta y Convenio, ya que sólo se podrá 
	 * seleccionar este último, una vez que se haya seleccionado una propuesta, de entre los convenios que tengan 
	 * como propuesta base a la dada. Debido a que el ef convenio es de tipo popup, el método de carga no es útil,
	 * ya que la propuesta pasará directamente como parámetro al vínculo del mismo. Sin embargo debe definirse uno
	 * porque se pide como obligatorio en el toba editor para poder utilizar este esquema.
	 * @param int $id_propuesta
	 */
	function get_convenios_propuesta($id_propuesta)
	{
		
	}

	//-----------------------------------------------------------------------------------
	//---- form_tipo_plan ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Sólo para entidades nuevas, ya que en edición no puede modificarse el Tipo de Plan.
	 */
	function evt__form_tipo_plan__habilitar_dependencias_cambio_tipo_plan($tipo_plan)
	{
		/**
		 * Se asignan los datos temp del Tipo de Plan, ya que el evento implícito de modificación de este formulario 
		 * no se diparara en Toba, por haber disparado explícitamente este evento.
		 * También se setea un flag para evitar la modificación de los restantes datos, ya que se dispara la modificación
		 * de los formularios para plan personalizado, convenio y plan estructurado, y los datos no deben conservarse
		 * ante el cambio del Tipo de Plan.
		 */
		$this->datos_tipo_plan_temp = $tipo_plan;
		/**
		 * Si ya habían guardado datos principales del plan en la entidad, por haber cambiado de solapa, se resetea
		 * el Datos Relación al cambiar el Tipo del Plan, sin resetear el DR de Elementos asociado.
		 */
		$this->cn()->entidad()->reset_datos_principales_plan();
		// Análogo para los datos del Alumno y Período Lectivo guardados en variables de sesión del cn manejador.
		$this->cn()->unset_datos_alumno();
		$this->cn()->unset_datos_periodo();
		$this->cambio_tipo = true;
	}

	function evt__form_tipo_plan__modificacion($datos)
	{
		$this->datos_tipo_plan_temp = $datos;
	}

	function conf__form_tipo_plan(toba_ei_formulario $form)
	{
		if (isset($this->datos_tipo_plan_temp)) {
			$datos = $this->datos_tipo_plan_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_plan();
		}
		$form->set_datos($datos);

		if (!$this->cn()->entidad_es_nueva()) {
			$form->eliminar_evento('modificacion');
			$form->set_solo_lectura();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_plan_personalizado ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_plan_personalizado__modificacion($datos)
	{
		if (!$this->cambio_tipo) {
			$this->datos_plan_personalizado_temp = $datos;
		}
	}

	function conf__form_plan_personalizado(toba_ei_formulario $form)
	{
		if ($this->hay_tipo_plan_seleccionado() && $this->get_tipo_plan() != planes::tipo_convenio && $form->existe_ef('convenio')) {
			$form->desactivar_efs(array('convenio'));
		}
		$form->ef('periodo_insc')->set_descripcion('Períodos vigentes o vencidos del Año académico, Responsable, Modalidad y Ubicación seleccionadas');
		$form->ef('fecha_entrada_vigencia')->set_estado_defecto(guarani_fecha::get_hoy(true));
		if (isset($this->datos_plan_personalizado_temp)) {
			$datos = $this->datos_plan_personalizado_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_plan();
			;
			$modalidad = $this->cn()->entidad()->get_modalidad_plan_pers();
			if (!empty($modalidad)) {
				$datos['modalidad'] = $modalidad['modalidad'];
			}
			$datos_version = $this->cn()->entidad()->get_datos_version();
			if (!empty($datos_version['version'])) {
				$datos['version'] = $datos_version['version'];
			}
			if (!$this->cn()->entidad_es_nueva()) {
				/**
				 * En caso de edición de un Plan Personalizado (o Convenio), debe recuperarse a partir del Plan Versión
				 * cuál es el único Alumno asociado al mismo, y su Ubicación.
				 */
				$datos_version = $this->cn()->entidad()->get_datos_version();
				if (isset($datos_version['plan_version'])) {
					$alumno = toba::consulta_php('co_alumnos')->get_alumno_de_plan_version($datos_version['plan_version']);
					$datos['persona'] = $alumno['persona'];
					$datos['ubicacion'] = $alumno['ubicacion'];
					// Se recuperan los datos relativos al período de Inscripción a partir del Plan Versión
					// y el alumno, de la tabla sga_propuestas_aspira.
					$datos_prop = toba::consulta_php('co_inscripciones')->get_datos_insc_aceptada_o_pendiente_para_persona_planversion($datos['persona'], $datos_version['plan_version']);
					$datos['anio_academico'] = $datos_prop['anio_academico'];
					$datos['periodo_insc'] = $datos_prop['periodo_insc'];
				}
				// DATOS NO MODIFICABLES DEL PLAN PERSONALIZADO EN EDICIÓN (YA SEA NUEVO O ACTIVO).
				$form->ef('propuesta')->set_solo_lectura();
				$form->ef('persona')->set_solo_lectura();
				$form->ef('ubicacion')->set_solo_lectura();
				$form->ef('anio_academico')->set_solo_lectura();
				$form->ef('periodo_insc')->set_solo_lectura();
				$form->ef('modalidad')->set_solo_lectura();
				$form->ef('cobrable')->set_solo_lectura();
				// Si el plan es de Tipo Convenio, datos no modificables en edición.
				if ($form->existe_ef('convenio')) {
					$form->ef('convenio')->set_solo_lectura();
				}
			} else {
				/**
				 * Si es entidad nueva, aún no hay un Plan Versión, ni un Alumno con la Inscripción correspondiente
				 * dados de alta. Debe verificar si estos datos ya se setearon "temporalmente" y quedaron guardados en 
				 * las variables del cn man.
				 */
				$datos_alumno = $this->cn()->get_datos_alumno();
				if (!empty($datos_alumno)) {
					$datos['persona'] = $datos_alumno['persona'];
					$datos['ubicacion'] = $datos_alumno['ubicacion'];
				}
				$datos_periodo = $this->cn()->get_datos_periodo();
				if (!empty($datos_periodo)) {
					$datos['anio_academico'] = $datos_periodo['anio_academico'];
					$datos['periodo_insc'] = $datos_periodo['periodo_insc'];
				}
			}
		}
		$form->set_datos($datos);

		// Para los Planes Estructurados, cuando el Plan no es Nuevo, se bloqeua su edición.
		// Para Planes Personalizados y Convenios se permite modificar estructura y algunos datos siempre que no estén dados de baja.
		if ($this->cn()->entidad()->edicion_plan_bloqueada()) {
			$form->eliminar_evento('modificacion');
			$form->set_solo_lectura();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_plan --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_plan__modificacion($datos)
	{
		if (!$this->cambio_tipo) {
			$this->datos_plan_temp = $datos;
			$this->cn()->entidad()->set_datos_cobrable($this->datos_plan_temp);
		}
	}

	function conf__form_plan(toba_ei_formulario $form)
	{
		if (isset($this->datos_plan_temp)) {
			$datos = $this->datos_plan_temp;
		} else {
			$datos_modalidad = aplanar_matriz($this->cn()->entidad()->get_modalidades_plan(), 'modalidad');
			$datos = $this->cn()->entidad()->get_datos_plan();
			$datos['modalidad'] = $datos_modalidad;
		}

		if ($this->cn()->entidad_es_nueva()) {
			$form->set_datos_defecto($datos);
			$form->ef('fecha_entrada_vigencia')->set_estado_defecto(guarani_fecha::get_hoy(true));
			// Si no hay más propuestas de tipo curso para asignarle un plan, 
			// se setea en modo sólo lectura el ef administra_cursos
			$propuestas = $this->get_lista_propuestas_activas_tipo_plan(comunes::si);
			if (guarani_arreglos::count($propuestas) == 0) {
				$form->ef('administra_cursos')->set_solo_lectura();
				$form->ef('administra_cursos')->set_descripcion('No hay propuestas tipo curso disponibles');
			}
			$form->desactivar_efs(array('plan_estado_descripcion'));
		} else {
			$form->ef('propuesta')->set_solo_lectura();
			$form->ef('cobrable')->set_solo_lectura();
			$form->ef('administra_cursos')->set_solo_lectura();
			$form->set_datos($datos);
		}

		if ($this->cn()->entidad()->edicion_plan_bloqueada()) {
			$form->eliminar_evento('modificacion');
			$form->set_solo_lectura();
		}
	}

	function get_lista_propuestas_activas_tipo_plan($administra_cursos)
	{
		if ($administra_cursos == comunes::si) {
			$filtro_curso = "sga_propuestas.propuesta_tipo = " . propuesta::tipo_curso;
			// Si es un plan nuevo, se debe cumplir con el requerimiento que una propuesta tenga sólo un plan
			if ($this->cn()->entidad_es_nueva()) {
				$filtro_curso .= " AND sga_propuestas.propuesta NOT IN (SELECT propuesta FROM sga_planes WHERE estado <> " . quote(planes::estado_baja) . ")";
			} else {
				// Cuando se está modificando un plan de propuesta tipo curso ya guardado, 
				// se debe traer en el combo sólo la propuesta que ya se eligió para el plan 
				// anteriormente, ya que sino venía el combo vacío
				$datos = $this->cn()->entidad()->get_datos_version();
				$filtro_curso .= " AND sga_propuestas.propuesta = " . quote($datos['propuesta']);
			}
			return toba::consulta_php('co_propuestas')->get_lista_propuestas_activas($filtro_curso);
		} else {
			return toba::consulta_php('co_propuestas')->get_lista_propuestas_activas();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_plan_version ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_plan_version__modificacion($datos)
	{
		if (!$this->cambio_tipo) {
			$this->datos_version_temp = $datos;
		}
	}

	function conf__form_plan_version(toba_ei_formulario $form)
	{
		$datos = isset($this->datos_version_temp) ? $this->datos_version_temp : $this->cn()->entidad()->get_datos_version();

		if ($this->cn()->entidad_es_nueva()) {
			$form->set_datos_defecto($datos);
			$form->desactivar_efs(array('version_estado_descripcion'));
		} else {
			$form->set_datos($datos);
		}

		switch ($this->cn()->entidad()->get_estado_version()) {
			case planes::estado_activo:
			case planes::estado_activo_vigente:
				$form->set_solo_lectura();
				$form->set_solo_lectura(array('documento_baja', 'fecha_baja'), false);
				break;
			case planes::estado_baja:
				$form->eliminar_evento('modificacion');
				$form->set_solo_lectura();
				break;
			case planes::estado_nuevo:
				$form->set_solo_lectura(array('documento_baja', 'fecha_baja'));
				break;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function post_eventos()
	{
		try {
			if (isset($this->datos_plan_temp)) {
				$this->cn()->entidad()->set_datos_cabecera_y_modalidad($this->datos_plan_temp);
				if ($this->datos_plan_temp['administra_cursos'] == comunes::si) {
					// Se limpia la variable datos_version_temp, ya que el form está oculto, pero 
					// el evento de modificación se dispara de todas formas asignando valores nulos.
					unset($this->datos_version_temp);
					if ($this->cn()->entidad_es_nueva()) {
						// Debe generar los datos de una Versión única asociada al Plan creado, 
						// con datos default, ya que este tipo de Plan no podrá Versionarse.
						$nombre_version_default = substr('Versión única para el Plan Curso '.$this->datos_plan_temp['nombre'], 0, 100);
						$id_version_default = 'V1';
						$datos_version = array('version' => $id_version_default, 'nombre' => $nombre_version_default, 'estado' => planes::estado_nuevo);
						$this->cn()->entidad()->set_datos_version($datos_version);
					}
				}
				unset($this->datos_plan_temp);
			}
			if (isset($this->datos_version_temp)) {
				$this->cn()->entidad()->set_datos_version($this->datos_version_temp);
				unset($this->datos_version_temp);
			}
			if (isset($this->datos_tipo_plan_temp)) {
				$this->cn()->entidad()->set_datos_cabecera_y_modalidad($this->datos_tipo_plan_temp);
				unset($this->datos_tipo_plan_temp);
			}
			if (isset($this->datos_plan_personalizado_temp)) {
				$this->cn()->entidad()->set_datos_cabecera_y_modalidad($this->datos_plan_personalizado_temp);
				// Debe guardar los datos de la persona y la ubicación temporalmente, para al Guardar, dar de alta la 
				// Inscripción a Propuesta y al Alumno. Sólo para el caso de Alta.
				if ($this->cn()->entidad_es_nueva()) {
					if (isset($this->datos_plan_personalizado_temp['persona'])) {
						// Debe chequear que esté el dato porque aunque sea obligatorio, se verifica recién en el 
						// presincronizar a mano por php, ya que si se seteaban los campos obligatorios no se podían 
						// agregar y eliminar las dependencias al cambiar el combo Tipo Plan. Si se implementa esto 
						// de otra forma, no es necesario este chequeo.
						$this->cn()->set_datos_alumno($this->datos_plan_personalizado_temp);
						// Debe generar también los datos de una Versión única asociada al Plan creado, con datos default.
						$nombre_persona = toba::consulta_php('co_personas')->get_nombre_persona($this->datos_plan_personalizado_temp['persona']);
						$nombre_version_default = 'Versión única para el alumno ' . $nombre_persona;
						$id_version_default = 'V1';
						$datos_version = array('version' => $id_version_default, 'nombre' => $nombre_version_default, 'estado' => planes::estado_nuevo);
						$this->cn()->entidad()->set_datos_version($datos_version);
					}
				}
				if (isset($this->datos_plan_personalizado_temp['periodo_insc'])) {
					// Se seleccionó Período de Inscripción
					$this->cn()->set_datos_periodo($this->datos_plan_personalizado_temp);
				}
				unset($this->datos_plan_personalizado_temp);
			}
		} catch (guarani_error $e) {
			$this->resetear_cambio_pantalla();
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Consultas AJAX ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * El usuario está modificando el Convenio asociado al Plan Personalizado, Deben obtenerse los datos del mismo, 
	 * para setearlos por defecto como Fecha y Documento de Alta del Plan Personalizado.
	 */
	function ajax__cambio_convenio($params, toba_ajax_respuesta $respuesta)
	{
		$datos_convenio = toba::consulta_php('co_convenios')->get_datos_convenio($params['convenio']);
		$salida = array();
		if (isset($datos_convenio['vigencia_desde_visual'])) {
			$salida['fecha'] = $datos_convenio['vigencia_desde_visual'];
		}
		if (isset($datos_convenio['documento'])) {
			$salida['documento'] = $datos_convenio['documento'];
			$salida['doc_desc'] = $datos_convenio['doc_desc'];
		}
		$respuesta->set($salida);
	}

	function ajax__nombre_plan_defecto($params, toba_ajax_respuesta $respuesta)
	{
		// Dato por defecto para el Nombre del Plan.
		if (isset($params['persona'])) {
			$nombre_persona = toba::consulta_php('co_personas')->get_nombre_persona($params['persona']);
			if ($nombre_persona) {
				$plan_descr = 'Plan personalizado para el alumno ' . $nombre_persona;
				$respuesta->set(array('nombre' => $plan_descr));
			}
		} else {
			$respuesta->set(false);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		/**
		 * Se utiliza desde las dependencias (formularios) que se agregan y eliminan ante el cambio de Tipo de Plan
		 * para evitar que se chequeen los campos obligatorios ante la selección de un nuevo valor en ese combo, 
		 * antes de modificar las dependencias asociadas a la pantalla según ese valor.
		 */
		echo "
		//---- Validacion general ----------------------------------
		
		validar_todo = true;
		
		{$this->objeto_js}.set_validar_todo = function(valida)
		{
			validar_todo = valida;
		}

		{$this->objeto_js}.get_validar_todo = function()
		{
			return validar_todo;
		}

		";
	}

}
?>