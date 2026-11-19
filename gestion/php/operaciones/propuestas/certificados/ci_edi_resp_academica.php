<?php
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Certificados
 * 
 */
class ci_edi_resp_academica extends guarani_pers_ci
{
	protected $datos_temp;
	
	//-----------------------------------------------------------------------------------
	//---- responsables_academicas ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__responsables_academicas(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp)) {
			$this->controlador()->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_temp);
		} else {
			$datos = aplanar_matriz($this->cn()->entidad()->get_datos_ra(), 'responsable_academica');
			$form->set_datos(array('responsable_academica' => $datos));
		}
	}

	function evt__responsables_academicas__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_ra($datos['responsable_academica']);
		} catch (guarani_error $e) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}

}
?>