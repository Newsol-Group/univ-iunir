<?php

namespace siu\operaciones\acceso;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;
use siu\modelo\util;

class pagelet_recuperar_pass extends pagelet
{
	const MAIL_ENVIADO = 'mail_enviado';
	const MAIL_NO_EXISTENTE = 'mail_no_existente';
	const TOKEN_CORRECTO = 'token_correcto';
	const TOKEN_INCORRECTO = 'token_incorrecto';
    const TOKEN_EXPIRADO = 'token_expirado';

	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'recuperar_pass';
	}

	protected function obfuscate_mail($mail)
	{
		$aux = substr_replace($mail, '***', 2, strpos($mail, '@') - 2);
		$pos_punto = strpos($aux, '.', strpos($aux, '@'));
		$aux = substr_replace($aux, '***', $pos_punto);
		return $aux;
	}

	function prepare()
	{
		$this->data['url_volver'] = kernel::vinculador()->crear('acceso', 'index');
		$this->add_var_js('url_tipo_documentos', kernel::vinculador()->crear('acceso', 'datos_tipo_documento'));
		
		switch ($this->estado) {
			case self::MAIL_ENVIADO:
				$this->data['email'] = $this->obfuscate_mail($this->controlador->get_var('email'));
				$this->data['url_cambiar_email'] = kernel::vinculador()->crear('acceso', 'cambiar_email');
				break;
			case self::MAIL_NO_EXISTENTE:
				break;
			case self::TOKEN_CORRECTO:
				$this->data['token'] = $this->controlador->get_var('token');
				$this->data['mensajes'] = $this->controlador->get_var('mensajes');
                $this->data['action_reset_pass'] = kernel::vinculador()->crear('acceso', 'reset_pass');
				break;
			case self::TOKEN_INCORRECTO:
				break;
            case self::TOKEN_EXPIRADO:
				break;
			default:
				$this->data['nombre_usuario'] = $this->controlador->get_var_default('nombre_usuario', '');
				$this->data['action_recuperar'] = kernel::vinculador()->crear('acceso', 'recuperar');
				$this->data['paises'] =  $this->controlador->get_opciones__paises();
				$this->data['tipos_doc'] =[];
				$this->data['documento'] = $this->controlador->get_var('documento');
				//$this->data['action_recuperar'] = kernel::vinculador()->crear('acceso', 'recuperar');
		}
        
        // Si se seteo un mensaje en el controlador lo agrego para que lo consuma el twig
        if ($this->controlador->hay_var('mensajes')) {
            $this->data['mensajes'] = $this->controlador->get_var('mensajes');
        }
	}

}