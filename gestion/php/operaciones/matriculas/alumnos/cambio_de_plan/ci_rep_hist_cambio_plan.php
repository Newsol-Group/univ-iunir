<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Alumnos
 * 
 */
class ci_rep_hist_cambio_plan extends guarani_pers_ci
{
	protected $s__alumno;
	
	function ini()
	{
		$this->s__alumno = toba::memoria()->get_dato('alumno');
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array(	'apellido', 'nombres', 'ubicacion_nombre',
															'documento_principal', 'legajo',
															'propuesta_nombre_abreviado', 'plan_codigo', 'plan_version_codigo'));
			
			$this->ev_info->set_columnas_fila(array('apellido', 'nombres', 'ubicacion_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('documento_principal', 'legajo'), 2);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre_abreviado', 'plan_codigo', 'plan_version_codigo'), 3);
			
			
			$this->ev_info->set_max_columnas_por_fila(3);
			
			$this->ev_info->set_etiqueta('apellido', 'Apellido');
			$this->ev_info->set_etiqueta('nombres', 'Nombres');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación Actual');
			
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			
			$this->ev_info->set_etiqueta('propuesta_nombre_abreviado', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_codigo', 'Plan');
			$this->ev_info->set_etiqueta('plan_version_codigo', 'Versión');
			
			$this->ev_info->set_td_etiqueta_width('100px');
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
		$where = 'sga_alumnos.alumno = '.toba::db()->quote($this->s__alumno);
		$datos = guarani::co('co_alumnos')->get_listado($where);
		return current($datos);
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_planes ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_planes(toba_ei_cuadro $cuadro)
	{
		$datos = guarani::co('co_alumnos')->get_listado_historico_cambio_planes($this->s__alumno);
		$cuadro->set_datos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__cerrar = function()
		{
			window.close();
		}
		";
	}

}
?>