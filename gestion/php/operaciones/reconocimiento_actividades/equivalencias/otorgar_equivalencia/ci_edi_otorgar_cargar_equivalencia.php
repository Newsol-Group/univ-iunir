<?php
/**
 *
 *
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Otorgar Equivalencia
 * 
 */
class ci_edi_otorgar_cargar_equivalencia extends guarani_pers_ci
{
	protected $s__datos;
	protected $datos_actividades_temp;
	protected $datos_origenes;
	protected $origen_ext = false;

	function puede_registrar_desaprobadas()
	{
		return (guarani::parametros()->get_valor('equiv_registrar_desaprobados') == comunes::si);
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Ventana que se ejecuta al inicio de la etapa de configuración. Antes de la configuración de la pantalla y sus componentes
	 * Se utiliza por ejemplo para determinar qué pantalla mostrar, eliminar tabs, etc.
	 */
	function conf()
	{
		$this->s__datos = $this->controlador()->get_datos_basicos();
		$datos_alu = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($this->s__datos['alumno']);
		$this->s__datos['alumno_persona'] = $datos_alu['persona'];
		$this->s__datos['alumno_apellido_y_nombre'] = $datos_alu['apellido_y_nombre'];
		$this->s__datos['id_alumno_propuesta'] = $datos_alu['propuesta'];
		$this->s__datos['id_alumno_plan'] = $datos_alu['plan'];
	}

	function post_configurar()
	{
		if ($this->cn()->entidad()->hay_equivalencia_seleccionada()) {
			$this->controlador()->controlador()->evento('cancelar')->desactivar();
			$this->controlador()->controlador()->evento('guardar')->desactivar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: seleccion_equiv
	//-----------------------------------------------------------------------------------

	function conf__seleccion_equiv(toba_ei_pantalla $pantalla)
	{
		/**
		 * Si la equivalencia otorgada NO es de tipo Interna, se deshabilita el evento de Aplicar Matrices.
		 * En caso contrario se configuran los parámetros correspondientes para su vínculo.
		 */
		if ($this->s__datos['tipo_equivalencia'] <> equivalencia::origen_int_libre &&
				$this->s__datos['tipo_equivalencia'] <> equivalencia::origen_int_prop &&
				$this->s__datos['tipo_equivalencia'] <> equivalencia::origen_int_plan &&
				$this->s__datos['tipo_equivalencia'] <> equivalencia::origen_int_version) {
			$pantalla->evento('aplicar_matrices')->desactivar();
		} else {
			$params = array('alumno' => $this->s__datos['alumno'],
				'persona' => $this->s__datos['alumno_persona'],
				'alumno_nombre' => $this->s__datos['alumno_apellido_y_nombre'],
				'alumno_plan_version' => $this->s__datos['cod_plan_version_origen'],
				'alumno_version_nombre' => $this->s__datos['alumno_version'],
				'alumno_propuesta' => $this->s__datos['id_alumno_propuesta'],
				'alumno_prop_nombre' => $this->s__datos['alumno_propuesta'],
				'alumno_plan' => $this->s__datos['id_alumno_plan'],
				'alumno_plan_nombre' => $this->s__datos['alumno_plan'],
				'propuesta_origen' => $this->s__datos['propuesta'],
				'plan_origen' => $this->s__datos['plan'],
				'version_origen' => $this->s__datos['plan_version'],
				'tipo_equivalencia' => $this->s__datos['tipo_equivalencia']);

			// Parámetros para el vínculo del evento aplicar matrices.
			$this->evento('aplicar_matrices')->vinculo()->set_parametros($params);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_actividad -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

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

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: edicion
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		// Se eliminan eventos
		if (is_null($this->cn()->entidad()->get_cursor_equiv_otorgada())) {
			$pantalla->eliminar_evento('eliminar');
		}
		// Dato en memoria para la selección de la actividad si no se eligió un convenio con lista de actividades específicas
		if ($this->s__datos['tipo_equivalencia'] != equivalencia::origen_ext_convenio) {
			toba::memoria()->eliminar_dato('lista_actividades');
			toba::memoria()->set_dato('plan_version', $this->s__datos['cod_plan_version_origen']);
			toba::memoria()->set_dato('alumno', $this->s__datos['alumno']);
		}
		// Se ocultaOculto el ml de orígenes externos o internos según corresponda
		if (($this->s__datos['tipo_equivalencia'] == equivalencia::origen_ext_convenio) ||
			($this->s__datos['tipo_equivalencia'] == equivalencia::origen_ext_libre ) ||
			($this->s__datos['tipo_equivalencia'] == equivalencia::origen_ext_pase)) {
			$pantalla->eliminar_dep('form_ml_origen_int');
		} else {
			$pantalla->eliminar_dep('form_ml_origen_ext');
		}

		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		$this->controlador()->controlador()->evento('guardar')->desactivar();
	}

	/**
	 * Ventana de extensión para ejecutar controles antes de salir de la página.
	 * Se ejecuta luego de lanzar los eventos del ci.
	 * Si se lanza una excepcion se evita el cambio de pantalla.
	 */
	function evt__edicion__salida()
	{
		unset($this->datos_actividades_temp);
		unset($this->datos_origenes);
		if ($this->cn()->entidad()->hay_equivalencia_seleccionada()) {
			$this->cn()->entidad()->unset_cursor_equiv_otorgada();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_actividad ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
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
				$datos['fecha'] = guarani_fecha::get_hoy();
				if ($this->s__datos['tipo_equivalencia'] == equivalencia::origen_ext_convenio) {
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

	//-----------------------------------------------------------------------------------
	//---- ORIGENES  --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- form_ml_origen_ext -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Permite cambiar la configuración del ML previo a la generación de la salida
	 * El formato debe ser una matriz array('id_fila' => array('id_ef' => valor, ...), ...)
	 */
	function conf__form_ml_origen_ext(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_origenes)) {
			$form_ml->set_datos($this->datos_origenes);
		} else {
			$datos = $this->cn()->entidad()->get_datos_externa();
			if (!empty($datos)) {
				$form_ml->set_datos($datos);
			}
		}
	}

	/**
	 * Atrapa la interacción del usuario con el botón asociado
	 * 
	 * @param array $datos Estado del componente al momento de ejecutar el evento. El formato es el mismo que en la carga de la configuración
	 */
	function evt__form_ml_origen_ext__modificacion($datos)
	{
		$this->origen_ext = true;
		$this->datos_origenes = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_origen_int -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Permite cambiar la configuración del ML previo a la generación de la salida
	 * El formato debe ser una matriz array('id_fila' => array('id_ef' => valor, ...), ...)
	 */
	function conf__form_ml_origen_int(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_origenes)) {
			$form_ml->set_datos($this->datos_origenes);
		} else {
			$datos = $this->cn()->entidad()->get_datos_interna();
			if (!empty($datos)) {
				$form_ml->set_datos($datos);
			}
		}
	}

	/**
	 * Atrapa la interacción del usuario con el botón asociado
	 * 
	 * @param array $datos Estado del componente al momento de ejecutar el evento. El formato es el mismo que en la carga de la configuración
	 */
	function evt__form_ml_origen_int__modificacion($datos)
	{
		$this->datos_origenes = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Validaciones -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function validar_datos()
	{
		$this->validar_datos_actividades();
		$this->validar_datos_origenes();
		$this->cn()->entidad()->validar_datos_otorgadas($this->datos_actividades_temp);
	}

	function validar_datos_actividades()
	{
		// Validación de datos obligatorios
		if (is_null($this->datos_actividades_temp['elemento'])) {
			$msj = guarani_mensajes::get('dato_faltante', array('Actividad'));
			throw new guarani_error($msj);
		}

		// Validación de la fecha de vencimiento segun parámetros
		if (($this->datos_actividades_temp['instancia'] == equivalencia::equiv_instancia_parcial) && (guarani::parametros()->get_valor('equiv_parcial_registrar_fin_de_vigencia') == comunes::si)) {
			// Chequear q se haya incluido vencimiento
			if (is_null($this->datos_actividades_temp['fecha_vigencia'])) {
				$msj = guarani_mensajes::get('dato_faltante', array('Vencimiento'));
				throw new guarani_error($msj);
			}
		} else {
			if (($this->datos_actividades_temp['instancia'] == equivalencia::equiv_instancia_regularidad) && (guarani::parametros()->get_valor('equiv_regularidad_registrar_fin_de_vigencia') == comunes::si)) {
				// Chequear q se haya incluido vencimiento
				if (is_null($this->datos_actividades_temp['fecha_vigencia'])) {
					$msj = guarani_mensajes::get('dato_faltante', array('Vencimiento'));
					throw new guarani_error($msj);
				}
			}
		}
	}

	function validar_datos_origenes()
	{
		// Se valida que se haya ingresado al menos un origen para la actividad si el parámetro lo requiere
		if ($this->s__datos['tipo_equivalencia'] == equivalencia::origen_ext_pase) {
			if ((guarani::parametros()->get_valor('equiv_pase_registrar_actividades_externas') == comunes::si) && (!$this->tiene_origen_para_actividad($this->datos_origenes))) {
				$msj = guarani_mensajes::get('800EQU_validar_actividades_externa');
				throw new guarani_error($msj);
			}
		} else {
			if (($this->s__datos['tipo_equivalencia'] == equivalencia::origen_ext_libre) || ($this->s__datos['tipo_equivalencia'] == equivalencia::origen_ext_convenio)) {
				if ((guarani::parametros()->get_valor('equiv_externa_registrar_actividades_externas') == comunes::si) && (!$this->tiene_origen_para_actividad($this->datos_origenes))) {
					$msj = guarani_mensajes::get('800EQU_validar_actividades_externa');
					throw new guarani_error($msj);
					return false;
				}
			}
		}
		return true;
	}

	function tiene_origen_para_actividad($origenes)
	{
		foreach ($origenes as $origen) {
			if ($origen['apex_ei_analisis_fila'] != 'B') {
				return true;
			}
		}
		return false;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Originalmente este método limpia las variables y definiciones del componente, y en caso de existir 
	 * un CN asociado ejecuta su cancelar. Para mantener este comportamiento llamar a parent::evt__cancelar
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
			if (!is_null($this->cn()->entidad()->get_cursor_equiv_otorgada())) { //se está editando
				$this->cn()->entidad()->set_datos_equiv_otorgada($this->datos_actividades_temp);
				$cursor = $this->cn()->entidad()->get_cursor_equiv_otorgada();
			} else {
				//se está agregando
				$cursor = $this->cn()->entidad()->agregar_equiv_otorgada($this->datos_actividades_temp);
			}
			$this->cn()->entidad()->set_cursor_equiv_otorgada($cursor);
			if ($this->origen_ext) {
				$this->cn()->entidad()->set_datos_externa($this->datos_origenes);
			} else {
				//se está agregando
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

	/**
	 * Evento disparado al seleccionar un conjunto de matrices, para evaluar y generar las equivalencias 
	 * a otorgar por aplicación de las mismas, para su posterior edición.
	 */
	function evt__generar_equiv_matrices($claves)
	{		
		$ajustar_notas = guarani::parametros()->get_valor('equiv_automatica_ajustar_nota');
		$pasar_desap = guarani::parametros()->get_valor('equiv_automatica_pasar_desaprobados');
		$venc_origen = guarani::parametros()->get_valor('equiv_automatica_vencimiento_origen');
		$pasar_reg = guarani::parametros()->get_valor('equiv_automatica_pasar_regularidades');
		$reg_con_nota = guarani::parametros()->get_valor('equiv_automatica_pasar_regularidades_con_nota');
		
		// Retorno del popup codificado con el delimitador para poder retornar claves múltiples.
		$matrices = explode('$$&$$', $claves);
		/**
		 * Id interno de la función evaluar matrices. Se utiliza internamente para relacionar a los registros de todas las
		 * equivalencias internas asociadas a una misma equivalencia otorgada, del conjunto de todas las equivalencias 
		 * evaluadas para la matriz. Inicialmente es 1, y luego se debe utilizar el mayor id retornado en la llamada
		 * anterior a la función.
		 */
		$id_interno = 1;
		$plan_version_alumno = $this->s__datos['cod_plan_version_origen'];
		$alumno = $this->s__datos['alumno'];
		$persona = $this->s__datos['alumno_persona'];
		$plan_origen = $this->s__datos['plan'];
		$plan_version_origen = $this->s__datos['plan_version'];
		$propuesta_origen = $this->s__datos['propuesta'];
		$msj_equiv_no_otorgadas = '';
		foreach ($matrices as $matriz) {
			/**
			 * Se obtienen las equivalencias que se otorgarían como resultado de la aplicación de cada Matriz seleccionada.
			 */
			$equivalencias_matriz = guarani::act('act_equivalencias')->evaluar_equivalencias_matriz($id_interno, $matriz, $alumno, $plan_version_alumno, $persona, $ajustar_notas, $pasar_desap, $venc_origen, $pasar_reg, $reg_con_nota, $propuesta_origen, $plan_origen, $plan_version_origen);			
			// El resultado de la función evaluarlo para dar de alta los datos en las tablas correspondientes del DR.
			$id_nueva = null;
			$id_mayor = 0;
			if (!empty($equivalencias_matriz)) {
				foreach ($equivalencias_matriz as $equiv) {
					/**
					 * Recorrer dando de alta registros en Equivalencia Otorgada primero, y en Equivalencias Internas después,
					 * hasta que se modifique el id interno. Ahí se cambia el cursor de Equiv Otorgada. La tabla cabecera: 
					 * equiv tramite siempre tiene el cursor posicionado en la única fila (en edición o siendo agregada).
					 * Además se va actualizando el MAYOR id interno, para setearlo al salir de este foreach en la var
					 * de id interno para la próxima llamada a la función.
					 */
					$id_equiv_otorgada = $equiv['id'];
					if ($id_nueva <> $id_equiv_otorgada) {
						$id_nueva = $id_equiv_otorgada;
						if ($id_nueva > $id_mayor) {
							$id_mayor = $id_nueva;
						}
						try {
							$id_fila = $this->cn()->entidad()->agregar_equiv_otorgada($equiv);
							$this->cn()->entidad()->set_cursor_equiv_otorgada($id_fila);
						} catch (guarani_error $e) {
							/**
							 * En caso de que las validaciones no permitan dar de alta la nueva equiv otorgada, 
							 * se saltea este registro. El cursor no quedará posicionado en ninguna fila de esta tabla, 
							 * para tampoco dar de alta las equivalencias internas de dicha equiv otorgada que vendrán
							 * en los próximos registros del array retornado por la matriz.
							 */
							$datos_elemento = current(toba::consulta_php('co_elementos')->get_actividad_nombre($equiv['elemento']));
							$msj_equiv_no_otorgadas .= guarani_mensajes::get('800EQU_equiv_no_otorgada', array($datos_elemento['codigo_y_nombre'])) . " " . $e->get_mensaje() . "<br><br>";
							$this->cn()->entidad()->resetear_errores();
						}
					}
					if ($this->cn()->entidad()->hay_equivalencia_seleccionada()) {
						// Si se pudo dar de alta una equiv otorgada para estas equivalencias internas.
						$datos_equiv_interna['propuesta'] = $equiv['int_propuesta'];
						$datos_equiv_interna['elemento'] = $equiv['int_elemento'];
						$datos_equiv_interna['fecha'] = $equiv['int_fecha'];
						$datos_equiv_interna['nota'] = $equiv['int_nota'];
						$datos_equiv_interna['origen'] = $equiv['int_origen'];
						$datos_equiv_interna['resultado'] = $equiv['int_resultado'];
						$datos_equiv_interna['id_acta'] = $equiv['int_id_acta'];
						$datos_equiv_interna['equivalencia_origen'] = $equiv['int_equivalencia'];
						$datos_equiv_interna['reconocimiento_act'] = $equiv['int_reconocimiento_act'];						
						$this->cn()->entidad()->agregar_interna($datos_equiv_interna);						
					}
				}
				$this->cn()->entidad()->unset_cursor_equiv_otorgada();
				$id_interno = $id_mayor;
			}
		}

		if (!empty($msj_equiv_no_otorgadas)) {
			toba::notificacion()->agregar($msj_equiv_no_otorgadas, 'info');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__escala_nota_default($params, toba_ajax_respuesta $respuesta)
	{
		$elemento = current($params);
		$escala_nota_equivalencia = toba::consulta_php('co_elementos')->get_escala_nota_equivalencia_elemento_plan_version($elemento, $this->s__datos['cod_plan_version_origen']);
		if (!empty($escala_nota_equivalencia)) {
			$salida = array('escala_nota_equivalencia' => $escala_nota_equivalencia);
			$respuesta->set($salida);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JavaScript -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$id_js = $this->get_id_objeto_js();
		echo "
		
		function respuesta_popup(claves)
		{
			params = [];
			params = [claves];
			$id_js.set_evento(new evento_ei('generar_equiv_matrices', false, false, params), true);
		}
		";
	}

}
?>