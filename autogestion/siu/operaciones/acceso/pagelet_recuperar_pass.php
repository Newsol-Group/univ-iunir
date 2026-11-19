<?php
namespace siu\operaciones\acceso;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

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

	function prepare()
	{
		switch ($this->estado) {
			// Se seleccionó una materia que no está inscripto
			case self::MAIL_ENVIADO:
				$this->data['mail'] = \persona::obfuscate_mail($this->controlador->get_var('mail'));
				break;
			case self::MAIL_NO_EXISTENTE:
				$this->data['action_reset_pass'] = kernel::vinculador()->crear('acceso', 'reset_pass');
				break;
			case self::TOKEN_CORRECTO:
				$this->data['token'] = $this->controlador->get_var('token');
				break;
			case self::TOKEN_INCORRECTO:
				break;
			case self::TOKEN_EXPIRADO:
				break;
			default:
				$this->data['paises'] = catalogo::consultar('etiquetas', 'paises');
				$parametros['pais']='54';
				$this->data['tipos_doc'] = catalogo::consultar('etiquetas', 'tipos_documento',$parametros);
				$this->data['documento'] = $this->controlador->get_var('documento');
				$this->data['action_recuperar'] = kernel::vinculador()->crear('acceso', 'recuperar');

				//Tipos de documentos
			/*	$this->add_var_js('doc_dni', \persona::doc_dni);
				$this->add_var_js('doc_dni_temporal', \persona::doc_dni_temporal);
				$this->add_var_js('doc_cedula', \persona::doc_cedula);
				$this->add_var_js('doc_CUIL', \persona::doc_CUIL);
				$this->add_var_js('doc_LE', \persona::doc_LE);
				$this->add_var_js('doc_LC', \persona::doc_LC);
				$this->add_var_js('doc_pasaporte', \persona::doc_pasaporte);*/
				$this->add_var_js('url_tipo_documentos', kernel::vinculador()->crear('acceso', 'cascada_tipo_documentos'));

				//RegExp validación
				$this->add_var_js('exp_reg_numerica', \persona::exp_reg_numerica);
				$this->add_var_js('exp_reg_alfanum', \persona::exp_reg_alfanum);
		}

        // Si se seteo un mensaje en el controlador lo agrego para que lo consuma el twig
        if ($this->controlador->hay_var('mensajes')) {
            $this->data['mensajes'] = $this->controlador->get_var('mensajes');
        }
        $this->add_mensaje_js('msj_tipo_documento_ayuda', kernel::traductor()->trans('alta_uc.documento.ayuda'));

		$this->add_var_js('error_documento_requerido', kernel::traductor()->trans('acceso.recuperar.error_documento_requerido'));
		$this->add_var_js('error_documento_numerico', kernel::traductor()->trans('acceso.recuperar.error_documento_numerico'));
		$this->add_var_js('error_documento_alfanum', kernel::traductor()->trans('acceso.recuperar.error_documento_alfanum'));
	}

}