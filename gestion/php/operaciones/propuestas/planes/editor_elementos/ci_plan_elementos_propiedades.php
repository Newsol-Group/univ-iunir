<?php
/**
 * Edicion de las propiedades de los elementos que conforman el PLAN
 */
class ci_plan_elementos_propiedades extends guarani_pers_ci
{
	protected $s__id_nodo;
	protected $s__id_nodo_padre;
	protected $datos_orden_comp_temp;
	protected $datos_regla_temp;
	protected $datos_mg_prop_temp;
	protected $datos_mod_atrib_temp;
	protected $datos_act_prop_temp;
	protected $error;

	function set_edicion_nodo($id_nodo, $id_nodo_padre)
	{
		$this->s__id_nodo = $id_nodo;
		$this->s__id_nodo_padre = $id_nodo_padre;
		// Se define la pantalla según el tipo de nodo
		$tipo = $this->elemento($this->s__id_nodo)->get_tipo();
		$subtipo = $this->elemento($this->s__id_nodo)->get_subtipo();
		if ($tipo == elemento::tipo_modulo) {
			if ($subtipo == elemento::subtipo_materia_generica) {
				$pantalla = 'mg';
			} elseif ($subtipo == elemento::subtipo_modulo_rtf) {
				$pantalla = 'modulo_rtf';
			} else {
				$pantalla = 'modulo';
			}
			$this->dep('regla')->set_regla_tipo(regla::tipo_cumplimiento_modulos);
			$this->dep('regla')->resetear();
			$this->conf__form_regla();
		} else {
			$pantalla = 'actividad';
		}
		$this->set_pantalla($pantalla);
	}

	function desactivar_edicion_nodo()
	{
		unset($this->s__id_nodo);
		unset($this->s__id_nodo_padre);
	}

	//--------------------------------------------------------------------------
	//--- Modificacion de las propiedades de los nodos
	//--------------------------------------------------------------------------
	
	//--------------------------------------------------------------------------
	//---- ACTIVIDADES ---------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__form_act_prop__modificacion($datos)
	{
		$this->datos_act_prop_temp = $datos;
	}

