<?php

namespace siu\operaciones\acceso;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

use SIU\Chulupi\nucleo\NuevoRequest as nuevo_request;

class pagelet_form_login extends pagelet
{
	function ini()
	{
           if (  kernel::sesion()->esta_seteada('mensaje_vista_acceso_error_token'))
	   {
                throw new nuevo_request('alta_usuario', 'index');
           } 
	}

	function get_nombre()
	{
		return 'form_login';
	}

	function prepare()
	{
		$lm = kernel::login_manager();

		$modelo = array();
		foreach ($lm->get_auth_providers() as $auth) {
			/** @var $auth authentication_provider */
			$modelo[$auth->get_id()] = $auth->get_modelo();
		}

		$this->data['proveedores'] = $modelo;
		$this->data['hay_servicios_externos'] = !empty($modelo['cuentas_ext']['proveedores']);
		$this->data['url_crear'] = kernel::vinculador()->crear('alta_usuario', 'index');
		$this->data['url_recuperar'] = kernel::vinculador()->crear('acceso', 'recuperar');
		$this->data['url_reenviar_mail'] = kernel::vinculador()->crear('acceso', 'reenvio_mail', array('camb_msj' => 1));
                $this->data['url_ayuda_menu'] = kernel::vinculador()->crear('ayuda_menu', 'index');

                $operacion = kernel::ruteador()->get_id_operacion();
                $this->add_var_js('url_tipo_documentos', kernel::vinculador()->crear($operacion, 'datos_tipo_documento'));
	}

}