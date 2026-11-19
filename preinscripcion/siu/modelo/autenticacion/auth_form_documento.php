<?php

namespace siu\modelo\autenticacion;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\IntentosLogin as intentos_login;
use siu\errores\error_preinscripcion_login;
use siu\errores\error_preinscripcion_mail_no_validado;
use siu\extension_kernel\controlador_preinscripcion;
use siu\modelo\util;
use siu\preinscripcion;
use siu\errores\error_preinscripcion;
use siu\modelo\g3\consultas_bd\paises;
use siu\modelo\g3\consultas_bd\tipos_documentos;
use siu\modelo\g3\g3;
use siu\modelo\comunes;

class auth_form_documento extends auth_con_sesion
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

			$pais_documento = trim(kernel::request()->getPost('pais_documento'));
                        $tipo_documento = trim(kernel::request()->getPost('tipo_documento'));
                        $nro_documento = trim(kernel::request()->getPost('nro_documento'));
                        $fecha_nacimiento = trim(kernel::request()->getPost('fecha_nacimiento'));

                        // Guardo en sesión por si falla la validación y hay que repopular los campos
                        kernel::sesion()->set('form_documento_pais_documento', $pais_documento);
                        kernel::sesion()->set('form_documento_tipo_documento', $tipo_documento);
                        kernel::sesion()->set('form_documento_nro_documento', $nro_documento);
                        kernel::sesion()->set('form_documento_fecha_nacimiento', $fecha_nacimiento);

                        $clave_captcha = $this->generar_clave_captcha($pais_documento, $tipo_documento, $nro_documento, $fecha_nacimiento);
                        $usa_captcha = kernel::proyecto()->get('usa_captcha');
                        // Si usa captcha valido el mismo
                        if ($usa_captcha && $this->controlar_captcha_login($clave_captcha)) {
                            $this->check_recaptcha_login2();
                        }

                        // Valido todos los campos
                        $campo = 'País emisor del documento';
                        // Si "País emisor del documento" no es un integer
                        if (filter_var($pais_documento, FILTER_VALIDATE_INT) === false) {
                            $msg_campo_invalido = kernel::traductor()->trans('login.error.campo_invalido', ['%campo%' => $campo]);
                            throw new error_preinscripcion($msg_campo_invalido);
                        }
                        $campo = 'Tipo de Documento';
                        // Si "Tipo de Documento" no es un integer
                        if (filter_var($tipo_documento, FILTER_VALIDATE_INT) === false) {
                            $msg_campo_invalido = kernel::traductor()->trans('login.error.campo_invalido', ['%campo%' => $campo]);
                            throw new error_preinscripcion($msg_campo_invalido);
                        }
                        
                        $campo = 'Número de documento';
                        // Si "Número de documento" es vacío
                        if ($nro_documento == '') {
                            $msg_campo_obligatorio = kernel::traductor()->trans('login.error.campo_obligatorio', ['%campo%' => $campo]);
                            throw new error_preinscripcion($msg_campo_obligatorio);
                        }
			
                        $campo = 'Fecha de Nacimiento';
                        // Si "Fecha de Nacimiento" es vacío
                        if ($fecha_nacimiento == '') {
                            $msg_campo_obligatorio = kernel::traductor()->trans('login.error.campo_obligatorio', ['%campo%' => $campo]);
                            throw new error_preinscripcion($msg_campo_obligatorio);
                        }

                        // Si "Fecha de Nacimiento" es inválida
                        if (!es_fecha_valida($fecha_nacimiento)) {
                            $msg_campo_invalido = kernel::traductor()->trans('login.error.campo_invalido', ['%campo%' => $campo]);
                            throw new error_preinscripcion($msg_campo_invalido);
                        }

                        $pass = trim(kernel::request()->getPost('password'));
                        $campo = 'Clave';
                        // Si "Clave" es vacío
                        if ($pass == '') {
                            $msg_campo_obligatorio = kernel::traductor()->trans('login.error.campo_obligatorio', ['%campo%' => $campo]);
                            throw new error_preinscripcion($msg_campo_obligatorio);
                        }

                        $fecha_nacimiento_obj = \DateTime::createFromFormat(comunes::formato_fecha_visual_php, $fecha_nacimiento);
                        $fecha_nacimiento_db = $fecha_nacimiento_obj->format(comunes::formato_fecha_php);
                        $usuario = preinscripcion::usuario()->get_usuario_by_documento($pais_documento, $tipo_documento, $nro_documento, $fecha_nacimiento_db);
                    
                        // Si no existe el usuario
                        if (is_null($usuario)) {
                            $msg_usuario_no_encontrado = kernel::traductor()->trans('login.error.usuario_no_encontrado', ['%documento%' => $nro_documento]);
                            throw new error_preinscripcion($msg_usuario_no_encontrado);
                        }
                        
                    } catch (error_preinscripcion $e) {
                        $this->error_login = $e;
                        intentos_login::error($clave_captcha);
                        kernel::log()->add_error($e);
                        return null;
                    }

                    if (!empty($usuario))
                    {
                            // Login tradicional
                            $this->usuario = $usuario;

                            try {
                                
                                    $this->id_persona = $this->validar_user_pass($usuario, $pass, $clave_captcha);

                                    // Borro de la sesión si autentica bien
                                    kernel::sesion()->borrar('form_documento_pais_documento');
                                    kernel::sesion()->borrar('form_documento_tipo_documento');
                                    kernel::sesion()->borrar('form_documento_nro_documento');
                                    kernel::sesion()->borrar('form_documento_fecha_nacimiento');
                                    
                                    return $this->id_persona;
                            } catch (error_preinscripcion_login $e) {
                                    //guardar errores para mostrar en el render_login()
                                    $this->error_login = $e;
                                    intentos_login::error($clave_captcha);
                                    kernel::log()->add_error($e);
                            } catch (error_preinscripcion_mail_no_validado $e) {
                                    //guardar errores para mostrar en el render_login()
                                    $this->error_login = $e;
                                    intentos_login::error($clave_captcha);
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
		$datos['accion'] = kernel::vinculador()->crear('acceso', 'index', ['auth' => 'form_documento']);

                // Si falló la validación tomo los datos previamente ingresados
                if (kernel::sesion()->esta_seteada('form_documento_pais_documento')) {
                    $datos['pais'] = kernel::sesion()->get('form_documento_pais_documento');
                    kernel::sesion()->borrar('form_documento_pais_documento');
                } else { // Sino por defecto Argentina
                    $datos['pais'] = paises::ARGENTINA;
                }

                // Si falló la validación tomo los datos previamente ingresados
                if (kernel::sesion()->esta_seteada('form_documento_tipo_documento')) {
                    $datos['tipo_documento'] = kernel::sesion()->get('form_documento_tipo_documento');
                    kernel::sesion()->borrar('form_documento_tipo_documento');
                } else { // Sino por defecto DNI
                    $datos['tipo_documento'] = tipos_documentos::DNI;
                }

                // Si falló la validación tomo los datos previamente ingresados
                if (kernel::sesion()->esta_seteada('form_documento_nro_documento')) {
                    $datos['nro_documento'] = kernel::sesion()->get('form_documento_nro_documento');
                    kernel::sesion()->borrar('form_documento_nro_documento');
                } else { // Sino por defecto vacío
                    $datos['nro_documento'] = '';
                }

                // Si falló la validación tomo los datos previamente ingresados
                if (kernel::sesion()->esta_seteada('form_documento_fecha_nacimiento')) {
                    $datos['fecha_nacimiento'] = kernel::sesion()->get('form_documento_fecha_nacimiento');
                    kernel::sesion()->borrar('form_documento_fecha_nacimiento');
                } else { // Sino por defecto vacío
                    $datos['fecha_nacimiento'] = '';
                }

                // Recupero todos los países
                $datos['paises'] = util::opciones()->get_paises(false);

                // Recupero todos los tipos de documentos del país
                $condicion = "mdp_tipo_documento_pais.pais = {$datos['pais']}";
                $datos['tipos_documentos'] = g3::entidad('tipos_documentos_pais')->get_opciones_pais($condicion);
                

		$datos['usar_captcha'] = false; //se pisa si es post el usuario excede el limite
		if (kernel::request()->isPost()) {
                        $clave_captcha = $this->generar_clave_captcha($datos['pais'], $datos['tipo_documento'], $datos['nro_documento'], $datos['fecha_nacimiento']);
			$datos['usar_captcha'] = $this->usar_captcha_login($clave_captcha);
			if ($this->error_login) {
				$datos['error'] = $this->error_login->get_mensaje();
			}
		}
		return $datos;
	}

	protected function validar_user_pass($usuario, $password, $clave_captcha)
	{
		$parametros = $this->get_parametros();
		$clase_login = kernel::localizador()->instanciar($parametros['clase_login']);
		$id_persona = $clase_login->autenticar($usuario, $password);
		intentos_login::eliminar($clave_captcha);
		return $id_persona;
	}

        protected function generar_clave_captcha($pais_documento, $tipo_documento, $nro_documento, $fecha_nacimiento)
	{
            $clave_captcha = "{$pais_documento}|{$tipo_documento}|{$nro_documento}|{$fecha_nacimiento}";
            return base64_encode($clave_captcha);
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