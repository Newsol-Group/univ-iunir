<?php

namespace siu\operaciones\censo;

use SIU\Chulupi\kernel;
use siu\operaciones\censo\_comun\pagelet_con_edicion;

class pagelet_datos_salud extends pagelet_con_edicion
{
	
	function get_nombre()
	{
		return 'datos_salud';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\censo\datos_salud\form_datos_salud');
			$this->form->set_autoset_activo(true);
		}
		return $this->form;
	}

	function get_js_files()
    {
		$files = parent::get_js_files();
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
	}
	
}