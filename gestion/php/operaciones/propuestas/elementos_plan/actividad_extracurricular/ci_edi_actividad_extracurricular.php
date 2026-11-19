<?php 
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class ci_edi_actividad_extracurricular extends guarani_pers_ci
{
	function conf()
	{
	    if( ! $this->cn()->entidad_es_nueva() && $this->cn()->entidad()->pertenece_a_plan_activo() ) {
			$this->dep('elemento')->set_solo_lectura('codigo');	
			$this->dep('elemento')->set_solo_lectura('nombre');	
		}
	}

	function evt__elemento__modificacion($datos)
	{
	    $this->cn()->entidad()->set_datos_elemento($datos);
	}

	function conf__elemento(toba_ei_formulario $form)
	{
		$form->set_datos( $this->cn()->entidad()->get_datos_elemento() );
	}

	//---- extracurricular --------------------------------------------------------------

	function evt__extracurricular__modificacion($datos)
	{
		$this->cn()->entidad()->set_datos($datos);
	}
	
	function conf__extracurricular(toba_ei_formulario $form)
	{
		$form->set_datos( $this->cn()->entidad()->get_datos() );
	}
}

?>