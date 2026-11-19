<?php
namespace siu\operaciones\datos_censales\_comun;

use SIU\Chulupi\kernel;
use siu\operaciones\datos_censales\_comun\util_form_censo;
use siu\modelo\transacciones\becas;

abstract class pagelet_con_form_dc extends pagelet_con_mensajes
{	
	const FORM_ERROR_VALIDACION = 'form_error_validacion';
	const FORM_GUARDADO = 'form_guardado';
	
	protected $form;
	
	function get_nombre_seccion()
	{
		return 'datos_censales.form.'.$this->get_nombre();
	}
	
	function marcar_faltantes()
	{
		$campos_faltantes = $this->controlador->get_campos_faltantes();
		if (isset ($campos_faltantes[$this->get_nombre()])) {
			$campos_pagelet = $campos_faltantes[$this->get_nombre()];
			$form = $this->form;
			foreach ($campos_pagelet as $campo) {
				$campo_descr = util_form_censo::get_campo_descr($campo);
				$obligatorio_becas = util_form_censo::obligatorio_becas($campo) || util_form_censo::obligatorio_reapertura($campo);
				$mensaje_obligatorio = ($obligatorio_becas)? 'convocatorias_becas.campo_obligatorio' : 'form_validacion_error.vacio_impr';
				if (!empty($campo_descr)) {
					// El campo es un ID interno y tiene un campo descriptivo
					$form->agregar_error($campo_descr, kernel::traductor()->trans($mensaje_obligatorio));
				} else {
					// Se agrega el error directamente al campo
					$form->agregar_error($campo, kernel::traductor()->trans($mensaje_obligatorio));
				}
			}
		}
	}
	
	function hay_campos_faltantes()
	{
		return $this->controlador->hay_datos_faltantes();
	}

	function hay_datos_faltantes_convocatoria()
	{
		return $this->controlador->hay_datos_faltantes_convocatoria();
	}

	function viene_de_convocatorias_becas()
	{
            return becas::viene_de_convocatorias_becas();
	}
	
	function get_url_siguiente_paso()
	{
            return becas::get_url_siguiente_paso();
	}
}