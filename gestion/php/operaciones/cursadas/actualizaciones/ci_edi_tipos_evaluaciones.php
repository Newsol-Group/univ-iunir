<?php
class ci_edi_tipos_evaluaciones extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Por defecto, se setea el campo sga_evaluaciones_tipos.automatica = N
	 *
	 */
	function evt__formulario__modificacion($datos)
	{
		$datos['automatica'] = comunes::no;
		parent::evt__formulario__modificacion($datos);
	}

}

?>