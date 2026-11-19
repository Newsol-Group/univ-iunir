<?php
/**
 * @ToDo cambiar el nombre del ci_nav_planes
 */
class ci_nav_planes extends ci_navegacion_planes
{
	protected $s__plan_versionar;
	protected $s__datos_version;
	
	// Copiar estructura de Plan Personalizado.
	protected $s__plan_a_copiar;
	protected $datos_tipo_plan_temp;
	protected $datos_plan_temp;
	protected $cambio_tipo;

	function ini()
	{
		if (toba::zona()->cargada() && !$this->cn()->entidad_cargada()) {
			$plan_version = toba::zona()->get_editable();
			$this->s__seleccion = $plan_version;
			$this->cn()->cargar($plan_version, true);
			$this->set_pantalla('edicion');
			$this->dep('editor_plan')->set_pantalla('elementos');
		}
		$this->cn()->set_planes_personalizados($this->usa_planes_personalizados());
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		/**
		 * Se elimina el Evento de Eliminación física de un Plan, cuando la edición está bloqueada (es decir, los planes
		 * no son Nuevos). Se modificó a compararlo directamente a estado <> 'nuevo', ya que en el caso de
		 * los Planes Personalizados se permite editar planes Activos (vigentes y no vigentes), pero no
		 * eliminarlos.
		 */
		if (!$this->cn()->entidad()->es_nueva_version()) {
			$this->pantalla()->eliminar_evento('eliminar');
			if ($this->cn()->entidad()->get_estado_version() == planes::estado_baja) {
				$this->pantalla()->eliminar_evento('guardar');
			}
		} else {
			if (!$this->cn()->entidad()->esta_cargado()) {
				$this->pantalla()->eliminar_evento('eliminar');
			} else {
				$this->evento('eliminar')->set_msg_confirmacion($this->get_msg_confirmar_eliminar('version'));
			}
		}
		$this->hay_cambios = $this->cn()->entidad()->hay_cambios();
	}

	function evt__cancelar()
	{
		parent::evt__cancelar();
		unset($this->s__plan_versionar);
		unset($this->s__datos_version);
		unset($this->s__plan_a_copiar);
		$this->dep('editor_plan')->set_estado_inicial();
	}

	function evt__guardar()
	{
		$this->set_no_volver_al_guardar();
		parent::evt__guardar();
		$this->dep('editor_plan')->set_estado_inicial();
		$id_plan_version = $this->cn()->entidad()->get_id_plan_version();
		toba::zona()->cargar($id_plan_version);
	}

	function evt__eliminar()
	{
		try {
			$this->cn()->eliminar();
			$this->set_pantalla('seleccion');
			toba::zona()->resetear();
			toba::notificacion()->agregar($this->get_mensaje('eliminar_ok'), 'info');
		} catch (toba_error $e) {
			$plan_version = toba::zona()->get_editable();
			$this->cn()->resetear();
			$this->cn()->cargar($plan_version, true);
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}
	
	//--------------------------------------------------------------------------
	//---- PANTALLA: seleccion
	//--------------------------------------------------------------------------
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}

