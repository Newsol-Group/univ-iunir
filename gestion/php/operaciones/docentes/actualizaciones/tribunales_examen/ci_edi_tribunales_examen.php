<?php
/**
 * @package Docentes
 * @subpackage Actualizaciones
 */
class ci_edi_tribunales_examen extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__informacion(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_tribunal_examen();
			$form->set_datos($datos);
		}
	}
	
	function evt__informacion__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_tribunal_examen($datos);	
		} catch ( guarani_error $e ) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}
}
?>