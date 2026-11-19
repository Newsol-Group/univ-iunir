<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class ci_edi_matrices_equivalencia extends guarani_pers_ci
{
	protected $s__plan_version;		// Plan Versión seleccionado en la pantalla de navegación.
	protected $s__datos_plan_version_propuesta; // Datos asociados al plan versión seleccionado en la pantalla inicial.
	protected $s__edicion_actual;	// Indica si actualmente se está editando o agregando una Matriz o un Grupo (para saber qué pantalla mostrar).

	protected $indx_msg_eof = '800_eof_cuadro';	// Seteo del mensaje eof para cuadros en los ci's de edición hijos.
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin'; // Mensaje de confirmación para el evento Eliminar de los nodos del árbol.

	// Elemento visual para mostrar los datos del plan versión-propuesta destino seleccionados en la pantalla inicial.
	protected $ev_info_prop_destino;
	
	// Funciones que permiten obtener los datos de plan versión y propuesta seleccionados en la pantalla inicial.
	function get_plan_version_destino()
	{
		return $this->s__plan_version;	
	}
	
	function get_propuesta_destino()
	{
		return $this->s__datos_plan_version_propuesta['propuesta'];
	}
	
	function get_propuesta_destino_nombre()
	{
		return $this->s__datos_plan_version_propuesta['propuesta_nombre'];
	}
	
	function get_plan_destino()
	{
		return $this->s__datos_plan_version_propuesta['plan'];
	}
	
	//Permite obtener el índice del mensaje eof para cuadros a utilizarse en los ci's hijos.
	function get_indx_msg_eof()
	{
		return $this->indx_msg_eof;	
	}
	
	function ini()
	{
		//Se recupera el plan versión seleccionado en la pantalla de navegación y sus datos asociados.
		$this->s__plan_version = $this->controlador()->get_plan_version_seleccionado();
		//Seteo de los datos para el ev de información del destino seleccionado.
		$filtro = "sga_planes_versiones.plan_version = $this->s__plan_version";
		$this->s__datos_plan_version_propuesta = current(toba::consulta_php('co_matrices_de_equivalencia')->get_propuestas_planes_versiones_activos_matrices_equivalencia($filtro));
		$this->s__datos_plan_version_propuesta['plan_nombre_estado']	= $this->s__datos_plan_version_propuesta['plan_nombre'] .  ' (' . $this->s__datos_plan_version_propuesta['plan_estado_descr'] . ')';
		$this->s__datos_plan_version_propuesta['version_nombre_estado']	= $this->s__datos_plan_version_propuesta['plan_version_nombre'] . ' (' . $this->s__datos_plan_version_propuesta['plan_version_estado_descr'] . ')';
	}
	
	//Construcción del árbol de la pantalla de selección de Matrices y Grupos.
	function construir_arbol()
	{
		//Configuración del árbol 
		$constructor = guarani::constructor_arbol();
		
		//Iconos de acciones
		$img_editar = toba_recurso::imagen_proyecto('acciones/editar.gif');
		$img_agregar = toba_recurso::imagen_proyecto('acciones/agregar.gif');
		$img_eliminar = toba_recurso::imagen_proyecto('acciones/borrar.gif');
		$img_nula = toba_recurso::imagen_proyecto('acciones/nulo.gif');

		//Iconos de conceptos
		$img_propuesta_raiz = toba_recurso::imagen_proyecto('equivalencias/propuesta_alcance.png');
		$img_alcance = toba_recurso::imagen_proyecto('equivalencias/propuesta_alcance.png');
		$img_matriz = toba_recurso::imagen_proyecto('equivalencias/matriz_grupo.png');
		$img_grupo = toba_recurso::imagen_proyecto('equivalencias/matriz_grupo.png');
		$img_orden = toba_recurso::imagen_proyecto('equivalencias/ordenar.gif');
		/**
		 * El nivel raíz se define únicamente cuando hay un nodo principal del cual se desprende todo el árbol y 
		 * cuyos datos no se encuentran en la consulta que alimenta al árbol.
		 */
		//Nivel raíz (propuesta destino)
		$nivel = guarani::arbol_nivel('propuesta', 'plan_version', 'propuesta_descr', 'Propuesta Destino');
		$nivel->agregar_icono($img_propuesta_raiz, null, $this->s__datos_plan_version_propuesta['propuesta_descr']);
		$nivel->agregar_utileria($img_nula, '', '');
		$nivel->agregar_utileria($img_nula, '', '');
		$nivel->agregar_utileria($img_agregar, 'agregar_matriz', 'Agregar una Matriz a la Propuesta-Plan-Versión');
		$constructor->set_nivel_raiz($nivel, $this->s__datos_plan_version_propuesta['plan_version'], $this->s__datos_plan_version_propuesta['propuesta_descr']);
		
		//Nivel Alcance (para agrupar las matrices por el mismo)
		$nivel = guarani::arbol_nivel('alcance', 'alcance', 'alcance', 'Alcance de la Matriz', 'Propuesta Destino');
		$nivel->agregar_icono($img_alcance);
		$constructor->set_nivel($nivel);
		
		//Nivel Matriz de Equivalencia
		$nivel = guarani::arbol_nivel('matriz', 'matriz', 'matriz_nombre', 'Matriz de Equivalencia', 'Alcance de la Matriz');
		$nivel->agregar_icono($img_matriz, 'seleccionar_matriz', 'Editar la Matriz');
		$nivel->agregar_utileria($img_editar, 'seleccionar_matriz', 'Editar la Matriz');
		$msj_elim = guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array('la Matriz y sus Grupos asociados'));
		$nivel->agregar_utileria($img_eliminar, 'eliminar_matriz', 'Eliminar la Matriz', $msj_elim);
		$nivel->agregar_utileria($img_agregar, 'agregar_grupo', 'Agregar un Grupo a la Matriz');
		$nivel->agregar_utileria($img_orden, 'ordenar_grupo', 'Ordenar Grupos');
		$nivel->set_metodo_configurador($this, 'configuracion_nodo__matriz');
		$constructor->set_nivel($nivel);
		
		//Nivel Grupo de Matriz
		$nivel = guarani::arbol_nivel('grupo', 'grupo_equivalencia', 'grupo_nombre', 'Grupo de Matriz', 'Matriz de Equivalencia');
		$nivel->agregar_icono($img_grupo, 'seleccionar_grupo', 'Editar el Grupo');
		$nivel->agregar_utileria($img_editar, 'seleccionar_grupo', 'Editar el Grupo');
		$msj_elim = guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array('el Grupo y sus Actividades asociadas'));
		$nivel->agregar_utileria($img_eliminar, 'eliminar_grupo', 'Eliminar el Grupo', $msj_elim);
		$nivel->agregar_utileria($img_nula, '', '');
		$nivel->set_metodo_configurador($this, 'configuracion_nodo__grupo');
		$constructor->set_nivel($nivel);
		
		//Carga de datos
		$constructor->set_datos($this->get_datos_arbol());
		return $constructor;
	}

	/**
	 * Se configura el ci de edición deseleccionando todos los nodos del árbol, y agregando la dependencia correspondiente
	 * a la derecha del árbol, según si se está editando o agregando una matriz o un grupo (seteado en las variables 
	 * de sesión correspondientes).  Si existe una matriz o grupo para modificar o agregar entonces se asocia el 
	 * evento Guardar a la pantalla (si se está eliminando o no se seleccionó ningun evento del árbol no tiene sentido).
	 */
	function conf()
	{
		if ($this->s__edicion_actual) {
   			$this->pantalla()->agregar_dep($this->s__edicion_actual);
			$this->controlador()->pantalla()->agregar_evento('limpiar');
   			$this->controlador()->pantalla()->agregar_evento('guardar');
  		} else {
   			$this->dep('seleccion_matriz_grupo')->deseleccionar_nodos();
  		}
  		//Se setean los datos de la selección de propuesta-plan-version destino para el elemento visual.
  		$this->ev_info_prop_destino()->set_datos($this->s__datos_plan_version_propuesta);
	}

	function get_datos_arbol()
	{
		$condicion = 'sga_equiv_matrices.plan_version_destino = ' . toba::db()->quote($this->s__datos_plan_version_propuesta['plan_version']);
		$datos_arbol = $this->cn()->get_listado($condicion);
		return $datos_arbol;
	}
	
	function configuracion_nodo__matriz(guarani_nodo_basico $nodo)
	{
		//MATRIZ NO ELIMINABLE.
		/**
		 * La Matriz no será eliminable si ha sido utilizada con anterioridad (Se considera que fue utilizada cuando 
		 * existe alguna equivalencia otorgada a un alumno donde se aplicó alguno de los grupos de equivalencia que la 
		 * componen. En este caso se deberá dar de baja la misma (no eliminarse).
		 */ 
		$id_matriz = $nodo->get_id();
		$equivalencias_otorgadas = toba::consulta_php('co_matrices_de_equivalencia')->get_matriz_equivalencias_otorgadas($id_matriz);
		if (!empty($equivalencias_otorgadas)) {
			$nodo->eliminar_evento('eliminar_matriz');
		}
		//MATRIZ NO EDITABLE. No hay casos en que deba deshabilitarse por completo la edición de la matriz.
	}

	function configuracion_nodo__grupo(guarani_nodo_basico $nodo)
	{
		//GRUPO NO ELIMINABLE.
		/**
		 * El grupo no es eliminable si ha sido utilizado con anterioridad en el otorgamiento de Equivalencias. 
		 * En este caso deberá darse de baja el mismo (no eliminarlo).
		 */
		$id_grupo = $nodo->get_id();
		$equivalencias_otorgadas = toba::consulta_php('co_matrices_de_equivalencia')->get_grupo_equivalencias_otorgadas($id_grupo);
		if (!empty($equivalencias_otorgadas)) {
			$nodo->eliminar_evento('eliminar_grupo');
		}
		//GRUPO NO EDITABLE. No hay casos en que deba deshabilitarse por completo la edición de un grupo.
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
	function nodo__propuesta__agregar_matriz($id_plan_version_destino)
	{
		$this->actualizar_datos();
		$this->s__edicion_actual = 'editor_matriz';
		$this->cn()->agregar_matriz_a_plan_version($id_plan_version_destino);
	}
	
	function nodo__matriz__seleccionar_matriz($id_matriz)
	{
		$this->actualizar_datos();
		$this->s__edicion_actual = 'editor_matriz';
		$this->cn()->cargar_matriz($id_matriz);
	}
	
	function nodo__matriz__eliminar_matriz($id_matriz)
	{	
		$this->actualizar_datos();
		$this->cn()->eliminar_matriz($id_matriz);
	}
	
	function nodo__matriz__agregar_grupo($id_matriz)
	{
		$this->actualizar_datos();
		$this->s__edicion_actual = 'editor_grupo';
		$this->cn()->agregar_grupo_a_matriz($id_matriz);
	}
	
	function nodo__matriz__ordenar_grupo($id_matriz)
	{
		$this->actualizar_datos();
		$this->s__edicion_actual = 'editor_orden';
		$this->cn()->cargar_matriz($id_matriz);
	}
	
	function nodo__grupo__seleccionar_grupo($id_grupo)
	{
		$this->actualizar_datos();
		$this->s__edicion_actual = 'editor_grupo';
		$this->cn()->cargar_grupo($id_grupo);
	}

	function nodo__grupo__eliminar_grupo($id_grupo)
	{
		$this->actualizar_datos();
		$this->cn()->eliminar_grupo($id_grupo);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos CI ----------------------------------------------------------------------
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
	 * Se limpia la variable de sesión que mantiene el componente que está actualmente en edición (matriz o grupo), 
	 * para saber qué pantalla mostrar a la derecha del árbol al configurar el ci. Se desmarcan los nodos actualmente 
	 * seleccionados en el árbol y se resetea el cn.
	 */
	function evt__resetear()
	{
		$this->limpiar_edicion();
		$this->dep('seleccion_matriz_grupo')->deseleccionar_nodos();
		$this->cn()->resetear();
	}
	
	/**
	 * Se resetean las variables de sesión del ci hijo de edición actual de matriz o grupos.
	 * Luego, se resetea la variable de sesión que mantiene el componente que está actualmente en edición 
	 * (esto se utiliza desde el ci navegación al cancelar edición o volver a la pantalla de selección).
	 */	
	function limpiar_edicion()
	{
		if (!empty($this->s__edicion_actual)) {
			if ($this->s__edicion_actual == 'editor_grupo') {
				$this->dependencia($this->s__edicion_actual)->resetear_vars();
			}
			$this->s__edicion_actual = null;	
		}
	}
	
	function set_hubo_cambios()
	{
		$this->controlador()->set_hubo_cambios();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliar para la visualización de selección inicial de propuesta -------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_prop_destino()
	{
		if (!isset($this->ev_info_prop_destino)) {
			$this->ev_info_prop_destino = guarani::ev_info_datos();
			$this->ev_info_prop_destino->set_controlador($this);
			$this->ev_info_prop_destino->set_titulo('Destino');
			$this->ev_info_prop_destino->set_style('width: 100%; border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info_prop_destino->set_style_fila('height:15px');
			$this->ev_info_prop_destino->set_columnas_a_mostrar(array('propuesta_descr', 'plan_nombre_estado', 'version_nombre_estado', 'plan_version_actual_nombre'));
			$this->ev_info_prop_destino->set_etiqueta('propuesta_descr', 'Propuesta');
			$this->ev_info_prop_destino->set_etiqueta('plan_nombre_estado', 'Plan');
			$this->ev_info_prop_destino->set_etiqueta('version_nombre_estado', 'Versión');
			$this->ev_info_prop_destino->set_etiqueta('plan_version_actual_nombre', 'Versión actual');

		}
		return $this->ev_info_prop_destino;
	}
	
}

?>