<?php
/**
 *
 *
 * @package Propuestas Formativas
 * @subpackage Actividades
 */
class ci_edi_optativas_de_actividades_genericas extends guarani_pers_ci
{
	protected $s__mat_gen;			//Actividad seleccionada en la pantalla de navegación.
	protected $s__optativa;			//Optativa seleccionada en la segunda pantalla de edición.
	protected $s__datos_optativa;	//Datos de la optativa seleccionada en segunda pantalla.
	protected $s__edicion_actual;	//Indica si actualmente se está editando una optativa (para saber si mostrar o no la pantalla).

	//Elementos de Visualización
	protected $ev_info_generica_optativa;

	// Funciones que permiten obtener las variables de sesión que mantienen la actividad generica y optativa en edición.
	function get_actividad_generica()
	{
		return $this->s__mat_gen;
	}

	function get_actividad_optativa()
	{
		return $this->s__optativa;
	}

	function get_edicion_actual()
	{
		return $this->s__edicion_actual;	
	}
	
	function ini()
	{
		//Se recuperan el id y la descripción de la Actividad seleccionada en la pantalla de Navegación.
		$this->s__mat_gen = $this->controlador()->get_mat_generica();
		$this->s__optativa = $this->controlador()->get_optativa_seleccionada();
		$this->s__datos_optativa = toba::consulta_php('co_elementos')->get_datos_basicos_elemento_revision($this->s__optativa);
		$this->s__datos_optativa['actividad_descr'] = '(' . $this->s__datos_optativa['codigo'] . ') ' . $this->s__datos_optativa['nombre'];
	}
	
