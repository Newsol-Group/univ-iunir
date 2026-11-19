<?php
/**
 *
 * 
 * @package 	Matrícula
 * @subpackage 	Competencias
 * 
 */
class ci_nav_asignacion extends ci_navegacion 
{
	
	/**
	 * Retorna la asignación seleccionada.
	 *
	 */
	function get_seleccion()
	{
		return $this->s__seleccion;
	}

}

?>