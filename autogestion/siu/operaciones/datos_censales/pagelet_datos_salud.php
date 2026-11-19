<?php
namespace siu\operaciones\datos_censales;

use siu\operaciones\datos_censales\_comun\pagelet_con_edicion;
use SIU\Chulupi\kernel;
use siu\operaciones\datos_censales\_comun\util_form_censo;

class pagelet_datos_salud extends pagelet_con_edicion
{
	const FORM_ERROR_VALIDACION = 'form_error_validacion';
	const FORM_GUARDADO = 'form_guardado';

	protected $form;

	function get_nombre_seccion()
	{
		return 'datos_censales.form.' . $this->get_nombre();
	}

	function marcar_faltantes()
	{
		$campos_faltantes = $this->controlador->get_campos_faltantes();
		if (isset($campos_faltantes[$this->get_nombre()])) {
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

	function get_nombre()
	{
		return 'datos_salud';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\datos_censales\datos_salud\form_datos_salud');
			$this->form->set_autoset_activo(true);
		}
		return $this->form;
	}

	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/utilidades.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/plugin_autosave.js");
		return $files;
	}

	function prepare() 
	{
		$form = $this->get_form();
		$this->add_var_js('autosave', $form->is_autoset_activo());
		$this->add_var_js('msj_sin_cambios', kernel::traductor()->trans('form_sin_cambios'));

		switch ($this->estado) {
			case self::FORM_ERROR_VALIDACION:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('guardado_datos_error'),
					'exitoso' => false
				));
				break;
			case self::FORM_GUARDADO:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('guardado_datos_exitoso'),
					'exitoso' => true
				));
			default:
				$form->inicializar();
				$datos = $this->controlador->modelo()->info__datos_censales();
				$form->set_datos($datos);
		}

		if ($this->controlador->vista()->control_faltantes_activado()) {
			$this->marcar_faltantes();
		}

		$form->activar_csrf($this->generar_csrf());
		$this->add_form($form);
	}

}