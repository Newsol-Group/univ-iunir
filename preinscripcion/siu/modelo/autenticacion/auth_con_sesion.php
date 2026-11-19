<?php

namespace siu\modelo\autenticacion;

use SIU\Chulupi\acceso\AuthenticationProvider as authentication_provider;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;

abstract class auth_con_sesion extends authentication_provider
{
	protected $session;
	protected $usuario;

	function __construct($id, $conf)
	{
		parent::__construct($id, $conf);
		$this->session = kernel::sesion();
	}

	public function esta_logueado()
	{
		if ($this->session->abierta()) {
			if ($this->excede_maxtime() || $this->excede_timeout()) {
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * Se invoca luego de abrir sesion en el kernel (luego de una autenticacion exitosa)
	 */
	public function evt_sesion_abierta()
	{
		$this->session->set('__kernel_ses_start', time());
		$this->session->set('__kernel_ses_last_request', time());
	}

	public function evt_nuevo_request()
	{
		$this->session->set('__kernel_ses_last_request', time());
	}

	protected function excede_maxtime()
	{
		$maxtime = kernel::proyecto()->get('sesion_maxtime') * 60;
		$hora_entrada = $this->session->get('__kernel_ses_start');

		return (time() - $hora_entrada) > $maxtime;
	}

	protected function excede_timeout()
	{
		$timeout = kernel::proyecto()->get('sesion_timeout') * 60;
		$hora_ultimo_request = $this->session->get('__kernel_ses_last_request');

		return (time() - $hora_ultimo_request) > $timeout;
	}

	protected function validate_param($key, $method = 'post', $type = validador::TIPO_ALPHA, $options = array())
	{
		$value = ($method == 'post') ? kernel::request()->getPost($key, '') : kernel::url()->get_param($key, false, '');
		return $this->validate_value($value, $type, $options, $key, $method);
	}

	protected function validate_value($value, $type, $options, $key = '-', $method = '-')
	{
		$rs = validador::validar($value, $type, $options);
		return $rs;
	}

}
?>