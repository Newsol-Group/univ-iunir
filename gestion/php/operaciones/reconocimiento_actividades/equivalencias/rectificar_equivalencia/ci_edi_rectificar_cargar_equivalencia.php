<?php
/**
 *
 * 
 * @package		Reconocimiento de Actividades
 * @subpackage	Equivalencias
 * 
 */
class ci_edi_rectificar_cargar_equivalencia extends guarani_pers_ci
{
	protected $s__datos;
	protected $datos_actividades_temp;
	protected $datos_origenes;
	protected $origen_ext = false;

	function puede_registrar_desaprobadas()
	{
		if (guarani::parametros()->get_valor('equiv_registrar_desaprobados') == comunes::si) {
			return true;
		} else {
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//---- Configuraciones -----------------------------------------------------
	//--------------------------------------------------------------------------
	
	function conf()
	{
		$this->s__datos = $this->controlador()->get_datos_basicos();
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA: seleccion_equiv
	//--------------------------------------------------------------------------
	
	//--------------------------------------------------------------------------
	//---- cuadro_actividad ----------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_actividades(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_equiv_otorgada();
		$etiquetas = guarani_etiquetas::get_tabla('sga_equiv_otorgada');
		$etiqueta = $etiquetas->get_enf('plural');
		$mje = guarani_mensajes::get('800_eof_cuadro', array($etiqueta));

		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje($mje);
	}

	function evt__cuadro_actividades__seleccion($seleccion)
	{
		unset($this->datos_actividades_temp);
		unset($this->datos_origenes);
		$this->cn()->entidad()->set_cursor_equiv_otorgada($seleccion);
		$this->set_pantalla('edicion');
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		// Se eliminan eventos de manera condicional
		if (is_null($this->cn()->entidad()->get_cursor_equiv_otorgada())) {
			$pantalla->eliminar_evento('eliminar');
		}

		// Se guarda el dato en memoria para la selección de la actividad si no 
		// se eligió un convenio con lista de actividades específicas
		if ($this->s__datos['origen'] == equivalencia::origen_ext_convenio) {
			$actividades_convenio = toba::consulta_php('co_convenios')->get_actividades_convenio($this->s__datos['convenio']);
			$in = guarani_sql::armar_condicion_in($actividades_convenio, 'sga_elementos', 'elemento');
			$lista_actividades = toba::consulta_php('co_elementos')->get_actividades_plan_version($this->s__datos['plan_version'], $in);
			toba::memoria()->eliminar_dato('plan_version');
			toba::memoria()->eliminar_dato('alumno');
			toba::memoria()->set_dato('lista_actividades', $lista_actividades);
		} else {
			toba::memoria()->eliminar_dato('lista_actividades');
			toba::memoria()->set_dato('plan_version', $this->s__datos['plan_version']);
			toba::memoria()->set_dato('alumno', $this->s__datos['alumno']);
		}

		// Se oculta el ml de orígenes externos o internos según corresponda
		if (($this->s__datos['origen'] == equivalencia::origen_ext_convenio) ||
			($this->s__datos['origen'] == equivalencia::origen_ext_libre ) ||
			($this->s__datos['origen'] == equivalencia::origen_ext_pase)) {
			$pantalla->eliminar_dep('form_ml_origen_int');
		} else {
			$pantalla->eliminar_dep('form_ml_origen_ext');
		}
	}

	//--------------------------------------------------------------------------
	//---- form_actividad ------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Permite cambiar la configuración del formulario previo a la generación de la salida
	 * El formato del carga debe ser array(<campo> => <valor>, ...)
	 */
	function conf__form_actividad(toba_ei_formulario $form)
	{
		$solo_lectura = array();

		if (isset($this->datos_actividades_temp)) {
			$datos = $this->datos_actividades_temp;
		} else {
			if (!is_null($this->cn()->entidad()->get_cursor_equiv_otorgada())) {
				$datos = $this->cn()->entidad()->get_dato_equiv_otorgada();
			} else {
				$datos = array();
				// Fecha
				$datos['fecha'] = guarani_fecha::get_hoy();
				// Tipo Equivalencia
				if ($this->s__datos['origen'] == equivalencia::origen_ext_convenio) {
					$datos['instancia'] = equivalencia::equiv_instancia_total;
					$solo_lectura[] = 'instancia';
				}
			}
		}
		if (isset($datos['escala_nota'])) {
			$datos['escala_asignada'] = $datos['escala_nota'];
			$datos['con_nota'] = 1;
		} else {
			$datos['escala_asignada'] = 0;
			$datos['con_nota'] = 0;
		}
		$form->set_datos($datos);
		$form->set_solo_lectura($solo_lectura);
	}

	/**
	 * Atrapa la interacción del usuario con el botón asociado
	 * 
	 * @param array $datos Estado del componente al momento de ejecutar el evento. El formato es el mismo que en la carga de la configuración
	 */
	function evt__form_actividad__modificacion($datos)
	{
		$this->datos_actividades_temp = $datos;
	}

	//--------------------------------------------------------------------------
	//---- ORIGENES  -----------------------------------------------------------
	//--------------------------------------------------------------------------
	
	//--------------------------------------------------------------------------
	//---- form_ml_origen_ext --------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Permite cambiar la configuración del ML previo a la generación de la salida
	 * El formato debe ser una matriz array('id_fila' => array('id_ef' => valor, ...), ...)
	 */
	function conf__form_ml_origen_ext(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_origenes)) {
			$form_ml->set_datos($this->datos_origenes);
		} else {
			if (!is_null($this->cn()->entidad()->get_cursor_equiv_otorgada())) {
				$datos = $this->cn()->entidad()->get_datos_externa();
				$form_ml->set_datos($datos);
			}
		}
	}

