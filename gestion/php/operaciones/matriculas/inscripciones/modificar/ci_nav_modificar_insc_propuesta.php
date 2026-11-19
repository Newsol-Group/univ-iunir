<?php
/**
 *
 * 
 *
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 * 
 */
class ci_nav_modificar_insc_propuesta extends ci_nav_sin_listado_inicial 
{
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->cn()->cargar(array('propuesta_aspira' => $seleccion['propuesta_aspira']));
		$this->set_pantalla('edicion');
	}
	
	function get_seleccion()
	{
		return $this->s__seleccion;
	}
}
?>