	//--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		$this->s__filtro = "";
	}

	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (!$this->usa_planes_personalizados()) {
			$cuadro->evento('copiar_estructura_a_plan_personalizado')->ocultar();
		} else {
			$cuadro->evento('copiar_estructura_a_plan_personalizado')->mostrar();
			$cuadro->evento('copiar_estructura_a_plan_personalizado')->set_msg_ayuda('Copiar estructura del plan');
			$msj_confirm = guarani_mensajes::get('800PRO_copiar_plan');
			$cuadro->evento('copiar_estructura_a_plan_personalizado')->set_msg_confirmacion($msj_confirm);
		}
		parent::conf__cuadro($cuadro);
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->dep('editor_plan')->dep('editor_elementos')->dep('disponibles')->resetear_filtros();
		$this->cn()->cargar($seleccion['plan_version'], true);
		$this->set_pantalla('edicion');
		toba::zona()->cargar($seleccion['plan_version']);
	}

	function evt__cuadro__versionar($seleccion)
	{
		$this->s__plan_versionar = $seleccion['plan_version'];
		$this->set_pantalla('pant_versionar');
	}

	function evt__cuadro__copiar_estructura_a_plan_personalizado($seleccion)
	{
		// Se pasa a la pantalla de solicitud de los datos principales del 
		// Plan a crear a partir del seleccionado
		$this->s__plan_a_copiar = current($seleccion);
		$this->set_pantalla('pant_copiar_plan');
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: pant_versionar
	//--------------------------------------------------------------------------

	function evt__versionar()
	{
		$versionador = toba::cn($this->cn()->get_proceso_versionador());

		$parametros = array(
			'id_plan_version' => $this->s__plan_versionar,
			'version' => $this->s__datos_version['version'],
			'nombre' => $this->s__datos_version['nombre']
		);

		$nuevo_plan_version = $versionador->procesar($parametros);

		if ($nuevo_plan_version) {
			toba::zona()->resetear();
			unset($this->s__plan_versionar);
			unset($this->s__datos_version);
			$this->evt__cuadro__seleccion(array('plan_version' => $nuevo_plan_version));
		}
	}

	//--------------------------------------------------------------------------
	//---- form_versonado ------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_versonado(guarani_pers_ei_formulario $form)
	{
		if (empty($this->s__datos_version)) {
			$this->s__datos_version = $this->cn()->get_datos_sugeridos_plan_version($this->s__plan_versionar);
		}
		$form->set_datos($this->s__datos_version);
	}

	function evt__form_versonado__modificacion($datos)
	{
		$this->s__datos_version = $datos;
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: pant_copiar_plan
	//--------------------------------------------------------------------------
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

	function hay_tipo_plan_seleccionado()
	{
		return isset($this->datos_tipo_plan_temp);
	}

	function get_tipo_plan()
	{
		if (isset($this->datos_tipo_plan_temp)) {
			return current($this->datos_tipo_plan_temp);
		}
	}

	function conf__pant_copiar_plan(guarani_ei_pantalla $pantalla)
	{
		if ($this->hay_tipo_plan_seleccionado()) {
			$pantalla->agregar_dep('form_copiar_plan');
		} elseif ($pantalla->existe_dependencia('form_copiar_plan')) {
			$pantalla->eliminar_dep('form_copiar_plan');
		}
		parent::conf();
	}

	//--------------------------------------------------------------------------
	//---- form_copiar_tipo_plan -----------------------------------------------
	//--------------------------------------------------------------------------

	function evt__form_copiar_tipo_plan__habilitar_dependencias_cambio_tipo_plan($tipo_plan)
	{
		/**
		 * Se asignan los datos temp del Tipo de Plan, ya que el evento implícito de modificación de este formulario 
		 * no se diparara en Toba, por haber disparado explícitamente este evento.
		 * También se setea un flag para evitar la modificación de los restantes datos, ya que se dispara la modificación
		 * de los formularios para plan personalizado, convenio y plan estructurado, y los datos no deben conservarse
		 * ante el cambio del Tipo de Plan.
		 */
		$this->datos_tipo_plan_temp = $tipo_plan;
		$this->cambio_tipo = true;
	}

	function conf__form_copiar_tipo_plan(guarani_pers_ei_formulario $form)
	{
		if (isset($this->datos_tipo_plan_temp)) {
			$form->set_datos($this->datos_tipo_plan_temp);
		}
	}

	function evt__form_copiar_tipo_plan__modificacion($datos)
	{
		$this->datos_tipo_plan_temp = $datos;
	}

	//--------------------------------------------------------------------------
	//---- form_copiar_plan ----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_copiar_plan(guarani_pers_ei_formulario $form)
	{
		if ($this->hay_tipo_plan_seleccionado() && $this->get_tipo_plan() != planes::tipo_convenio && $form->existe_ef('convenio')) {
			$form->desactivar_efs(array('convenio'));
		}
		if (isset($this->datos_plan_temp)) {
			$form->set_datos($this->datos_plan_temp);
		}
	}

	function evt__form_copiar_plan__modificacion($datos)
	{
		if (!$this->cambio_tipo) {
			$this->datos_plan_temp = $datos;
		}
	}

	/**
	 * Se duplica el Plan con todos sus datos asociados en la BD, según el plan versión seleccionado.
	 * Si se ejecuta sin errores críticos, se setea el nuevo Plan Versión generado como la Selección actual
	 * para su edición. En caso contrario se emite el mensaje correspondiente.
	 */
	function evt__copiar_plan()
	{
		try {
			if (isset($this->datos_plan_temp)) {
				/**
				 * Datos de la persona y la ubicación para dar de alta la Inscripción a Propuesta y al Alumno.
				 */
				$this->cn()->set_datos_alumno($this->datos_plan_temp);
				// Se seleccionó Período de Inscripción
				$this->cn()->set_datos_periodo($this->datos_plan_temp);
				$tipo_plan = $this->get_tipo_plan();
				if ($tipo_plan == planes::tipo_convenio) {
					$convenio = $this->datos_plan_temp['convenio'];
				} else {
					$convenio = null;
				}
				$id_plan_version = $this->cn()->duplicar_plan($this->s__plan_a_copiar, $tipo_plan, $convenio);

				if ($id_plan_version) {
					toba::zona()->resetear();
					unset($this->s__plan_a_copiar);
					unset($this->datos_tipo_plan_temp);
					unset($this->datos_plan_temp);
					$this->evt__cuadro__seleccion(array('plan_version' => $id_plan_version));
				}
			}
		} catch (guarani_error $e) {
			$this->resetear_cambio_pantalla();
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function js_evt_guardar()
	{
		if ($this->get_id_pantalla() == 'edicion' && $this->cn()->entidad()->es_version_activa() && !$this->cn()->entidad()->es_plan_personalizado() && $this->dep('editor_plan')->existe_dependencia('form_plan_version')) {
			$msj_baja_plan_version = guarani_mensajes::get('800PRO_confirma_baja_plan_version', array($this->cn()->entidad()->get_codigo_version(), $this->cn()->entidad()->get_nombre()));
			$doc_baja = $this->dep('editor_plan')->dependencia('form_plan_version')->ef('documento_baja')->tiene_estado() ? 1 : 0;
			$fecha_baja = $this->dep('editor_plan')->dependencia('form_plan_version')->ef('fecha_baja')->tiene_estado() ? 1 : 0;

			return "
				function confirmar_baja_plan_version() {
					if ({$doc_baja} || {$fecha_baja}) {
						return confirm('$msj_baja_plan_version');
					}
					return true;
				}

				{$this->objeto_js}.evt__guardar = function() {
					if (confirmar_no_cambios()) {
						return confirmar_baja_plan_version();
					} else {
						return false;
					}
				}
			";
		} else {
			return parent::js_evt_guardar();
		}
	}

	//--------------------------------------------------------------------------
	//---- Javascript ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();

		if ($this->get_id_pantalla() == 'pant_copiar_plan') {
			$id_js_form_copiar_tipo_plan = $this->dependencia('form_copiar_tipo_plan')->get_id_objeto_js();

			echo "
				/**
				* Se utiliza desde las dependencias (formularios) que se agregan y eliminan ante el cambio de Tipo de Plan
				* para evitar que se chequeen los campos obligatorios ante la selección de un nuevo valor en ese combo, 
				* antes de modificar las dependencias asociadas a la pantalla según ese valor.
				*/
		
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
		
				//---- Procesamiento de EFs -----------------------------------	
				
				/** Cuando se cambia el campo del Tipo de Plan seleccionado se dispara un submit para agregar 
				*  las dependencias correspondientes al mismo.
				*/
				{$id_js_form_copiar_tipo_plan}.evt__tipo_plan__procesar = function(es_inicial)
				{
					if (!es_inicial) {
						/**
						 * Se deshabilitan las validaciones de las dependencias del Ci Controlador, ante el cambio del valor 
						 * del Combo Tipo Plan, para poder hacer el submit y eliminar y agregar las dependencias necesarias, 
						 * sin controlar los campos obligatorios.
						 **/
						{$id_js_form_copiar_tipo_plan}.controlador.set_validar_todo(false);    
						tipo = this.ef('tipo_plan').get_estado();
						params = [tipo];
						this.set_evento(new evento_ei('habilitar_dependencias_cambio_tipo_plan', false, false, params), true);  
					}
				} 
			";
		}
	}

}
?>