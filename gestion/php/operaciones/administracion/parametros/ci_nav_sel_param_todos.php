<?php
/**
 * 	
 *
 * @package		Administración
 * @subpackage 	Parámetros
 * 
 */
class ci_nav_sel_param_todos extends ci_nav_parametros
{
	function conf__pant_todos(guarani_ei_pantalla $pantalla)
	{
		$this->controlador()->set_pantalla_retorno('pant_todos');
	}
	
	function conf__pant_operacion(guarani_ei_pantalla $pantalla)
	{
		$this->controlador()->set_pantalla_retorno('pant_operacion');
	}
	
	function conf__pant_modulo(guarani_ei_pantalla $pantalla)
	{
		$this->controlador()->set_pantalla_retorno('pant_modulo');
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->controlador()->evt__cuadro__seleccion($seleccion);
	}

}
?>