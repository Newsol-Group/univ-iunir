<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
class ci_edi_actividad_materia extends guarani_pers_ci
{
	protected $datos_elemento_temp;
	protected $datos_materia_temp;
	
	function conf()
	{
		if(!$this->cn()->entidad_es_nueva() && $this->cn()->entidad()->pertenece_a_plan_activo() ) {
			$this->dep('elemento')->set_solo_lectura('codigo');	
			$this->dep('elemento')->set_solo_lectura('nombre');	
		}
	}

	//---- Form elementos -------------
	
	function evt__elemento__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_elemento($datos);
		} catch ( guarani_error $e ) {
			$this->datos_elemento_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__elemento(toba_ei_formulario $form)
	{
		if (isset($this->datos_elemento_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_elemento_temp);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_elemento());
		}
	}

	//---- Form Inicializacion -------------

	function evt__materia__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_materia($datos);
		} catch ( guarani_error $e ) {
			$this->datos_materia_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__materia(toba_ei_formulario $form)
	{
		if (isset($this->datos_materia_temp)) {
			$form->set_datos($this->datos_materia_temp);		
		} else {
			$form->set_datos( $this->cn()->entidad()->get_datos_materia() );
		}
	}
}

?>