<?php

namespace siu\operaciones\censo;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;

class pagelet_datos_personales extends pagelet_con_form
{
	protected $form_loc;
	protected $form_cp;

	function get_nombre()
	{
		return 'datos_personales';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\censo\datos_personales\form_datos_personales');
			$this->form->set_autoset_activo(true);
		}
		return $this->form;
	}

	function get_form_localidad()
	{
		if (!isset($this->form_loc)) {
			$this->form_loc = kernel::localizador()->instanciar('operaciones\censo\_comun\selector_localidad\form_localidad');
		}
		return $this->form_loc;
	}

	function get_form_cp()
	{
		if (!isset($this->form_cp)) {
			$this->form_cp = kernel::localizador()->instanciar('operaciones\censo\_comun\selector_localidad\form_cp');
		}
		return $this->form_cp;
	}

	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/plugin_autosave.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/_comun/selector_localidad/selector_localidad.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/_comun/selector_localidad/selector_cp.js");
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
			case self::FORM_CAMBIOS_DESDE_IMPRESION:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('cambios_desde_impresion'),
					'exitoso' => true,
					'cambios_desde_impresion' => true
				));
				break;
			case self::FORM_GUARDADO:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('guardado_datos_exitoso'),
					'exitoso' => true,
					'cambios_desde_impresion' => false
				));
			default:
				$form->inicializar();
				$datos = kernel::persona()->datos()->get_datos_formularios_preinsc();
				$form->set_datos($datos);
		}

		if ($this->controlador->vista()->control_faltantes_activado()) {
			$this->marcar_faltantes();
		}

		$form->activar_csrf($this->generar_csrf());
		$this->add_form($form);

		//---- Form del selector de localidades --------------------------------
		$form_loc = $this->get_form_localidad();
		$form_loc->inicializar();
		$this->add_form($form_loc);

		$this->add_var_js('selector_localidad', kernel::load_template('_comun/selector_localidad/selector_localidad.twig')->render(array(
					'form_loc' => $form_loc
		)));

		//---- Form del selector de códigos postales ---------------------------
		$form_cp = $this->get_form_cp();
		$form_cp->inicializar();
		$this->add_form($form_cp);

		$this->add_var_js('selector_cp', kernel::load_template('_comun/selector_localidad/selector_cp.twig')->render(array(
					'form_cp' => $form_cp
		)));
		$this->add_var_js('loc_sin_cp', kernel::traductor()->trans('loc_sin_cp'));

		//----------------------------------------------------------------------
		$this->add_var_js('url_provincias', kernel::vinculador()->crear('censo', 'cascada_provincias'));
		$this->add_var_js('url_departamentos', kernel::vinculador()->crear('censo', 'cascada_departamentos'));
		$this->add_var_js('url_localidades', kernel::vinculador()->crear('censo', 'cascada_localidades'));
		$this->add_var_js('url_cod_postales', kernel::vinculador()->crear('censo', 'cascada_cod_postales'));
		$this->add_var_js('url_cp_unico', kernel::vinculador()->crear('censo', 'cp_unico'));
		$this->add_var_js('error_vacio', kernel::traductor()->trans('form_validacion_error.vacio'));
		$this->add_var_js('ingresa_cp', true);

		$this->add_var_js('url_descr_localidad', kernel::vinculador()->crear('censo', 'get_descr_localidad'));
		$this->add_var_js('url_lista_localidades', kernel::vinculador()->crear('censo', 'lista_localidades'));

		$this->data['url_lista_localidades'] = kernel::vinculador()->crear('censo', 'lista_localidades');
	}

}
?>