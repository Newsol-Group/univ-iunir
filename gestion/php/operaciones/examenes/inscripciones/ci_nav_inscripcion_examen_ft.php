<?php
class ci_nav_inscripcion_examen_ft extends ci_nav_inscripcion_examen
{
	function conf()
	{
		$this->cn()->set_fuera_de_termino();
		parent::conf();
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA FINALIZAR
	//--------------------------------------------------------------------------

	function conf__finalizar()
	{
		parent::conf__finalizar();
		if ($this->cn()->get_estado() != 'NUEVA') {
			$this->dep('form_autorizacion')->set_solo_lectura();
		}
	}

	//--------------------------------------------------------------------------
	//---- formulario_autorizacion ---------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_autorizacion(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp['autorizacion'])) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp['autorizacion']);
		} else {
			$datos['autorizacion'] = $this->cn()->get_autorizacion();
			$form->set_datos($datos);
		}
	}

	function evt__form_autorizacion__modificacion($datos)
	{
		try {
			$this->cn()->set_autorizacion($datos['autorizacion']);
		} catch (guarani_error $e) {
			$this->datos_temp['autorizacion'] = $datos;
			throw $e;
		}
	}

}
?>