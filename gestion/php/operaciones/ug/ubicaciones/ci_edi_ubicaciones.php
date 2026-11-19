<?php
class ci_edi_ubicaciones extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	const chars_maximo_combo = 200;
	
	function conf()
	{
		toba_ef_combo::set_maximo_descripcion(self::chars_maximo_combo);
	}

	//---- formulario -------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		$form->ef('email')->set_expreg(comunes::er_email);
		parent::conf__formulario($form);
	}

	function evt__formulario__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos($datos);	
		} catch ( guarani_error $e ) {
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