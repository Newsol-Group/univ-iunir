<?php
namespace siu\operaciones\datos_censales;

use \siu\operaciones\datos_censales\_comun\pagelet_con_form_dc;
use SIU\Chulupi\kernel;

class pagelet_domicilio extends pagelet_con_form_dc
{
	protected $form_loc;
	protected $form_cp;
	
	function get_nombre()
    {
        return 'domicilio';
    }
    
	function get_form($id_html = null)
	{
		if (! isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\datos_censales\domicilio\form_domicilio');
			$this->form->set_autoset_activo(true);
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
	
	function get_js_files()
    {
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/plugin_autosave.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/utilidades.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/selectores/selector_localidad/selector_localidad.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/selectores/selector_localidad/selector_cp.js");
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