<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Aprobación por Resolución
 */
class ci_edi_anular_aprobacion extends guarani_pers_ci
{
	//Elemento Visual de Información
	protected $ev_info;
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- Configuraciones --------------------------------------------------------------
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$tramite = $this->controlador()->get_tramite_aprobacion_seleccionado();
		$datos = toba::consulta_php('co_aprobacion_por_resolucion')->get_datos_tramite_aprobacion_por_resolucion($tramite);
		$formateo = new toba_formateo('html');
		$datos['fecha_format'] = $formateo->formato_fecha($datos['fecha']);
		
		if ($datos['tipo_reconocimiento'] == aprobacion_por_resolucion::activ_modulos) {
			$pantalla->agregar_dep('actividades_modulos_reconocimiento');
			unset($datos['total_creditos']);
		}
		
		$this->ev_info()->set_datos($datos);
	}

	//---- actividades_aprobacion -------------------------------------------------------
	
	function conf__actividades_aprobacion(toba_ei_cuadro $cuadro)
	{
		$tramite = $this->controlador()->get_tramite_aprobacion_seleccionado();
		$actividades = toba::consulta_php('co_aprobacion_por_resolucion')->get_actividades_tramite_aprobacion($tramite);
		$cuadro->set_datos($actividades);
	}

	//---- actividades_modulos_reconocimiento -------------------------------------------
	
	function conf__actividades_modulos_reconocimiento(toba_ei_cuadro $cuadro)
	{
		$tramite = $this->controlador()->get_tramite_aprobacion_seleccionado();
		$reconocimiento = toba::consulta_php('co_aprobacion_por_resolucion')->get_actividades_reconocimiento_tramite_aprobacion($tramite);
		$cuadro->set_datos($reconocimiento);
	}

	//---- form_observaciones -----------------------------------------------------------
	
	function evt__form_observaciones__modificacion($datos)
	{
		$this->controlador()->set_observaciones($datos);
	}
	
	//---- Componente visual para mostrar Información ------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('nro_tramite', 'documento_numero', 'fecha_format', 'alumno_nombre_completo', 'alumno_identificacion', 'legajo', 'propuesta_tramite', 'plan_tramite', 'version_tramite', 'tipo_reconocimiento_descr', 'total_creditos'));
			$this->ev_info->set_max_columnas_por_fila(3);
				
			$this->ev_info->set_columnas_fila(array('nro_tramite', 'documento_numero', 'fecha_format'), 1);
			$this->ev_info->set_columnas_fila(array('alumno_nombre_completo', 'alumno_identificacion', 'legajo'), 2);
			$this->ev_info->set_columnas_fila(array('propuesta_tramite', 'plan_tramite', 'version_tramite'), 3);
			$this->ev_info->set_columnas_fila(array('tipo_reconocimiento_descr', 'total_creditos'), 4);
			
			$this->ev_info->set_etiqueta('nro_tramite', 'Nº TRAMITE');
			$this->ev_info->set_etiqueta('documento_numero', 'Nº RESOLUCIÓN');
			$this->ev_info->set_etiqueta('fecha_format', 'FECHA');
			$this->ev_info->set_etiqueta('alumno_nombre_completo', 'Apellido y Nombre');
			$this->ev_info->set_etiqueta('alumno_identificacion', 'Identificación');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			$this->ev_info->set_etiqueta('propuesta_tramite', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_tramite', 'Plan');
			$this->ev_info->set_etiqueta('version_tramite', 'Versión');
			$this->ev_info->set_etiqueta('tipo_reconocimiento_descr', 'RECONOCE');
			$this->ev_info->set_etiqueta('total_creditos', 'Créditos Otorgados');
		}
		
		return $this->ev_info;
	}
	
}
?>