	/**
	 * Se configura el ci de edición deseleccionando todos los nodos del árbol, y agregando la dependencia 
	 * correspondiente a la derecha del árbol, según si se está editando o no una optativa.
	 */
	function conf()
	{
		if ($this->s__edicion_actual) {
			$this->pantalla()->agregar_dep($this->s__edicion_actual);
		} else {
			$this->dep('seleccion_plan_version')->deseleccionar_nodos();
		}
	}
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$datos = $this->get_datos_generica_optativa();
		$this->ev_info_generica_optativa()->set_datos($datos);
		/**
		 * Se verifica si la Actividad Genérica no tiene regla por puntaje, entonces no se agrega el form para 
		 * editar el puntaje
		 */
		if ($pantalla->existe_dependencia('form_puntaje')) {
			$pantalla->eliminar_dep('form_puntaje');
		}
		if ($this->cn()->entidad()->es_generica_por_puntaje() && $this->cn()->entidad()->modo_optativas_temporales()) {
			$pantalla->agregar_dep('form_puntaje');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Form Puntaje -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	// Puntaje de la asociación de la optativa a la materia genérica si es que la regla de la misma es por puntaje.
	function conf__form_puntaje(toba_ei_formulario $form)
	{
		if (isset($this->datos_puntaje_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_puntaje_temp);
		} else {
			$puntaje = $this->cn()->entidad()->get_puntaje_componente_optativa_seleccionada();
			$form->set_datos(array('puntaje' => $puntaje));
		}
	}

	function evt__form_puntaje__modificacion($datos)
	{
		$this->cn()->entidad()->set_puntaje_componente_optativa_seleccionada($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- Árbol ------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//Construcción del árbol de la pantalla de selección de Comisiones y Subcomisiones.
	function construir_arbol()
	{
		//Configuración del árbol
		$constructor = guarani::constructor_arbol();

		//Iconos de acciones
		$img_editar = toba_recurso::imagen_proyecto('acciones/editar.gif');

		//Iconos de conceptos
		$img_actividad = toba_recurso::imagen_proyecto('optativas/actividad_optativa.png');
		$img_plan_version = toba_recurso::imagen_proyecto('optativas/elemento_plan_version.png');
		$img_nula = toba_recurso::imagen_proyecto('acciones/nulo.gif');

		/* El nivel raíz se define únicamente cuando hay un nodo principal del cual se desprende todo el árbol y
		* cuyos datos no se encuentran en la consulta que alimenta al árbol.
		*/
		//Nivel raíz (actividad optativa)
		$nivel = guarani::arbol_nivel('actividad', 'elemento', 'actividad_descr', 'Actividad');
		$nivel->agregar_icono($img_actividad, null, $this->s__datos_optativa['actividad_descr']);
		$nivel->agregar_utileria($img_nula, '', '');
		$constructor->set_nivel_raiz($nivel, $this->s__optativa, $this->s__datos_optativa['actividad_descr']);

		//Nivel Plan Version
		$nivel = guarani::arbol_nivel('atributos_optativa_plan_version', 'plan_version', 'plan_version_nombre', 'Plan Versión', 'Actividad');
		$nivel->agregar_icono($img_plan_version, 'seleccionar_plan_version', 'Editar los Atributos de la Optativa para el Plan Versión');
		$nivel->agregar_utileria($img_editar, 'seleccionar_plan_version', 'Editar los Atributos de la Optativa para el Plan Versión');
		$constructor->set_nivel($nivel);

		//Carga de datos
		$constructor->set_datos($this->get_datos_arbol());
		return $constructor;
	}

	function get_datos_arbol()
	{
		// Se obtienen las configuraciones para los distintos planes versiones de la optativa seleccionada.
		$datos_arbol = $this->cn()->get_datos_optativa_planes_versiones();
		foreach ($datos_arbol as $key => $dato) {
			$datos_plan = toba::consulta_php('co_planes')->get_plan_version_nombre($dato['plan_version']);
			$datos_arbol[$key]['plan_version_nombre'] = $datos_plan['plan_version_nombre'];
		}
		return $datos_arbol;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos  ARBOL ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * En cada evento del árbol lo primero que se realiza es un actualizar datos que guarda los datos que se 
	 * estaban editando antes de cambiar de nodo en el árbol. 
	 * Además se actualiza la variable de sesión que mantiene si la dependencia debe mostrarse (pantalla de
	 * configuración de la optativa).
	 */
	function nodo__atributos_optativa_plan_version__seleccionar_plan_version($id_plan_version)
	{
		$this->actualizar_datos();
		$this->set_edicion_actual('editor_optativa');
		$this->cn()->cargar_optativa_plan_version($this->s__optativa, $id_plan_version);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos CI ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Verifica si había algo cargado previamente. En caso de haber alguna configuración de optativa seleccionada, 
	 * se resetean los datos para poder cambiar al nuevo nodo, seteando el cursor en la nueva fila. 
	 */
	function actualizar_datos()
	{
		if ($this->cn()->hay_plan_version_seleccionado()) {
			/**
			 * En este caso al cambiar de nodo no se sincroniza con la BD, ni se guardan los datos en el DT
			 * Sólo se reseteará y se apuntará a la nueva fila en edición.
			 */
			$this->evt__resetear();
		}
	}

	function set_edicion_actual($edicion_actual)
	{
		$this->s__edicion_actual = $edicion_actual;
		$this->dependencia($this->s__edicion_actual)->set_pantalla('pant_datos_generales');
	}

	/**
	 * Se limpia la variable de sesión que mantiene el componente que está actualmente en edición, 
	 * para saber si mostrar la pantalla a la derecha del árbol al configurar el ci. Se desmarcan los nodos 
	 * actualmente seleccionados en el árbol y se resetea la selección en el DT de plan versión.
	 */
	function evt__resetear()
	{
		$this->cn()->deseleccionar_plan_version();
		$this->limpiar_edicion();
		$this->dep('seleccion_plan_version')->deseleccionar_nodos();
	}

	/**
	 * Se resetean las variables de sesión del ci hijo de edición actual de optativas.
	 * Luego, se resetea la variable de sesión que mantiene el componente que está actualmente en edición 
	 * (esto se utiliza desde el ci navegación al cancelar edición o volver a la pantalla de selección).
	 */	
	function limpiar_edicion()
	{
		if (!empty($this->s__edicion_actual)) {
			if ($this->s__edicion_actual == 'editor_optativa') {
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
	//---- Elementos de visualización ---------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_datos_generica_optativa()
	{
		return $this->controlador()->get_datos_mat_generica();
	}

	function ev_info_generica_optativa()
	{
		if (!isset($this->ev_info_generica_optativa)) {
			$this->ev_info_generica_optativa = guarani::ev_info_datos();
			$this->ev_info_generica_optativa->set_controlador($this);
			$this->ev_info_generica_optativa->set_columnas_a_mostrar(array('nombre', 'estado_desc', 'regla_nombre'));
			$this->ev_info_generica_optativa->set_columnas_fila(array('nombre', 'estado_desc'), 1);
			$this->ev_info_generica_optativa->set_columnas_fila(array('regla_nombre'), 2);
			$this->ev_info_generica_optativa->set_max_columnas_por_fila(2);
			$this->ev_info_generica_optativa->set_etiqueta('nombre', 'Actividad Genérica');
			$this->ev_info_generica_optativa->set_etiqueta('estado_desc', 'Estado');
			$this->ev_info_generica_optativa->set_etiqueta('regla_nombre', 'Forma cumplimiento');
			$this->ev_info_generica_optativa->set_td_etiqueta_width('185px');
		}
		return $this->ev_info_generica_optativa;
	}
}
?>