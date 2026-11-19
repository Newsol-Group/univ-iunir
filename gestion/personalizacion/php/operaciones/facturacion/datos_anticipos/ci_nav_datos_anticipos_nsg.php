<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Alumnos
 *
 */
class ci_nav_datos_anticipos_nsg extends ci_nav_sin_listado_inicial
{
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->cn()->cargar(array('alumno' => $seleccion['alumno']));
		$this->set_pantalla('edicion');
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_alumno_seleccionado()
	{
		return $this->s__seleccion;
	}

}
?>