	/**
	 * Atrapa la interacción del usuario con el botón asociado
	 * @param array $datos Estado del componente al momento de ejecutar el evento. El formato es el mismo que en la carga de la configuración
	 */
	function evt__form_ml_origen_ext__modificacion($datos)
	{
		$this->origen_ext = true;
		$this->datos_origenes = $datos;
	}

	//--------------------------------------------------------------------------
	//---- form_ml_origen_int --------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Permite cambiar la configuración del ML previo a la generación de la salida
	 * El formato debe ser una matriz array('id_fila' => array('id_ef' => valor, ...), ...)
	 */
	function conf__form_ml_origen_int(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_origenes)) {
			$form_ml->set_datos($this->datos_origenes);
		} else {
			if (!is_null($this->cn()->entidad()->get_cursor_equiv_otorgada())) {
				$datos = $this->cn()->entidad()->get_datos_interna();
				$form_ml->set_datos($datos);
			}
		}
	}

	/**
	 * Atrapa la interacción del usuario con el botón asociado
	 * @param array $datos Estado del componente al momento de ejecutar el evento. El formato es el mismo que en la carga de la configuración
	 */
	function evt__form_ml_origen_int__modificacion($datos)
	{
		$this->datos_origenes = $datos;
	}

	//--------------------------------------------------------------------------
	//---- Validaciones --------------------------------------------------------
	//--------------------------------------------------------------------------

	function validar_datos()
	{
		$this->validar_datos_origenes();
	}

	function validar_datos_origenes()
	{
		// Se valida que se haya ingresado al menos un origen para la actividad si el parámetro lo requiere
		if ($this->s__datos['origen'] == equivalencia::origen_ext_pase) {
			if ((guarani::parametros()->get_valor('equiv_pase_registrar_actividades_externas') == comunes::si) && (empty($this->datos_origenes))) {
				$msj = guarani_mensajes::get('800EQU_validar_actividades_externa');
				throw new guarani_error($msj);
				return false;
			}
		} else {
			if (($this->s__datos['origen'] == equivalencia::origen_ext_libre) || ($this->s__datos['origen'] == equivalencia::origen_ext_convenio)) {
				if ((guarani::parametros()->get_valor('equiv_externa_registrar_actividades_externas') == comunes::si) && (empty($this->datos_origenes))) {
					$msj = guarani_mensajes::get('800EQU_validar_actividades_externa');
					throw new guarani_error($msj);
					return false;
				}
			}
		}

		return true;
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Originalmente este método limpia las variables y definiciones del componente, 
	 * y en caso de exisitr un CN asociado ejecuta su cancelar. Para mantener este 
	 * comportamiento llamar a parent::evt__cancelar
	 */
	function evt__cancelar()
	{
		$this->cn()->entidad()->unset_cursor_equiv_otorgada();
		unset($this->datos_actividades_temp);
		unset($this->datos_origenes);

		$this->set_pantalla('seleccion_equiv');
	}

	/**
	 * Atrapa la interacción del usuario a través del botón asociado. El método no recibe parámetros
	 */
	function evt__eliminar()
	{
		try {
			if (!is_null($this->cn()->entidad()->get_cursor_equiv_otorgada())) {
				$cursor = $this->cn()->entidad()->get_cursor_equiv_otorgada();
				$this->cn()->entidad()->set_cursor_equiv_otorgada($cursor);
				if ($this->origen_ext) {
					$this->cn()->entidad()->eliminar_todo_externas();
				} else {
					$this->cn()->entidad()->eliminar_todo_internas();
				}
				$this->cn()->entidad()->eliminar_equiv_otorgada($cursor);
				unset($this->datos_actividades_temp);
				unset($this->datos_origenes);

				$this->set_pantalla('seleccion_equiv');
			}
		} catch (guarani_error $e) {
			throw new guarani_error($e->getMessage());
		}
	}

	/**
	 * Atrapa la interacción del usuario a través del botón asociado. El método no recibe parámetros
	 */
	function evt__guardar()
	{
		try {
			$this->validar_datos();
			if (!is_null($this->cn()->entidad()->get_cursor_equiv_otorgada())) {
				// Se está editando
				$this->cn()->entidad()->set_datos_equiv_otorgada($this->datos_actividades_temp);
				$cursor = $this->cn()->entidad()->get_cursor_equiv_otorgada();
			} else {
				// Se está agregando
				$cursor = $this->cn()->entidad()->agregar_equiv_otorgada($this->datos_actividades_temp);
			}
			
			$this->cn()->entidad()->set_cursor_equiv_otorgada($cursor);
			
			if ($this->origen_ext) {
				$this->cn()->entidad()->set_datos_externa($this->datos_origenes);
			} else {
				// Se está agregando
				$this->cn()->entidad()->set_datos_interna($this->datos_origenes);
			}

			$this->cn()->entidad()->unset_cursor_equiv_otorgada();
			
			unset($this->datos_actividades_temp);
			unset($this->datos_origenes);

			$this->set_pantalla('seleccion_equiv');
		} catch (guarani_error $e) {
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__agregar()
	{
		$this->set_pantalla('edicion');
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__escala_nota_default($params, toba_ajax_respuesta $respuesta)
	{
		$elemento = current($params);
		$escala_nota_equivalencia = toba::consulta_php('co_elementos')->get_escala_nota_equivalencia_elemento_plan_version($elemento, $this->s__datos['plan_version']);
		if (!empty($escala_nota_equivalencia)) {
			$salida = array('escala_nota_equivalencia' => $escala_nota_equivalencia);
			$respuesta->set($salida);
		}
	}
}