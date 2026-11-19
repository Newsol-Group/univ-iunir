<?php

namespace siu\modelo\autenticacion;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\IntentosLogin as intentos_login;
use siu\errores\error_preinscripcion_login;
use siu\errores\error_preinscripcion_mail_no_validado;
use siu\extension_kernel\controlador_preinscripcion;
use siu\errores\error_preinscripcion;

class auth_form extends auth_con_sesion
{
	protected $id_persona = null;
	protected $usarios_bd;
	protected $error_login = null;

	/**
	 * Si es existoso tiene que llamar 	$this->session->abrir_sesion($id_persona, false);
	 * Si no guardar los errores necesarios para renderizarse en render_login
	 * @return bool si tuvo exito
	 */
	public function autenticar()
	{
		if (kernel::request()->isPost()) {

                        try {

                            $usuario = trim(a_minusculas(kernel::request()->getPost('usuario')));

                            // Guardo en sesión por si falla la validación y hay que repopular los campos
                            kernel::sesion()->set('form_usuario', $usuario);

                            $usa_captcha = kernel::proyecto()->get('usa_captcha');
                            // Si usa captcha valido el mismo
                            if ($usa_captcha && $this->controlar_captcha_login($usuario)) {
                                $this->check_recaptcha_login2();
                            }

                            $campo = 'E-mail';
                            // Si "Fecha de Nacimiento" es vacío
                            if ($usuario == '') {
                                $msg_campo_obligatorio = kernel::traductor()->trans('login.error.campo_obligatorio', ['%campo%' => $campo]);
                                throw new error_preinscripcion($msg_campo_obligatorio);
                            }

                            $pass = trim(kernel::request()->getPost('password'));
                            $campo = 'Clave';
                            // Si "Clave" es vacío
                            if ($pass == '') {
                                $msg_campo_obligatorio = kernel::traductor()->trans('login.error.campo_obligatorio', ['%campo%' => $campo]);
                                throw new error_preinscripcion($msg_campo_obligatorio);
                            }

                        } catch (error_preinscripcion $e) {
                            $this->error_login = $e;
                            intentos_login::error($usuario);
                            kernel::log()->add_error($e);
                            return null;
                        }
                        
			if (!empty($usuario)) {
				// Login tradicional
				$this->usuario = $usuario;
				$pass = kernel::request()->getPost('password');
				
				try {
                                    $this->id_persona = $this->validar_user_pass($usuario, $pass);

                                    // Borro de la sesión si autentica bien
                                    kernel::sesion()->borrar('form_usuario');

                                    return $this->id_persona;
				} catch (error_preinscripcion_login $e) {
					//guardar errores para mostrar en el render_login()
					$this->error_login = $e;
					intentos_login::error($usuario);
					kernel::log()->add_error($e);
				} catch (error_preinscripcion_mail_no_validado $e) {
					//guardar errores para mostrar en el render_login()
					$this->error_login = $e;
					intentos_login::error($usuario);
					kernel::log()->add_error($e);
				}
			} else {
				// Login desde asociación de SE
				$id_se = kernel::sesion()->get('id_se');
				$mail_se = kernel::sesion()->get('mail_se');
				$medio_se = kernel::sesion()->get('medio_se');
			}
		}
		return null;
	}

	public function get_modelo()
	{
		$datos = [];
		$datos['accion'] = kernel::vinculador()->crear('acceso', 'index', ['auth' => 'form']);
		$datos['campos'] = array(
			"usuario" => array('name' => "usuario", 'type' => "text", 'value' => ''),
			"password" => array('name' => "password", 'type' => "password", 'value' => ''),
			"submit" => array('name' => "login", 'type' => "submit", 'value' => 'Ingresar'),
			"recaptcha_challenge_field" => array('name' => "recaptcha_challenge_field", 'type' => "hidden", 'value' => ''),
			"recaptcha_response_field" => array('name' => "recaptcha_response_field", 'type' => "hidden", 'value' => ''),
		);

                // Si falló la validación tomo los datos previamente ingresados
                if (kernel::sesion()->esta_seteada('form_usuario')) {
                    $datos['usuario'] = kernel::sesion()->get('form_usuario');
                    kernel::sesion()->borrar('form_usuario');
                } else { // Sino por defecto vacío
                    $datos['usuario'] = '';
                }

		$datos['usar_captcha'] = false; //se pisa si es post el usuario excede el limite
		if (kernel::request()->isPost()) {
			$usuario = trim(a_minusculas(kernel::request()->getPost('usuario')));
			$datos['usar_captcha'] = $this->usar_captcha_login($usuario);
			if ($this->error_login) {
				$datos['error'] = $this->error_login->get_mensaje();
			}
		}
		return $datos;
	}

	protected function validar_user_pass($usuario, $password)
	{
		$parametros = $this->get_parametros();
		$clase_login = kernel::localizador()->instanciar($parametros['clase_login']);
		$id_persona = $clase_login->autenticar($usuario, $password);
		intentos_login::eliminar($usuario);
		return $id_persona;
	}

	/**
	 * Cerrar sesion en el proveedor.
	 */
	public function logout()
	{
		//como es la misma sesion que el kernel no la toco.
	}

	//--------------------------------------------------------------------------
	//---- RECAPTCHA -----------------------------------------------------------
	//--------------------------------------------------------------------------

	protected function check_recaptcha_login2()
	{
		$rs = controlador_preinscripcion::check_recaptcha2();
		if (!$rs) {
                    $msg_error_recaptcha = kernel::traductor()->trans('error_recaptcha');
                    throw new error_preinscripcion($msg_error_recaptcha);
		}
	}
	
	protected function usar_captcha_login($identificador_usuario)
	{
		return intentos_login::usar_recaptcha($identificador_usuario);
	}

	function controlar_captcha_login($identificador_usuario)
	{
		return intentos_login::controlar_recaptcha($identificador_usuario);
	}

	/**
	 * Se invoca luego de abrir sesion en el kernel (luego de una autenticacion exitosa)
	 */
	public function evt_sesion_abierta()
	{
		parent::evt_sesion_abierta();
		kernel::sesion()->set('_usuario_alumno_', $this->usuario);
	}

	function evt_persona_instanciada($persona, $autenticada)
	{
		if (($autenticada) && (kernel::sesion()->esta_seteada('_usuario_alumno_'))) {
			$persona->set_email(kernel::sesion()->get('_usuario_alumno_'));
		}
	}

}