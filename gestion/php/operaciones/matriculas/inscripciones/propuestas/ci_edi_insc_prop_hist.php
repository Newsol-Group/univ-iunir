<?php
class ci_edi_insc_prop_hist extends guarani_pers_ci
{
	protected $s__seleccion;
	protected $datos_prop;
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
	}

	function post_eventos()
	{
	}

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$pantalla->eliminar_dep('form_info_periodos');
		$pantalla->eliminar_dep('form_info_gral');		
	}	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$persona = toba::memoria()->get_dato('persona');
		$condicion = " sga_propuestas_aspira.persona = $persona ";
		$datos = toba::consulta_php('co_inscripciones')->get_inscriptos($condicion);
		$cuadro->set_datos($datos);	
	}

	function evt__cuadro__seleccion($seleccion)
	{		
		$this->s__seleccion = $seleccion;

		$persona = toba::memoria()->get_dato('persona');
		$propuesta = $this->s__seleccion['propuesta'];
		$condicion = " sga_propuestas_aspira.persona = $persona AND sga_propuestas_aspira.propuesta = $propuesta ";
		$this->datos_prop = current(toba::consulta_php('co_inscripciones')->get_inscriptos($condicion));
		
		$formateo = new toba_formateo('html');		
		$this->datos_prop['fecha_inicio'] = $formateo->formato_fecha($this->datos_prop['fecha_inicio']);
		$this->datos_prop['fecha_fin'] = $formateo->formato_fecha($this->datos_prop['fecha_fin']);
		$this->datos_prop['fecha_cierre_requisitos'] = $formateo->formato_fecha($this->datos_prop['fecha_cierre_requisitos']);
		
		$this->pantalla()->agregar_dep('form_info_gral');
		$this->pantalla()->agregar_dep('form_info_periodos');		
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_info_basica -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_info_basica(toba_ei_formulario $form)
	{
		$persona = toba::memoria()->get_dato('persona');
		$datos = toba::consulta_php('co_personas')->get_datos_persona($persona);
		$form->set_datos($datos);		
	}

	//-----------------------------------------------------------------------------------
	//---- form_info_gral ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_info_gral(toba_ei_formulario $form)
	{
		$form->set_datos($this->datos_prop);
	}

	//-----------------------------------------------------------------------------------
	//---- form_info_gral ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_info_periodos(toba_ei_formulario $form)
	{
		$form->set_datos($this->datos_prop);
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__cancelar = function()
		{
			window.close();
		}
		";
	}

}

?>