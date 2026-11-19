<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class ci_edi_comision extends ci_edicion_entidad
{
	//Datos temporales de los Formularios y Cuadros de selección
	protected $datos_generales_temp;
	protected $seleccion_propuestas_temp;
	protected $seleccion_modalidades_temp;
	protected $datos_docentes_temp;
	protected $seleccion_docentes_temp;
	protected $datos_dedicacion_horaria_temp;
	protected $datos_instancias_temp;
	
	// Filas agregadas por defecto.
	protected $s__modalidades_defecto_agregadas = false;
	protected $s__propuestas_defecto_agregadas = false;
	
	//Datos del Filtro de Docentes.
	protected $s__datos_filtro_docentes;
	protected $s__filtro_docentes;
	
	//Mapeo utilizado para agregar la responsabilidad de los docentes correspondientes a una cátedra.
	protected $s__mapeo_docentes_a_asociar;
	
	//Generales
	protected $s__actividad_seleccionada;
	protected $s__periodo_seleccionado;
	protected $s__ubicacion_seleccionada;
	protected $indx_msg_eof;
	protected $resetear_propuestas;
	
	function ini()
	{
		$this->indx_msg_eof = $this->controlador()->get_indx_msg_eof();
		$this->dep('seleccion_modalidades')->set_columna_ordenamiento('modalidad_nombre');
		$this->dep('seleccion_propuestas')->set_columna_ordenamiento('nombre_abreviado');
		$this->dep('datos_instancias')->set_columna_ordenamiento('instancia_nombre');
	}

	/**
	 * Se resetean las variables de sesión utilizadas para mantener el estado de los filtros y otra información 
	 * para poder limpiarlas desde el ci padre o desde el ci de navegación, ante el cambio de nodo en el árbol, o el
	 * evento cancelar edición de un nodo o volver a la pantalla de selección.
	 */
	function resetear_vars()
	{
		unset($this->s__datos_filtro_docentes);
		unset($this->s__filtro_docentes);
		unset($this->s__mapeo_docentes_a_asociar);
		unset($this->s__actividad_seleccionada);
		unset($this->s__periodo_seleccionado);
		unset($this->s__ubicacion_seleccionada);

		$this->s__modalidades_defecto_agregadas = false;
		$this->s__propuestas_defecto_agregadas = false;

		$this->dep('editor_per_inscr')->resetear_vars();
		$this->dep('editor_educacion_virtual')->resetear_vars();
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		// Se obtienen los datos del Filtro y la selección del usuario en la pantalla inicial (ci de navegación)
		$this->s__periodo_seleccionado = $this->controlador()->get_periodo();
		$this->s__ubicacion_seleccionada = $this->controlador()->get_ubicacion();
		$this->s__actividad_seleccionada = $this->controlador()->get_actividad();

		// Si no hay docentes asociados a la cátedra, entonces en no tiene sentido poder 
		// agregar ni quitar dedicaciones horarias, por lo cual se deshabilita esta solapa.
		$docentes_asociados = $this->cn()->entidad()->get_datos_docentes_asociados();
		if (empty($docentes_asociados)) {
			$this->pantalla()->tab('pant_dedicacion_horaria')->desactivar();
		}
		
		// Si se trata de una comisión nueva desactivo la solapa "Educación Virtual"		
		if ($this->cn()->entidad()->es_comision_nueva()) {
			$this->pantalla()->tab('pant_educacion_virtual')->desactivar();
		}
		
		// Se desactivan los extras de la cabecera (link "Ver parámetros")
		$this->pantalla()->set_no_mostrar_extras_cabecera();
	}

	/**
	 * Se extiende la función del padre, ci_edicion_entidad, donde se verifica la editabilidad de la entidad, y en caso
	 * de no ser editable se setean de solo lectura las dependencias y se eliminan los eventos de modificación sobre la 
	 * misma. Se definen los eventos adicionales que en este caso particular también permiten modificar la entidad, y se
	 * habilita el evento del filtro.
	 */
	function post_configurar()
	{
		// Para esta operación particular se desea que los filtros tengan activados sus eventos, 
		// ya que no modifican la entidad, sino que solo permiten visualizar información. 
		unset($this->componentes_eventos_a_inhabilitar['filtro']);
		parent::post_configurar();
		if (!$this->cn()->entidad()->es_editable()) {
			// Para el cuadro de Docentes a seleccionar para la Comisión y el formulario ml de Docentes asociados.
			$inhabilitar_eventos['formulario_ml'] = array('verificar_eliminacion');
			$inhabilitar_eventos['cuadro'] = array('agregar');
			$inhabilitar_eventos['formulario'] = array('confirmar', 'eliminar');
			$this->inhabilitar_componentes_eventos($inhabilitar_eventos);
		}
		if ($this->dep('editor_per_inscr')->hay_excepcion_en_edicion()) {
			$this->desactivar_eventos();
		}
	}

	function desactivar_eventos()
	{
		$this->controlador->controlador->evento('guardar')->desactivar();
		$this->controlador->controlador->evento('limpiar')->desactivar();
		$this->controlador->controlador->evento('cancelar')->desactivar();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA GENERAL 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- datos_generales --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__datos_generales(toba_ei_formulario $form)
	{
		$form->ef('letra_desde')->set_expreg(comunes::er_letra);
		$form->ef('letra_hasta')->set_expreg(comunes::er_letra);

		$datos_defecto = array(
			'inscripcion_habilitada' => guarani::parametros()->get_valor('cur_comision_habilitada'),
			'cobrable' => $this->get_valor_defecto_cobrable($this->s__actividad_seleccionada['elemento'])
		);
		$form->set_datos_defecto($datos_defecto);

		if (!$this->cn()->entidad_es_nueva()) {
			// El campo cobrable no es editable si la Comision ya tiene Inscriptos.
			if (!$this->cn()->entidad()->puede_editar_cobrable()) {
				$form->set_solo_lectura(array('cobrable'));
			}
		}
		if (isset($this->datos_generales_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_generales_temp);
		} else {
			// Se setea el ef oculto elemento con la Actividad seleccionada en el Filtro inicial (ci de navegación), que se
			// usa como campo maestro para obtener difentes valores.
			$form->ef('elemento')->set_estado($this->s__actividad_seleccionada['elemento']);
			if ($this->cn()->entidad_es_nueva()) {
				// Se procede de forma análoga para la Ubicación de la comisión.
				if (!empty($this->s__ubicacion_seleccionada)) {
					$form->ef('ubicacion')->set_estado($this->s__ubicacion_seleccionada);
				}
			}
			$form->set_datos($this->cn()->entidad()->get_datos_generales());
		}

		if (guarani::parametros()->get_valor('pro_plan_pers_usa_planes_personalizados') == comunes::no) {
			$form->desactivar_efs(array('acepta_planes_personalizados'));
		}
	}

	function evt__datos_generales__modificacion($datos)
	{
		// Se setea el período lectivo con el valor que el usuario seleccionó en el filtro inicial (ci de navegación).
		$datos['periodo_lectivo'] = $this->s__periodo_seleccionado;
		$this->datos_generales_temp = $datos;
	}

	/**
	 * Este campo debe presentar por defecto el valor que tenga la actividad en 
	 * los planes en las que se encuentra y, en caso de tener diferentes valores 
	 * en los planes, indicar el que tiene la actividad en su definición. 
	 * 
	 * @param int $elemento
	 * @return string
	 */
	protected function get_valor_defecto_cobrable($elemento)
	{
		$datos_propuestas = $this->dep('seleccion_propuestas')->get_datos();
		$planes = array();
		foreach ($datos_propuestas as $dato) {
			if ($dato['requerido']) {
				$planes[] = $dato['plan'];
			}
		}
		return toba::consulta_php('co_elementos')->get_valor_defecto_cobrable($elemento, $planes);
	}

	/**
	 * Este evento se dispara explícitamente cuando el usuario decide cambiar la cátedra asociada a la comisión, para
	 * eliminar consecuentemente a todos los docentes de la cátedra asociados a la comisión y sus dedicaciones horarias.
	 */
	function evt__datos_generales__cambio_catedra_docentes($datos)
	{
		$datos_comision = $this->cn()->entidad()->get_datos_generales();
		$catedra_antigua = $datos_comision['catedra'];
		$docentes_comision = $this->cn()->entidad()->get_datos_docentes_asociados();
		foreach ($docentes_comision as $docente) {
			$es_docente_catedra = toba::consulta_php('co_catedras')->es_docente_catedra($catedra_antigua, $docente['docente']);
			if ($es_docente_catedra) {
				$id_fila = $this->cn()->entidad()->get_id_fila_docente($docente['docente']);
				//Elimina el docente perteneciente a la cátedra anterior así como sus dedicaciones horarias asignadas en la comisión.
				$this->cn()->entidad()->eliminar_docente_asociado($id_fila);
			}
		}
		/**
		 * Como este evento se dispara explícitamente desde js, cuando se dispara el procesar del ef catedra, luego de su 
		 * ejecución debe invocarse explícitamente al evento de modificación del form, para que procese los cambios en los
		 * datos, ya que no se disparará de manera implícita (no puede haber 2 eventos distintos para un mismo componente 
		 * en un mismo pedido de pagina).
		 */
		$this->evt__datos_generales__modificacion($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_modalidades --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_modalidades(form_ml_requerido $form_ml)
	{
		if (isset($this->seleccion_modalidades_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->seleccion_modalidades_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_modalidades();
		}

		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_modalidad_cursada', 'modalidad', 'modalidad', true);
		$datos_posibles = toba::consulta_php('co_alumnos')->get_modalidades_cursadas_alumnos($filtro);

		$form_ml->set_datos($datos);

		// Por defecto deberán aparecer seleccionadas todas las modalidades.
		if ($this->cn()->entidad_es_nueva() && !$this->s__modalidades_defecto_agregadas) {
			$this->s__modalidades_defecto_agregadas = true;
			foreach ($datos_posibles as $dato) {
				$dato['requerido'] = 1;
				$form_ml->agregar_registro($dato);
			}
		} else {
			$form_ml->set_datos_posibles($datos_posibles);
		}
	}

	function evt__seleccion_modalidades__modificacion($datos)
	{
		$this->seleccion_modalidades_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_grupo_propuestas --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_grupo_propuestas(toba_ei_formulario $form)
	{
		$datos = array();
		if (isset($this->datos_generales_temp['grupo_propuesta'])) {
			$this->controlador()->set_hubo_cambios();
			$datos['grupo_propuesta'] = $this->datos_generales_temp['grupo_propuesta'];
		} else {
			$datos['grupo_propuesta'] = $this->cn()->entidad()->get_columna_comision('grupo_propuesta');
		}
		$form->set_datos($datos);
	}

	function evt__form_grupo_propuestas__modificacion($datos)
	{
		$grupo_propuesta = $this->cn()->entidad()->get_columna_comision('grupo_propuesta');
		if ($datos['grupo_propuesta'] != $grupo_propuesta) {
			$this->cn()->entidad()->eliminar_propuestas();
			$this->resetear_propuestas = true;
		}
		$this->datos_generales_temp['grupo_propuesta'] = $datos['grupo_propuesta'];
	}
	
	//-----------------------------------------------------------------------------------
	//---- seleccion_propuestas ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_propuestas(form_ml_requerido $form_ml)
	{
		if (isset($this->seleccion_propuestas_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->seleccion_propuestas_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_propuestas();
		}

		// Se filtra por planes estructurados para que en el ML no aparezcan las propuestas vinculadas a planes personalizados		
		$filtro = "sga_planes.tipo_plan = '" . planes::tipo_estructurado . "'";
		$filtro .= empty($datos) ? '' : " AND " . guarani_sql::armar_condicion_not_in_dupla($datos, 'sga_propuestas', 'propuesta', 'propuesta', $datos, 'sga_planes', 'plan', 'plan');
		$grupo_propuesta = $this->cn()->entidad()->get_columna_comision('grupo_propuesta');
		if (!empty($grupo_propuesta)) {
			$filtro .= " AND sga_propuestas.propuesta IN (SELECT sga_propuestas_x_grupo.propuesta FROM sga_propuestas_x_grupo WHERE sga_propuestas_x_grupo.grupo_propuesta = ".toba::db()->quote($grupo_propuesta).")";
		}
		$datos_posibles = toba::consulta_php('co_propuestas')->get_propuestas_por_elemento($this->s__actividad_seleccionada['elemento'], $filtro);

		$form_ml->set_datos($datos);

		// Por defecto deberán aparecer seleccionadas todas las propuestas.
		if ($this->cn()->entidad_es_nueva() && !$this->s__propuestas_defecto_agregadas) {
			$this->s__propuestas_defecto_agregadas = true;
			foreach ($datos_posibles as $dato) {
				$dato['requerido'] = 1;
				$form_ml->agregar_registro($dato);
			}
		} else {
			$form_ml->set_datos_posibles($datos_posibles);
		}
	}

	function evt__seleccion_propuestas__modificacion($datos)
	{
		if (!$this->resetear_propuestas) {
			$this->validar_ingreso_una_propuesta($datos);
			$this->seleccion_propuestas_temp = $datos;
		}
	}
	
	/**
	 * Se valida que se ingrese al menos una propuesta en esta instancia, ya que
	 * es fundamental para poder obtener los valores de los parámetros del sistema.
	 * En caso negativo, se lanza una excepción.
	 * 
	 * @param  array $datos
	 * @return boolean
	 * @throws guarani_error
	 */
	protected function validar_ingreso_una_propuesta($datos)
	{
		if (guarani::parametros()->get_valor('pro_plan_pers_usa_planes_personalizados') == comunes::si &&
			$this->datos_generales_temp['acepta_planes_personalizados'] == comunes::no) {
			foreach ($datos as $propuesta) {
				if ($propuesta['requerido']) {
					return true;
				}
			}
			$mje = guarani_mensajes::get('800_no_seleccion', array('una Propuesta'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DOCENTES 
	//-----------------------------------------------------------------------------------

	function conf__pant_docentes(toba_ei_pantalla $pant)
	{		
		$datos_comision = $this->cn()->entidad()->get_datos_generales();
		if (!isset($this->s__datos_filtro_docentes)) {
			if (!empty($datos_comision['catedra'])) {
				$catedra = $datos_comision['catedra'];
				$this->dependencia('filtro_docentes')->columna('catedra')->set_estado(array('condicion' => 'igual_a', 'valor' => $catedra));
				$this->dependencia('filtro_docentes')->columna('catedra')->set_visible(true);
				/**
				 * En caso de que se haya elegido una cátedra en la solapa de datos generales de la comisión, el cuadro 
				 * de docentes a asociar debe cargarse inicialmente según este filtro como si el usuario hubiese presionado 
				 * filtrar. El filtro por cátedra se maneja como una parámetro aparte en la sql.
				 */
				$this->s__datos_filtro_docentes = array('catedra' => $catedra);
				$this->s__filtro_docentes = '';
			} else {
				$this->dependencia('filtro_docentes')->columna('catedra')->resetear_estado();
				$this->dependencia('filtro_docentes')->columna('catedra')->set_visible(false);
				unset($this->s__datos_filtro_docentes);
				unset($this->s__filtro_docentes);
			}
		}

		// Verifica si hay datos en el cuadro de docentes a seleccionar para activar o desactivar el evento de Agregar Docentes.
		$docentes_a_seleccionar = $this->dependencia('seleccion_docentes')->get_datos();
		if (empty($docentes_a_seleccionar)) {
			$this->dependencia('seleccion_docentes')->evento('agregar')->desactivar();
		}

		// Si hay al menos una encuesta de tipo "Docente" o "Docente y Académica" para la comisión se muestra un mensaje informativo.
		if (isset($datos_comision['comision']) && toba::consulta_php('co_encuestas')->get_cant_encuestas_x_comision($datos_comision['comision']) > 0) {
			$pant->set_descripcion(guarani_mensajes::get('800CUR_encuestas_generadas_com'));
		}
	}

	function evt__pant_docentes__salida()
	{
		unset($this->s__datos_filtro_docentes);
		unset($this->s__filtro_docentes);
	}

	//-----------------------------------------------------------------------------------
	//---- docentes_asociados -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__docentes_asociados(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_docentes_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form_ml->set_datos($this->datos_docentes_temp);
		} else {
			$form_ml->set_datos($this->cn()->entidad()->get_datos_docentes_asociados());
		}
	}

	function evt__docentes_asociados__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_docentes_asociados($datos);
		} catch (guarani_error $e) {
			$this->datos_docentes_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__docentes_asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_docente_asociado($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_docentes --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_docentes(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_docentes)) {
			$filtro->set_datos($this->s__datos_filtro_docentes);
		}
	}

	function evt__filtro_docentes__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_docentes = $datos;
			if (isset($datos['catedra'])) {
				$this->dependencia('filtro_docentes')->columna('catedra')->resetear_estado();
			}
			$filtro_ra = '';
			if (isset($datos['responsable_academica'])) {
				$filtro_ra .= " AND EXISTS (SELECT * FROM sga_docentes_ra WHERE sga_docentes_ra.docente = sga_docentes.docente AND sga_docentes_ra.responsable_academica = {$datos['responsable_academica']['valor']})";
				$this->dep('filtro_docentes')->columna('responsable_academica')->resetear_estado();
			}
			$this->s__filtro_docentes = "{$this->dep('filtro_docentes')->get_sql_where()} $filtro_ra";
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
		 * Si no están seteados los filtros (sobre comisiones o actividades), entonces es la carga inicial del cuadro 
		 * (el usuario no presionó filtrar o presionó limpiar). No se traen datos (no se ejecuta la consulta) y se pone 
		 * un mensaje en el cuadro que indica al usuario que debe filtrar para obtener datos.
		 */
		$indx_msj = $this->indx_msg_eof;
		if (!isset($this->s__filtro_docentes)) {
			$indx_msj .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj));
		} else {
			// El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$indx_msj .= '_filtrado';
			// Recupera la etiqueta enfatizada y plural del concepto asociado a la tabla de docentes.
			$etiqueta = guarani_etiquetas::get_tabla('sga_docentes');
			$etiqueta = $etiqueta->get_enf('plural');
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));
			// Recupera los docentes ya asociados a la comisión para no mostrarlos dentro de los disponibles para seleccionar.
			$docentes_asociados = $this->cn()->entidad()->get_datos_docentes_asociados();
			// Arma el filtro para obtener los docentes según la condición not in y el filtro ingresado por el usuario.
			$filtro = 'true';
			if (!empty($docentes_asociados)) {
				$condicion_not_in = guarani_sql::armar_condicion_in($docentes_asociados, 'sga_docentes', 'docente', 'docente', true);
				$filtro .= ' AND ' . $condicion_not_in;
			}
			if ($this->s__filtro_docentes) {
				$filtro .= ' AND ' . $this->s__filtro_docentes;
			}
			/**
			 * Si se ingresó una cátedra como filtro, entonces debe agregarse la condición de que los docentes estén 
			 * activos en dicha cátedra, además de estar activos en sga_docentes.
			 */
			$catedra = null;
			if (isset($this->s__datos_filtro_docentes['catedra'])) {
				$filtro .= " AND sga_catedras_docentes.estado = '" . comunes::estado_activo . "'";
				$catedra = is_array($this->s__datos_filtro_docentes['catedra']) ? $this->s__datos_filtro_docentes['catedra']['valor'] : $this->s__datos_filtro_docentes['catedra'];
			}
			$docentes = toba::consulta_php('co_docentes')->get_docentes_activos_catedras($catedra, $filtro);
			if (isset($this->s__datos_filtro_docentes['catedra'])) {
				/**
				 * Se arma un arreglo de docentes para poder manetener la responsabilidad asociada a los mismos, 
				 * si se seleccionó cátedra para la comisión.
				 */
				foreach ($docentes as $docente) {
					$this->s__mapeo_docentes_a_asociar[$docente['docente']]['responsabilidad'] = $docente['responsabilidad'];
					$this->s__mapeo_docentes_a_asociar[$docente['docente']]['firma'] = $docente['firma'];
				}
			}

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
			if (isset($this->s__mapeo_docentes_a_asociar)) {
				foreach ($this->seleccion_docentes_temp as $key => $docente) {
					$this->seleccion_docentes_temp[$key]['responsabilidad'] = isset($this->s__mapeo_docentes_a_asociar[$docente['docente']]['responsabilidad']) ? $this->s__mapeo_docentes_a_asociar[$docente['docente']]['responsabilidad'] : null;
					$this->seleccion_docentes_temp[$key]['firma'] = isset($this->s__mapeo_docentes_a_asociar[$docente['docente']]['firma']) ? $this->s__mapeo_docentes_a_asociar[$docente['docente']]['firma'] : null;
				}
			}
			foreach ($this->seleccion_docentes_temp as $docente) {
				$this->cn()->entidad()->agregar_docente_asociado($docente);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DEDICACIÓN HORARIA 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- dedicacion_horaria -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__dedicacion_horaria(toba_ei_formulario_ml $form_ml)
	{
		// Se setean las opciones del combo de Docentes a partir de los docentes actualmente asignados a la comisión.
		$docentes_asociados = $this->cn()->entidad()->get_datos_docentes_asociados();
		$opciones = array();
		foreach ($docentes_asociados as $docente) {
			$opciones[$docente['docente']] = $docente['nombre_persona'];
		}
		$form_ml->ef('docente')->set_opciones($opciones);
		if (isset($this->datos_dedicacion_horaria_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form_ml->set_datos($this->datos_dedicacion_horaria_temp);
		} else {
			$form_ml->set_datos($this->cn()->entidad()->get_datos_dedicaciones_horarias());
		}
	}

	function evt__dedicacion_horaria__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_dedicaciones_horarias($datos);
		} catch (guarani_error $e) {
			$this->datos_dedicacion_horaria_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DIAS Y HORARIOS
	//-----------------------------------------------------------------------------------

	function evt__pant_dias_horarios__salida()
	{
		if ($this->cn()->entidad()->hay_banda_horaria_seleccionada()) {
			$this->dep('editor_banda_horaria')->evt__cancelar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DATOS INSTANCIAS 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- datos instancias -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__datos_instancias(form_ml_requerido $form_ml)
	{
		if (isset($this->datos_instancias_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_instancias_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_instancias();
		}
		
		$propuestas = $this->cn()->entidad()->get_datos_propuestas(false);
		$ids_propuestas = array_column($propuestas, "propuesta");

		foreach ($datos as $key => $dato) {
			$datos[$key]['propuestas'] = $ids_propuestas;
			$datos[$key]['escala_asignada'] = $dato['escala_nota'];
		}

		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_instancias', 'instancia', 'instancia', true);
		$datos_posibles = toba::consulta_php('co_comisiones')->get_listado_instancias_comisiones($filtro);
		$escala_notas_regular = $escala_notas_promocion = null;
		foreach ($datos_posibles as $key => $instancia) {
			if (empty($instancia['escala_nota']) && empty($instancia['porc_asistencia'])) {
				if ($instancia['instancia'] == comision::inst_cursar) {
					if (!isset($escala_notas_regular)) {
						$escala_notas_regular = $this->cn()->entidad()->get_escala_notas_defecto(comision::inst_cursar);
					}
					$datos_posibles[$key]['escala_nota'] = $escala_notas_regular;
					$datos_posibles[$key]['porc_asistencia'] = guarani::parametros()->get_valor('asistencia_porcentaje_regulares');
				} elseif ($instancia['instancia'] == comision::inst_promocionar) {
					if (!isset($escala_notas_promocion)) {
						$escala_notas_promocion = $this->cn()->entidad()->get_escala_notas_defecto(comision::inst_promocionar);
					}
					$datos_posibles[$key]['escala_nota'] = $escala_notas_promocion;
					$datos_posibles[$key]['porc_asistencia'] = guarani::parametros()->get_valor('asistencia_porcentaje_promociones');
				}
			}
			$datos_posibles[$key]['propuestas'] = $ids_propuestas;
			$datos_posibles[$key]['escala_asignada'] = 0;
		}

		$icono_informacion = new icono_info_escala_nota();
		$form_ml->ef('escala_nota')->agregar_icono_utileria($icono_informacion);

		$form_ml->set_datos($datos);
		$form_ml->set_datos_posibles($datos_posibles);

		/**
		 * Se chequea el parámetro de sistema que indica si el usuario debe registrar o no el Porcentaje de Asistencia
		 * de forma manual. Si no es así, entonces se setea este campo como solo lectura.
		 */
		if (guarani::parametros()->get_valor('asistencia_registrar_asistencia') == comunes::no) {
			$form_ml->ef('porc_asistencia')->set_solo_lectura(true);
		}
	}

	/**
	 * Se define la configuración de las filas del form ml de Instancias. En caso de edición, para cada instancia asociada 
	 * a la comisión, si la misma tiene Alumnos inscriptos no podrá desasociarse de la Comisión, por lo cual deshabilitarse
	 * el checkbox requerido para dicha fila.
	 *
	 * La instancia Promoción estará deshabilitada para su asociación a la Comisión si la Actividad no es promocionable
	 * en ninguno de los planes asociados a la Comisión.
	 * 
	 * @param integer $fila clave interna de la fila del form que se está configurando
	 */
	function conf_fila__datos_instancias($fila)
	{
		$datos_ml_instancias = $this->dep('datos_instancias')->get_datos();
		$datos_posibles_ml_instancias = $this->dep('datos_instancias')->get_datos_posibles();

		// Las filas deben agregarse con el offset del x_dbr_clave porque en el caso del alta puede no coincidir.
		$instancias = array();
		foreach ($datos_ml_instancias as $instancia) {
			$instancias[$instancia['x_dbr_clave']] = $instancia;
		}
		foreach ($datos_posibles_ml_instancias as $instancia_posible) {
			$instancias[] = $instancia_posible;
		}

		// Caso edición.
		if (!$this->cn()->entidad_es_nueva() && $this->cn()->entidad()->es_editable()) {
			if (!empty($instancias[$fila]['requerido']) && ($instancias[$fila]['requerido'] == '1')) {
				$datos_comision = $this->cn()->entidad()->get_datos_generales();
				$comision = $datos_comision['comision'];
				$instancia = toba::db()->quote($instancias[$fila]['instancia']);
				$filtro = "sga_insc_cursada_instancias.instancia = $instancia";

				// Inscriptos a Cursada para la Comisión y la Instancia.
				$insc_comision_instancia = toba::consulta_php('co_inscripciones_cursadas')->get_inscripciones_por_comision($comision, $filtro);
				if (!empty($insc_comision_instancia)) {
					$this->dep('datos_instancias')->ef('requerido')->set_solo_lectura(true);
				} else {
					$this->dep('datos_instancias')->ef('requerido')->set_solo_lectura(false);
				}
			} else {
				$this->dep('datos_instancias')->ef('requerido')->set_solo_lectura(false);
			}
		} else {
			$this->dep('datos_instancias')->ef('requerido')->set_solo_lectura(false);
		}

		// En caso de que no se haya puesto en solo lectura por la condición anterior.
		if (!$this->dep('datos_instancias')->ef('requerido')->es_solo_lectura()) {
			// Verifico para la instancia promoción si la actividad es promocionable para algún plan versión de la comisión.
			// En el alta la fila del xdbr_clave puede no corresponderse con el offset en el arreglo (arrancan en 1).
			if ($instancias[$fila]['instancia'] == comision::inst_promocionar) {
				// En caso de alta o edición
				// Verifica si la actividad es promocionable en algun plan de los asociados a la comisión.
				$planes_comision = $this->cn()->entidad()->get_datos_propuestas(false); //con false recupera todas las propuestas, no sólo las vinculadas a planes estructurados
				$actividad_promocionable = toba::consulta_php('co_elementos')->actividad_promocionable_en_planes_versiones_activas($this->s__actividad_seleccionada['elemento'], $planes_comision);
				if (!$actividad_promocionable) {
					$this->dep('datos_instancias')->ef('requerido')->set_estado(0);
					$this->dep('datos_instancias')->ef('escala_nota')->resetear_estado();
					$this->dep('datos_instancias')->ef('requerido')->set_solo_lectura(true);
				} else {
					$this->dep('datos_instancias')->ef('requerido')->set_solo_lectura(false);
				}
			} else {
				$this->dep('datos_instancias')->ef('requerido')->set_solo_lectura(false);
			}
		}
	}

	function evt__datos_instancias__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_instancias($datos);
		} catch (guarani_error $e) {
			$this->datos_instancias_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Cáscara del método descripción del ef popup info_escala, que solo se utiliza para mostrar una imagen.
	 */
	function metodo($in)
	{
		return '';
	}

	/**
	 * Retorna los datos correspondientes al combo "Responsabilidad" de la lista de docentes asociados a la comisión.
	 *
	 * @param integer $docente
	 * @return array
	 */
	function get_responsabilidades_docentes($docente)
	{
		$comision = $this->cn()->entidad()->get_datos_generales();		
		$comision = isset($comision['comision']) ? $comision['comision'] : '';
		$docente_responsabilidades = toba::consulta_php('co_docentes')->get_docente_responsabilidades($docente, $comision);		
		if (!empty($docente_responsabilidades)) {
			return $docente_responsabilidades;
		}
		return toba::consulta_php('co_responsabilidades_docentes')->get_responsabilidades();
	}

	function get_propuestas_grupos()
	{
		return toba::consulta_php('co_propuestas')->get_propuestas_grupos_por_elemento($this->s__actividad_seleccionada['elemento']);
	}

	//-----------------------------------------------------------------------------------
	//---- Consultas AJAX ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * El usuario está modificando la cátedra asociada a la comisión, se verifica si existen asignaciones docentes activas
	 * (es decir con fecha de finalización mayor a la actual) de la cátedra. Si es así se retorna como respuesta el mensaje
	 * correspondiente a mostrar al usuario para confirmar el cambio.
	 */
	function ajax__validar_cambio_catedra($params, toba_ajax_respuesta $respuesta)
	{
		if ($this->cn()->entidad()->verificar_asignaciones_cambio_catedra()) {
			$msj = guarani_mensajes::get('800CUR_docentes_catedra');
			$salida = array('mensaje' => $msj);
			$respuesta->set($salida);
		} else {
			$respuesta->set(false);
		}
	}

	/**
	 * El usuario está modificando la cátedra asociada a la comisión, se verifica si existen asignaciones docentes activas
	 * (es decir con fecha de finalización mayor a la actual) de la cátedra. Si es así se retorna como respuesta el mensaje
	 * correspondiente a mostrar al usuario para confirmar el cambio.
	 */
	function ajax__verificar_dedicacion_docente($params, toba_ajax_respuesta $respuesta)
	{
		// En el primer parámetro se tiene el id de fila del docente que se desea eliminar.
		if ($this->cn()->entidad()->verificar_dedicacion_docente($params[0])) {
			$msj = guarani_mensajes::get('800CUR_docentes_dedicacion');
			$salida = array('mensaje' => $msj);
			$respuesta->set($salida);
		} else {
			$respuesta->set(false);
		}
	}

	function ajax__datos_responsabilidad($params, toba_ajax_respuesta $respuesta) 
	{
		$responsabilidad = $params['responsabilidad'];
		$datos_responsabilidad = toba::consulta_php('co_docentes')->get_datos_docente_responsabilidad($responsabilidad);		
		if (!empty($datos_responsabilidad)) {
			$datos_responsabilidad['fila'] = $params['fila'];
			$respuesta->set($datos_responsabilidad);
		} else {
			$respuesta->set(false);
		}
	}

	/**
	 * Ventana que se ejecuta una vez que todos los eventos se han disparado para este objeto
	 */
	function post_eventos()
	{
		try {
			if (isset($this->datos_generales_temp)) {
				$this->cn()->entidad()->set_datos_generales($this->datos_generales_temp);
				unset($this->datos_generales_temp);
			}
			if (isset($this->seleccion_modalidades_temp)) {
				$this->cn()->entidad()->set_datos_modalidades($this->seleccion_modalidades_temp);
				unset($this->seleccion_modalidades_temp);
			}
			if (isset($this->seleccion_propuestas_temp)) {
				$this->cn()->entidad()->set_datos_propuestas($this->seleccion_propuestas_temp);
				unset($this->seleccion_propuestas_temp);
			}
		} catch (guarani_error $e) {
			$this->resetear_cambio_pantalla();
			throw $e;
		}
	}
    
    function get_catedras_actividad_para_comisiones()
    {
        return toba::consulta_php('co_catedras')->get_catedras_actividad_para_comisiones($this->s__actividad_seleccionada['elemento']);
    }
}