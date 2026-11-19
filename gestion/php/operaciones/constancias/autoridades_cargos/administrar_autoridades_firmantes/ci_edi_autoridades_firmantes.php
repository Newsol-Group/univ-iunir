<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Autoridades Firmantes
 * 
 */
class ci_edi_autoridades_firmantes extends guarani_pers_ci
{
	protected $datos_temp;
	protected $datos_temp_ras;

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);		
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos());
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$this->datos_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_responsables_academicas -------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_responsables_academicas(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp_ras)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_ras);
		} else {
			$datos = aplanar_matriz($this->cn()->entidad()->get_datos_responsables_academicas(), 'responsable_academica');
			$form->set_datos(array('responsable_academica' => $datos));
		}
	}

	function evt__form_responsables_academicas__modificacion($datos)
	{
		$this->datos_temp_ras = $datos;
	}

	function post_eventos()
	{
		try {
			if (isset($this->datos_temp)) {
				$this->cn()->entidad()->set_datos($this->datos_temp);
				unset($this->datos_temp);
			}
			if (isset($this->datos_temp_ras)) {
				$this->cn()->entidad()->set_datos_responsables_academicas($this->datos_temp_ras['responsable_academica']);
				unset($this->datos_temp_ras);
			}
		} catch (guarani_error $e) {
			$this->resetear_cambio_pantalla();
			throw $e;
		}
	}
}
?>
