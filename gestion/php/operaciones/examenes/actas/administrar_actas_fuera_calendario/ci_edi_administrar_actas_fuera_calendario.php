<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Actas
 * 
 */
class ci_edi_administrar_actas_fuera_calendario extends guarani_pers_ci
{
	// Datos Generales del Acta y la Mesa.
	protected $datos_generales_temp;
	protected $datos_instancias_temp;
	protected $datos_docentes_temp;
	
	protected $s__escala_notas_defecto;
	
	// Mensajes
	protected $indx_msg_eof = '';
	protected $msj_eliminar_propuesta = '800EXA_no_eliminar_propuesta';
	protected $msj_eliminar_instancia = '800EXA_no_modificar_escala';
	protected $msj_alumnos = '800EXA_instancia_propuesta_faltante';
	protected $msj_eliminar_alumno = '800_confirmar_elimin';
	protected $msj_eof_alumnos_asociados = '800_sin_seleccion_alumnos';
	
	// Datos del Filtro de Docentes.
	protected $s__datos_filtro_docentes;
	protected $s__filtro_docentes;
	
	// Tribunal Docente.
	protected $s__tribunal_seleccionado;
	protected $aplico_tribunal = false;  //Mantiene si en este pedido de página el usuario aplicó un tribunal a los Docentes del Llamado.

	// Alumnos
	protected $s__instancia_alumno;
	protected $s__alumnos_agregados = array();

	function ini()
	{
		$this->dep('form_ml_instancias')->set_columna_ordenamiento('instancia_nombre');
		// Recupera la escala de notas defecto definida para la Actividad a la que se asocia el Acta en el Plan.
		$this->s__escala_notas_defecto = $this->controlador()->get_escala_notas_defecto();
	}

