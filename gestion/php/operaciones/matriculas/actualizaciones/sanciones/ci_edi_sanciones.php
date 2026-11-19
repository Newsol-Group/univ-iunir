<?php
class ci_edi_sanciones extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if ($this->cn()->entidad_es_nueva()) {
			$this->dep('formulario')->set_solo_lectura(array('documento_cancelacion','fecha_cancelacion', 'observaciones_cancelacion'));
		}
		parent::conf__formulario($form);
	}
}
?>