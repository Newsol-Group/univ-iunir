<?php

namespace siu\operaciones\alta_usuario;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;
use siu\preinscripcion;

class pagelet_confirm_doc_existente extends pagelet
{
	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'confirm_doc_existente';
	}

	function prepare()
	{
		if (!kernel::sesion()->esta_seteada('__doc_ex__datos_usuario')) {
			die();
		}

		$datos_usuario = kernel::sesion()->get('__doc_ex__datos_usuario');
		$valores_form = kernel::sesion()->get('__doc_ex__valores_form');
		$datos_ayuda = kernel::sesion()->get('__doc_ex__datos_ayuda');

		$apellido = $this->ocultar_texto($datos_usuario['apellido']);
		$nombres = $this->ocultar_texto($datos_usuario['nombres']);
		$fecha_reg = convertir_fecha_formato_frontend($datos_usuario['fecha_registro']);
		$mail = $this->ocultar_mail($datos_usuario['e_mail']);

		$this->data['confirm_doc_existente_1'] = kernel::traductor()->trans('confirm_doc_existente_1', array('%1%' => $apellido . ' ' . $nombres, '%2%' => $fecha_reg, '%3%' => $mail));

		//ayuda

		$this->data['datos_ayuda'] = $datos_ayuda;
		if (empty($datos_usuario['email_valido'])) {		
			
			//-- Cuenta no confirmada => se ofrece reenviar mail de alta -------
			$this->data['recuperar_clave'] = false;
			$this->data['url_accion'] = kernel::vinculador()->crear('alta_usuario', 'reenvio_mail');
			$this->data['confirm_doc_existente_2'] = kernel::traductor()->trans('confirm_doc_existente_2_2');
		} else {	
		
			//-- Cuenta confirmada => se ofrece recuperar clave ----------------
			$this->data['recuperar_clave'] = true;
			$this->data['url_accion'] = kernel::vinculador()->crear('acceso', 'recuperar');
			$this->data['confirm_doc_existente_2'] = kernel::traductor()->trans('confirm_doc_existente_2_1');
			//-- Cuenta confirmada => se ofrece cambiar correo---------------- 
			$this->data['url_continuar_2_2'] = kernel::vinculador()->crear('acceso', 'recuperar');
			$this->data['confirm_doc_existente_2_2'] = kernel::traductor()->trans('confirm_doc_existente_2_3');
			$this->data['confirm_doc_existente_3'] = kernel::traductor()->trans('confirm_doc_existente_3');
			//-- Cuenta confirmada => si no sos la misma persona---------------- 
			$this->data['url_continuar'] = kernel::vinculador()->crear('alta_usuario', 'continuar_preinscripcion');
		}


	}

	//--------------------------------------------------------------------------

	protected function ocultar_texto($texto)
	{
		return substr_replace($texto, '***', 3);
	}

	protected function ocultar_mail($mail)
	{
		$aux = substr_replace($mail, '***', 3, strpos($mail, '@') - 3);
		$pos_punto = strpos($aux, '.', strpos($aux, '@'));
		$aux = substr_replace($aux, '***', $pos_punto);
		return $aux;
	}

}
?>