<?php
namespace siu\operaciones\datos_censales;

use \siu\operaciones\datos_censales\_comun\pagelet_con_edicion;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_datos_estudios extends pagelet_con_edicion
{
	function get_nombre()
    {
        return 'datos_estudios';
    }
	
	function get_form($id_html = null)
	{
		if (! isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\datos_censales\datos_estudios\form_datos_estudios');
		}
		return $this->form;
	}
	
	function get_datos_estudios()
	{
		$datos = $this->controlador->modelo()->info__datos_estudios();
		foreach ($datos as $key => $dato) {
			$datos[$key]['LINK_EDITAR'] = kernel::vinculador()->crear('datos_censales', 'editar_datos_estudios', array('datos_estudios', $dato[catalogo::id]));
			$datos[$key]['LINK_ELIMINAR'] = kernel::vinculador()->crear('datos_censales', 'eliminar', array('datos_estudios', $dato[catalogo::id]));
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
					$datos = $this->controlador->get_datos_estudios();
					$form->set_datos($datos);
			}
		}
		
		$form->activar_csrf($this->generar_csrf());
		$this->add_form($form);
		
		$this->data['cant_antecedentes'] = \guarani_arreglos::count($this->get_datos_estudios());
		$this->data['modo'] = $this->pantalla;
		
		$this->add_var_js('url_lista_instituciones', kernel::vinculador()->crear('datos_censales', 'lista_instituciones'));
		$this->add_var_js('url_lista_colegios', kernel::vinculador()->crear('datos_censales', 'lista_colegios'));
		$this->add_var_js('url_lista_titulos', kernel::vinculador()->crear('datos_censales', 'lista_titulos'));
		$this->add_var_js('nivel_sin_estudios', \persona::nivel_sin_estudio);
		$this->add_var_js('nivel_secundario', \persona::nivel_secundario);
		$this->add_var_js('nivel_superior', \persona::nivel_superior);
		$this->add_var_js('nivel_universitario', \persona::nivel_universitario);
		$this->add_var_js('nivel_posgrado', \persona::nivel_posgrado);
		$this->add_var_js('estado_termino', \persona::estudio_estado_termino);
		$this->add_var_js('estado_abandono', \persona::estudio_estado_abandono);
		$this->add_var_js('razon_abandono_OTRO', \persona::estudio_razon_abandono_otras);
	}
}
?>