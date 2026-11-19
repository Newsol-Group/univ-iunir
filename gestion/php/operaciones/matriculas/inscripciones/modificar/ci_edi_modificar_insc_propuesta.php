<?php
/**
 *
 * 
 *
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 * 
 */
class ci_edi_modificar_insc_propuesta extends ci_edicion_simple 
{
	// Componente visual de información.
	protected $ev_info;
	
	function get_datos_conv()
	{
		$seleccion = $this->controlador()->get_seleccion();
		return toba::consulta_php('co_convenios')->get_convenios_propuestas_all($seleccion['propuesta']);
	}

	function get_datos_estudios()
	{
		$seleccion = $this->controlador()->get_seleccion();
		return toba::consulta_php('co_personas')->get_datos_estudios_persona($seleccion['persona']);
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		$seleccion = $this->controlador()->get_seleccion();
		if ($seleccion['plan_tipo'] != planes::tipo_personalizado && $seleccion['plan_tipo'] != planes::tipo_convenio) {
			$form->set_solo_lectura('fecha_inscripcion');
		}
		parent::conf__formulario($form);
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array(    'apellido_y_nombres', 'nro_identificacion',
															'propuesta_nombre_abreviado', 'plan_nombre', 'plan_version_nombre',
															'modalidad_nombre', 
															'anio_academico', 'periodo_nombre',
															'fecha_cierre_requisitos', 'fecha_inicio', 'fecha_fin'));
			
			$this->ev_info->set_columnas_fila(array('apellido_y_nombres', 'nro_identificacion'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_nombre', 'plan_version_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('modalidad_nombre'), 3);
			$this->ev_info->set_columnas_fila(array('anio_academico', 'periodo_nombre'), 4);
			$this->ev_info->set_columnas_fila(array('fecha_cierre_requisitos', 'fecha_inicio', 'fecha_fin'), 5);
			
			$this->ev_info->set_max_columnas_por_fila(3);
			
			$this->ev_info->set_etiqueta('apellido_y_nombres', 'Apellido y Nombre');
			$this->ev_info->set_etiqueta('nro_identificacion', 'Identificación');
			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('plan_version_nombre', 'Versión');
			$this->ev_info->set_etiqueta('modalidad_nombre', 'Modalidad de Cursada');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_nombre', 'Período Insc.');
			$this->ev_info->set_etiqueta('fecha_cierre_requisitos', 'Fecha Cierre Req.');
			$this->ev_info->set_etiqueta('fecha_inicio', 'Fecha de Inicio');
			$this->ev_info->set_etiqueta('fecha_fin', 'Fecha Finalización');
			
			$this->ev_info->set_td_etiqueta_width('140px');
		}
		return $this->ev_info;
	}
	
	function conf__pant_inicial()
	{
		// Seteo de datos en ev_info
		$this->ev_info()->set_datos($this->get_datos_ev_info());
	}
	
	function get_datos_ev_info()
	{
		$datos = array();
		$seleccion = $this->controlador()->get_seleccion();
		$datos_periodo_insc = guarani::co('co_periodos_de_inscripcion')->get_datos_periodo_insc($seleccion['propuesta_aspira']);
		$formateo = new toba_formateo('html');
		
		$datos['apellido_y_nombres'] = $seleccion['apellido_y_nombres'];
		$datos['nro_identificacion'] = $seleccion['nro_identificacion'];
		$datos['propuesta_nombre_abreviado'] = $seleccion['propuesta_nombre_abreviado'];
		$datos['plan_nombre'] = $seleccion['plan_nombre'];
		$datos['plan_version_nombre'] = $seleccion['plan_version_nombre'];
		$datos['modalidad_nombre'] = $seleccion['modalidad_nombre'];
		$datos['anio_academico'] = $datos_periodo_insc['anio_academico'];
		$datos['periodo_nombre'] = $datos_periodo_insc['periodo_nombre'];
		$datos['fecha_cierre_requisitos'] = $formateo->formato_fecha($datos_periodo_insc['fecha_cierre_requisitos']);
		$datos['fecha_inicio'] = $formateo->formato_fecha($datos_periodo_insc['fecha_inicio']);
		$datos['fecha_fin'] = $formateo->formato_fecha($datos_periodo_insc['fecha_fin']);
		
		return $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX  ------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__datos_estudio_anterior($params, toba_ajax_respuesta $respuesta)
	{
		$dato_estudio = current($params);
		$respuesta->set(array('institucion_anterior' => toba::consulta_php('co_personas')->get_institucion_dato_estudio($dato_estudio)));
	}
}
?>