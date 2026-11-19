<?php
/**
 * Representa un Grupo de Equivalencia que se asocia a una Matriz definida para un Plan Versión destino.
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class cn_ent_grupo_equivalencia extends cn_entidad
{
	function get_id_grupo()
	{
		return $this->tabla('grupo')->get_columna('grupo_equivalencia');
	}
	
	function get_matriz_grupo()
	{
		return $this->tabla('grupo')->get_columna('matriz');
	}
	
	//-------------------------------------------------------------------
	
	function get_datos_generales()
	{
		return $this->tabla('grupo')->get();
	}

	/**
	 * Se utiliza desde el cn man cuando se agrega desde el árbol una nuevo grupo, sólo para agregar el id de 
	 * matriz al que está asociado, por lo tanto no debe invocarse al validar datos.
	 * 
	 */
	function set_datos_iniciales($datos)
	{
		$this->tabla('grupo')->set($datos);	
	}

	function set_datos_generales($datos)
	{
		$this->validar_datos_generales($datos);
		$this->tabla('grupo')->set($datos);
	}
	
	//-------------------------------------------------------------------
	
	function get_datos_actividades_origen()
	{
		$condicion = array('origen_destino'=>equivalencia::grupo_activ_origen);
		return $this->tabla('actividades')->get_filas($condicion);	
	}
	
	function get_datos_actividades_destino()
	{
		$condicion = array('origen_destino'=>equivalencia::grupo_activ_destino);
		return $this->tabla('actividades')->get_filas($condicion);	
	}
	
	function set_datos_actividades($datos)
	{
		$this->tabla('actividades')->procesar_filas($datos);
	}
	
	function eliminar_actividad_grupo($id_fila_activ)
	{
		// Se elimina la asociación de la actividad seleccionada al Grupo.
		if (!is_null($id_fila_activ)) {
			$this->tabla('actividades')->eliminar_fila($id_fila_activ);
		}
	}

	// Se asocian al Grupo actual las actividades origen seleccionadas.
	function agregar_actividades_origen($activ_seleccionadas)
	{
		foreach ($activ_seleccionadas as $actividad) {
			$actividad['origen_destino'] = equivalencia::grupo_activ_origen;
			$this->agregar_actividad_grupo($actividad);
		}
	}

	// Se asocian al Grupo actual las actividades destino seleccionadas.
	function agregar_actividades_destino($activ_seleccionadas)
	{
		foreach ($activ_seleccionadas as $actividad) {
			$actividad['origen_destino'] = equivalencia::grupo_activ_destino;
			$this->agregar_actividad_grupo($actividad);
		}
	}
	
	/**
	 * Se asocia una nueva Actividad, ya sea origen o destino, con el id de elemento al Grupo padre que actualmente 
	 * se está agregando o editando.
	 */
	function agregar_actividad_grupo($actividad)
	{
		$this->tabla('actividades')->nueva_fila($actividad);
	}
	
	//---------------------------------------------------------------------
	
	function validar_datos_generales($datos)
	{
		// Se verifican que se hayan completado los campos correspondientes a grupo activo.
		$this->validar_campos_grupo_activo($datos['activo'], $datos);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
 	  * Determina si tienen valor los campos correspondientes según el valor del campo Activo del Grupo.
      *	@param char 	activo	determina si el grupo está o no activo.
      * @param array	datos	datos generales del grupo. 
     */
	function validar_campos_grupo_activo($activo, $datos) 
	{
		if ($activo == comunes::no) {
			if (!isset($datos['nro_resolucion_baja'])) {
				$this->set_error('800_dato_dep_obligatorio', array('Nro de Resolución de Baja', 'Activo'));
			}
			if (!isset($datos['fecha_baja'])) {
				$this->set_error('800_dato_dep_obligatorio', array('Fecha de Baja', 'Activo'));
			}
		} else {
			//No pueden editarse los campos fecha baja y nro resolucion baja.	
			$this->limpiar_campos_no_editables($datos, array('nro_resolucion_baja', 'fecha_baja')); 
		}
	}

	//---------------------------------------------------------------------
	
	/**
	 * Extensión de la función del cn_entidad para realizar controles previos a la sincronización de datos.
	 * Se controla que exista al menos una Actividad origen y una destino asociadas al Grupo de Equivalencia.
	 * Además si el grupo tiene seleccionada la opción Traslada Nota, se verifica que existan al menos una Actividad
	 * Destino y una Origen con las opciones de copia nota y traslada nota correspondientemente chequeadas.
	 */
	function pre_sincronizar()
	{
		$actividades_origen = $this->get_datos_actividades_origen();
		$this->validar_existe_actividad_origen($actividades_origen);
		$actividades_destino = $this->get_datos_actividades_destino();
		$this->validar_existe_actividad_destino($actividades_destino);
		/**
		 * Valida que si se seleccionó que el grupo tenga la propiedad de trasladar nota, entonces se hayan seleccionado
		 * al menos una actividad origen que traslada nota y una actividad destino que copia nota.
		 */
		$datos_grupo = $this->get_datos_generales();
		if ($datos_grupo['traslada_nota'] == comunes::si) {
			$this->validar_activ_origen_traslada($actividades_origen);
			$this->validar_activ_destino_copia($actividades_destino);
		}
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
	
	function validar_existe_actividad_origen($actividades)
	{
		if (empty($actividades)) {
			$this->set_error('800_no_seleccion', array('una Actividad Origen para el Grupo'));
		}
	}
	
	function validar_existe_actividad_destino($actividades)
	{
		if (empty($actividades)) {
			$this->set_error('800_no_seleccion', array('una Actividad Destino para el Grupo'));
		}
	}
	
	/**
	 * Se verifica que exista al menos una actividad origen que traslade nota.
	*/
	function validar_activ_origen_traslada($datos_actividades)
	{
		$hay_activ_origen = false;
		foreach ($datos_actividades as $actividad) {
			if ($actividad['origen_destino'] == equivalencia::grupo_activ_origen && $actividad['nota'] == comunes::si) {
				$hay_activ_origen = true;
				break;
			}
		}
		if (!$hay_activ_origen) {
			$this->set_error('800_no_seleccion', array('una Actividad Origen con la propiedad de Trasladar Nota'));
		}
	}
	
	/**
	 * Se verifica que exista al menos una actividad destino que copie nota.
	*/
	function validar_activ_destino_copia($datos_actividades)
	{
		$hay_activ_destino = false;
		foreach ($datos_actividades as $actividad) {
			if ($actividad['origen_destino'] == equivalencia::grupo_activ_destino && $actividad['nota'] == comunes::si) {
				$hay_activ_destino = true;
				break;
			}
		}
		if (!$hay_activ_destino) {
			$this->set_error('800_no_seleccion', array('una Actividad Destino con la propiedad de Copiar Nota'));
		}
	}
}


?>