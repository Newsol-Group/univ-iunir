<?php
namespace siu\operaciones\datos_censales;

use \siu\operaciones\datos_censales\_comun\pagelet_con_form_dc;
use SIU\Chulupi\kernel;

class pagelet_discapacidad extends pagelet_con_form_dc
{
	function get_nombre()
    {
        return 'discapacidad';
    }
    
	function get_form($id_html = null)
	{
		if (! isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\datos_censales\discapacidad\form_discapacidad');
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
		$this->add_var_js('msj_faltan_datos_discapacidad', kernel::traductor()->trans('faltan_datos_discapacidad'));
		$this->add_var_js('si', \comunes::si);
		
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