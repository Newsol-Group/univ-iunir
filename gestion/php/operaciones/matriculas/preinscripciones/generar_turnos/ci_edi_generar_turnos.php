<?php
class ci_edi_generar_turnos extends guarani_pers_ci
{
	function conf()
	{
		if ($this->controlador()->es_edicion()) {
			$this->dep('formulario')->set_solo_lectura();
			$this->dep('intervalos')->set_solo_lectura();
			$this->dep('intervalos')->desactivar_agregado_filas();
			$this->dep('form_ras')->set_solo_lectura();
			$this->dep('form_ubicacion')->set_solo_lectura();
            $this->dep('form_modalidad')->set_solo_lectura();
		}
	}
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(formulario $form)
	{
		$datos = $this->cn()->entidad()->get_datos_config();
		if (!empty($datos)) {
			$form->set_datos($datos);
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$this->cn()->entidad()->set_datos_config($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- intervalos -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__intervalos(guarani_pers_ei_formulario_ml $form_ml)
	{
		$datos = $this->cn()->entidad()->get_datos_config_intervalos();
		if (!empty($datos)) {
			$form_ml->set_datos($datos);
		}
	}

	function evt__intervalos__modificacion($datos)
	{
		$this->cn()->entidad()->set_datos_config_intervalos($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- form_ras ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ras(form_ras $form)
	{
		$datos = $this->cn()->entidad()->get_datos_config_ra();
		if (!empty($datos)) {
			$form->set_datos($datos);
		}
	}

	function evt__form_ras__modificacion($datos)
	{
		$this->cn()->entidad()->set_datos_config_ra($datos);
	}
    
	//-----------------------------------------------------------------------------------
	//---- form_ubicacion ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ubicacion(form_ubicacion $form)
	{
		$datos = $this->cn()->entidad()->get_datos_config_ubicacion();
		if (!empty($datos)) {
			$form->set_datos($datos);
		}
	}

	function evt__form_ubicacion__modificacion($datos)
	{
		$this->cn()->entidad()->set_datos_config_ubicacion($datos);
	}

    //-----------------------------------------------------------------------------------
    //---- form_modalidades ---------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__form_modalidad(form_modalidad $form)
    {
        $datos = $this->cn()->entidad()->get_datos_config_modalidad();
        if (!empty($datos)) {
            $form->set_datos($datos);
        }
    }

    function evt__form_modalidad__modificacion($datos)
    {
        $this->cn()->entidad()->set_datos_config_modalidad($datos);
    }
}
?>