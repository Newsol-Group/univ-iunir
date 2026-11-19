<?php
class ci_edi_condiciones_regularidad extends ci_edicion_simple 
{
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		parent::conf__formulario($form);
		
		if ($this->cn()->entidad()->es_nueva()) {
			$form->ef('es_condicion_defecto')->set_estado(comunes::no);
		}
	}

}
?>