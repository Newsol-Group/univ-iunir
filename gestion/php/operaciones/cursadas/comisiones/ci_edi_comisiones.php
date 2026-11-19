<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class ci_edi_comisiones extends guarani_pers_ci
{
	protected $s__actividad;  //Actividad seleccionada en la pantalla de navegación.
	protected $s__periodo;   //Período Lectivo seleccionado en los filtros de la pantalla inicial.
	protected $s__ubicacion;  //Ubicación seleccionada en los filtros de la pantalla inicial.
	protected $s__edicion_actual; //Indica si actualmente se está editando o agregando una comisión o una sucomisión (para saber qué pantalla mostrar).
	protected $s__filtro_plan_personalizado; //Plan personalizado seleccionado en el filtro de la pantalla inicial
	protected $indx_msg_eof = '800_eof_cuadro'; //Seteo del mensaje eof para cuadros en los ci's de edición hijos.
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin'; //Mensaje de confirmación para el evento Eliminar de los nodos del árbol.
	protected $indx_msg_confirmar_duplicar = '800CUR_duplicar_comision';

	// Funciones que permiten obtener las variables de sesión que mantienen la actividad y periodo seleccionados en la pant inicial.
	
	function get_actividad()
	{
		return $this->s__actividad;
	}

	function get_periodo()
	{
		return $this->s__periodo;
	}

	function get_ubicacion()
	{
		if (!empty($this->s__ubicacion)) {
			return $this->s__ubicacion;
		}
	}

	function get_indx_msg_eof()
	{
		return $this->indx_msg_eof;
	}

	function ini()
	{
		// Se recuperan el id y la descripción de la Actividad seleccionada en la pantalla de Navegación.
		$this->s__actividad = $this->controlador()->get_actividad_seleccionada();
		$this->s__periodo = $this->controlador()->get_periodo_lectivo_seleccionado();
		$this->s__ubicacion = $this->controlador()->get_ubicacion_seleccionada();
		$this->s__filtro_plan_personalizado = $this->controlador()->get_plan_personalizado_seleccionado();
	}

	/**
	 * Construcción del árbol de la pantalla de selección de Comisiones y Subcomisiones.
	 */
	function construir_arbol()
	{
		// Configuración del árbol 
		$constructor = guarani::constructor_arbol();

		// Íconos de acciones
		$img_editar = toba_recurso::imagen_proyecto('acciones/editar.gif');
		$img_agregar = toba_recurso::imagen_proyecto('acciones/agregar.gif');
		$img_eliminar = toba_recurso::imagen_proyecto('acciones/borrar.gif');
		$img_duplicar = toba_recurso::imagen_proyecto('acciones/duplicar.gif');
		$img_nula = toba_recurso::imagen_proyecto('acciones/nulo.gif');

		// Íconos de conceptos
		$img_actividad = toba_recurso::imagen_proyecto('comisiones/actividad.png');
		$img_comision = toba_recurso::imagen_proyecto('comisiones/comision.png');
		$img_subcomision = toba_recurso::imagen_proyecto('comisiones/subcomision.png');

		/**
		 * El nivel raíz se define únicamente cuando hay un nodo principal del cual se desprende todo el árbol y 
		 * cuyos datos no se encuentran en la consulta que alimenta al árbol.
		 */
		// Nivel raíz (actividad)
		$actividad_descr = '(' . $this->s__actividad['codigo'] . ') ' . $this->s__actividad['nombre'];
		$nivel = guarani::arbol_nivel('actividad', 'elemento', 'elemento_nombre', 'Actividad');
		$nivel->agregar_icono($img_actividad, null, $actividad_descr);
		$nivel->agregar_utileria($img_nula, '', '');
		$nivel->agregar_utileria($img_nula, '', '');
		$nivel->agregar_utileria($img_agregar, 'agregar_comision', 'Agregar una Comisión a la Actividad');
		$nivel->set_metodo_configurador($this, 'configuracion_nodo__actividad');
		$constructor->set_nivel_raiz($nivel, $this->s__actividad['elemento'], $actividad_descr);

		// Nivel Comisión
		$nivel = guarani::arbol_nivel('comision', 'comision', 'comision_nombre', 'Comisión', 'Actividad');
		$nivel->agregar_icono($img_comision, 'seleccionar_comision', 'Editar la Comisión');
		$nivel->agregar_utileria($img_editar, 'seleccionar_comision', 'Editar la Comisión');
		$msj_elim = guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array('la Comisión'));
		$nivel->agregar_utileria($img_eliminar, 'eliminar_comision', 'Eliminar la Comisión', $msj_elim);
		$nivel->agregar_utileria($img_agregar, 'agregar_subcomision', 'Agregar una Subcomisión a la Comisión');
		$msj_duplicar = guarani_mensajes::get($this->indx_msg_confirmar_duplicar);
		$nivel->agregar_utileria($img_duplicar, 'duplicar_comision', 'Duplicar la Comisión', $msj_duplicar);
		$constructor->set_nivel($nivel);

		// Nivel Subcomisión
		$nivel = guarani::arbol_nivel('subcomision', 'subcomision', 'subcomision_nombre', 'Subcomisión', 'Comisión');
		$nivel->agregar_icono($img_subcomision, 'seleccionar_subcomision', 'Editar la Subcomisión');
		$nivel->agregar_utileria($img_editar, 'seleccionar_subcomision', 'Editar la Subcomisión');
		$msj_elim = guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array('la Subcomisión asociada a la Comisión'));
		$nivel->agregar_utileria($img_eliminar, 'eliminar_subcomision', 'Eliminar la Subcomisión', $msj_elim);
		$nivel->agregar_utileria($img_nula, '', '');
		$constructor->set_nivel($nivel);

		// Carga de datos
		$constructor->set_datos($this->get_datos_arbol());
		return $constructor;
	}

	/**
	 * Se configura el ci de edición deseleccionando todos los nodos del árbol, y agregando la dependencia correspondiente
	 * a la derecha del árbol, según si se está editando o agregando una comisión o subcomisión (seteado en las variables 
	 * de sesión correspondientes).  Si existe una comisión o subcomisión para modificar o agregar entonces se asocia el 
	 * evento Guardar a la pantalla (si se está eliminando o no se seleccionó ningun evento del árbol no tiene sentido).
	 */
	function conf()
	{
		if ($this->s__edicion_actual) {
			$dep_a_eliminar = ($this->s__edicion_actual == 'editor_comision') ? 'editor_subcomision' : 'editor_comision';
			$this->pantalla()->eliminar_dep($dep_a_eliminar);
			$this->controlador()->pantalla()->agregar_evento('limpiar');
			$this->controlador()->pantalla()->agregar_evento('guardar');
		} else {
			$this->pantalla()->eliminar_dep('editor_comision');
			$this->pantalla()->eliminar_dep('editor_subcomision');
			$this->dep('seleccion_comision_subcomision')->deseleccionar_nodos();
		}
	}

	function get_datos_arbol()
	{
		$condicion = 'sga_comisiones.elemento = ' . toba::db()->quote($this->s__actividad['elemento']);
		$condicion .= ' AND sga_comisiones.periodo_lectivo = ' . toba::db()->quote($this->s__periodo);
		if (!empty($this->s__ubicacion)) {
			$condicion .= ' AND sga_comisiones.ubicacion = ' . toba::db()->quote($this->s__ubicacion);
		}
		if (!empty($this->s__filtro_plan_personalizado)) {
			$condicion .= ' AND sga_comisiones.acepta_planes_personalizados = ' . toba::db()->quote($this->s__filtro_plan_personalizado);
		}
		$datos_arbol = $this->cn()->get_listado($condicion);
		return $datos_arbol;
	}

	function configuracion_nodo__actividad(guarani_nodo_basico $nodo)
	{
		$actividad = $nodo->get_id();
		$nombre_por_plan = false;
		$nombres_alternativos = toba::consulta_php('co_elementos')->get_actividad_nombres_alternativos($actividad, $nombre_por_plan);
		if (!empty($nombres_alternativos)) {
			$info_extra = '<b>Nombres alternativos</b>:';
			foreach ($nombres_alternativos as $nombre_alternativo) {
				$info_extra .= '<br>' . $nombre_alternativo['nombre'];
			}
			$nodo->set_info_extra($info_extra);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos  ARBOL ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * En cada evento del árbol lo primero que se realiza es un actualizar datos que realiza el sincronizar de los datos que 
	 * se estaban editando o agregando antes de cambiar de nodo en el árbol. Además se actualiza las variable de sesión
	 * que mantiene que dependencia debe mostrarse (pantalla de comisión o pantalla de subcomisión o ninguna de ellas). Ante 
	 * el evento de eliminación de un nodo, no debe mostrarse ninguna pantalla y por lo tanto no debe agregarse ninguna 
	 * dependencia.
	 */
	function nodo__actividad__agregar_comision($id_actividad)
	{
		$this->actualizar_datos();
		$this->set_edicion_actual('editor_comision');
		$this->cn()->resetear_atributos_parametros();
		$this->cn()->agregar_comision_a_actividad($id_actividad, $this->get_periodo());
	}

	function nodo__comision__seleccionar_comision($id_comision)
	{
		$this->actualizar_datos();
		$this->set_edicion_actual('editor_comision');
		$this->cn()->cargar_comision($id_comision);
	}

	function nodo__comision__agregar_subcomision($id_comision)
	{
		// COMISIÓN SIN SUBCOMISIONES.
		// Si el parámetro de sistema indica que no se emplean subcomisiones, entonces no pueden agregarse.
		$propuestas_comision = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($id_comision), 'propuesta');
		$usa_subcomisiones = guarani::parametros()->get_valor('cur_usa_subcomisiones', $propuestas_comision);
		if ($usa_subcomisiones != comunes::si) {
			throw guarani::error(guarani_mensajes::get('800CUR_comision_sin_subcomisiones'));
		}
		// COMISIÓN NO EDITABLE.
		// Si la comisión tiene actas cerradas no pueden agregarse subcomisiones.
		$filtro = "sga_actas.estado = '".acta::estado_cerrada."'";
		$actas_cerradas = toba::consulta_php('co_evaluaciones')->get_cant_actas_evaluaciones_para_comision($id_comision, $filtro);
		if ($actas_cerradas > 0) {
			throw guarani::error(guarani_mensajes::get('800CUR_comision_con_actas_cerradas'));
		}
		$this->actualizar_datos();
		$this->set_edicion_actual('editor_subcomision');
		$this->dep('editor_subcomision')->disparar_limpieza_memoria();
		$this->cn()->agregar_subcomision_a_comision($id_comision);
	}

	function nodo__comision__eliminar_comision($id_comision)
	{
		// COMISIÓN NO ELIMINABLE.
		// Si la comisión tiene inscriptos a cursar (ya sea activos o dados de baja),
		// evaluaciones manuales o actas creadas (aunque estén anuladas), entonces no puede eliminarse.
		$inscriptos_comision = toba::consulta_php('co_inscripciones_cursadas')->get_cant_inscripciones_para_comision($id_comision);
		if ($inscriptos_comision > 0) {
			throw guarani::error(guarani_mensajes::get('800CUR_comision_con_inscriptos'));
		}
		$inscripciones_bajas_comision = toba::consulta_php('co_inscripciones_cursadas')->get_cant_inscripciones_bajas_para_comision($id_comision);
		if ($inscripciones_bajas_comision > 0) {
			throw guarani::error(guarani_mensajes::get('800CUR_comision_con_inscriptos_baja'));
		}
		$filtro = "sga_evaluaciones_tipos.automatica = '".comunes::no."'";
		$evaluaciones_comision = toba::consulta_php('co_evaluaciones')->get_cant_evaluaciones_para_comision($id_comision, $filtro);
		if ($evaluaciones_comision > 0) {
			throw guarani::error(guarani_mensajes::get('800CUR_comision_con_evaluaciones'));
		}
		$actas_comision = toba::consulta_php('co_evaluaciones')->get_cant_actas_evaluaciones_para_comision($id_comision);
		if ($actas_comision > 0) {
			throw guarani::error(guarani_mensajes::get('800CUR_comision_con_actas'));
		}
		$this->actualizar_datos();
		$this->cn()->eliminar_comision($id_comision);
	}

	function nodo__comision__duplicar_comision($id_comision)
	{
		// COMISIÓN NO DUPLICABLE.
		// No es duplicable si el período lectivo de la comisión ya culminó.
		$periodo_finalizado = toba::consulta_php('co_comisiones')->comision_con_periodo_lectivo_finalizado($id_comision);
		if ($periodo_finalizado) {
			throw guarani::error(guarani_mensajes::get('800CUR_comision_periodo_finalizado'));
		}
		/**
		 * Se actualizan los datos para resetear la dependencia actualmente cargada, si existe.
		 * Luego se intenta duplicar la comisión en base a la actividad y período lectivo seleccionados,
		 * si se logra sin errores críticos, se setea la edición actual. En caso contrario se emite el
		 * mensaje correspondiente, quedando la pantalla sin edición actual ni nodos seleccionados.
		 * Además si la duplicación fue existosa, debe seleccionarse manualmente el nuevo nodo creado para
		 * su edición, debido a que naturalmente la función del árbol evt__accion_nodo deja seleccionado el
		 * nodo al que pertenece el evento (que en este caso sería la comisión original que fue duplicada).
		 */
		try {
			$this->actualizar_datos();
			$id_comision_nueva = $this->cn()->duplicar_comision_para_actividad($id_comision, $this->s__actividad['elemento'], $this->s__periodo);
			$this->set_edicion_actual('editor_comision');
			$this->dep('seleccion_comision_subcomision')->deseleccionar_nodos();
			$this->dep('seleccion_comision_subcomision')->seleccionar_nodo('comision', $id_comision_nueva);
		} catch (guarani_error $e) {
			throw $e;
		}
	}

	function nodo__subcomision__seleccionar_subcomision($id_subcom)
	{
		$this->actualizar_datos();
		$this->set_edicion_actual('editor_subcomision');
		$this->dep('editor_subcomision')->disparar_limpieza_memoria();
		$this->cn()->cargar_subcomision($id_subcom);
	}

	function nodo__subcomision__eliminar_subcomision($id_subcomision)
	{
		// SUBCOMISIÓN NO ELIMINABLE.
		// Si la subcomisión tiene inscriptos a cursar o su comisión asociada tiene actas cerradas,
		// entonces no puede eliminarse.
		$inscriptos_subcomision = toba::consulta_php('co_inscripciones_cursadas')->get_cant_inscripciones_para_subcomision($id_subcomision);
		if ($inscriptos_subcomision > 0) {
			throw guarani::error(guarani_mensajes::get('800CUR_subcom_con_inscriptos'));
		}
		$actas_cerradas = toba::consulta_php('co_actas')->get_cant_actas_cerradas_subcomision($id_subcomision);
		if ($actas_cerradas > 0) {
			throw guarani::error(guarani_mensajes::get('800CUR_subcom_con_inscriptos'));
		}
		$this->actualizar_datos();
		$this->cn()->eliminar_subcomision($id_subcomision);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos CI -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Verifica si había algo cargado previamente. En caso de haber alguna entidad se sincronizan los datos para poder pasar
	 * al nuevo nodo, cargando la nueva entidad. Además se invoca al método resetear que resetea los datos.
	 */
	function actualizar_datos()
	{
		if ($this->cn()->entidad_cargada()) {
			if ($this->cn()->entidad()->es_editable()) {
				$this->controlador->evt__procesar();
			}
			$this->evt__resetear();
		}
	}

	function set_edicion_actual($edicion_actual)
	{
		$this->s__edicion_actual = $edicion_actual;
	}

	/**
	 * Se limpia la variable de sesión que mantiene el componente que está actualmente en edición (comisión o subcomisión), 
	 * para saber qué pantalla mostrar a la derecha del árbol al configurar el ci. Se desmarcan los nodos actualmente 
	 * seleccionados en el árbol y se resetea el cn.
	 */
	function evt__resetear()
	{
		$this->cn()->resetear();
		$this->limpiar_edicion();
		$this->dep('seleccion_comision_subcomision')->deseleccionar_nodos();
	}

	/**
	 * Se resetean las variables de sesión del ci hijo de edición actual de comisiones o subcomisiones.
	 * Luego, se resetea la variable de sesión que mantiene el componente que está actualmente en edición 
	 * (esto se utiliza desde el ci navegación al cancelar edición o volver a la pantalla de selección).
	 */
	function limpiar_edicion()
	{
		if (!empty($this->s__edicion_actual)) {
			if ($this->s__edicion_actual == 'editor_comision') {
				$this->dependencia($this->s__edicion_actual)->resetear_vars();
			}
			$this->s__edicion_actual = null;
		}
	}

	function set_hubo_cambios()
	{
		$this->controlador()->set_hubo_cambios();
	}

}
?>