	function conf__form_act_prop(toba_ei_formulario $form)
	{
		if (isset($this->s__id_nodo) && isset($this->s__id_nodo_padre)) {
			if (!isset($this->datos_act_prop_temp)) {
				$elemento = $this->elemento($this->s__id_nodo);
				$datos = $elemento->get_datos();
				$datos['modalidad'] = $elemento->get_modalidades();
			} else {
				$this->controlador->controlador->controlador->set_hubo_cambios();
				$datos = $this->datos_act_prop_temp;
			}
			$form->set_datos($datos);
			// Las escalas de notas no son obligatorias para actividades extracurriculares.
			if ($this->elemento($this->s__id_nodo)->get_subtipo() != elemento::subtipo_actividad_extracurricular) {
				$efs = array('escala_nota_cursada_regular', 'escala_nota_cursada_promocion', 'escala_nota_examen', 'escala_nota_equivalencia');
				foreach ($efs as $ef) {
					$form->ef($ef)->set_estilo_etiqueta('ei-form-etiq-oblig');
					$etiqueta = $form->ef($ef)->get_etiqueta().' (*)';
					$form->ef($ef)->set_etiqueta($etiqueta);
					$form->ef($ef)->set_obligatorio();
				}
			}
			if ($this->plan()->edicion_version_bloqueada()) {
				$form->eliminar_evento('modificacion');
				$form->set_solo_lectura();
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- MATERIAS GENERICAS --------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__form_mg_prop__modificacion($datos)
	{
		$this->datos_mg_prop_temp = $datos;
	}

	function conf__form_mg_prop(toba_ei_formulario $form)
	{
		$this->dep('regla')->set_solo_lectura();
		$this->dep('form_mg_prop')->set_solo_lectura('origen_creditos');
		$this->dep('form_mg_prop')->set_solo_lectura('creditos');
		if (isset($this->s__id_nodo) && isset($this->s__id_nodo_padre)) {
			if (!isset($this->datos_mg_prop_temp)) {
				$elemento = $this->elemento($this->s__id_nodo);
				$datos = $elemento->get_datos($this->s__id_nodo_padre);
			} else {
				$this->controlador->controlador->controlador->set_hubo_cambios();
				$datos = $this->datos_mg_prop_temp;
			}
			$form->set_datos($datos);
			if ($this->plan()->edicion_version_bloqueada()) {
				$form->set_solo_lectura();
				$form->eliminar_evento('modificacion');
			}
		}
	}

	function conf__cuadro_optativas(toba_ei_cuadro $cuadro)
	{
		if (guarani::parametros()->get_valor('pro_ver_optativas_en_estructura_plan') == comunes::si) {
			$datos = $this->elemento($this->s__id_nodo)->get_info_hijos();
		} else {
			$datos_elemento = $this->elemento($this->s__id_nodo)->get_datos();
			$plan_version = $this->cn()->entidad()->get_id_plan_version();
			$datos = toba::consulta_php('co_elementos')->get_optativas_generica($datos_elemento['elemento'], $plan_version);
		}
		$cuadro->set_datos($datos);
		$etiquetas = guarani_etiquetas::get_tabla('sga_elementos_comp');
		$msj_eof = guarani_mensajes::get('800_eof_cuadro', array($etiquetas->get_enf('plural')));
		if ($msj_eof) {
			$cuadro->set_eof_mensaje($msj_eof);
		}
	}

	function conf_evt__cuadro_optativas__ver_atributos(toba_evento_usuario $evento, $fila)
	{
		if (guarani::parametros()->get_valor('pro_ver_optativas_en_estructura_plan') == comunes::no) {
			$datos = $this->dependencia('cuadro_optativas')->get_datos();
			$datos_fila = $datos[$fila];
			$plan_version = $this->cn()->entidad()->get_id_plan_version();
			$evento->vinculo()->agregar_parametro(utf8_encode('elemento_revision'), utf8_encode($datos_fila['elemento_revision']));
			$evento->vinculo()->agregar_parametro(utf8_encode('plan_version'), utf8_encode($plan_version));
		} else {
			$evento->anular();
		}
	}

	//--------------------------------------------------------------------------
	//---- MGs y MODULOS -------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_regla()
	{
		if (isset($this->datos_regla_temp)) {
			$regla = $this->datos_regla_temp;
		} else {
			$regla = $this->get_datos_regla();
		}
		$this->dep('regla')->set_regla($regla['regla'], (isset($regla['parametros']) ? $regla['parametros'] : null));
		if ($this->plan()->edicion_version_bloqueada()) {
			$this->dep('regla')->set_solo_lectura();
		}
	}

	function get_datos_regla()
	{
		$datos_nodo = $this->elemento($this->s__id_nodo)->get_datos();
		$datos['regla'] = $datos_nodo['regla'];
		if (isset($datos_nodo['parametros'])) {
			$datos['parametros'] = $datos_nodo['parametros'];
		}
		return ($datos);
	}

	function evt__form_regla__modificacion($datos)
	{
		if (!isset($datos['parametros'])) {
			$datos['parametros'] = null;
		}
		$this->datos_regla_temp = $datos;
	}

	//--------------------------------------------------------------------------
	//---- MODULOS -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__modulo()
	{
		// Si no tiene hijos, se oculta el formulario que muestra la composición
		$cantidad_hijos = $this->elemento($this->s__id_nodo)->get_cantidad_hijos();
		if ($cantidad_hijos == 0) {
			$this->pantalla()->eliminar_dep('form_orden');
		}
	}

	function evt__form_mod_atrib__modificacion($datos)
	{
		$this->datos_mod_atrib_temp = $datos;
	}

	function conf__form_mod_atrib(toba_ei_formulario $form)
	{
		if (isset($this->s__id_nodo) && (isset($this->s__id_nodo_padre) || is_null($this->s__id_nodo_padre))) {
			$elemento = $this->elemento($this->s__id_nodo);
			if (!isset($this->datos_mod_atrib_temp)) {
				$datos = $elemento->get_datos($this->s__id_nodo_padre);
			} else {
				$datos = $this->datos_mod_atrib_temp;
				$this->controlador->controlador->controlador->set_hubo_cambios();
			}
			$form->set_datos($datos);
			if ($this->plan()->edicion_version_bloqueada()) {
				$form->set_solo_lectura();
				$form->eliminar_evento('modificacion');
				$this->dep('regla')->set_solo_lectura();
			}
		}
	}
	
	//--------------------------------------------------------------------------
	//---- MODULOS RTF ---------------------------------------------------------
	//--------------------------------------------------------------------------
	
	// Se definen métodos adicionales porque se utiliza un form de datos básicos distinto ya que se muestran
	// menos datos y no son editables. Se reutilizan los objetos para los datos de la regla y el orden de los
	// componentes del módulo, ya que estos datos son los mismos que para los módulos comunes y sí son editables.
	
	function conf__modulo_rtf()
	{
		// Si no tiene hijos, se oculta el formulario que muestra la composición
		$cantidad_hijos = $this->elemento($this->s__id_nodo)->get_cantidad_hijos();
		if ($cantidad_hijos == 0) {
			$this->pantalla()->eliminar_dep('form_orden');
		}
	}

	// No hay evento de modificación de este form ya que no se editan datos.
	function conf__form_modulo_rtf_atrib(toba_ei_formulario $form)
	{
		if (isset($this->s__id_nodo) && (isset($this->s__id_nodo_padre) || is_null($this->s__id_nodo_padre))) {
			$elemento = $this->elemento($this->s__id_nodo);
			$datos = $elemento->get_datos($this->s__id_nodo_padre);
			$form->set_datos($datos);
			if ($this->plan()->edicion_version_bloqueada()) {
				$this->dep('regla')->set_solo_lectura();
			}
		}
	}

	function conf__form_orden(toba_ei_formulario_ml $form_ml)
	{
		$form_ml->ef('puntaje')->set_expreg(comunes::er_numero_dos_decimales);
		/**
		 * Si el modo de cumplimiento del módulo no es Por Puntaje (regla = 100), ocultar la columa correspondiente 
		 * y eliminar su obligatoriedad. Se hace por JS
		 */
		if (!isset($this->datos_orden_comp_temp)) {
			$datos = $this->elemento($this->s__id_nodo)->get_info_hijos();
			foreach (array_keys($datos) as $id) {
				$icono = guarani::catalogo_elementos_plan()->get_icono($datos[$id]['entidad_subtipo']);
				$datos[$id]['icono'] = toba_recurso::imagen_proyecto($icono, true);
			}
		} else {
			$datos = $this->datos_orden_comp_temp;
			$this->controlador->controlador->controlador->set_hubo_cambios();
		}
		$form_ml->set_datos($datos);
		if ($this->plan()->edicion_version_bloqueada()) {
			$form_ml->set_solo_lectura();
			$form_ml->eliminar_evento('ordenar');
			$form_ml->eliminar_evento('modificacion');
			$form_ml->set_mostrar_utilerias_orden(false);
		}
	}

	function evt__form_orden__modificacion($datos)
	{
		$datos = $this->procesar_puntajes_datos($datos);
		$this->datos_orden_comp_temp = $datos;
	}

	function evt__form_orden__ordenar($datos)
	{
		$datos = $this->procesar_puntajes_datos($datos);
		$this->set_datos_hijos_modulo($datos);
	}

	protected function procesar_puntajes_datos($datos)
	{
		$datos_ret = $datos;
		$regla = $this->dep('regla')->get_regla();
		if (isset($regla) && $regla != regla::por_puntaje_aprobadas && $regla != regla::por_puntaje_cursadas) {
			$datos_ret = array();
			foreach ($datos as $dato) {
				if (isset($dato['puntaje'])) {
					unset($dato['puntaje']);
				}
				$datos_ret[] = $dato;
			}
		}
		return $datos_ret;
	}

	protected function set_datos_hijos_modulo($datos)
	{
		$datos_hijos = array();
		foreach ($datos as $dato) {
			$datos_hijos[$dato['id']]['orden'] = $dato['orden'];
			if (isset($dato['puntaje'])) {
				$datos_hijos[$dato['id']]['puntaje'] = $dato['puntaje'];
			}
		}
		$this->elemento($this->s__id_nodo)->set_datos_hijos_hijos($datos_hijos);
	}

	function post_eventos()
	{

		if (isset($this->datos_mg_prop_temp)) {
			if (isset($this->s__id_nodo) && isset($this->s__id_nodo_padre)) {
				$elemento = $this->elemento($this->s__id_nodo);
				$elemento->set_datos($this->datos_mg_prop_temp, $this->s__id_nodo_padre);
				unset($this->datos_mg_prop_temp);
			}
		}

		if (isset($this->datos_regla_temp)) {
			if (isset($this->s__id_nodo)) {
				$elemento = $this->elemento($this->s__id_nodo);
				$elemento->set_datos($this->datos_regla_temp);
				unset($this->datos_regla_temp);
			}
		}

		if (isset($this->datos_orden_comp_temp)) {
			$this->set_datos_hijos_modulo($this->datos_orden_comp_temp);
			unset($this->datos_orden_comp_temp);
		}

		if (isset($this->datos_mod_atrib_temp)) {
			if (isset($this->s__id_nodo) && (isset($this->s__id_nodo_padre) || is_null($this->s__id_nodo_padre))) {
				$elemento = $this->elemento($this->s__id_nodo);
				$elemento->set_datos($this->datos_mod_atrib_temp, $this->s__id_nodo_padre);
				unset($this->datos_mod_atrib_temp);
			}
		}

		if (isset($this->datos_act_prop_temp)) {
			if (isset($this->s__id_nodo) && isset($this->s__id_nodo_padre)) {
				$elemento = $this->elemento($this->s__id_nodo);
				$elemento->set_datos($this->datos_act_prop_temp);
				$elemento->set_modalidades($this->datos_act_prop_temp['modalidad']);
				unset($this->datos_act_prop_temp);
			}
		}
	}

	//--------------------------------------------------------------------------

	function plan()
	{
		return $this->cn()->entidad();
	}

	function elemento($nodo)
	{
		return $this->cn()->entidad()->elementos()->elemento($nodo);
	}

	//-----------------------------------------------------------------------
	
	/**
	 * Método para cargar el ef multiseleccion doble para seleccionar la modalidad del elemento
	 *
	 * @return <type>
	 */
	function get_modalidades_cursadas()
	{
		$datos_modalidad = $this->cn()->entidad()->get_modalidades_plan();
		$modalidades_cursadas = $this->cn()->entidad()->get_modalidades_cursadas(aplanar_matriz($datos_modalidad, 'modalidad'));
		return $modalidades_cursadas;
	}

}
?>