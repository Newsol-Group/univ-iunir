<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class ci_edi_confirmar_comisiones extends guarani_pers_ci
{
	protected $datos_form_ml_temp;
	
	function get_datos_comisiones_subcomisiones()
	{
		return $this->datos_form_ml_temp;	
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- form_ml_comisiones_subcomisiones ---------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_comisiones_subcomisiones(form_ml_comisiones_subcomisiones $form_ml)
	{
		if (isset($this->datos_form_ml_temp)) {
			$form_ml->set_datos($this->datos_form_ml_temp);
		} else {
			// Recupera los datos de las Comisiones correspondientes a la actividad seleccionada.
			$actividad = $this->controlador()->get_actividad_seleccionada();
			$per_lect = $this->controlador()->get_periodo_lectivo_seleccionado();
			$ubicacion = $this->controlador()->get_ubicacion_seleccionada();
			$datos = $this->cn()->get_listado_comisiones_subcomisiones($actividad, $per_lect, $ubicacion);
            $this->datos_form_ml_temp = $datos;
			$form_ml->set_datos($datos);
		}
	}

	function evt__form_ml_comisiones_subcomisiones__modificacion($datos)
	{
		$this->datos_form_ml_temp = $datos;	
	}

}
?>