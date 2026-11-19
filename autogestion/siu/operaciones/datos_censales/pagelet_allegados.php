<?php
namespace siu\operaciones\datos_censales;

use \siu\operaciones\datos_censales\_comun\pagelet_con_edicion;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_allegados extends pagelet_con_edicion
{
	protected $form_loc;
	protected $form_cp;
	
	function get_nombre()
    {
        return 'allegados';
    }
	
	function get_form($id_html = null)
	{
		if (! isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\datos_censales\allegados\form_allegados');
		}
		return $this->form;
	}
	
	function get_form_localidad()
	{
		if (! isset($this->form_loc)) {
			$this->form_loc = kernel::localizador()->instanciar('operaciones\datos_censales\_comun\selector_localidad\form_localidad');
		}
		return $this->form_loc;
	}
	
	function get_form_cp()
	{
		if (! isset($this->form_cp)) {
			$this->form_cp = kernel::localizador()->instanciar('operaciones\datos_censales\_comun\selector_localidad\form_cp');
		}
		return $this->form_cp;
	}
	
	function get_datos_allegados()
	{
		$datos = $this->controlador->modelo()->info__allegados();
		foreach ($datos as $key => $dato) {
			$datos[$key]['LINK_EDITAR'] = kernel::vinculador()->crear('datos_censales', 'editar_allegados', array('allegados', $dato[catalogo::id]));
			$datos[$key]['LINK_ELIMINAR'] = kernel::vinculador()->crear('datos_censales', 'eliminar', array('allegados', $dato[catalogo::id]));
		}
		return $datos;
	}
	
	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/jqueryui.extra.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/utilidades.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/selectores/selector_localidad/selector_localidad.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/selectores/selector_localidad/selector_cp.js");
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
					$datos = $this->controlador->get_allegados();
					$form->set_datos($datos);
			}
		}
		
		$form->activar_csrf($this->generar_csrf());
		$this->add_form($form);
		
		$this->data['cant_allegados'] = \guarani_arreglos::count($this->get_datos_allegados());
		$this->data['modo'] = $this->pantalla;
		
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
		
		//----------------------------------------------------------------------
		$this->add_var_js('url_provincias', kernel::vinculador()->crear('datos_censales', 'cascada_provincias'));
		$this->add_var_js('url_departamentos', kernel::vinculador()->crear('datos_censales', 'cascada_departamentos'));
		$this->add_var_js('url_localidades', kernel::vinculador()->crear('datos_censales', 'cascada_localidades'));
		$this->add_var_js('url_cod_postales', kernel::vinculador()->crear('datos_censales', 'cascada_cod_postales'));
		$this->add_var_js('url_cp_unico', kernel::vinculador()->crear('datos_censales', 'cp_unico'));
		
		$this->add_var_js('error_vacio', kernel::traductor()->trans('form_validacion_error.vacio'));
		$this->add_var_js('ingresa_cp', true);
		
		$this->add_var_js('url_descr_localidad', kernel::vinculador()->crear('datos_censales', 'get_descr_localidad'));
		$this->add_var_js('url_lista_localidades', kernel::vinculador()->crear('datos_censales', 'lista_localidades'));
		
		$this->data['url_lista_localidades'] = kernel::vinculador()->crear('datos_censales', 'lista_localidades');
	}
}
?>