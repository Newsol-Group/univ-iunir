<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class ci_nav_matrices_equivalencia extends ci_navegacion
{
	//Filtro sobre Comisiones y Actividades
	protected $s__datos_filtro;
	protected $s__filtro;
	
	//Selección Actividad
	protected $s__seleccion_prop_plan_version;

	function ini__operacion()
	{
		$plan_version = toba::memoria()->get_parametro('plan_version');
		if (!empty($plan_version)) {
			$this->s__seleccion_prop_plan_version = array('plan_version' => $plan_version);
			$this->set_pantalla('edicion');
		}
	}

	/**
	 * Permite obtener la selección de plan versión realizada por el usuario, para obtener las diferentes matrices de 
	 * equivalencia asociadas a la misma en la pantalla de edición (todavía no se realizó la carga ya que el DR mantiene 
	 * una única matriz o grupo (la elegida para editar o agregar).
	 */
	function get_plan_version_seleccionado()
	{
		return current($this->s__seleccion_prop_plan_version);
	}
	
	function resetear_variables()
	{
		unset($this->s__seleccion_prop_plan_version);
	}
	
	function ini()
	{
		//Se setea el hecho de no volver al guardar en el ci padre.
		parent::ini();
		$this->controlador()->set_no_volver_al_guardar();	
	}
	
	/**
	 * Se define la configuración de la pantalla de edición de Matrices y Grupos para que se puedan tener en cuenta los 
	 * cambios en el datos relación cuando se ejecuta el evento Guardar en el ci padre (ci_navegacion). Debe hacerse un 
	 * chequeo adicional porque en esta pantalla de edición no siempre hay una entidad cargada.
	 */
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		if ($this->cn()->entidad_cargada()) {
			 if ($this->cn()->entidad()->dep('datos')->hay_cambios()) {
			 	parent::set_hubo_cambios();
			 }
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION PLAN-VERSION DESTINO --------------------------------------
	//-----------------------------------------------------------------------------------

	//---- Filtro sobre propuestas-planes-versiones destino -----------------------------
	
	function conf__filtro_propuesta_destino(guarani_ei_filtro $filtro)
	{
		// Se asigna el 'igual a' como condición fija para las columna de 'tiene_matrices' ya que no tiene sentido otra condición.
		$filtro->columna('tiene_matrices')->set_condicion_fija('es_igual_a');
		if (isset($this->s__datos_filtro)) {
			$filtro->set_datos($this->s__datos_filtro);
		} 
	}

	function evt__filtro_propuesta_destino__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro = $datos;
			$filtro_con_matrices = '';
			if ((isset($datos['tiene_matrices']))) {
				$filtro_con_matrices = ($datos['tiene_matrices']['valor'] == comunes::si) ? ' AND EXISTS ' : ' AND NOT EXISTS ';
				$filtro_con_matrices .= '(SELECT * FROM sga_equiv_matrices WHERE sga_equiv_matrices.plan_version_destino = sga_planes_versiones.plan_version)';
				$this->dependencia('filtro_propuesta_destino')->columna('tiene_matrices')->resetear_estado();
			}
			$this->s__filtro = "{$this->dependencia('filtro_propuesta_destino')->get_sql_where()} $filtro_con_matrices";
		} else {
			unset($this->s__datos_filtro);
			$this->s__filtro = '';
		}
	}

	function evt__filtro_propuesta_destino__cancelar()
	{
		//Se limpian los datos y el filtro (condición where) del filtro sobre propuestas-planes-versiones destino.
		unset($this->s__datos_filtro);
		unset($this->s__filtro);
	}
	
	//---- cuadro_actividades ----------------------------------------------------------
	
	function conf__cuadro_propuestas_destino(toba_ei_cuadro $cuadro)
	{
		/**
 		 * Si no está seteado el filtro sobre propuestas, entonces es la carga inicial del cuadro (el usuario no 
 		 * presionó filtrar o presionó limpiar). No se traen datos (no se ejecuta la consulta) y se pone un mensaje 
 		 * en el cuadro que indica al usuario que debe filtrar para obtener datos.
		*/
		//Recupera el índice del mensaje de eof del cuadro del ci de navegación.
		$indx_msj = $this->indx_msg_eof;
		if (!isset($this->s__filtro)) {
			$indx_msj .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj));
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$indx_msj = $this->indx_msg_eof . '_filtrado';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array('Propuestas')));
			$propuestas = toba::consulta_php('co_matrices_de_equivalencia')->get_propuestas_planes_versiones_activos_matrices_equivalencia($this->s__filtro);
			$cuadro->set_datos($propuestas);
		}
	}
	
	function evt__cuadro_propuestas_destino__seleccion($seleccion)
	{
		$this->s__seleccion_prop_plan_version = $seleccion;
		$this->set_pantalla('edicion');	
	}

	//-----------------------------------------------------------------------------------
	//---- EVENTOS DEL CI ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//Se define el evento procesar que actúa como el guardar del ci de navegación pero no se muestra el cartel de 
	//'guardar ok' ante cada cambio de nodo en el árbol.
	function evt__procesar()
	{
		try {
			$this->cn()->guardar();
			if ($this->volver_al_guardar) {
				$this->cn()->resetear();
				$this->set_pantalla('seleccion_matriz_grupo');
			}
		} catch (guarani_error $e)	{
			throw $e;
		}
	}
	
	//Se define un evento para poder Cancelar la edición o el agregado de un Nodo en el árbol.
	function evt__limpiar()
	{
		$this->dependencia('editor_matrices')->evt__resetear();
	}

	//Se redefine el evento Volver del ci de navegación para que limpie la variable de sesión que mantiene el componente en edición.
	function evt__cancelar()
	{
		$this->dependencia('editor_matrices')->limpiar_edicion();
		$this->resetear_variables();
		parent::evt__cancelar();
	}
	
}
?>