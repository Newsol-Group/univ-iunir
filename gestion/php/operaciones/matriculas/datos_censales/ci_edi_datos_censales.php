<?php
/**
 *
 * 
 * @package		Matricula
 * @subpackage	Datos Censales
 * 
 */
class ci_edi_datos_censales extends guarani_pers_ci
{
	protected $datos_personales_temp;
	protected $datos_economicos_temp;
	protected $datos_actividades_temp;

	/**
	 * Se modifica el comportamiento del ci de navegación (seteando no volver al 
	 * guardar), para que cuando el usuario presione el botón Guardar se permanezca 
	 * en la pantalla de edición.
	 */
	function ini()
	{
		parent::ini();
		$this->controlador()->set_no_volver_al_guardar();
	}

	//--------------------------------------------------------------------------
	//---- Form datos personales -----------------------------------------------
	//--------------------------------------------------------------------------

	function evt__form_datos_pers__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_datos_personales($datos);
		} catch (guarani_error $e) {
			if (isset($datos['periodo_lectivo_localidad'])) {
				$localidad_cp = $datos['periodo_lectivo_localidad'];
				$datos['periodo_lectivo_localidad'] = guarani_texto::extraer_subclave($localidad_cp);
				$datos['periodo_lectivo_codigo_postal'] = guarani_texto::extraer_subclave($localidad_cp, 1);
			}
			if (isset($datos['procedencia_localidad'])) {
				$p_localidad_cp = $datos['procedencia_localidad'];
				$datos['procedencia_localidad'] = guarani_texto::extraer_subclave($p_localidad_cp);
				$datos['procedencia_codigo_postal'] = guarani_texto::extraer_subclave($p_localidad_cp, 1);
			}
			$this->datos_personales_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__form_datos_pers(toba_ei_formulario $form)
	{
		if (isset($this->datos_personales_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_personales_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_datos_personales();
			$form->set_datos($datos);
		}
	}

	//--------------------------------------------------------------------------
	//---- Form datos economicos -----------------------------------------------
	//--------------------------------------------------------------------------

	function evt__form_datos_econom__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_datos_economicos($datos);
		} catch (guarani_error $e) {
			$this->datos_economicos_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__form_datos_econom(toba_ei_formulario $form)
	{
		if (isset($this->datos_economicos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_economicos_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_datos_economicos();
			$form->set_datos($datos);
		}
	}

	//--------------------------------------------------------------------------
	//---- Form datos actividades ----------------------------------------------
	//--------------------------------------------------------------------------

	function evt__form_datos_activ__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_datos_actividades($datos);
		} catch (guarani_error $e) {
			$this->datos_actividades_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__form_datos_activ(toba_ei_formulario $form)
	{
		if (isset($this->datos_actividades_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_actividades_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_datos_actividades();
			$form->set_datos($datos);
		}
	}

}
?>