	function conf()
	{
		if ($this->cn()->entidad_es_nueva()) {
			$this->pantalla()->eliminar_tab('pant_alumnos');
		}
		$this->indx_msg_eof = $this->controlador()->get_indx_msg_eof();
		$etiqueta = guarani_etiquetas::get_tabla('sga_alumnos');
		$msj = guarani_mensajes::get($this->msj_eliminar_alumno, $etiqueta->get_enf('singular'));
		$this->dep('alumnos_asociados')->evento('eliminar')->set_msg_confirmacion($msj);
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Función invocada desde el ci de navegación para limpiar la memoria de las variables utilizadas en la edición.
	 */
	function resetear_variables()
	{
		unset($this->s__escala_notas_defecto);
		unset($this->s__filtro_docentes);
		unset($this->s__datos_filtro_docentes);
		unset($this->s__tribunal_seleccionado);
		unset($this->s__instancia_alumno);
		unset($this->s__alumnos_agregados);

		// Limpia las variables del selector de alumnos.
		$this->dependencia('alumnos_disponibles')->resetear_variables();
	}

	function get_alumnos_detalles()
	{
		return $this->s__alumnos_agregados;
	}

	/**
	 * Verifica si el Acta tiene Alumnos asociados (con o sin nota cargada) para la Instancia dada.
	 */
	function acta_con_alumnos_instancia($instancia)
	{
		$alumnos = array();
		if ($this->cn()->entidad_es_nueva()) {
			$alumnos = $this->s__alumnos_agregados;
		}
		return $this->cn()->entidad()->hay_alumnos_instancia($instancia, $alumnos);
	}

	/**
	 * Verifica si el Acta tiene Alumnos asociados (con o sin nota cargada) con la Propuesta y el Plan dados.
	 */
	function acta_con_alumnos_propuesta($propuesta, $plan)
	{
		$alumnos = array();
		if ($this->cn()->entidad_es_nueva()) {
			$alumnos = $this->s__alumnos_agregados;
		}
		return $this->cn()->entidad()->hay_alumnos_propuesta($propuesta, $plan, $alumnos);
	}

	/**
	 * Método utilizado desde el editor para cargar el combo de Instancias con las cuales se puede asociar el Alumno al Acta.
	 */
	function get_instancias_acta()
	{
		return $this->cn()->entidad()->get_datos_acta_instancias();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DATOS PRINCIPALES 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- form_datos_principales -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_datos_principales(form_datos_principales $form)
	{
		// Número de Acta: deberá estar visible y obligatorio sólo si exa_carga_manual_nro_acta_examenes = S.
		if (guarani::parametros()->get_valor('exa_carga_manual_nro_acta_examen') == comunes::no) {
			$form->desactivar_efs(array('nro_acta'));
		} else {
			$form->ef('nro_acta')->set_obligatorio(true);
			if (!$this->cn()->entidad_es_nueva()) {
				$form->ef('nro_acta')->set_solo_lectura();
			}
		}
		if (isset($this->datos_generales_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_generales_temp);
		} else {
			$datos_form = array();
			$datos_acta = $this->cn()->entidad()->get_datos_acta();
			$datos_mesa = $this->cn()->entidad()->get_datos_mesa();
			$datos_llamado = $this->cn()->entidad()->get_datos_llamado_mesa();
			if (!empty($datos_acta) && !empty($datos_mesa) && !empty($datos_llamado)) {
				$datos_form = array_merge($datos_acta, $datos_mesa, $datos_llamado);
			}
			// Se setea el ef oculto elemento con la Actividad a la que se asocia el Acta.
			$datos_form['elemento'] = $this->controlador()->get_actividad_acta();
			if ($this->cn()->entidad_es_nueva()) {
				// Para el caso del Alta se establece el Tipo de Acta como Normal.
				if (!isset($datos_form['tipo_acta'])) {
					$datos_form['tipo_acta'] = acta::tipo_normal;
				}
			}
			// Se setea la descripción asociada al tipo de acta.
			if (isset($datos_form['tipo_acta'])) {
				if ($datos_form['tipo_acta'] == acta::tipo_normal) {
					$datos_form['tipo_acta_descr'] = acta::tipo_normal_descr;
				} elseif ($datos_form['tipo_acta'] == acta::tipo_rectificativa) {
					$datos_form['tipo_acta_descr'] = acta::tipo_rectificativa_descr;
				}
			}
			$form->set_datos($datos_form);
		}
		// Campo rectifica a: visible solo en caso de Acta rectificativa.
		$datos = $form->get_datos();
		if ($datos['tipo_acta'] != acta::tipo_rectificativa) {
			$form->desactivar_efs(array('nro_acta_rectificada'));
		}
	}

	function evt__form_datos_principales__modificacion($datos)
	{
		$this->datos_generales_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_instancias -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Se define la configuración de las filas del form ml de Instancias. Si se está editando un Acta, en caso de que la 
	 * misma tenga alumnos entonces si la Instancia está asociada a la mesa no puede destildarse, ni tampoco editar
	 * la escala de notas asociada.
	 *
	 * @param integer $fila clave interna de la fila del form que se está configurando
	 */
	function conf_fila__form_ml_instancias($fila)
	{
		$datos_ml_instancias = $this->dependencia('form_ml_instancias')->get_datos(true);
		if (!empty($datos_ml_instancias[$fila]['requerido']) && ($datos_ml_instancias[$fila]['requerido'] == '1')) {
			$instancia = $datos_ml_instancias[$fila]['instancia'];
			if ($this->acta_con_alumnos_instancia($instancia)) {
				$this->dependencia('form_ml_instancias')->ef('requerido')->set_solo_lectura(true);
				$this->dependencia('form_ml_instancias')->ef('escala_nota')->set_solo_lectura(true);
			} else {
				$this->dependencia('form_ml_instancias')->ef('requerido')->set_solo_lectura(false);
				$this->dependencia('form_ml_instancias')->ef('escala_nota')->set_solo_lectura(false);
			}
		} else {
			$this->dependencia('form_ml_instancias')->ef('requerido')->set_solo_lectura(false);
			$this->dependencia('form_ml_instancias')->ef('escala_nota')->set_solo_lectura(false);
		}
	}

	function conf__form_ml_instancias(form_ml_requerido $form_ml)
	{
		$msj_ayuda = guarani_mensajes::get($this->msj_eliminar_instancia);
		$form_ml->ef('requerido')->set_descripcion($msj_ayuda);
		if (isset($this->datos_instancias_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_instancias_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_acta_instancias();
		}

		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_instancias', 'instancia', 'instancia', true);
		$datos_posibles = toba::consulta_php('co_mesas_de_examen')->get_instancias_aplicables_a_mesa($filtro);
		foreach ($datos_posibles as $key => $instancia) {
			if (empty($instancia['escala_nota'])) {
				$datos_posibles[$key]['escala_nota'] = $this->s__escala_notas_defecto;
			}
		}

		$icono_informacion = new icono_info_escala_nota();
		$form_ml->ef('escala_nota')->agregar_icono_utileria($icono_informacion);

		$form_ml->set_datos($datos);
		$form_ml->set_datos_posibles($datos_posibles);
	}

	function evt__form_ml_instancias__modificacion($datos)
	{
		$this->datos_instancias_temp = $datos;
	}

	/**
	 * Ventana que se ejecuta una vez que todos los eventos se han disparado para este objeto. Utilizada para manejar
	 * el seteo de datos temporales para solapas con más de un componente de edición de datos.
	 */
	function post_eventos()
	{
		try {
			if (isset($this->datos_generales_temp)) {
				$this->cn()->entidad()->set_datos_acta($this->datos_generales_temp);
				$this->cn()->entidad()->set_datos_llamado_mesa($this->datos_generales_temp);
				$this->cn()->entidad()->set_datos_mesa($this->datos_generales_temp);
				unset($this->datos_generales_temp);
			}
			if (isset($this->datos_instancias_temp)) {
				$this->cn()->entidad()->set_datos_acta_instancias($this->datos_instancias_temp);
				$this->cn()->entidad()->set_datos_mesa_instancias($this->datos_instancias_temp);
				unset($this->datos_instancias_temp);
			}
		} catch (guarani_error $e) {
			$this->resetear_cambio_pantalla();
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA ALUMNOS 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- alumnos_asociados ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__alumnos_asociados(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->msj_eof_alumnos_asociados));
		// En el Alta de un Acta el detalle de alumnos se mantiene en un arreglo en memoria.
		if ($this->cn()->entidad_es_nueva()) {
			$alumnos = $this->s__alumnos_agregados;
		} else {
			$alumnos = $this->cn()->entidad()->get_detalles();
		}
		$cuadro->set_datos($alumnos);
	}

	function evt__alumnos_asociados__eliminar($seleccion)
	{
		if ($this->cn()->entidad_es_nueva()) {
			$this->eliminar_alumno_asociado(current($seleccion));
		} else {
			$this->cn()->entidad()->eliminar_detalle($seleccion);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_instancia ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_instancia(toba_ei_formulario $form)
	{
		$datos['instancia'] = $this->s__instancia_alumno;
		$form->set_datos($datos);
	}

	function evt__form_instancia__modificacion($datos)
	{
		if (isset($datos)) {
			$this->s__instancia_alumno = $datos['instancia'];
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Metodos que usa el selector de Alumnos ---------------------------------------
	//-----------------------------------------------------------------------------------

	function agregar_alumno($alumno)
	{
		$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($alumno['alumno']);
		if ($this->cn()->entidad()->hay_seleccion_automatica_de_instancia($datos_alumno['propuesta'])) {
			$this->s__instancia_alumno = $this->cn()->entidad()->determinar_instancia($datos_alumno);
		}
		if ($this->cn()->entidad_es_nueva()) {
			$this->agregar_alumno_asociado($datos_alumno, $this->s__instancia_alumno);
		} else {
			$this->cn()->entidad()->agregar_alumno($datos_alumno, $this->s__instancia_alumno);
		}
	}

	function get_alumnos_asociados()
	{
		if ($this->cn()->entidad_es_nueva()) {
			return $this->s__alumnos_agregados;
		} else {
			return $this->cn()->entidad()->get_detalles();
		}
	}

	function agregar_alumno_asociado($datos_alumno, $instancia)
	{
		$this->cn()->entidad()->validar_alumno($datos_alumno, $instancia);
		$datos_alumno['instancia'] = $instancia;
		array_push($this->s__alumnos_agregados, $datos_alumno);
	}

	function eliminar_alumno_asociado($id_alumno)
	{
		foreach ($this->s__alumnos_agregados as $key => $alumno) {
			if ($alumno['alumno'] == $id_alumno) {
				unset($this->s__alumnos_agregados[$key]);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DOCENTES 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- form_tribunal ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Formulario que permite seleccionar un tribunal para aplicar todos sus docentes al Llamado de la Mesa.
	 */
	function conf__form_tribunal(toba_ei_formulario $form)
	{
		// Se setea el ef oculto elemento, para poder traer los tribunales de la Actividad para la que se creó la Mesa.
		$form->ef('elemento')->set_estado($this->controlador()->get_actividad_acta());
		if (isset($this->s__tribunal_seleccionado)) {
			$form->ef('tribunal')->set_estado($this->s__tribunal_seleccionado);
		}
	}

	function evt__form_tribunal__aplicar($datos)
	{
		if (!empty($datos['tribunal'])) {
			// Si hay un tribunal seleccionado entonces Se setea la variable temporal y se modifican 
			// los docentes asociados al Llamado para que sean sólo los del tribunal dado.
			$this->s__tribunal_seleccionado = $datos['tribunal'];
			$this->cn()->entidad()->asociar_docentes_tribunal_llamado($this->s__tribunal_seleccionado);
			$this->aplico_tribunal = true;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- docentes_asociados -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Se muestran los docentes actualmente asociados al Llamado de la Mesa de Exámen. El usuario puede haberlos asociado
	 * previamente mediante la selección en el cuadro inferior y el botón Agregar, o habiendo presionado el botón Aplicar
	 * Tribunal (con el cual quedan asociados todos los docentes de dicho tribunal de exámen al llamado, y se eliminan los
	 * anteriores).
	 */
	function conf__docentes_asociados(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_docentes_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form_ml->set_datos($this->datos_docentes_temp);
		} else {
			$form_ml->set_datos($this->cn()->entidad()->get_datos_docentes_llamado());
		}
	}

	function evt__docentes_asociados__modificacion($datos)
	{
		try {
			// Se realiza la modificación sólo si no se está en el caso en que el usuario acaba de aplicar un tribunal al 
			// llamado. En este último caso todas los docentes previos fueron eliminados, por lo que no se quiere considerar 
			// ninguna modificación adicional que se hubiese realizado con anterioridad sobre los mismos.
			if (!$this->aplico_tribunal) {
				$this->cn()->entidad()->set_datos_docentes_llamado($datos);
			}
		} catch (guarani_error $e) {
			$this->datos_docentes_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__docentes_asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_docente_llamado($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_docentes --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_docentes(toba_ei_filtro $filtro)
	{
		// Se setean las opciones del filtro de tipo combo cátedra, que dependen de la actividad asociada al Acta. 
		// Como las opciones dependen de este último valor, no puede cargarse el combo desde el editor.
		$catedras_actividades = toba::consulta_php('co_catedras')->get_catedras_actividad_para_mesas($this->controlador()->get_actividad_acta());
		$opciones = array();
		foreach ($catedras_actividades as $catedra) {
			$opciones[$catedra['valor']] = $catedra['descr'];
		}
		$filtro->columna('catedra')->get_ef()->set_opciones($opciones);
		if (isset($this->s__datos_filtro_docentes)) {
			$filtro->set_datos($this->s__datos_filtro_docentes);
		} else {
			// Si se seleccionó una Cátedra a asociar a la Mesa en la solapa de Datos Generales del Acta, debe
			// setearse por defecto el filtro de catedra con ese valor.
			$datos_mesa = $this->cn()->entidad()->get_datos_mesa();
			if (!empty($datos_mesa['catedra'])) {
				$filtro->columna('catedra')->set_estado(array('condicion' => 'igual_a', 'valor' => $catedra));
				$filtro->columna('catedra')->set_visible(true);
				// En caso de que se haya elegido una cátedra en la solapa de datos generales del Acta, el cuadro de docentes 
				// a asociar debe cargarse inicialmente según este filtro como si el usuario hubiese presionado filtrar.
				$this->s__datos_filtro_docentes = array('catedra' => $catedra);
				$this->s__filtro_docentes = '';
			}
		}
	}

	function evt__filtro_docentes__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_docentes = $datos;
			if (isset($datos['catedra'])) {
				$this->dep('filtro_docentes')->columna('catedra')->resetear_estado();
			}
			$this->s__filtro_docentes = $this->dep('filtro_docentes')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_docentes);
			$this->s__filtro_docentes = '';
		}
	}

	function evt__filtro_docentes__cancelar()
	{
		unset($this->s__datos_filtro_docentes);
		unset($this->s__filtro_docentes);
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_docentes -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_docentes(toba_ei_cuadro $cuadro)
	{
		/**
		 * Si no están seteados los filtros (sobre los docentes a asociar), entonces es la carga inicial del cuadro 
		 * (el usuario no presionó filtrar o presionó limpiar). No se traen datos (no se ejecuta la consulta) y se pone 
		 * un mensaje en el cuadro que indica al usuario que debe filtrar para obtener datos.
		 */
		//Setea el mensaje eof del cuadro de docentes.
		//Recupera el índice del mensaje de eof del cuadro del ci de navegación.
		$indx_msj = $this->indx_msg_eof;
		//Recupera la etiqueta enfatizada y plural del concepto asociado a la tabla de docentes.
		$etiqueta = guarani_etiquetas::get_tabla('sga_docentes');
		$etiqueta = $etiqueta->get_enf('plural');
		if (!isset($this->s__filtro_docentes)) {
			$indx_msj .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));
		} else {
			// El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$indx_msj .= '_filtrado';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));
			// Recupera los docentes ya asociados al Llamado de la Mesa para no mostrarlos dentro de los disponibles a seleccionar.
			$docentes_asociados = $this->cn()->entidad()->get_datos_docentes_llamado();
			// Arma el filtro para obtener los docentes según la condición not in y el filtro ingresado por el usuario.
			$filtro = 'true';
			if (!empty($docentes_asociados)) {
				$condicion_not_in = guarani_sql::armar_condicion_in($docentes_asociados, 'sga_docentes', 'docente', 'docente', true);
				$filtro .= ' AND ' . $condicion_not_in;
			}
			if ($this->s__filtro_docentes) {
				$filtro .= ' AND ' . $this->s__filtro_docentes;
			}
			$catedra = null;
			if (isset($this->s__datos_filtro_docentes['catedra'])) {
				$catedra = $this->s__datos_filtro_docentes['catedra']['valor'];
			}
			$docentes = toba::consulta_php('co_docentes')->get_docentes_activos_catedras($catedra, $filtro);
			$cuadro->set_datos($docentes);
		}
	}

	function evt__seleccion_docentes__seleccion($seleccion)
	{
		$this->seleccion_docentes_temp = $seleccion;
	}

	function evt__seleccion_docentes__agregar()
	{
		if (!empty($this->seleccion_docentes_temp)) {
			foreach ($this->seleccion_docentes_temp as $docente) {
				$this->cn()->entidad()->agregar_docente_llamado($docente);
			}
		}
	}

}
?>