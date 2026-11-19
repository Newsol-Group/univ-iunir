<?php
class ci_edi_resasignar_optativas extends guarani_pers_ci
{
	protected $s__datos_alumno;
	protected $s__nodo_seleccionado;
	protected $ev_info;
			
	function conf__pant_reasignar(pant_reasignar $pantalla)
	{
		$datos = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($this->controlador()->get_alumno_seleccionado());
		$this->s__datos_alumno = $datos;
		$this->cn()->set_alumno($datos);
		$this->ev_info()->set_datos($datos);
		
		if (!$this->dep('ci_cont_arbol_reasignacion')->hay_nodo_seleccionado()) {
				$pantalla->eliminar_dep('ci_reasignar');
		}				
		
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'legajo', 'propuesta_nombre_abreviado', 'plan_nombre', 'ubicacion_nombre'));
			$this->ev_info->set_max_columnas_por_fila(2);
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');			
			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');			
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			
		}
		return $this->ev_info;
	}
	
	/**
	 * Construcción del árbol de la pantalla de selección de Comisiones y Subcomisiones.
	 */
	function construir_arbol()
	{
		//Configuración del árbol 
		$img_raiz = toba_recurso::imagen_proyecto('propuesta/g_univ.png');
		$img_generica = toba_recurso::imagen_proyecto('propuesta/g_materia_generica.png');
		$img_editar = toba_recurso::imagen_proyecto('acciones/editar.gif');
				
		$constructor = guarani::constructor_arbol();
		$constructor->set_datos($this->get_datos_arbol());				
		$nivel = guarani::arbol_nivel('id', 'id', 'raiz');
		$nivel->agregar_icono($img_raiz);
		$constructor->set_nivel_raiz($nivel, 'id', 'Raíz del Plan');
		
		$nivel = guarani::arbol_nivel('elemento', 'elemento', 'nombre');		
		$nivel->agregar_icono($img_generica, 'seleccionar', 'Seleccionar Genérica');
		$constructor->set_nivel($nivel);
		
		return $constructor;
	}
	
	function nodo__elemento__seleccionar($nodo)
	{
		$this->cn()->set_nodo($nodo);
	}
			
	function get_datos_arbol()
	{
		return $this->cn()->get_actividades_genericas();
	}
	
	

}
?>