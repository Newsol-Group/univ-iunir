<?php
namespace siu\operaciones\censo;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;

class pagelet_datos_adicionales extends pagelet_con_form
{
	protected $form_propuesta;

	function get_nombre()
	{
		return 'datos_adicionales';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\censo\datos_adicionales\form_datos_adicionales');
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
		
		$cantidad_inscripciones = kernel::persona()->datos()->get_cantidad_inscripciones();
		$this->data['cant_inscripciones'] = $cantidad_inscripciones;
		
		$formulario = $this->get_form();
		$this->add_var_js('autosave', $formulario->is_autoset_activo());
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
				$formulario->inicializar();
				$datos = kernel::persona()->datos()->get_datos_formularios_preinsc();
				$formulario->set_datos($datos);
		}

		if ($this->controlador->vista()->control_faltantes_activado()) {
			$this->marcar_faltantes();
		}

		$formulario->activar_csrf($this->generar_csrf());
		$this->add_form($formulario);
	}

}