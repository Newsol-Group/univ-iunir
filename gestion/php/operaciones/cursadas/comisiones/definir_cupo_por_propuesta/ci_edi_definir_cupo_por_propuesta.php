<?php
/**
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class ci_edi_definir_cupo_por_propuesta extends guarani_pers_ci
{
	protected $datos_form_ml_propuestas;
	
	function get_datos_propuestas_cupo()
	{
		return $this->datos_form_ml_propuestas;	
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- form_ml_propuestas -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_propuestas(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_form_ml_propuestas)) {
			$datos = $this->datos_form_ml_propuestas;
		} else {
			$comision = $this->controlador()->get_comision_seleccionada();
			$datos = toba::consulta_php('co_comisiones')->get_propuestas_cupo_comision($comision);
		}
		$form_ml->set_datos($datos);
	}

	function evt__form_ml_propuestas__modificacion($datos)
	{
		$this->datos_form_ml_propuestas = $datos;	
	}
}
?>