<?php
class ci_edi_responsables_academicas extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		$form->ef('email')->set_expreg(comunes::er_email);
		$form->set_datos_defecto(array('institucion' => guarani_configuracion::instancia()->get_valor_config('institucion_instalacion')));
		if (! $this->cn()->entidad_es_nueva()) {
			$form->set_solo_lectura(array('institucion'));
		}
		parent::conf__formulario($form);
	}

	function evt__formulario__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos($datos);	
		} catch (guarani_error $e) {
			if (isset($datos['localidad'])) {
				$localidad_cp = $datos['localidad'];
				$datos['localidad'] = guarani_texto::extraer_subclave($localidad_cp);
				$datos['codigo_postal'] = guarani_texto::extraer_subclave($localidad_cp, 1);
			}
			$this->datos_temp = $datos;
			throw $e;
		}
	}
}
?>