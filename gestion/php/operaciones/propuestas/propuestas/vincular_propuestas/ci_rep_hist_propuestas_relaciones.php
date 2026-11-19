<?php
/**
 * 
 * @package Propuestas Formativas
 */
class ci_rep_hist_propuestas_relaciones extends guarani_pers_ci
{
	protected $s__propuesta;
	protected $s__plan;

	function ini()
	{
		$this->s__propuesta = toba::memoria()->get_parametro('propuesta');
		$this->s__plan = toba::memoria()->get_parametro('plan');
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'plan_nombre', 'propuesta_tipo_descripcion'));
			$this->ev_info->set_columnas_fila(array('nombre', 'plan_nombre', 'propuesta_tipo_descripcion'), 1);
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_td_etiqueta_width('70px');
			$this->ev_info->set_etiqueta('nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('propuesta_tipo_descripcion', 'Tipo');
		}
		return $this->ev_info;
	}

	function conf__pant_inicial()
	{
		$this->ev_info()->set_datos($this->get_datos_ev_info());
	}

	function get_datos_ev_info()
	{
		$datos = toba::consulta_php('co_propuestas')->get_datos_propuesta($this->s__propuesta);
		if (! empty($this->s__plan)) {
			$filtro = "sga_planes.plan = {$this->s__plan}";
			$datos_plan = current(toba::consulta_php('co_planes')->get_planes_nombres($filtro));
			$datos['plan_nombre'] = $datos_plan['descr'];
		}
		return $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$filtro = "sga_propuestas_relacion.propuesta = {$this->s__propuesta}";
		$filtro .= empty($this->s__plan) ? ' AND sga_propuestas_relacion.plan IS NULL' : " AND sga_propuestas_relacion.plan = {$this->s__plan}";
		$datos = guarani::co('co_propuestas_relaciones')->get_listado($filtro);
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