<?php

/**
 * 
 * 
 * @package 	Matr�cula
 * @subpackage 	Alumnos
 *
 */
class ci_nav_datos_facturacion_nsg extends ci_nav_sin_listado_inicial
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


	/**
	 * 12/09/2025
	 * Newsol - Joaquin
	 * Interacción con la interfaz segun datos de facturación cargados.
	 */

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$js_content = file_get_contents(toba::proyecto()->get_path() . 
			'/personalizacion/php/operaciones/facturacion/datos_facturacion/datos_facturacion.js');
		echo $js_content;
	}
}
