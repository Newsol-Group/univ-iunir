<?php
namespace siu\operaciones\alta_usuario_cursos;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_crear_usuario extends pagelet
{
	const FORM_INICIAL = 'inicial';
	const FORM_ERROR_RECAPTCHA = 'error_recaptcha';
	const FORM_ERROR_VALIDACION = 'form_error_validacion';
	const FORM_ERROR_CSRF = 'form_error_csrf';
	
	protected $form;
	protected $form_loc;
	
	function get_nombre()
	{
		return 'crear_usuario';
	}
	
	function get_form($id_html = null)
	{
		if (! isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\alta_usuario_cursos\crear_usuario\form_crear_usuario');
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
	
	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/utilidades.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/selectores/selector_localidad/selector_localidad.js");
		return $files;
	}
	
	function prepare() {
        $valida_con_renaper = kernel::proyecto()->valida_con_renaper();
        $this->add_var_js('url_sugerencia_user', kernel::vinculador()->crear('alta_usuario_cursos', 'sugerencia_user'));
        $this->add_var_js('url_validar_user', kernel::vinculador()->crear('alta_usuario_cursos', 'validar_usuario'));

        $this->add_var_js('url_paises', kernel::vinculador()->crear('alta_usuario_cursos', 'cascada_nacionalidad'));
        $this->add_var_js('url_provincias', kernel::vinculador()->crear('alta_usuario_cursos', 'cascada_provincias'));
        $this->add_var_js('url_departamentos', kernel::vinculador()->crear('alta_usuario_cursos', 'cascada_departamentos'));
        $this->add_var_js('url_localidades', kernel::vinculador()->crear('alta_usuario_cursos', 'cascada_localidades'));
        $this->add_var_js('url_cod_postales', kernel::vinculador()->crear('alta_usuario_cursos', 'cascada_cod_postales'));
        $this->add_var_js('url_tipo_documentos', kernel::vinculador()->crear('alta_usuario_cursos', 'cascada_tipo_documentos'));
        $this->add_var_js('url_tipo_documentos_validacion', kernel::vinculador()->crear('alta_usuario_cursos', 'datos_tipo_documento_validar'));
        $this->add_var_js('mat_validacion_renaper', $valida_con_renaper);
        $this->add_var_js('url_nro_tramite_dni_validacion', kernel::vinculador()->crear('alta_usuario_cursos', 'datos_nro_tramite_dni'));

        $this->add_var_js('error_vacio', kernel::traductor()->trans('form_validacion_error.vacio'));

        $this->add_var_js('url_descr_localidad', kernel::vinculador()->crear('alta_usuario_cursos', 'get_descr_localidad'));
        $this->add_var_js('url_lista_localidades', kernel::vinculador()->crear('alta_usuario_cursos', 'lista_localidades'));

        $this->add_mensaje_js('msj_nro_documento_dni_leyenda', kernel::traductor()->trans('alta_uc.dni.leyenda'));
        $this->add_mensaje_js('msj_tipo_documento_ayuda', kernel::traductor()->trans('alta_uc.documento.ayuda'));
        $this->add_mensaje_js('msj_nro_documento_dni_error', kernel::traductor()->trans('alta_uc.dni.error'));
        $this->add_mensaje_js('msj_nro_documento_cuil_leyenda', kernel::traductor()->trans('alta_uc.cuil.leyenda'));

        $this->add_mensaje_js('msj_nro_tramite_dni_leyenda', kernel::traductor()->trans('alta_uc.nro_tramite.dni.leyenda'));
        $this->add_mensaje_js('msj_nro_tramite_dni_error', kernel::traductor()->trans('alta_uc.nro_tramite.dni.error'));
        $this->add_mensaje_js('msj_ws_renaper_error', kernel::traductor()->trans('alta_uc.nro_tramite.dni.error_ws_renaper'));
        $this->add_mensaje_js('msj_ayuda_numero_tramite', kernel::traductor()->trans('alta_uc.ayuda_numero_tramite'));
        $this->add_mensaje_js('msj_ws_renaper_error_persona', kernel::traductor()->trans('alta_uc.ws_renaper_error_persona'));
        $this->add_mensaje_js('msj_no_coincide', kernel::traductor()->trans('alta_uc.msj_no_coincide'));

        $this->add_var_js('msj_Exito', \siu\modelo\entidades\ws_renaper::EXITO);

        $nro_tramite_dni = str_replace(['/', 'i'], '', \comunes::er_numero_entero);
        $this->add_var_js('regexp_nro_tramite_dni', $nro_tramite_dni);

        $this->add_var_js('nro_documento_dni_dnt_min', \persona::NRO_DOCUMENTO_DNI_DNT_MIN);
        $this->add_var_js('nro_documento_dni_dnt_max', \persona::NRO_DOCUMENTO_DNI_DNT_MAX);

        $this->add_var_js('tipo_documento_dni', \persona::doc_dni);
        $this->add_var_js('tipo_documento_dnt', \persona::doc_dni_temporal);
        $this->add_var_js('tipo_documento_cuil_cuit', \persona::doc_CUIL);
        $this->add_var_js('tipo_documento_cedula_diplomatica', \persona::doc_diplomatica);
        $this->add_var_js('tipo_documento_pasaporte', \persona::doc_pasaporte);

        $this->data['mensajes'] = $this->controlador->get_var_default('mensajes', array());
        $this->data['url_volver'] = kernel::vinculador()->crear('acceso', 'index');
        $this->data['usar_captcha'] = $this->controlador->usa_captcha();
        $this->data['mat_validacion_renaper'] = $valida_con_renaper;
        $this->data['url_img_nro_tramite_dni'] = kernel::vinculador()->vinculo_img('img/nro_de_tramite_dni.jpeg');
        $url_identidad_genero = kernel::proyecto()->get_url_identidad_genero();
        $this->data['url_identidad_genero'] = $url_identidad_genero;
        $this->add_var_js('identidad_genero_especifique', \persona::identidad_genero_ninguna);

        $form = $this->get_form();

        switch ($this->estado) {
            case static::FORM_ERROR_VALIDACION:
                $this->data['mensajes'] = $this->controlador->get_var('mensajes');
                $this->data['error'] = true;
                break;
            case static::FORM_ERROR_RECAPTCHA:
                $this->data['mensajes'] = $this->controlador->get_var('mensajes');
                $this->data['error'] = true;
                $form->inicializar();
                $form->set_datos_desde_post();
                break;
            case static::FORM_ERROR_CSRF:
                $this->data['mensajes'] = $this->controlador->get_var('mensajes');
                $this->data['error'] = true;
                $form->inicializar();
                $form->set_datos_desde_post();
                break;
            default:
                $this->data['nombre_usuario'] = $this->controlador->get_var_default('nombre_usuario', '');
                $this->data['action_crear'] = kernel::vinculador()->crear('alta_usuario_cursos', 'crear');
                $form->inicializar();
                $datos = $this->controlador->get_datos_persona();
                $form->set_datos($datos);
        }

        $this->data['inicial'] = static::FORM_INICIAL;
        $this->data['form_error_validacion'] = static::FORM_ERROR_VALIDACION;
        $this->data['error_recaptcha'] = static::FORM_ERROR_RECAPTCHA;
        $this->data['form_error_csrf'] = static::FORM_ERROR_CSRF;
        $this->data['csrf'] = $this->generar_csrf();
        $this->add_form($form);

        //---- Form del selector de localidades --------------------------------
        $form_loc = $this->get_form_localidad();
        $form_loc->inicializar();
        $this->add_form($form_loc);

        $this->add_var_js('selector_localidad', kernel::load_template('crear_usuario/selector_localidad.twig')->render(array(
                    'form_loc' => $form_loc
        )));
    }

}