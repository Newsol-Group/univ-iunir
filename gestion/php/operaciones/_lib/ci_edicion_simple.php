<?php

class ci_edicion_simple extends ci_edicion_entidad
{
	protected $datos_temp;
	
	function conf__formulario(toba_ei_formulario $form)
	{
		if(isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);		
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos());
		}
	}

	function evt__formulario__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos($datos);	
		} catch ( guarani_error $e ) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}

	
}
?>