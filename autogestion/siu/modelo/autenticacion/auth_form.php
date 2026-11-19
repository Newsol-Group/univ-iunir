<?php

namespace siu\modelo\autenticacion;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\IntentosLogin;
use siu\errores\error_guarani_login;
use siu\modelo\datos\catalogo;
use siu\extension_kernel\controlador_g3w2;

class auth_form extends auth_con_sesion {

	protected $id_persona = null;
	protected $usarios_bd;

	/**
	 * @var error_guarani_login
	 */
	protected $error_login = null;


	public function autenticar()
	{
		if (kernel::request()->isPost()) {
			$usuario = kernel::request()->getPost('usuario');
			$pass = kernel::request()->getPost('password');

			try {
                //Valido el captcha.
				if ($this->controlar_captcha_login($usuario)) {
					$this->check_recaptcha_login();
				}

                //Valido el usuario y la contraseña.
				return $this->id_persona = $this->validar_user_pass($usuario, $pass);

			} catch (error_guarani_login $e) {
				//guardar errores para mostrar en el render_login()
				$this->error_login = $e;
				IntentosLogin::error($usuario);
                                kernel::log()->add_debug("=== Error autenticar ===", kernel::traductor()->trans($e->get_mensaje()));
			}
		}
		return null;
	}

	public function get_modelo(){
		$datos = array();
		$datos['accion'] = kernel::vinculador()->crear('acceso', 'index', array('auth' => 'form'));
		$datos['campos'] = array(
				"usuario" => array('name'=>"usuario", 'type'=>"text", 'value' => ''),
				"password" => array('name'=>"password", 'type'=>"password", 'value' => ''),
				"submit" => array('name'=>"login", 'type'=>"submit", 'value' => 'Ingresar')
			);

		$datos['usar_captcha'] = false; //se pisa si es post el usuario excede el limite
		$datos['captcha_site_key'] = kernel::proyecto()->get_captcha_site_key();
        
		if (kernel::request()->isPost()) {
			$usuario = kernel::request()->getPost('usuario');
			$datos['usar_captcha'] = $this->usar_captcha_login($usuario);
			if($this->error_login){
//				$this->vista()->pagelet('form')->data['mensajes']['error'] = $this->error_login->get_mensaje();
//				$this->vista()->pagelet('form')->data['mensajes']['url'] = array('url_olvide_pass' => $url_olvide_pass);
				$datos['error'] = $this->error_login->get_mensaje();
			}
		}
		return $datos;
	}

	protected function validar_user_pass($usuario, $password)
	{
		$id_persona = $this->get_clase_usuarios()->autenticar_usuario_clave($usuario, $password);
		IntentosLogin::eliminar($usuario);
		return $id_persona;
	}


	/**
	 * Cerrar sesion en el proveedor.
	 */
	public function logout()
	{
		// Ver ticket #34224 (https://redmine.siu.edu.ar/redmine/issues/34224)
		$this->registrar_propuesta_inicial_logout();
		
		// Registro fecha y hora del logout. Como es la misma sesion que el kernel no la toco.
		$log_ingreso = kernel::sesion()->get('log_ingreso');
		if(kernel::proyecto()->get('registrar_log_ingresos') && isset($log_ingreso))
		{
			$parametros = array ( 'log_ingreso' => $log_ingreso );
			catalogo::consultar('persona', 'registrar_logout', $parametros);					
		}
		
		
	}


	/**************************************************************************/
	// RECAPTCHA
	/**************************************************************************/
	
	protected function usar_captcha_login($identificador_usuario)
	{
		if (kernel::proyecto()->usa_captcha()) {
			return IntentosLogin::usar_recaptcha($identificador_usuario);
		}

		return false;
	}

	protected function controlar_captcha_login($identificador_usuario)
	{
		if (kernel::proyecto()->usa_captcha()) {
			return IntentosLogin::controlar_recaptcha($identificador_usuario);
		}

		return false;
	}


    protected function check_recaptcha_login()
    {
        $rs = controlador_g3w2::check_recaptcha2();
        if (! $rs) {
            throw new error_guarani_login(-4);
        }
    }

}