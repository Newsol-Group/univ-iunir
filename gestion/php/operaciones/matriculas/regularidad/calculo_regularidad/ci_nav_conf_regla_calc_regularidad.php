<?php
/**
 * 
 * 
 * @package		Matricula
 * @subpackage	Regularidad
 * 
 */
class ci_nav_conf_regla_calc_regularidad extends ci_navegacion
{

	function get_seleccion()
	{
		return $this->s__seleccion;
	}
	
	function evt__cancelar()
	{
		$this->dep('editor')->evt__filtro_propuestas__cancelar();
		parent::evt__cancelar();
	}
}
?>