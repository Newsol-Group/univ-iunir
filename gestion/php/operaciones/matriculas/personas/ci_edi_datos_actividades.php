<?php
class ci_edi_datos_actividades extends guarani_pers_ci
{
	protected $datos_actividades_temp;
	
	function evt__formulario__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_datos_actividades($datos);
		} catch (guarani_error $e) {
			$this->datos_actividades_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos_actividades_temp)) {
			$this->controlador->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_actividades_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_datos_actividades();
			$form->set_datos($datos);
		}
	}
}

?>