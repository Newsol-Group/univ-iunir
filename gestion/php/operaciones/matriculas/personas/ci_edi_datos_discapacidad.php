<?php
class ci_edi_datos_discapacidad extends guarani_pers_ci
{
	const chars_maximo_radio = 200;
	protected $datos_discapacidad_temp;

	function evt__formulario__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_discapacidad($datos);
		} catch (guarani_error $e) {
			$this->datos_discapacidad_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__formulario(toba_ei_formulario $form)
	{
		$form->ef('condicion_discapacidad')->set_maximo_descripcion(static::chars_maximo_radio);
		if (isset($this->datos_discapacidad_temp)) {
			$this->controlador->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_discapacidad_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_discapacidad();
			$form->set_datos($datos);
		}
	}
}