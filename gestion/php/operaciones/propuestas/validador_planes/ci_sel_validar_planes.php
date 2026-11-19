<?php 
class ci_sel_validar_planes extends guarani_pers_ci
{
	//------ formulario ------------------

	function conf__formulario(toba_ei_formulario $form) 
	{
		$datos = $this->cn()->get_datos();
		if (! empty($datos)) {
			$form->set_datos($datos);
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$this->cn()->set_datos($datos);
	}

	//------ Eventos ---------------------

	function evt__accion() 
	{
		$this->cn()->validar();
		$this->set_pantalla('resultado');
	}

	function evt__volver()
	{
		$this->cn()->limpiar_memoria();
		$this->set_pantalla('seleccion');
	}

	//------ Auxiliares ------------------

	function get_certificados($plan_version)
	{
		return $this->cn()->get_listado_certificados($plan_version);
	}

	function get_casos_testeo($plan_version)
	{
		return $this->cn()->get_listado_casos_testeo($plan_version);
	}
}
?>