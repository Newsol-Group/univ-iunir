<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Acciones
 */
class ci_edi_acciones extends guarani_pers_ci
{	
	protected $datos_acc_temp;

	//---- PANTALLA DE DATOS PRINCIPALES -----------------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- form_acciones ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__form_acciones__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_acciones($datos);
		} catch ( guarani_error $e ) {
			$this->datos_acc_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__form_acciones(toba_ei_formulario $form)
	{
		if(isset($this->datos_acc_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_acc_temp);		
		} else {
			$datos = $this->cn()->entidad()->get_datos_acciones();
			$form->set_datos($datos);
		}
	}	
	
	

		
		
}

?>