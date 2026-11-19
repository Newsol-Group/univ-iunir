<?php
/**
 * 
 * @package Encuestas
 */
class ci_edi_modificar_vigencia_encuestas extends guarani_pers_ci
{
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(form_modificar_vigencia_encuestas $form)
	{
		if (isset($this->datos_temp_habilitacion)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_habilitacion);
		} else {
			$datos = $this->cn()->entidad()->get_datos();
			if (guarani_fecha::es_menor($datos['fecha_desde'], guarani_fecha::get_hoy())) {
				$form->set_solo_lectura(array('fecha_desde'));
			}
			$form->set_datos($datos);
		}
	}

	function evt__formulario__modificacion($datos)
	{
		try {
			if (guarani_fecha::es_menor($datos['fecha_desde'], guarani_fecha::get_hoy())) {
				unset($datos['fecha_desde']);
			}
			$datos['kolla_sincronizado'] = comunes::no;
			$this->cn()->entidad()->set_datos($datos);	
		} catch (guarani_error $e) {
			$this->datos_temp_habilitacion = $datos;
			throw $e;
		}
	}
}
?>