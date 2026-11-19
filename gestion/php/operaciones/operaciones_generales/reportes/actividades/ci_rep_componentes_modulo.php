<?php
class ci_rep_componentes_modulo extends guarani_pers_ci
{
	protected $s__plan_version;
	protected $s__id;
	
	function ini()
	{
		parent::ini();
		$this->s__plan_version = toba::memoria()->get_parametro('plan_version');
		$this->s__id = toba::memoria()->get_parametro('id');
	}
	
	function conf()
	{
		$nombre = guarani::co('co_elementos')->get_nombre_modulo($this->s__id);
		$this->set_titulo('ACTIVIDADES DEL MÓDULO: ' . $nombre);
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$datos = guarani::co('co_planes')->get_detalles_componentes_modulos($this->s__id, $this->s__plan_version);
		$cuadro->set_datos($datos);
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		echo "
		{$this->objeto_js}.evt__cancelar = function()
		{
			window.close();
		}
		";
	}

}
?>