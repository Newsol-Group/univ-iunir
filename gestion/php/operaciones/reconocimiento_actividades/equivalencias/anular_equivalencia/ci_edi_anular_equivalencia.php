<?php
class ci_edi_anular_equivalencia extends guarani_pers_ci
{
	// Datos del trámite de equivalencia seleccionado en la primer pantalla.
	protected $s__tramite_a_cerrar;
	
	// Datos del form donde se cargan las observaciones de la anulación. Como no se utiliza Dr, se guardan en una
	// variable de sesión que se recupera desde el controlador al ejecutarse el evt Anular Trámite.
	protected $s__observ;
	
	// Elemento Visual de Información y datos para el mismo.
	protected $ev_info;
	protected $s__datos_seleccion;
	
	//--------------- Auxiliares ---------------------------------------------------------
	
	function get_observ_anulacion()
	{
		return $this->s__observ;
	}
	
	function resetear_vars()
	{
		unset($this->s__tramite_a_cerrar);
		unset($this->s__datos_seleccion);
		unset($this->s__observ);	
	}
	
	//--------------- Configuraciones ----------------------------------------------------
	
	function ini()
	{
		parent::ini();

		// Se recupera el trámite de equivalencia seleccionado en la primer pantalla.
		$this->s__tramite_a_cerrar = $this->controlador()->get_tramite_equiv_seleccionado();
		// Se setean los datos del ev visual.
		$this->s__datos_seleccion = toba::consulta_php('co_equivalencias')->get_datos_tramite_equivalencia_otorgado($this->s__tramite_a_cerrar);
	}
	
	function conf__pant_previsualizar(guarani_ei_pantalla $pantalla)
	{
		//Seteo de datos en ev
		$this->ev_info()->set_datos($this->s__datos_seleccion);
	}
	
	//---- Componente visual para mostrar Información ------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 20px;');	
			$this->ev_info->set_columnas_a_mostrar(array('equivalencia_tramite', 'documento_numero', 'fecha_formato_visual', 'rectifica_a', 'alumno_nombre_completo', 'alumno_identificacion', 'legajo', 'propuesta_alumno', 'plan_alumno', 'version_alumno', 'origen_descr'));
			$this->ev_info->set_max_columnas_por_fila(4);
				
			$this->ev_info->set_columnas_fila(array('equivalencia_tramite', 'documento_numero', 'fecha_formato_visual'), 1);
			$this->ev_info->set_columnas_fila(array('alumno_nombre_completo'), 2);
			$this->ev_info->set_columnas_fila(array('alumno_identificacion', 'legajo'), 3);
			$this->ev_info->set_columnas_fila(array('propuesta_alumno'), 4);
			$this->ev_info->set_columnas_fila(array('plan_alumno'), 5);
			$this->ev_info->set_columnas_fila(array('version_alumno'), 6);
			$this->ev_info->set_columnas_fila(array('origen_descr', 'rectifica_a'), 7);
			$this->ev_info->set_columnas_fila(array('institucion_nombre'), 8);
			
			$this->ev_info->set_etiqueta('equivalencia_tramite', 'Nº de Trámite');
			$this->ev_info->set_etiqueta('documento_numero', 'Nº de Resolución');
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('rectifica_a', 'Rectifica a');
			$this->ev_info->set_etiqueta('alumno_nombre_completo', 'Apellido y Nombre');
			$this->ev_info->set_etiqueta('alumno_identificacion', 'Identificación');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			$this->ev_info->set_etiqueta('propuesta_alumno', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_alumno', 'Plan');
			$this->ev_info->set_etiqueta('version_alumno', 'Versión');
			$this->ev_info->set_etiqueta('origen_descr', 'Tipo de Equivalencia');
			$this->ev_info->set_etiqueta('estado_nombre', 'Estado');
			$this->ev_info->set_etiqueta('institucion_nombre', 'Institución Externa');
		}
		return $this->ev_info;
	}
	
	//---- actividades_equivalencia_tramite ---------------------------------------------
	
	function conf__actividades_equivalencia_tramite(toba_ei_cuadro $cuadro)
	{
		$detalle_tramite_equivalencia = toba::consulta_php('co_equivalencias')->get_detalle_actividades_tramite_equivalencia($this->s__tramite_a_cerrar);
		$cuadro->set_datos($detalle_tramite_equivalencia);
	}

	//---- form_observ_tramite ----------------------------------------------------------
	
	function evt__form_observ_tramite__modificacion($datos)
	{
		$this->s__observ = $datos['observaciones'];
	}
	
}
?>