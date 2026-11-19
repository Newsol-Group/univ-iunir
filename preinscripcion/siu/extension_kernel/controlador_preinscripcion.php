<?php

namespace siu\extension_kernel;

use SIU\Chulupi\nucleo\controlador as controlador_base;
use SIU\Chulupi\nucleo\ruteador;
use SIU\Chulupi\util\u;
use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernel;

abstract class controlador_preinscripcion extends controlador_base
{
	/**
	 *
	 * @param type $key
	 * @param type $method
	 * @param string $type valores posibles: string, int, float, url, string_array, int_array
	 * @param array 
	 * 	allow_quotes: boolean
	 *  trim: boolean
	 * @return type 
	 */
	protected function get_param($key, $method = 'post', $type = 'string', $options = array())
	{
		$value = ($method == 'post') ? kernel::request()->getPost($key) : kernel::url()->get_param($key);
		return u::sanitize_value($value, $type, $options);
	}

	protected function force_ssl()
	{
		if ((kernel::proyecto()->get_ssl_alcance() == ruteador::SSL_ALCANCE_NONE) || (!kernel::proyecto()->get_ssl_redirigir_ssl())) {
			return;
		}

		if (!isset($_SERVER['HTTPS']) || $_SERVER["HTTPS"] != "on") {
			header("Location: " . kernel::url()->self(true));
			die;
		}
	}

	//--------------------------------------------------------------------------
	//---- RECAPTCHA -----------------------------------------------------------
	//--------------------------------------------------------------------------

	function usa_captcha()
	{
		return kernel::proyecto()->get('usa_captcha');
	}

	protected function accion__recaptcha()
	{
		$error = null;
		$public_key = kernel::proyecto()->get_captcha_public_key();
		echo $this->recaptcha_get_html($public_key, $error);
		$this->finalizar_request();
	}

	protected function recaptcha_get_html($public_key, $error)
	{
		$html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>';
		$html .= '<body>';
		$use_ssl = (kernel::proyecto()->get_ssl_alcance() == 'all') ? true : false;
		$html .= recaptcha_get_html($public_key, $error, $use_ssl);
		$html .= '</body></html>';
		return $html;
	}

	protected function check_recaptcha()
	{
		$remote_addr = $_SERVER["REMOTE_ADDR"];
		$recaptcha_challenge_field = $this->get_post('recaptcha_challenge_field');
		$recaptcha_response_field = $this->get_post('recaptcha_response_field');
		$private_key = kernel::proyecto()->get_captcha_private_key();

		$parametros_extra = array();
		if (kernel::proyecto()->usa_proxy()) {
			$parametros_extra['parametros_proxy'] = kernel::proyecto()->get_parametros_proxy();
		}

		if ($recaptcha_response_field) {
			$resp = recaptcha_check_answer($private_key, $remote_addr, $recaptcha_challenge_field, $recaptcha_response_field, $parametros_extra);
			if ($resp->is_valid) {
				return true;
			} else {
				return false;
			}
		}

		return false;
	}
	
	//--------------------------------------------------------------------------
	
	protected function guardar_archivos_subidos($indice, $path)
	{
		if (!isset($_FILES[$indice])) {
			return array();
		}

		$descriptor = $_FILES[$indice];

		$archivo = array(
			'nombre' => $descriptor['name'],
			'tmp_name' => $descriptor['tmp_name'],
			'error' => isset($_FILES['error']) ? $_FILES['error'] : '',
			'size' => $descriptor['size']
		);
		
		$nombres = array();
		
		if (!is_uploaded_file($archivo['tmp_name']) || $archivo['error'] > 0) {
			throw new ErrorKernel('Error subiendo archivo');
		}

		$nombre_fs = $this->get_nombre_archivo_subido($archivo['nombre']);
		$destino = "$path/$nombre_fs";
		move_uploaded_file($archivo['tmp_name'], $destino);
		$nombres = array(
			'nombre_fs' => $nombre_fs,
			'nombre_original' => $archivo['nombre'],
			'size' => $descriptor['size']
		);

		return $nombres;
	}
	
}