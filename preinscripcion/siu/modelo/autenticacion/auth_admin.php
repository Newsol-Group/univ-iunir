<?php

namespace siu\modelo\autenticacion;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\IntentosLogin as intentos_login;
use siu\errores\error_preinscripcion_login;

class auth_admin extends auth_con_sesion
{
	protected $id_persona = null;
	
	/**
	 * @var error_preinscripcion_login
	 */
	protected $error_login = null;

	public function autenticar()
	{
		if (kernel::request()->isPost()) {
			$usuario = kernel::request()->getPost('usuario');
			$pass = kernel::request()->getPost('password');
			try {
				$this->usuario = $usuario;
				return $this->id_persona = $this->validar_user_pass($usuario, $pass);
			} catch (error_preinscripcion_login $e) {
				$this->error_login = $e;
				intentos_login::error($usuario);
				kernel::log()->add_error($e);
			}
		}
		return null;
	}

	public function get_modelo()
	{
		$datos = array();

		$datos['accion'] = kernel::vinculador()->crear('acceso_admin', 'index', array('auth' => 'admin'));

		$datos['campos'] = array(
			"usuario" => array('name' => "usuario", 'type' => "text", 'value' => ''),
			"password" => array('name' => "password", 'type' => "password", 'value' => ''),
			"submit" => array('name' => "login", 'type' => "submit", 'value' => 'Ingresar')
		);

		if (isset($this->error_login)) {
			$datos['error'] = $this->error_login->get_mensaje();
		}

		return $datos;
	}

	protected function validar_user_pass($usuario, $password)
	{
		$parametros = $this->get_parametros();
		$clase_login = kernel::localizador()->instanciar($parametros['clase_login']);
		return $clase_login->autenticar($usuario, $password);
	}

	/**
	 * Se invoca luego de abrir sesion en el kernel (luego de una autenticacion exitosa)
	 */
	public function evt_sesion_abierta()
	{
		parent::evt_sesion_abierta();
		kernel::sesion()->set('_usuario_', $this->usuario);
	}

	/**
	 * Cerrar sesion en el proveedor.
	 */
	public function logout()
	{
		// Como es la misma sesión que el kernel no se toca.
	}

	function evt_persona_instanciada($persona, $autenticada)
	{
		if (($autenticada) && (kernel::sesion()->esta_seteada('_usuario_'))) {
			$persona->set_email(kernel::sesion()->get('_usuario_'));
		}
	}

}
?>