<?php
class ci_edi_datos_economicos extends guarani_pers_ci
{
	protected $datos_economicos_temp;

	function evt__formulario__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_datos_economicos($datos);
		} catch (guarani_error $e) {
			$this->datos_economicos_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos_economicos_temp)) {
			$this->controlador->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_economicos_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_datos_economicos();
			$form->set_datos($datos);
		}
	}
}

?>