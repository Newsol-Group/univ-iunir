<?php
/**
 * 
 * @package Encuestas
 */
class ci_edi_datos_censales extends ci_edicion_entidad
{
	protected $s__datos_personales = [];
	protected $s__domicilio  = [];
	protected $s__financiamiento = [];
	protected $s__datos_laborales = [];
	protected $s__tecnologia = [];
	protected $s__deportes = [];
	protected $s__idiomas = [];
	protected $s__datos_salud = [];
	protected $s__preferencias = [];
	protected $s__discapacidad = [];
		
	function post_configurar()
	{
		if (! $this->cn()->entidad()->es_editable()) {
			$this->inhabilitar_componentes_eventos();
			$this->inhabilitar_eventos_controlador();
		}
	}

	function get_datos_censales()
	{
		return array_merge(	empty($this->s__datos_personales) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(1, 7, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__datos_personales,
							empty($this->s__domicilio) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(9, 26, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__domicilio,
							empty($this->s__financiamiento) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(50, 69, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__financiamiento,
							empty($this->s__datos_laborales) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(70, 78, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__datos_laborales,
							empty($this->s__tecnologia) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(90, 104, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__tecnologia,
							empty($this->s__deportes) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(105, 117, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__deportes,
							empty($this->s__idiomas) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(118, 125, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__idiomas,
							empty($this->s__datos_salud) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(130, 131, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__datos_salud,
							empty($this->s__preferencias) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(135, 157, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__preferencias,
							empty($this->s__discapacidad) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(158, 193, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__discapacidad);
	}

	function existe_dato_censal_obligatorio()
	{
		$datos_censales = $this->get_datos_censales();
		foreach ($datos_censales as $dato) {
			if ($dato['obligatorio'] == comunes::si) {
				return true;
			}
		}
		return false;
	}

	//--------------------------------------------------------------------------
	//---- form_datos_personales -----------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_datos_personales(form_ml_datos_censales $form_ml)
	{
		$datos = empty($this->s__datos_personales) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(1, 7, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__datos_personales;
		$form_ml->set_datos($datos);
		$form_ml->colapsar();
	}

	function evt__form_datos_personales__modificacion($datos)
	{
		$this->s__datos_personales = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_domicilio ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_domicilio(form_ml_datos_censales $form_ml)
	{
		$datos = empty($this->s__domicilio) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(9, 26, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__domicilio;
		$form_ml->set_datos($datos);
		$form_ml->colapsar();
	}

	function evt__form_domicilio__modificacion($datos)
	{
		$this->s__domicilio = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_financiamiento ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_financiamiento(form_ml_datos_censales $form_ml)
	{
		$datos = empty($this->s__financiamiento) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(50, 69, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__financiamiento;
		$form_ml->set_datos($datos);
		$form_ml->colapsar();
	}

	function evt__form_financiamiento__modificacion($datos)
	{
		$this->s__financiamiento = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_datos_laborales ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_datos_laborales(form_ml_datos_censales $form_ml)
	{
		$datos = empty($this->s__datos_laborales) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(70, 78, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__datos_laborales;
		$form_ml->set_datos($datos);
		$form_ml->colapsar();
	}

	function evt__form_datos_laborales__modificacion($datos)
	{
		$this->s__datos_laborales = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_tecnologia --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_tecnologia(form_ml_datos_censales $form_ml)
	{
		$datos = empty($this->s__tecnologia) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(90, 104, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__tecnologia;
		$form_ml->set_datos($datos);
		$form_ml->colapsar();
	}

	function evt__form_tecnologia__modificacion($datos)
	{
		$this->s__tecnologia = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_deportes ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_deportes(form_ml_datos_censales $form_ml)
	{
		$datos = empty($this->s__deportes) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(105, 117, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__deportes;
		$form_ml->set_datos($datos);
		$form_ml->colapsar();
	}

	function evt__form_deportes__modificacion($datos)
	{
		$this->s__deportes = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_idiomas -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_idiomas(form_ml_datos_censales $form_ml)
	{
		$datos = empty($this->s__idiomas) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(118, 125, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__idiomas;
		$form_ml->set_datos($datos);
		$form_ml->colapsar();
	}

	function evt__form_idiomas__modificacion($datos)
	{
		$this->s__idiomas = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_datos_salud -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_datos_salud(form_ml_datos_censales $form_ml)
	{
		$datos = empty($this->s__datos_salud) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(130, 131, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__datos_salud;
		$form_ml->set_datos($datos);
		$form_ml->colapsar();
	}

	function evt__form_datos_salud__modificacion($datos)
	{
		$this->s__datos_salud = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- form_preferencias ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_preferencias(form_ml_datos_censales $form_ml)
	{
		$datos = empty($this->s__preferencias) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(135, 157, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__preferencias;
		$form_ml->set_datos($datos);
		$form_ml->colapsar();
	}

	function evt__form_preferencias__modificacion($datos)
	{
		$this->s__preferencias = $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_discapacidad ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_discapacidad(form_ml_datos_censales $form_ml)
	{
		$datos = empty($this->s__discapacidad) ? toba::consulta_php('co_encuestas')->get_datos_censales_config(158, 193, $this->cn()->entidad()->get_columna_habilitacion('habilitacion')) : $this->s__discapacidad;
		$form_ml->set_datos($datos);
		$form_ml->colapsar();
	}

	function evt__form_discapacidad__modificacion($datos)
	{
		$this->s__discapacidad = $datos;
	}

	function resetear_variables()
	{
		unset($this->s__datos_personales);
		unset($this->s__domicilio);
		unset($this->s__financiamiento);
		unset($this->s__datos_laborales);
		unset($this->s__tecnologia);
		unset($this->s__deportes);
		unset($this->s__idiomas);
		unset($this->s__datos_salud);
		unset($this->s__preferencias);
		unset($this->s__discapacidad);
	}
}