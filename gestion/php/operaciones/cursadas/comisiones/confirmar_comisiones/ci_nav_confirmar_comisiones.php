<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class ci_nav_confirmar_comisiones extends ci_navegacion
{
	protected $indx_msg_eof = '800_eof_cuadro';
	
	//Filtro sobre Comisiones y Actividades
	protected $s__datos_filtro;
	protected $s__filtro_comisiones;
	protected $s__filtro_actividades;
	
	//Selección Actividad
	protected $s__seleccion_actividad;
	
	//Elemento de Visualización
	protected $ev_info;
	
	/**
	 * Función que permite obtener la selección de actividad realizada por el usuario, para obtener las diferentes
	 * comisiones asociadas a la misma en la pantalla de edición (todavía no se realizó la carga ya que el DR mantiene
	 * una única comisión (la elegida para editar o agregar).
	 */
	function get_actividad_seleccionada()
	{
		return $this->s__seleccion_actividad['elemento'];
	}
	
	/**
	 * Función que permite obtener la selección de actividad realizada por el usuario, para obtener las diferentes
	 * comisiones asociadas a la misma en la pantalla de edición (todavía no se realizó la carga ya que el DR mantiene
	 * una única comisión (la elegida para editar o agregar).
	 */
	function get_cant_comisiones_actividad_seleccionada()
	{
		return $this->s__seleccion_actividad['cantidad_comisiones'];
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
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ACTIVIDAD -------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- formulario de filtro sobre comisiones y actividades --------------------------
	
	function conf__form_filtro_comisiones_actividades(toba_ei_formulario $form)
	{
		$ra = $form->ef('responsable_academica')->get_etiqueta();
		$form->ef('propuesta')->set_descripcion(guarani_mensajes::get('800_filtro_combo_cascada', array($ra)));
		$prop = $form->ef('propuesta')->get_etiqueta();
		$form->ef('plan')->set_descripcion(guarani_mensajes::get('800_filtro_combo_cascada', array($prop)));
		if (isset($this->s__datos_filtro)) {
			$form->set_datos_defecto($this->s__datos_filtro);
		} 
	}

	function evt__form_filtro_comisiones_actividades__filtrar($datos)
	{
		$this->s__filtro_comisiones = '';
		$this->s__filtro_actividades = "vw_actividades.disponible_para IN ('".elemento::para_mesas_de_examen_y_comisiones."', '".elemento::solo_para_creacion_comisiones."')";
		
		if (isset($datos)) {
			/**
			 * Se arma la condición where para los filtros sobre Comisiones y Actividades según los datos ingresados en el
			 * formulario y las tablas a las que los mismos afectan.
			 */
			$this->s__datos_filtro = $datos;
			//Filtros sobre las Comisiones. El año académico no se considera porque filtra a los períodos lectivos que se 
			//muestran y ambos campos son obligatorios.
			$periodo_lectivo = toba::db()->quote($datos['periodo_lectivo']);
			$this->s__filtro_comisiones .= "sga_comisiones.periodo_lectivo = $periodo_lectivo";
			if (isset($datos['ubicacion'])) {
				$ubicacion = toba::db()->quote($datos['ubicacion']);
				$this->s__filtro_comisiones .= " AND sga_comisiones.ubicacion = $ubicacion";
			}
			//Filtros sobre las Actividades.
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
				$cond = '('.$filtro_condicion->get_sql('f_limpiar_acentos(vw_actividades.nombre::varchar)', $actividad);
				$cond .= ' OR '.$filtro_condicion->get_sql('f_limpiar_acentos(vw_actividades.codigo::varchar)', $actividad).')';
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
			if (isset($ubicacion)) {
				$cond = "sga_propuestas_oferta.ubicacion = $ubicacion";
				$this->s__filtro_actividades = ($this->s__filtro_actividades) ? $this->s__filtro_actividades . ' AND ' . $cond : $cond;
			}
		} else {
			unset($this->s__datos_filtro);
		}
	}

	function evt__form_filtro_comisiones_actividades__cancelar()
	{
		//Se limpian los datos y el filtro (condición where) del formulario con datos sobre Comisiones y Actividades.
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
	
	//---- cuadro_actividades ----------------------------------------------------------
	
	function conf__cuadro_actividades(toba_ei_cuadro $cuadro)
	{
		/**
 		 * Si no están seteados los filtros (sobre comisiones o actividades), entonces es la carga inicial del cuadro 
 		 * (el usuario no presionó filtrar o presionó limpiar). No se traen datos (no se ejecuta la consulta) y se pone 
 		 * un mensaje en el cuadro que indica al usuario que debe filtrar para obtener datos.
		*/
		//Setea el mensaje eof del cuadro de actividades.
		//Recupera el índice del mensaje de eof del cuadro del ci de navegación.
		
		$indx_msj = $this->indx_msg_eof;
		if (!isset($this->s__filtro_comisiones) || !isset($this->s__filtro_actividades)) {
			$indx_msj .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj));
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$indx_msj = $this->indx_msg_eof . '_filtrado';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array('Actividades')));
			$actividades = $this->cn()->get_listado_confirmar_comisiones($this->s__filtro_comisiones, $this->s__filtro_actividades);
			//Se setea la imagen con el color correspondiente a si la actividad tiene o no comisiones asociadas.
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
	
	function conf_evt__cuadro_actividades__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_actividades')->get_datos();
		if ($datos[$fila]['cantidad_comisiones'] > 0) {
			$evento->mostrar();
		} else {
			$evento->ocultar();
		}
	}
	
	function evt__cuadro_actividades__seleccion($seleccion)
	{
		$this->s__seleccion_actividad = $seleccion;
		$this->set_pantalla('edicion');	
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICIÓN -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$datos = $this->get_datos_ev_actividad_comisiones();
		$this->ev_info()->set_datos($datos);
	}
	
	//-----------------------------------------------------------------------------------
	//---- EVENTOS DEL CI ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__guardar()
	{
		try {
			/**
		 	 * Recuperar los datos del form ml y procesar las modificaciones de habilitación y visibilidad de las
		 	 * comisiones y subcomisiones que correspondan.
		 	 */
			$datos = $this->dependencia('editor')->get_datos_comisiones_subcomisiones();
			$this->cn()->habilitar_deshabilitar_inscripcion_comisiones($datos);
			if ($this->volver_al_guardar) {
				$this->cn()->resetear();
				$this->set_pantalla('seleccion');
			}
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e)	{
			throw $e;
		} 
	}
	
	//-----------------------------------------------------------------------------------
	//---- Elementos de visualización ---------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function get_datos_ev_actividad_comisiones()
	{
		if (!isset($this->s__datos_seleccion)) {
			$this->s__datos_seleccion['anio_academico'] = $this->s__datos_filtro['anio_academico'];
			$this->s__datos_seleccion['periodo_lectivo_nombre'] = toba::consulta_php('co_periodos_lectivos')->get_nombre_periodo_lectivo($this->s__datos_filtro['periodo_lectivo']);
			$datos_ubicacion = toba::consulta_php('co_ubicaciones')->get_nombre_ubicacion($this->s__datos_filtro['ubicacion']);
			$this->s__datos_seleccion['ubicacion_nombre'] = $datos_ubicacion['ubicacion_nombre'];
			$datos_actividad = toba::consulta_php('co_elementos')-> get_actividad_nombre($this->get_actividad_seleccionada());
			$this->s__datos_seleccion['actividad_descr'] = $datos_actividad[0]['codigo_y_nombre'];
			if (isset($this->s__datos_filtro['responsable_academica'])) {
				$datos_ra = toba::consulta_php('co_responsables_academicas')->get_datos_responsable_academica($this->s__datos_filtro['responsable_academica']);				
				$this->s__datos_seleccion['resp_acad_nombre'] = $datos_ra[0]['resp_acad_nombre'];
			}
			// La cantidad de comisiones de la actividad se obtiene de la carga del cuadro a editar.
			$this->s__datos_seleccion['total_comisiones'] = $this->get_cant_comisiones_actividad_seleccionada();
		}
		return $this->s__datos_seleccion;
	}
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'periodo_lectivo_nombre', 'ubicacion_nombre', 'resp_acad_nombre', 'actividad_descr', 'total_comisiones'));
			$this->ev_info->set_columnas_fila(array('anio_academico', 'periodo_lectivo_nombre', 'ubicacion_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('actividad_descr', 'total_comisiones', 'resp_acad_nombre'), 2);
			$this->ev_info->set_max_columnas_por_fila(4);
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_lectivo_nombre', 'Período Lectivo');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('resp_acad_nombre', 'Responsable Académica');
			$this->ev_info->set_etiqueta('actividad_descr', 'Actividad');
			$this->ev_info->set_etiqueta('total_comisiones', 'Total Comisiones');
			$this->ev_info->set_td_etiqueta_width('130px');
		}
		return $this->ev_info;
	}
}
?>