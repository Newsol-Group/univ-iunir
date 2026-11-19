<?php
class ci_edi_datos_personales extends guarani_pers_ci
{
	protected $datos_personales_temp;
	
	function evt__formulario__modificacion($datos)
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

	function conf__formulario(toba_ei_formulario $form)
	{
		if (isset($this->datos_personales_temp)) {
			$this->controlador->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_personales_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_datos_personales();
			$form->set_datos($datos);
		}
	}
}
?>