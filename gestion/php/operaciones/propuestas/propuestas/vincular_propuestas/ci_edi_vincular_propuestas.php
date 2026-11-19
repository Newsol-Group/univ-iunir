<?php
/**
 * 
 * @package Propuestas Formativas
 */
class ci_edi_vincular_propuestas extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- form_datos_grupo -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_datos_grupo(form_datos_grupo $form)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);		
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_grupo_seleccionado());
		}
	}

	function evt__form_datos_grupo__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_grupo($datos);	
		} catch (guarani_error $e) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}
}
?>