<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class ci_nav_comisiones extends ci_navegacion
{
	protected $indx_msg_eof = '800_eof_cuadro';
	// Filtro sobre Comisiones y Actividades
	protected $s__datos_filtro;
	protected $s__filtro_comisiones;
	protected $s__filtro_actividades;
	// Selección Actividad
	protected $s__seleccion_actividad;

	/**
	 * Función que permite obtener la selección de actividad realizada por el usuario, para obtener las diferentes
	 * comisiones asociadas a la misma en la pantalla de edición (todavía no se realizó la carga ya que el DR mantiene
	 * una única comisión (la elegida para editar o agregar).
	 */
	function get_actividad_seleccionada()
	{
		return $this->s__seleccion_actividad;
	}

	/**
	 * Retorna el año académico que el usuario seleccionó en el filtro.
	 */
	function get_anio_academico_seleccionado()
	{
		return $this->s__datos_filtro['anio_academico'];
	}

	/**
	 * Función que permite obtener el Período Lectivo que el usuario seleccionó en el filtro de Selección, y se utiliza
	 * desde la edición para precargar su valor.
	 */
	function get_periodo_lectivo_seleccionado()
	{
		return $this->s__datos_filtro['periodo_lectivo'];
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
		// Se setea el hecho de no volver al guardar en el ci padre.
		parent::ini();
		$this->controlador()->set_no_volver_al_guardar();
	}

	function conf()
	{
		/**
		 * Si ya se seleccionó una Actividad estoy en alguna de las pantallas de Edición, entonces se agrega al título el 
		 * Período Lectivo seleccionado en la pantalla de navegación, y su correspondiente Año Académico para saber qué 
		 * Comisiones de la Actividad seleccionada se están editando.
		 */
		if (!empty($this->s__seleccion_actividad) && !empty($this->s__datos_filtro)) {
			$this->set_titulo('Administración de Comisiones - Año Académico: ' . $this->s__datos_filtro['anio_academico'] . ' - Período Lectivo: ' . $this->s__datos_filtro['periodo_nombre']);
		}
	}

	/**
	 * Se define la configuración de la pantalla de edición de comisiones para que se puedan tener en cuenta los cambios
	 * en el datos relación cuando se ejecuta el evento Guardar en el ci padre (ci_navegacion). Debe hacerse un chequeo
	 * adicional porque en esta pantalla de edición no siempre hay una entidad cargada.
	 */
	function conf__edicion_comisiones(toba_ei_pantalla $pantalla)
	{
		if ($this->cn()->entidad_cargada()) {
			if ($this->cn()->entidad()->hay_cambios()) {
				parent::set_hubo_cambios();
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ACTIVIDAD 
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}

	//-----------------------------------------------------------------------------------
	//---- formulario de filtro sobre comisiones y actividades --------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_filtro_comisiones_actividades(toba_ei_formulario $form)
	{
		$ra = $form->ef('responsable_academica')->get_etiqueta();
		$form->ef('propuesta')->set_descripcion(guarani_mensajes::get('800_filtro_combo_cascada', array($ra)));
		$prop = $form->ef('propuesta')->get_etiqueta();
		$form->ef('plan')->set_descripcion(guarani_mensajes::get('800_filtro_combo_cascada', array($prop)));
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$form->ef('ubicacion')->set_obligatorio(true);
		}
		if (isset($this->s__datos_filtro)) {
			$form->set_datos_defecto($this->s__datos_filtro);
		}
	}

	function evt__form_filtro_comisiones_actividades__filtrar($datos)
	{
		$this->s__filtro_comisiones = '';
		$this->s__filtro_actividades = "vw_actividades.disponible_para IN ('" . elemento::para_mesas_de_examen_y_comisiones . "', '" . elemento::solo_para_creacion_comisiones . "') AND
										vw_actividades.estado = '" . elemento::estado_activo . "' AND 
										sga_planes.estado IN ('" . planes::estado_activo . "','" . planes::estado_activo_vigente . "') ";

		if (isset($datos)) {
			/**
			 * Se arma la condición where para los filtros sobre Comisiones y Actividades según los datos ingresados en el
			 * formulario y las tablas a las que los mismos afectan.
			 */
			$this->s__datos_filtro = $datos;
			$this->s__datos_filtro['periodo_nombre'] = toba::consulta_php('co_periodos_lectivos')->get_nombre_periodo_lectivo($this->s__datos_filtro['periodo_lectivo']);
			// Filtros sobre las Comisiones. El año académico no se considera porque filtra 
			// los períodos lectivos que se muestran y ambos campos son obligatorios.
			$periodo_lectivo = toba::db()->quote($datos['periodo_lectivo']);
			$this->s__filtro_comisiones .= "sga_comisiones.periodo_lectivo = $periodo_lectivo";
			if (isset($datos['ubicacion'])) {
				$ubicacion = toba::db()->quote($datos['ubicacion']);
				$this->s__filtro_comisiones .= " AND sga_comisiones.ubicacion = $ubicacion";
			}
			if (isset($datos['acepta_planes_personalizados'])) {
				$acepta_planes_personalizados = toba::db()->quote($datos['acepta_planes_personalizados']);
				$this->s__filtro_comisiones .= " AND sga_comisiones.acepta_planes_personalizados = $acepta_planes_personalizados";
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
			if (isset($datos['comisiones'])) {
				if ($datos['comisiones'] == comunes::si) {
					$ubicacion_filter = isset($ubicacion) ? " AND sga_elementos_ra.elemento IN (SELECT DISTINCT c.elemento FROM sga_comisiones as c WHERE c.periodo_lectivo = $periodo_lectivo AND c.ubicacion = $ubicacion)" 
                                                                           : " AND sga_elementos_ra.elemento IN (SELECT DISTINCT c.elemento FROM sga_comisiones as c WHERE c.periodo_lectivo = $periodo_lectivo)";
					$this->s__filtro_actividades = $this->s__filtro_actividades . $ubicacion_filter;
				} else {
                                        $ubicacion_filter = isset($ubicacion) ? " AND sga_elementos_ra.elemento NOT IN (SELECT DISTINCT c.elemento FROM sga_comisiones as c WHERE c.periodo_lectivo = $periodo_lectivo AND c.ubicacion = $ubicacion)" 
                                                                           : " AND sga_elementos_ra.elemento NOT IN (SELECT DISTINCT c.elemento FROM sga_comisiones as c WHERE c.periodo_lectivo = $periodo_lectivo)";
					$this->s__filtro_actividades = $this->s__filtro_actividades . $ubicacion_filter;
				}
			}
		} else {
			unset($this->s__datos_filtro);
		}
	}

	function evt__form_filtro_comisiones_actividades__cancelar()
	{
		// Se limpian los datos y el filtro (condición where) del formulario con datos sobre Comisiones y Actividades.
		unset($this->s__datos_filtro);
		unset($this->s__filtro_comisiones);
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

	function conf__cuadro_actividades(toba_ei_cuadro $cuadro)
	{
		/**
		 * Si no están seteados los filtros (sobre comisiones o actividades), entonces es la carga inicial del cuadro 
		 * (el usuario no presionó filtrar o presionó limpiar). No se traen datos (no se ejecuta la consulta) y se pone 
		 * un mensaje en el cuadro que indica al usuario que debe filtrar para obtener datos.
		 */
		$indx_msj = $this->indx_msg_eof;
		if (!isset($this->s__filtro_comisiones) || !isset($this->s__filtro_actividades)) {
			$indx_msj .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj));
		} else {
			// El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$indx_msj = $this->indx_msg_eof . '_filtrado';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array('Actividades')));
			$actividades = toba::consulta_php('co_elementos')->get_actividades_propuestas_con_comisiones($this->s__filtro_comisiones, $this->s__filtro_actividades);
			// Se setea la imagen con el color correspondiente a si la actividad tiene o no comisiones asociadas.
			foreach ($actividades as $clave => $valor) {
				if ($actividades[$clave]['cantidad_comisiones'] > 0) {
					$actividades[$clave]['tiene_comisiones'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true);
				} else {
					$actividades[$clave]['tiene_comisiones'] = toba_recurso::imagen_proyecto('iconos_estados/rojo.png', true);
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
			$cuadro->set_datos($actividades);
		}
	}

	function evt__cuadro_actividades__seleccion($seleccion)
	{
		$this->s__seleccion_actividad = $seleccion;
		$this->set_pantalla('edicion_comisiones');
	}

	//-----------------------------------------------------------------------------------
	//---- EVENTOS DEL CI ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	/**
	 * Se define el evento procesar que actúa como el guardar del ci de navegación pero no 
	 * se muestra el cartel de guardar ok' ante cada cambio de nodo en el árbol.
	 */
	function evt__procesar()
	{
		try {
			$this->cn()->guardar();
			if ($this->volver_al_guardar) {
				$this->cn()->resetear();
				$this->set_pantalla('seleccion_comision_subcomision');
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
		$this->cn()->resetear_atributos_parametros();
		$this->dep('editor_comisiones')->evt__resetear();
	}

	/**
	 * Se redefine el evento Volver del ci de navegación para que limpie la variable de sesión 
	 * que mantiene el componente en edición.
	 */
	function evt__cancelar()
	{
		parent::evt__cancelar();
		$this->dep('editor_comisiones')->limpiar_edicion();
		unset($this->s__seleccion_actividad);
	}

}