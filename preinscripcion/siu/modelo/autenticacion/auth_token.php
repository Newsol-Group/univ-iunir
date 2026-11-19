<?php

namespace siu\modelo\autenticacion;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\errores\error_preinscripcion;
use siu\errores\error_preinscripcion_token;
use siu\preinscripcion;

class auth_token extends auth_con_sesion
{
	protected $error_login = null;

	public function autenticar()
	{
		$token = $this->validate_param('t', 'get', validador::TIPO_ALPHANUM);
		try {                   
			$expiracion_token = preinscripcion::usuario()->validar_expiracion_token($token);
                        $datos = preinscripcion::usuario()->validar_mail($token);
                        $this->e_mail = $datos['e_mail'];
			kernel::sesion()->set('mensaje_primer_acceso', 'mensaje_primer_acceso');
			return $datos['id_preinscripcion'];
		} catch (error_preinscripcion_token  $e) {
			kernel::sesion()->set('mensaje_vista_acceso_error_token', 'error_token_expiro');
			$this->error_login = $e;
		} catch (error_preinscripcion $e) {
			kernel::sesion()->set('mensaje_vista_acceso_error', 'error_token');
			$this->error_login = $e;
		}
		return false;
	}

	public function get_modelo()
	{
		$datos = array();
		if ($this->error_login) {
			$datos['error'] = $this->error_login->get_mensaje();
		}
		return $datos;
	}

	/**
	 * Cerrar sesion en el proveedor.
	 */
	public function logout()
	{
		//como es la misma sesion que el kernel no la toco.
	}

	/**
	 * Se invoca luego de abrir sesion en el kernel (luego de una autenticacion exitosa)
	 */
	public function evt_sesion_abierta()
	{
		parent::evt_sesion_abierta();
		kernel::sesion()->set('_usuario_alumno_', $this->e_mail);
	}

	function evt_persona_instanciada($persona, $autenticada)
	{
		if (($autenticada) && (kernel::sesion()->esta_seteada('_usuario_alumno_'))) {
			$persona->set_email(kernel::sesion()->get('_usuario_alumno_'));
		}
	}

}
?>