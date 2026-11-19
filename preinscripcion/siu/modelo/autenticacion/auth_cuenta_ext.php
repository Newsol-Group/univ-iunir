<?php

namespace siu\modelo\autenticacion;

use SIU\Chulupi\acceso\ErrorKernelLogin as error_kernel_login;
use SIU\Chulupi\ErrorKernel as error_kernel;
use SIU\Chulupi\kernel;
use SIU\Chulupi\nucleo\NuevoRequest as nuevo_request;
use siu\errores\error_preinscripcion;
use siu\errores\error_preinscripcion_login;
use siu\errores\error_preinscripcion_mail_no_validado;
use siu\extension_kernel\servicios_externos_preinscripcion;
use siu\preinscripcion;

class auth_cuenta_ext extends auth_con_sesion
{
	protected $error;
	protected $persona_existe = true;
	protected $asociar_usuario_se = false;
	protected $datos;
	protected $datos_usuario;

	/**
	 * Autentica en base al request, y retorna si tuvo exito o no.
	 * @return boolean
	 */
	public function autenticar()
	{
		$proveedor = $_GET['f'];
		try {
			$url_procesar = $this->get_url_procesar($proveedor);
			$datos = $this->autenticar_proveedor($proveedor, $url_procesar);

			$datos_usuario = preinscripcion::usuario()->get_datos_usuario($datos['mail']);
			if (!empty($datos_usuario) && empty($datos_usuario[$proveedor])) {
				$this->datos = $datos;
				$this->asociar_usuario_se = true;
				$this->datos_usuario = $datos_usuario;
			} else {
				$parametros = $this->get_parametros();
				$clase_login = kernel::localizador()->instanciar($parametros['clase_login']);
				$persona = $clase_login->autenticar_login_externo($datos);
				if (is_null($persona)) {
					$this->persona_existe = false;
					$this->datos = $datos;
				}
				$this->usuario = $datos['mail'];
				return $persona;
			}
		} catch (error_preinscripcion_login $e) {
			kernel::log()->add_error($e);
			if ($e->getCode() == -1) {
				$this->error = 'login.errores.cuenta_externa_invalida';
			}
		} catch (error_preinscripcion_mail_no_validado $e) {
			kernel::log()->add_error($e);
			$this->error = $e->get_mensaje();
		} catch (error_kernel $e) {
			kernel::log()->add_error($e);
			$this->error = $e->get_mensaje();
		}
		return false;
	}

	public function obtener_datos_cuenta($proveedor, $url_procesar)
	{
		$datos = $this->autenticar_proveedor($proveedor, $url_procesar);
		return $datos;
	}

	public function logout()
	{
		
	}

	/**
	 * Retorna un arreglo con datos necesarios para renderizar este tipo de autenticacion. Queda a cargo del cliente
	 * la visualizacion
	 * @return array
	 */
	public function get_modelo()
	{
		$modelo = array();

		$modelo['proveedores'] = array();
		foreach ($this->conf['proveedores'] as $proveedor_id => $proveedor) {
			if ($proveedor['activo']) {
				$icono = isset($proveedor['icono']) ? $proveedor['icono'] : 'img/login/openid.png';
				$datos_proveedor = array(
					'id' => $proveedor_id,
					'url' => $this->get_url_procesar($proveedor_id),
					'icono' => kernel::vinculador()->vinculo_recurso($icono)
				);
				$modelo['proveedores'][] = $datos_proveedor;
			}
		}

		$modelo['error'] = isset($this->error) ? $this->error : null;
		return $modelo;
	}

	protected function autenticar_proveedor($proveedor_id, $url_procesar)
	{
		if ((!isset($this->conf['proveedores'][$proveedor_id])) || (!$this->conf['proveedores'][$proveedor_id]['activo'])) {
			throw new error_preinscripcion("El servicio $proveedor_id no está configurado como cuenta externa");
		}
		$conf_proveedor = $this->conf['proveedores'][$proveedor_id];

		$se = new servicios_externos_preinscripcion();

		if ($conf_proveedor['tipo'] == 'openid') {
			$datos = $se->openid_mail($conf_proveedor, $url_procesar);
		} else if ($conf_proveedor['tipo'] == 'oauth2') {
			$datos = $se->oauth2_mail($conf_proveedor, $url_procesar);
		} else {
			throw new error_kernel_login('No se encuentra como manejar al servicio. De que "tipo" es?');
		}
		$datos['proveedor'] = $proveedor_id;
		return $datos;
	}

	/**
	 * @param $proveedor_id
	 * @return string
	 */
	protected function get_url_procesar($proveedor_id)
	{
		return kernel::vinculador()->crear_sin_chequeo('acceso', 'index', array('auth' => $this->get_id(), 'f' => $proveedor_id)); // . "&auth=openid&f=$proveedor";
	}

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
		if ($this->asociar_usuario_se) {
			kernel::sesion()->set('id_se', $this->datos['id']);
			kernel::sesion()->set('mail_se', $this->datos['mail']);
			kernel::sesion()->set('medio_se', $this->datos['proveedor']);
			kernel::sesion()->set('datos_usuario', $this->datos_usuario);
			throw new nuevo_request('asociar_usuario_se');
		}
		if (!$this->persona_existe) {
			kernel::sesion()->set('alta_se_medio', $this->datos['proveedor']);
			kernel::sesion()->set('alta_se_id', $this->datos['id']);
			kernel::sesion()->set('alta_se_mail', $this->datos['mail']);
			throw new nuevo_request('alta_usuario');
		}
	}

}
?>