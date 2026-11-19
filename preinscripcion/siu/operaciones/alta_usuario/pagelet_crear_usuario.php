<?php

namespace siu\operaciones\alta_usuario;

use SIU\Chulupi\kernel;
use SIU\Chulupi\nucleo\NuevoRequest as nuevo_request;
use siu\operaciones\_comun\pagelet_con_form;
use siu\modelo\g3\g3;
use siu\modelo\comunes;
use siu\modelo\ws_renaper;

class pagelet_crear_usuario extends pagelet_con_form
{
	const FORM_INICIAL = 'inicial';
	const FORM_ERROR_RECAPTCHA = 'error_recaptcha';
	const MAIL_ENVIADO = 'mail_enviado';
	const FORM_ERROR_CSRF = 'form_error_csrf';

	function ini()
	{
		if (!g3::periodos()->existe_periodo_abierto()) {
			kernel::sesion()->set('msj_pagina_error', kernel::traductor()->trans('no_existen_periodos'));
			throw new nuevo_request('pagina_error');
		}
	}

	function get_nombre()
	{
		return 'crear_usuario';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\alta_usuario\crear_usuario\form_crear_usuario');
		}
		return $this->form;
	}

	function prepare()
	{
		$this->add_var_js('url_paises', kernel::vinculador()->crear('alta_usuario', 'cascada_nacionalidad'));
		$this->add_var_js('url_tipo_documentos', kernel::vinculador()->crear('alta_usuario', 'datos_tipo_documento'));
		$this->add_var_js('url_tipo_documentos_validacion', kernel::vinculador()->crear('alta_usuario', 'datos_tipo_documento_validar'));
        $this->add_var_js('url_nro_tramite_dni_validacion', kernel::vinculador()->crear('alta_usuario', 'datos_nro_tramite_dni'));
        $this->add_var_js('msj_Exito', ws_renaper::EXITO);
        
        $nro_tramite_dni = str_replace(['/', 'i'], '', \siu\modelo\comunes::er_numero_entero);
		$this->add_var_js('regexp_nro_tramite_dni', $nro_tramite_dni);
        //obtengo el parametro de renaper
        $parametro = kernel::proyecto()->get_mat_validacion_renaper();
        $this->add_var_js('mat_validacion_renaper', $parametro);
        $this->data['mat_validacion_renaper'] = $parametro;
		
		$this->add_mensaje_js('msj_nro_documento_dni_leyenda', kernel::traductor()->trans('nro_documento.dni.leyenda'));
		$this->add_mensaje_js('msj_nro_documento_dni_error', kernel::traductor()->trans('nro_documento.dni.error'));
        $this->add_mensaje_js('msj_nro_documento_dni_rep', kernel::traductor()->trans('nro_documento.dni.rep'));
		$this->add_mensaje_js('msj_nro_documento_cuil_leyenda', kernel::traductor()->trans('nro_documento.cuil.leyenda'));
        
        $this->add_mensaje_js('msj_nro_tramite_dni_leyenda', kernel::traductor()->trans('nro_tramite.dni.leyenda'));
		$this->add_mensaje_js('msj_nro_tramite_dni_error', kernel::traductor()->trans('nro_tramite.dni.error'));
        $this->add_mensaje_js('msj_ws_renaper_error', kernel::traductor()->trans('nro_tramite.dni.error_ws_renaper'));
        $this->add_mensaje_js('msj_ayuda_numero_tramite', kernel::traductor()->trans('ayuda_numero_tramite'));
        $this->add_mensaje_js('msj_ayuda_documento', kernel::traductor()->trans('ayuda_documento'));
        $this->add_mensaje_js('info_dni_en_tramite', kernel::traductor()->trans('info_dni_en_tramite'));

		$this->add_var_js('nro_documento_dni_dnt_min', comunes::NRO_DOCUMENTO_DNI_DNT_MIN);
		$this->add_var_js('nro_documento_dni_dnt_max', comunes::NRO_DOCUMENTO_DNI_DNT_MAX);
		
		$this->add_var_js('tipo_documento_dni', comunes::TIPO_DOCUMENTO_DNI);
		$this->add_var_js('tipo_documento_dnt', comunes::TIPO_DOCUMENTO_DNT);
		$this->add_var_js('tipo_documento_cuil_cuit', comunes::TIPO_DOCUMENTO_CUIL_CUIT);
		$this->add_var_js('tipo_documento_cedula_diplomatica', comunes::TIPO_DOCUMENTO_CEDULA_DIPLOMATICA);
		$this->add_var_js('tipo_documento_pasaporte', comunes::TIPO_DOCUMENTO_PASAPORTE);
		$this->add_var_js('url_img_nro_tramite_dni', kernel::vinculador()->vinculo_img('img/nro_de_tramite_dni.jpeg'));
        
        $this->data['url_img_nro_tramite_dni'] = kernel::vinculador()->vinculo_img('img/nro_de_tramite_dni.jpeg');
		$this->data['mensajes'] = $this->controlador->get_var_default('mensajes', array());
		$this->data['url_volver'] = kernel::vinculador()->crear('acceso', 'index');
		$this->data['usar_captcha'] = $this->controlador->usa_captcha();
		$this->data['captcha_site_key'] = kernel::proyecto()->get_captcha_site_key(); 
		
		$this->data['inicial']					= static::FORM_INICIAL;
		$this->data['form_error_validacion']	= static::FORM_ERROR_VALIDACION;
		$this->data['error_recaptcha']			= static::FORM_ERROR_RECAPTCHA;
		$this->data['form_error_csrf']			= static::FORM_ERROR_CSRF;
		$this->data['csrf']						= $this->generar_csrf();

		if (kernel::sesion()->esta_seteada('alta_se_id')) {
			$this->data['msg_alta_se'] = kernel::traductor()->trans('login.msg_alta_se', array('%1%' => capitalizar(kernel::sesion()->get('alta_se_medio')), '%2%' => kernel::sesion()->get('alta_se_mail')));
			$this->data['aclaracion_clave'] = kernel::traductor()->trans('login.se.aclaracion_clave', array('%1%' => capitalizar(kernel::sesion()->get('alta_se_medio'))));
		}

		$form = $this->get_form();

		switch ($this->estado) {
			case static::MAIL_ENVIADO:
				$this->data['email'] = $this->controlador->get_var('email');
				break;
			case static::FORM_ERROR_VALIDACION:
				$this->data['mensajes'] = $this->controlador->get_var('mensajes');
				$this->data['mensajes'][0]['raw'] = true;//para que haga el link
				$this->data['error'] = true;
				$this->data['url_recaptcha'] = kernel::vinculador()->crear('alta_usuario', 'recaptcha');
				$form->inicializar();
				$form->set_datos_desde_post();
				break;
			case static::FORM_ERROR_RECAPTCHA:
				$this->data['mensajes'] = $this->controlador->get_var('mensajes');
				$this->data['error'] = true;
				$this->data['url_recaptcha'] = kernel::vinculador()->crear('alta_usuario', 'recaptcha');
				$form->inicializar();
				$form->set_datos_desde_post();
				break;
			case static::FORM_ERROR_CSRF:
				$this->data['mensajes'] = $this->controlador->get_var('mensajes');
				$this->data['error'] = true;
				$this->data['url_recaptcha'] = kernel::vinculador()->crear('alta_usuario', 'recaptcha');
				$form->inicializar();
				$form->set_datos_desde_post();
				break;
			default:
				$this->data['nombre_usuario'] = $this->controlador->get_var_default('nombre_usuario', '');
				$this->data['action_crear'] = kernel::vinculador()->crear('alta_usuario', 'crear');
				$this->data['url_recaptcha'] = kernel::vinculador()->crear('alta_usuario', 'recaptcha');
				$form->inicializar();

				if (kernel::sesion()->esta_seteada('alta_se_mail')) {
					$datos = array();
					$datos['e_mail'] = kernel::sesion()->get('alta_se_mail');
					$datos['e_mail_rep'] = kernel::sesion()->get('alta_se_mail');
					$form->set_datos($datos);
				}
		}

		$datos_form = $form->get_datos();
		$this->add_var_js('tipo_documento', $datos_form['tipo_documento'] ?? null);

		$this->add_form($form);
	}

}