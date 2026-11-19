<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class ci_edi_mesas_de_examen extends guarani_pers_ci
{
	protected $s__actividad;		//Actividad seleccionada en la pantalla de navegación.
	protected $s__turno;			//Turno de Exámen seleccionado en los filtros de la pantalla inicial.
	protected $s__ubicacion;		//Ubicación seleccionada en los filtros de la pantalla inicial.
	protected $s__edicion_actual;	//Indica si actualmente se está editando o agregando una Mesa o un Llamado (para saber qué pantalla mostrar).
	protected $s__filtro_plan_personalizado; //Plan personalizado seleccionado en el filtro de la pantalla inicial 
	
	protected $indx_msg_eof = '800_eof_cuadro';	//Seteo del mensaje eof para cuadros en los ci's de edición hijos.
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin'; //Mensaje de confirmación para el evento Eliminar de los nodos del árbol.

	function get_actividad()
	{
		return $this->s__actividad;
	}

	function get_turno()
	{
		return $this->s__turno;
	}

	function get_ubicacion()
	{
		if (!empty($this->s__ubicacion)) {
			return $this->s__ubicacion;
		}
	}

	/**
	 * Permite obtener el índice del mensaje eof para cuadros a utilizarse en los ci's hijos.
	 */
	function get_indx_msg_eof()
	{
		return $this->indx_msg_eof;
	}

	function ini()
	{
		// Se recuperan el id y la descripción de la Actividad seleccionada en la pantalla de Navegación.
		$this->s__actividad = $this->controlador()->get_actividad_seleccionada();
		$this->s__turno = $this->controlador()->get_turno_examen_seleccionado();
		$this->s__ubicacion = $this->controlador()->get_ubicacion_seleccionada();
		$this->s__filtro_plan_personalizado = $this->controlador()->get_plan_personalizado_seleccionado();
		// Se setea el dato del Turno de Exámen seleccionado en el cn manejador, ya que se utilizará en las clases de negocio
		// para diferentes validaciones y búsqueda de datos, y el mismo no se guarda en ninguno de los Drs manipulados.
		$this->cn()->set_turno_examen($this->s__turno);
	}

	/**
	 * Construcción del árbol de la pantalla de selección de Mesas y Llamados.
	 */
	function construir_arbol()
	{
		// Configuración del árbol 
		$constructor = guarani::constructor_arbol();

		// Íconos de acciones
		$img_editar = toba_recurso::imagen_proyecto('acciones/editar.gif');
		$img_agregar = toba_recurso::imagen_proyecto('acciones/agregar.gif');
		$img_eliminar = toba_recurso::imagen_proyecto('acciones/borrar.gif');
		$img_nula = toba_recurso::imagen_proyecto('acciones/nulo.gif');

		// Íconos de conceptos
		$img_actividad = toba_recurso::imagen_proyecto('mesas/actividad.png');
		$img_mesa = toba_recurso::imagen_proyecto('mesas/mesa.png');
		$img_llamado = toba_recurso::imagen_proyecto('mesas/llamado.png');

		// El nivel raíz se define únicamente cuando hay un nodo principal del cual se desprende 
		// todo el árbol y  cuyos datos no se encuentran en la consulta que alimenta al árbol.
		
		// Nivel raíz (actividad)
		$actividad_descr = '(' . $this->s__actividad['codigo'] . ') ' . $this->s__actividad['nombre'];
		$nivel = guarani::arbol_nivel('actividad', 'elemento', 'elemento_nombre', 'Actividad');
		$nivel->agregar_icono($img_actividad, null, $actividad_descr);
		$nivel->agregar_utileria($img_nula, '', '');
		$nivel->agregar_utileria($img_nula, '', '');
		$nivel->agregar_utileria($img_agregar, 'agregar_mesa', 'Agregar una Mesa a la Actividad');
		$nivel->set_metodo_configurador($this, 'configuracion_nodo__actividad');
		$constructor->set_nivel_raiz($nivel, $this->s__actividad['elemento'], $actividad_descr);

		// Nivel Mesa de Exámen
		$nivel = guarani::arbol_nivel('mesa', 'mesa_examen', 'mesa_nombre', 'Mesa', 'Actividad');
		$nivel->agregar_icono($img_mesa, 'seleccionar_mesa', 'Editar la Mesa');
		$nivel->agregar_utileria($img_editar, 'seleccionar_mesa', 'Editar la Mesa');
		$msj_elim = guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array('la Mesa de Exámen y sus Llamados asociados'));
		$nivel->agregar_utileria($img_eliminar, 'eliminar_mesa', 'Eliminar la Mesa', $msj_elim);
		$nivel->agregar_utileria($img_nula, '', '');
		$nivel->set_metodo_configurador($this, 'configuracion_nodo__mesa');
		$constructor->set_nivel($nivel);

		// Nivel Llamado
		$nivel = guarani::arbol_nivel('llamado', 'llamado_mesa', 'llamado_nombre', 'Llamado', 'Mesa');
		$nivel->agregar_icono($img_llamado, 'seleccionar_llamado', 'Editar el Llamado');
		$nivel->agregar_utileria($img_editar, 'seleccionar_llamado', 'Editar el Llamado');
		$nivel->agregar_utileria($img_nula, '', '');
		$nivel->agregar_utileria($img_nula, '', '');
		$nivel->set_metodo_configurador($this, 'configuracion_nodo__llamado');
		$constructor->set_nivel($nivel);

		// Carga de datos
		$constructor->set_datos($this->get_datos_arbol());
		return $constructor;
	}

	/**
	 * Se configura el ci de edición deseleccionando todos los nodos del árbol, y agregando la dependencia correspondiente
	 * a la derecha del árbol, según si se está editando o agregando una mesa o un llamado (seteado en las variables 
	 * de sesión correspondientes).  Si existe una mesa o llamado para modificar o agregar entonces se asocia el 
	 * evento Guardar a la pantalla (si se está eliminando o no se seleccionó ningun evento del árbol no tiene sentido).
	 */
	function conf()
	{
		if ($this->s__edicion_actual) {
			$this->pantalla()->agregar_dep($this->s__edicion_actual);
			$this->controlador()->pantalla()->agregar_evento('guardar');
			$this->controlador()->pantalla()->agregar_evento('limpiar');
		} else {
			$this->dep('seleccion_mesa_llamado')->deseleccionar_nodos();
		}
	}

	function get_datos_arbol()
	{
		$condicion = 'sga_mesas_examen.elemento = ' . toba::db()->quote($this->s__actividad['elemento']);
		$condicion .= ' AND sga_llamados_turno.turno_examen = ' . toba::db()->quote($this->s__turno);
		if (!empty($this->s__ubicacion)) {
			$condicion .= ' AND sga_mesas_examen.ubicacion = ' . toba::db()->quote($this->s__ubicacion);
		}
		if (!empty($this->s__filtro_plan_personalizado)) {
			$condicion .= ' AND sga_mesas_examen.acepta_planes_personalizados = ' . toba::db()->quote($this->s__filtro_plan_personalizado);
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

	function configuracion_nodo__mesa(guarani_nodo_basico $nodo)
	{
		//MESA NO ELIMINABLE.
		/**
		 * Si la Mesa de Exámen tiene Inscriptos (aunque estén dados de baja) o Actas creadas (aunque estén anuladas) en 
		 * alguno de sus Llamados asociados, entonces se quita el evento Eliminar para ese nodo.
		 */
		$id_mesa = $nodo->get_id();
		$inscriptos_llamados_mesa = toba::consulta_php('co_inscripciones_examenes')->get_cant_inscripciones_para_llamados_mesa($id_mesa);
		$inscripciones_bajas_llamados_mesa = toba::consulta_php('co_inscripciones_examenes')->get_cant_inscripciones_bajas_para_llamados_mesa($id_mesa);
		$actas_llamados_mesa = toba::consulta_php('co_evaluaciones')->get_cant_actas_evaluaciones_para_llamados_mesa($id_mesa);
		$evals_llamados_mesa = toba::consulta_php('co_evaluaciones')->get_cant_evaluaciones_para_llamados_mesa($id_mesa);

		if ($inscriptos_llamados_mesa > 0 || $inscripciones_bajas_llamados_mesa > 0 || $actas_llamados_mesa > 0 || $evals_llamados_mesa > 0) {
			$nodo->eliminar_evento('eliminar_mesa');
		}
		//MESA NO EDITABLE. Los controles están incluídos en no eliminable y no es necesario nada adicional para los 
		//llamados ya que estos se agregan y eliminan desde la entidad mesa.
	}

	function configuracion_nodo__llamado(guarani_nodo_basico $nodo)
	{
		//LLAMADO NO ELIMINABLE.
		//No es necesario agregar ningún control porque se verifica en la entidad Mesa de Exámen donde se asocian y 
		//desasocian los llamados a la misma.
		//LLAMADO NO EDITABLE. Se verifica la editabilidad de la entidad en el correspondiente ci. No se necesitan controles
		//adicionales.
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos  ARBOL ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * En cada evento del árbol lo primero que se realiza es un actualizar datos que realiza el sincronizar de los datos que 
	 * se estaban editando o agregando antes de cambiar de nodo en el árbol. Además se actualiza las variable de sesión
	 * que mantiene que dependencia debe mostrarse (pantalla de mesa, pantalla de llamado o ninguna de ellas). Ante 
	 * el evento de eliminación de un nodo, no debe mostrarse ninguna pantalla y por lo tanto no debe agregarse ninguna 
	 * dependencia.
	 */
	function nodo__actividad__agregar_mesa($id_actividad)
	{
		$this->actualizar_datos();
		$this->s__edicion_actual = 'editor_mesa';
		$this->cn()->agregar_mesa_a_actividad($id_actividad, $this->get_turno());
	}

	function nodo__mesa__seleccionar_mesa($id_mesa)
	{
		$this->actualizar_datos();
		$this->s__edicion_actual = 'editor_mesa';
		$this->cn()->cargar_mesa($id_mesa);
	}

	function nodo__mesa__eliminar_mesa($id_mesa)
	{
		$this->actualizar_datos();
		$this->cn()->eliminar_mesa($id_mesa);
	}

	function nodo__llamado__seleccionar_llamado($id_llamado)
	{
		$this->actualizar_datos();
		$this->s__edicion_actual = 'editor_llamado';
		$this->cn()->cargar_llamado($id_llamado);
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

	/**
	 * Se limpia la variable de sesión que mantiene el componente que está actualmente en edición (mesa o llamado), 
	 * para saber qué pantalla mostrar a la derecha del árbol al configurar el ci. Se desmarcan los nodos actualmente 
	 * seleccionados en el árbol y se resetea el cn.
	 */
	function evt__resetear()
	{
		$this->cn()->resetear();
		$this->limpiar_edicion();
		$this->dep('seleccion_mesa_llamado')->deseleccionar_nodos();
	}

	/**
	 * Se resetean las variables de sesión del ci hijo de edición actual de mesa o llamados.
	 * Luego, se resetea la variable de sesión que mantiene el componente que está actualmente en edición 
	 * (esto se utiliza desde el ci navegación al cancelar edición o volver a la pantalla de selección).
	 */
	function limpiar_edicion()
	{
		if (!empty($this->s__edicion_actual)) {
			$this->dependencia($this->s__edicion_actual)->resetear_vars();
			$this->s__edicion_actual = null;
		}
	}

	function set_hubo_cambios()
	{
		$this->controlador()->set_hubo_cambios();
	}

}
?>