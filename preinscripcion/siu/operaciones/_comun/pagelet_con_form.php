<?php

namespace siu\operaciones\_comun;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;
use siu\operaciones\_comun\preinscripcion_form;

abstract class pagelet_con_form extends pagelet
{
	const FORM_CAMBIOS_DESDE_IMPRESION = 'form_cambios_desde_impresion';
	const FORM_ERROR_VALIDACION = 'form_error_validacion';
	const FORM_GUARDADO = 'form_guardado';

	protected $form;

	function marcar_faltantes()
	{
		$campos_faltantes = $this->controlador->vista()->get_campos_faltantes();
		if (isset($campos_faltantes[$this->get_nombre()])) {
			$campos_pagelet = $campos_faltantes[$this->get_nombre()];
			$form = $this->form;
			foreach ($campos_pagelet as $campo) {
				$campo_descr = preinscripcion_form::get_campo_descr($campo);
				if (!empty($campo_descr)) {
					// El campo es un ID interno y tiene un campo descriptivo
					$form->agregar_error($campo_descr, kernel::traductor()->trans('form_validacion_error.vacio_impr'));
				} else {
					// Se agrega el error directamente al campo
					$form->agregar_error($campo, kernel::traductor()->trans('form_validacion_error.vacio_impr'));
				}
			}
		}
	}

}