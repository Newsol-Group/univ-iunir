<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class ci_nav_mesas_de_examen extends ci_navegacion
{
	// Filtro sobre Comisiones y Actividades
	protected $s__datos_filtro;
	protected $s__filtro_mesas;
	protected $s__filtro_actividades;
	
	//Se utiliza para el combo 'Tiene mesas'
	protected $tiene_mesas;
	// Selección Actividad
	protected $s__seleccion_actividad;

	/**
	 * Función que permite obtener la selección de actividad realizada por el usuario, para obtener las diferentes
	 * mesas asociadas a la misma en la pantalla de edición (todavía no se realizó la carga ya que el DR mantiene
	 * una única mesa (la elegida para editar o agregar).
	 */
	function get_actividad_seleccionada()
	{
		return $this->s__seleccion_actividad;
	}

	/**
	 * Función que permite obtener el Turno de Exámen que el usuario seleccionó en el filtro de Selección, y se utiliza
	 * desde la edición para precargar su valor.
	 */
	function get_turno_examen_seleccionado()
	{
		return $this->s__datos_filtro['turno_examen'];
	}

	/**
	 * Función que permite obtener la Ubicación que el usuario seleccionó en el filtro de Selección, y se utiliza
	 * desde la edición para precargar su valor.
	 */
	function get_ubicacion_seleccionada()
	{
		if (!empty($this->s__datos_filtro['ubicacion'])) {
			return $this->s__datos_filtro['ubicacion'];
		}
	}

	/**
	 * Función que permite obtener lo que el usuario seleccionó con respecto al plan personalizado 
	 * en el filtro de Selección 
	 */
	function get_plan_personalizado_seleccionado()
	{
		if (!empty($this->s__datos_filtro['acepta_planes_personalizados'])) {
			return $this->s__datos_filtro['acepta_planes_personalizados'];
		}
	}

	function ini()
	{
		//Se setea el hecho de no volver al guardar en el ci padre.
		parent::ini();
		$this->controlador()->set_no_volver_al_guardar();
	}

	function conf()
	{
		parent::conf();
		// Si ya se seleccionó una Actividad, entonces se agrega al título el Turno de Exámen 
		// seleccionado en la pantalla de navegación, y el Año Académico correspondiente para saber qué 
		// Mesas de la Actividad seleccionada se están editando.
		if (!empty($this->s__seleccion_actividad) && !empty($this->s__datos_filtro)) {
			$this->set_titulo('Administración de Mesas de Examen - Año Académico: ' . $this->s__datos_filtro['anio_academico'] . ' - Turno: ' . $this->s__datos_filtro['turno_nombre']);
		}
	}

	/**
	 * Se define la configuración de la pantalla de edición de comisiones para que se puedan tener en cuenta los cambios
	 * en el datos relación cuando se ejecuta el evento Guardar en el ci padre (ci_navegacion). Debe hacerse un chequeo
	 * adicional porque en esta pantalla de edición no siempre hay una entidad cargada.
	 */
	function conf__edicion_mesas(toba_ei_pantalla $pantalla)
	{
		if ($this->cn()->entidad_cargada()) {
			if ($this->cn()->entidad()->dep('datos')->hay_cambios()) {
				parent::set_hubo_cambios();
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ACTIVIDAD 
	//-----------------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	//---- formulario de filtro sobre mesas y actividades -------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_filtro_actividades_mesas(toba_ei_formulario $form)
	{
		$ra = $form->ef('responsable_academica')->get_etiqueta();
		$form->ef('propuesta')->set_descripcion(guarani_mensajes::get('800_filtro_combo_cascada', array($ra)));
		$prop = $form->ef('propuesta')->get_etiqueta();
		$form->ef('plan')->set_descripcion(guarani_mensajes::get('800_filtro_combo_cascada', array($prop)));
		if (guarani::parametros()->get_valor('pro_plan_pers_usa_planes_personalizados') == comunes::no) {
			$form->desactivar_efs(array('acepta_planes_personalizados'));
		}
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$form->ef('ubicacion')->set_obligatorio(true);
		}
		if (isset($this->s__datos_filtro)) {
			$form->set_datos_defecto($this->s__datos_filtro);
		}
	}

	function evt__form_filtro_actividades_mesas__filtrar($datos)
	{
		$this->s__filtro_mesas = '';
		$this->s__filtro_actividades = "vw_actividades.disponible_para IN ('" . elemento::para_mesas_de_examen_y_comisiones . "', '" . elemento::solo_para_creacion_mesas_de_examen . "') AND
										vw_actividades.estado = '" . elemento::estado_activo . "'";
		if (isset($datos)) {
			// Se arma la condición where para los filtros sobre Mesas y Actividades según los datos ingresados en el
			// formulario y las tablas a las que los mismos afectan.
			$this->s__datos_filtro = $datos;
			$this->tiene_mesas = false;
			$this->s__datos_filtro['turno_nombre'] = toba::consulta_php('co_turnos_de_examen')->get_nombre_turno_examen($this->s__datos_filtro['turno_examen']);

			// Filtros sobre las Mesas. El año académico no se considera porque filtra a los turnos de exámen que se 
			// muestran y ambos campos son obligatorios. El turno se da como parámetro directamente a la consulta.
			if (isset($datos['ubicacion'])) {
				$ubicacion = toba::db()->quote($datos['ubicacion']);
				$this->s__filtro_mesas = "sga_mesas_examen.ubicacion = $ubicacion";
			}
			if (isset($datos['acepta_planes_personalizados'])) {
				$acepta_planes_personalizados = toba::db()->quote($datos['acepta_planes_personalizados']);
				if ($this->s__filtro_mesas != '') {
					$this->s__filtro_mesas .= " AND ";
				}
				$this->s__filtro_mesas .= "sga_mesas_examen.acepta_planes_personalizados = $acepta_planes_personalizados";
			}
			// Filtros sobre las Actividades.
			if (isset($datos['responsable_academica'])) {
				$ra = toba::db()->quote($datos['responsable_academica']);
				$cond = "sga_elementos_ra.responsable_academica = $ra";
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
			if (isset($datos['propuesta'])) {
				$propuesta = toba::db()->quote($datos['propuesta']);
				$cond = "sga_propuestas.propuesta = $propuesta";
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
			if (isset($datos['plan'])) {
				$plan = toba::db()->quote($datos['plan']);
				$cond = "sga_planes.plan = $plan";
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
			if (isset($datos['anio_de_cursada'])) {
				$anio = toba::db()->quote($datos['anio_de_cursada']);
				$cond = "sga_elementos_plan.anio_de_cursada = $anio";
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
			if (isset($datos['actividad'])) {
				$actividad = guarani_texto::limpiar_acentos($datos['actividad']);
				$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
				$cond = '('.$filtro_condicion->get_sql('f_limpiar_acentos(sga_elementos_plan.nombre::varchar)', $actividad);
				$cond .= ' OR '.$filtro_condicion->get_sql('f_limpiar_acentos(vw_actividades.codigo::varchar)', $actividad).')';
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
			if (isset($ubicacion)) {
				$cond = "sga_propuestas_oferta.ubicacion = $ubicacion";
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
			if (isset($datos['mesas'])) {
				$turno_examen = toba::db()->quote($this->s__datos_filtro['turno_examen']);
				if ($datos['mesas'] == comunes::si) {
					$this->tiene_mesas = comunes::si;
					$this->s__filtro_actividades .= " AND vw_actividades.elemento IN (
													SELECT DISTINCT me.elemento
													FROM sga_llamados_turno as llt
													JOIN sga_llamados_mesa as llm ON llm.llamado = llt.llamado
													JOIN sga_mesas_examen as me ON me.mesa_examen = llm.mesa_examen
													WHERE llt.turno_examen = $turno_examen)";
				} else {
					$this->tiene_mesas = comunes::no;
					$this->s__filtro_actividades .= " AND vw_actividades.elemento NOT IN (
													SELECT DISTINCT me.elemento
													FROM sga_llamados_turno as llt
													JOIN sga_llamados_mesa as llm ON llm.llamado = llt.llamado
													JOIN sga_mesas_examen as me ON me.mesa_examen = llm.mesa_examen
													WHERE llt.turno_examen = $turno_examen)";
				}
			}
		} else {
			unset($this->s__datos_filtro);
		}
	}

	function evt__form_filtro_actividades_mesas__cancelar()
	{
		// Se limpian los datos y el filtro (condición where) del formulario con datos sobre Mesas y Actividades.
		unset($this->s__datos_filtro);
		unset($this->s__filtro_mesas);
		unset($this->s__filtro_actividades);
	}

	function get_ubicaciones()
	{
		$datos = guarani::co('co_ubicaciones')->get_ubicaciones();
		if (guarani_arreglos::count($datos) == 1) {
			return $datos;
		} else {
			$no_seleccion = array(array('valor' => apex_ef_no_seteado, 'descr' => '-- Seleccione --'));
			return array_merge($no_seleccion, $datos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_actividades -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_actividades_mesas(toba_ei_cuadro $cuadro)
	{
		/**
		 * Si no están seteados los filtros (sobre mesas o actividades), entonces es la carga inicial del cuadro 
		 * (el usuario no presionó filtrar o presionó limpiar). No se traen datos (no se ejecuta la consulta) y se pone 
		 * un mensaje en el cuadro que indica al usuario que debe filtrar para obtener datos.
		 */
		// Setea el mensaje eof del cuadro de actividades.
		// Recupera el índice del mensaje de eof del cuadro del ci de navegación.
		$indx_msj = $this->indx_msg_eof;
		if (!isset($this->s__filtro_mesas) || !isset($this->s__filtro_actividades)) {
			$indx_msj .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj));
		} else {
			// El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$indx_msj = $this->indx_msg_eof . '_filtrado';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array('Actividades')));
			$actividades = toba::consulta_php('co_elementos')->get_actividades_propuestas_con_mesas_turno($this->s__datos_filtro['turno_examen'], $this->s__filtro_mesas, $this->s__filtro_actividades);
			// Se setea la imagen con el color correspondiente a si la actividad tiene o no mesas asociadas.
			foreach ($actividades as $clave => $valor) {
				if ($this->tiene_mesas == comunes::si) {
					//Filtro si tiene mesas seteado 'Con mesas'
					if ($actividades[$clave]['cantidad_mesas'] > 0) {
						$actividades[$clave]['tiene_mesas'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true);
						$actividades[$clave]['nombre_html'] = $valor['nombre'];
						if ($valor['tiene_nombres_alternativos']) {
							$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '32000043', array('actividad' => $valor['elemento']), array('celda_memoria' => 'celda_nombres_alternativos'));
							$javascript = "abrir_popup('info','$vinculo',{'width':'650','height':'420'})";
							$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
							$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver nombres alternativos');
							$salida .= '</a>';
							$actividades[$clave]['nombre_html'] = "{$valor['nombre']} $salida";
						}
					} else {
						unset($actividades[$clave]);
					}
				} else if ($this->tiene_mesas == comunes::no) {
					//Filtro si tiene mesas seteado 'Sin mesas'
					if ($actividades[$clave]['cantidad_mesas'] <= 0) {
						$actividades[$clave]['tiene_mesas'] = toba_recurso::imagen_proyecto('iconos_estados/rojo.png', true);
						$actividades[$clave]['nombre_html'] = $valor['nombre'];
						if ($valor['tiene_nombres_alternativos']) {
							$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '32000043', array('actividad' => $valor['elemento']), array('celda_memoria' => 'celda_nombres_alternativos'));
							$javascript = "abrir_popup('info','$vinculo',{'width':'650','height':'420'})";
							$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
							$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver nombres alternativos');
							$salida .= '</a>';
							$actividades[$clave]['nombre_html'] = "{$valor['nombre']} $salida";
						}
					} else {
						unset($actividades[$clave]);
					}
				} else {
					//Filtro si tiene mesas no esta seteado
					if ($actividades[$clave]['cantidad_mesas'] > 0) {
						$actividades[$clave]['tiene_mesas'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true);
					} else {
						$actividades[$clave]['tiene_mesas'] = toba_recurso::imagen_proyecto('iconos_estados/rojo.png', true);
					}
					$actividades[$clave]['nombre_html'] = $valor['nombre'];
					if ($valor['tiene_nombres_alternativos']) {
						$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '32000043', array('actividad' => $valor['elemento']), array('celda_memoria' => 'celda_nombres_alternativos'));
						$javascript = "abrir_popup('info','$vinculo',{'width':'650','height':'420'})";
						$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
						$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver nombres alternativos');
						$salida .= '</a>';
						$actividades[$clave]['nombre_html'] = "{$valor['nombre']} $salida";
					}
				}
			}
			$cuadro->set_datos($actividades);
		}
	}

	function evt__cuadro_actividades_mesas__seleccion($seleccion)
	{
		$this->s__seleccion_actividad = $seleccion;
		$this->set_pantalla('edicion_mesas');
	}

	//-----------------------------------------------------------------------------------
	//---- EVENTOS DEL CI ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	/**
	 * Se define el evento procesar que actúa como el guardar del ci de navegación pero no 
	 * se muestra el cartel de 'guardar ok' ante cada cambio de nodo en el árbol.
	 */
	function evt__procesar()
	{
		try {
			$this->cn()->guardar();
			if ($this->volver_al_guardar) {
				$this->cn()->resetear();
				$this->set_pantalla('seleccion_mesa_llamado');
			}
		} catch (guarani_error $e) {
			throw $e;
		}
	}

	/**
	 * Se define un evento para poder Cancelar la edición o el agregado de un Nodo en el árbol.
	 */
	function evt__limpiar()
	{
		$this->dep('editor_mesas')->evt__resetear();
	}

	/**
	 * Se redefine el evento Volver del ci de navegación para que limpie la variable de 
	 * sesión que mantiene el componente en edición.
	 */
	function evt__cancelar()
	{
		parent::evt__cancelar();
		$this->dep('editor_mesas')->limpiar_edicion();
	}

}