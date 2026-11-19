<?php
namespace siu\operaciones\datos_censales;

use \siu\operaciones\datos_censales\_comun\pagelet_con_edicion;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_actuacion_profesional extends pagelet_con_edicion
{
	function get_nombre()
    {
        return 'actuacion_profesional';
    }
	
	function get_form($id_html = null)
	{
		if (! isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\datos_censales\actuacion_profesional\form_actuacion_profesional');
		}
		return $this->form;
	}
	
	function get_datos_actuacion_profesional()
	{
		$datos = $this->controlador->modelo()->info__antecedentes_profesionales();
		foreach ($datos as $key => $dato) {
			$datos[$key]['LINK_EDITAR'] = kernel::vinculador()->crear('datos_censales', 'editar_actuacion_profesional', array('actuacion_profesional', $dato[catalogo::id]));
			$datos[$key]['LINK_ELIMINAR'] = kernel::vinculador()->crear('datos_censales', 'eliminar', array('actuacion_profesional', $dato[catalogo::id]));
		}
		return $datos;
	}
	
	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/utilidades.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/date-es-AR.js");
		return $files;
	}
	
	function prepare()
	{
		$this->add_var_js('msj_sin_cambios', kernel::traductor()->trans('form_sin_cambios'));
		$this->add_var_js('error_vacio', kernel::traductor()->trans('form_validacion_error.vacio'));
		$this->add_var_js('error_fechas', kernel::traductor()->trans('datos_censales.error_fechas'));
		$this->add_var_js('msj_confirmar_eliminar', kernel::traductor()->trans('datos_censales.msj_confirmar_eliminar'));
		$this->add_var_js('error_fecha_posterior', kernel::traductor()->trans('datos_censales.error_fecha_posterior'));
		$this->add_var_js('error_fecha_antigua', kernel::traductor()->trans('datos_censales.error_fecha_antigua'));
		
		$form = $this->get_form();
		
		if ($this->pantalla == self::ESTADO_EDICION) {
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
					$datos = $this->controlador->get_actuacion_profesional();
					$form->set_datos($datos);
			}
		}
		
		$form->activar_csrf($this->generar_csrf());
		$this->add_form($form);
		
		$this->data['cant_antecedentes'] = \guarani_arreglos::count($this->get_datos_actuacion_profesional());
		$this->data['modo'] = $this->pantalla;
	}
}
?>