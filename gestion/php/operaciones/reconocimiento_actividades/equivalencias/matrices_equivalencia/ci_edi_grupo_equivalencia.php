<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class ci_edi_grupo_equivalencia extends guarani_pers_ci
{
	//Generales
	protected $s__plan_version_seleccionado;	// Plan-Versión seleccionado en la pantalla de navegación.
	
	//Datos temporales del formulario
	protected $datos_generales_temp;
	protected $datos_activ_origen_temp;
	protected $datos_activ_destino_temp;

	function ini()
	{
		$this->s__plan_version_seleccionado 	= $this->controlador()->get_plan_version_destino();
	}
	
	//-----------------------------------------------------------------------------------
	//---- auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Funciones para retornar los valores de las selecciones iniciales y demás valores utilizados para la configuración
	 * de los cuadros de selección de actividades.
	 */
	
	function get_plan_version_seleccionado()
	{
		return $this->s__plan_version_seleccionado;
	}
	
	function get_datos_matriz_asociada()
	{
		$id_matriz = $this->cn()->entidad()->get_matriz_grupo();
		$datos_matriz = toba::consulta_php('co_matrices_de_equivalencia')->get_datos_matriz($id_matriz);
		return $datos_matriz;
	}
	
	/**
	 * Se resetean las variables de sesión utilizadas al mantener información, para poder limpiarlas desde el ci padre o 
	 * desde el ci de navegación, ante el cambio de nodo en el árbol, o el evento cancelar edición de un nodo o volver a 
	 * la pantalla de selección.
	 */
	function resetear_vars()
	{
		$this->dependencia('seleccion_actividades')->resetear_vars();
		unset($this->s__plan_version_seleccionado);
	}

	// Funciones utilizadas desde el ci de selección de actividades origen y destino.
	
	function get_actividades_origen_asociadas()
	{
		return $this->cn()->entidad()->get_datos_actividades_origen();
	}
	
	function get_actividades_destino_asociadas()
	{
		return $this->cn()->entidad()->get_datos_actividades_destino();
	}
	
	//-----------------------------------------------------------------------------------
	//---- datos_generales --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_datos_grupo(toba_ei_formulario $form)
	{
		$form->ef('nombre')->set_estado_defecto('Grupo');
		$form->ef('activo')->set_estado_defecto(comunes::si);
		// Si el Grupo no es nuevo y ya fue utilizado para otorgar equivalencias, debe restringirse la edición de campos.
		if (!$this->cn()->entidad_es_nueva()) {
			$id_grupo = $this->cn()->entidad()->get_id_grupo();
			$equivalencias_grupo = toba::consulta_php('co_matrices_de_equivalencia')->get_grupo_equivalencias_otorgadas($id_grupo);
			if (!empty($equivalencias_grupo)) {
				$form->set_solo_lectura(array('nro_resolucion_alta', 'traslada_nota'));
			}
		}
		
		if (isset($this->datos_generales_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_generales_temp);
		} else {
			// Se está editando un grupo ya existente.
			$form->set_datos($this->cn()->entidad()->get_datos_generales());			
		} 
	}

	function evt__form_datos_grupo__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_generales($datos);
		} catch (guarani_error $e) {
			$this->datos_generales_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- actividades_origen -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__actividades_origen(toba_ei_formulario_ml $form_ml)
	{
		// Se verifica si en lo datos del grupo se definió que no se traslada nota para deshabilitar el campo correspondiente.
		if (isset($this->datos_activ_origen_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_activ_origen_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_actividades_origen();
		}
		$form_ml->set_datos($datos);
	}

	function evt__actividades_origen__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_actividades($datos);
		} catch ( guarani_error $e ) {
			$this->datos_instancias_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	function evt__actividades_origen__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_actividad_grupo($seleccion);
	}
	
	//-----------------------------------------------------------------------------------
	//---- actividades_destino ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__actividades_destino(toba_ei_formulario_ml $form_ml)
	{
		// Se verifica si en lo datos del grupo se definió que no se traslada nota para deshabilitar el campo correspondiente.
		if (isset($this->datos_activ_destino_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_activ_destino_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_actividades_destino();
		}
		$form_ml->set_datos($datos);
	}

	function evt__actividades_destino__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_actividades($datos);
		} catch ( guarani_error $e ) {
			$this->datos_activ_destino_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	function evt__actividades_destino__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_actividad_grupo($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- ci_edi_seleccion_actividades -------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function agregar_actividades_origen($activ_seleccionadas)
	{
		if (empty($activ_seleccionadas)) {
			$msj_error = guarani_mensajes::get('800_no_seleccion', array('una Actividad Origen para el Grupo'));
			throw new guarani_error($msj_error);
		} else {
			$this->cn()->entidad()->agregar_actividades_origen($activ_seleccionadas);
		}
	}
	
	function agregar_actividades_destino($activ_seleccionadas)
	{
		if (empty($activ_seleccionadas)) {
			$msj_error = guarani_mensajes::get('800_no_seleccion', array('una Actividad Destino para el Grupo'));
			throw new guarani_error($msj_error);
		} else {
			$this->cn()->entidad()->agregar_actividades_destino($activ_seleccionadas);
		}
	}
}

?>