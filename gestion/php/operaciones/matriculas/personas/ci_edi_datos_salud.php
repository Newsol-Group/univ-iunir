<?php
class ci_edi_datos_salud extends guarani_pers_ci
{
	//--------------------------------------------------------------------------
	//---- form_salud ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_salud(guarani_pers_ei_formulario $form)
	{
		$datos = $this->cn()->entidad()->get_datos_datos_personales();
		if (!empty($datos)) {
			$form->set_datos($datos);
		}
	}

	function evt__form_salud__modificacion($datos)
	{
		$this->cn()->entidad()->set_datos_datos_personales($datos);
	}

}