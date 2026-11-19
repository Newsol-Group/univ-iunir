<?php
class ci_edi_excepciones_vigencia_regularidad extends ci_edicion_simple
{
	
	protected $s__temp_actividad;    
	protected $s__planes_defecto_agregadas = false;
	protected $seleccion_planes_temp;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{    
		if (empty($this->cn()->entidad()->get_datos_excepcion())) {
			$pantalla->eliminar_dep('formulario');
			$pantalla->eliminar_dep('seleccion_planes');
		} else {
			$this->dep('form_actividad')->set_solo_lectura();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_actividad ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_actividad(form_actividad $form)
	{
		if (isset($this->s__temp_actividad)) {
			$this->controlador->set_hubo_cambios();            
			$form->set_datos($this->s__temp_actividad);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_excepcion());
		}        
	}

	function evt__form_actividad__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_excepcion($datos);
		} catch (guarani_error $e) {
			$this->s__temp_actividad = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	function conf__formulario(toba_ei_formulario $form)
	{
		if(isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);        
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_excepcion());
		}
	}

	function evt__formulario__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_excepcion($datos);    
		} catch ( guarani_error $e ) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}
	
	function conf__seleccion_planes(form_ml_requerido $form_ml)
	{
		$form_ml->set_columna_ordenamiento('propuesta_nombre');        
				
		$datos_posibles = $this->cn()->get_planes_excepcion();
		if ($this->cn()->entidad_es_nueva()) {                
			foreach ($datos_posibles as $dato) {                    
				$form_ml->agregar_registro($dato);
			}
		} else {
			/* 
				* Tengo que ver cuales son los que tienen q venir marcado.
				* Tomo los datos del dt para verificar cuales se habían seleccionado anteriormente.
				*/
			$marcados = aplanar_matriz($this->cn()->entidad()->get_planes(), 'plan');
			foreach(array_keys($datos_posibles) as $key) {
				if (in_array($datos_posibles[$key]['plan'], $marcados)) {
					$datos_posibles[$key]['requerido'] = 1;
				}
			}                
			$form_ml->set_datos_posibles($datos_posibles);
		}        
	}

	function evt__seleccion_planes__modificacion($datos)
	{
		$this->cn()->entidad()->set_planes($datos);
	}

}
?>