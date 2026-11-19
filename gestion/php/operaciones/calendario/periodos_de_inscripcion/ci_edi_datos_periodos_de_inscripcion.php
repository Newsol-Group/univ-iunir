<?php
class ci_edi_datos_periodos_de_inscripcion extends guarani_pers_ci
{
	protected $datos_princ_temp;
	protected $datos_ubicacion_temp;
	protected $seleccion_modalidades_temp;
	
	//Filas agregadas por defecto.
	protected $s__modalidades_defecto_agregadas = false;
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA DATOS PRINCIPALES ---------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- Configuraciones --------------------------------------------------------------
	
	function conf__datos_principales(toba_ei_pantalla $pantalla)
	{
		$this->dep('seleccion_modalidades')->set_columna_ordenamiento('modalidad_nombre');
	}
	
	//---- form_datos_princ -------------------------------------------------------------
	
	function conf__form_datos_princ(toba_ei_formulario $form)
	{
		if (isset($this->datos_princ_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_princ_temp);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos());
			$form->set_datos($this->cn()->entidad()->get_datos_per_inscr_prop());
		}
	}

	function evt__form_datos_princ__modificacion($datos)
	{
		try {			
			$this->cn()->entidad()->set_datos($datos);
			$this->cn()->entidad()->set_datos_per_inscr_prop($datos);
		} catch ( guarani_error $e ) {
			$this->datos_princ_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	//---- seleccion_modalidades --------------------------------------------------------
	
	function conf__seleccion_modalidades(form_ml_requerido $form_ml)
	{
		if (isset($this->seleccion_modalidades_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->seleccion_modalidades_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_per_inscr_modalidad();
		}
		
		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_modalidad_cursada', 'modalidad', 'modalidad', true);
		$datos_posibles = toba::consulta_php('co_planes')->get_modalidades($filtro);
		$form_ml->set_datos($datos);

		//Por defecto deberán aparecer seleccionadas todas las modalidades.
		if ($this->cn()->entidad_es_nueva() && !$this->s__modalidades_defecto_agregadas) {
			$this->s__modalidades_defecto_agregadas = true;
			foreach ($datos_posibles as $dato) {
				$dato['requerido'] = 1;
				$form_ml->agregar_registro($dato);
			}
		} else {
			$form_ml->set_datos_posibles($datos_posibles);
		}
	}

	function evt__seleccion_modalidades__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_per_inscr_modalidad($datos);
		} catch ( guarani_error $e ) {
			$this->seleccion_modalidades_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
}
?>