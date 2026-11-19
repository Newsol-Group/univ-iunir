<?php
/**
 *
 *
 * @package 	Comunicaciones
 * @subpackage 	-
 * 
 */
class ci_edi_errores extends guarani_pers_ci
{
	//-----------------------------------------------------------------------------------
	//---- form_errores -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_errores(toba_ei_formulario $form)
	{
		$datos = $this->cn()->entidad()->get_datos();
		$form->set_datos($datos);
	}

}
?>