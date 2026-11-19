<?php
class ci_edi_datos_per_reporte extends guarani_pers_ci
{
	function get_persona_seleccionada()
	{
		return $this->controlador()->get_persona_seleccionada();
	}
	
	//---- form_datos_basicos -----------------------------------------------------------
	
	function conf__form_datos_basicos(toba_ei_formulario $form)
	{
		$persona = $this->get_persona_seleccionada();
		$datos = toba::consulta_php('co_personas')->get_datos_persona($persona);
		$form->set_datos($datos);
		$form->set_solo_lectura();
	}

	//---- cuadro_documentos ------------------------------------------------------------
	
	function conf__cuadro_documentos(toba_ei_cuadro $cuadro)
	{
		$persona = $this->get_persona_seleccionada();
		$datos = toba::consulta_php('co_personas')->get_documentos_sec_persona($persona);
		$cuadro->set_datos($datos);
		
	}

	//---- cuadro_contactos -------------------------------------------------------------
	
	function conf__cuadro_contactos(toba_ei_cuadro $cuadro)
	{
		$persona = $this->get_persona_seleccionada();
		$datos = toba::consulta_php('co_personas')->get_contactos_persona($persona);
		$cuadro->set_datos($datos);
	}

	//---- cuadro_allegados -------------------------------------------------------------
	
	function conf__cuadro_allegados(toba_ei_cuadro $cuadro)
	{
		$persona = $this->get_persona_seleccionada();
		$datos = toba::consulta_php('co_personas')->get_allegados_persona($persona);
		$cuadro->set_datos($datos);
	}

	//---- cuadro_estudios --------------------------------------------------------------
	
	function conf__cuadro_estudios(toba_ei_cuadro $cuadro)
	{
		$persona = $this->get_persona_seleccionada();
		$datos = toba::consulta_php('co_personas')->get_estudios_persona($persona);
		$cuadro->set_datos($datos);
	}

	//---- cuadro_antecedentes ----------------------------------------------------------
	
	function conf__cuadro_antecedentes(toba_ei_cuadro $cuadro)
	{
		$persona = $this->get_persona_seleccionada();
		$datos = toba::consulta_php('co_personas')->get_antecedentes_persona($persona);
		$cuadro->set_datos($datos);
	}

}
?>