<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_edi_anular_tramite_certificacion extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_tramite());
		}
	}

	function evt__formulario__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_tramite($datos);
		} catch (guarani_error $e) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}
}
?>