<?php
class ci_edi_elecciones extends guarani_pers_ci
{
	protected $datos_eleccion_institucion;
	protected $datos_eleccion_propuesta;
	
	//--------------------------------------------------------------------------
	//---- form_eleccion_institucion ------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_eleccion_institucion(form_eleccion_institucion $form)
	{
		if (isset($this->datos_eleccion_institucion)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_eleccion_institucion);
		} else {
			$datos = $this->cn()->entidad()->get_datos_eleccion_institucion();
			$form->set_datos($datos);
		}
	}

	function evt__form_eleccion_institucion__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_eleccion_institucion($datos);
		} catch (guarani_error $e) {
			$this->datos_eleccion_institucion = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	//--------------------------------------------------------------------------
	//---- form_eleccion_propuesta ------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_eleccion_propuesta(form_eleccion_propuesta $form)
	{
		if (isset($this->datos_eleccion_propuesta)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_eleccion_propuesta);
		} else {
			$datos = $this->cn()->entidad()->get_datos_eleccion_propuesta();
			$form->set_datos($datos);
		}
	}

	function evt__form_eleccion_propuesta__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_eleccion_propuesta($datos);
		} catch (guarani_error $e) {
			$this->datos_eleccion_propuesta = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

}
?>