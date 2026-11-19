<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage
 * 
 */
class ci_nav_grupos_destinatarios extends ci_navegacion
{
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		parent::evt__cancelar();
		$this->dep('editor')->dep('personas_disponibles')->resetear_variables();
	}

	function evt__eliminar()
	{
		parent::evt__eliminar();
		$this->dep('editor')->dep('personas_disponibles')->resetear_variables();
	}

	function evt__guardar()
	{
		parent::evt__guardar();
		$this->dep('editor')->dep('personas_disponibles')->resetear_variables();
	}
}
?>