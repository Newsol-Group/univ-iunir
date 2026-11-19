<?php

namespace siu\modelo;

use SIU\Chulupi\kernel;
use siu\errores\error_preinscripcion_login;
use siu\errores\error_preinscripcion_clave;
use siu\errores\error_preinscripcion_usuario;
use siu\preinscripcion;
use SIU\Chulupi\util\db\DbPdo;

class admin
{
	protected static $instancia;
	protected $db;

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\admin");
		}
		return self::$instancia;
	}
	
	function login($usuario, $clave)
	{
		$q_usuario = kernel::db()->quote($usuario);
		$q_clave = kernel::db()->quote($this->cifrar_clave(trim($clave)));
		$id_pto_acceso = kernel::db()->quote($_SERVER['ID_PTO_ACCESO']);

		$sql = "SELECT 
					id_usuario,
					usuario,
					clave,
					id_pto_acceso
				FROM 
					admin_usuarios
				WHERE
					usuario = $q_usuario
					AND clave = $q_clave
					AND id_pto_acceso = $id_pto_acceso
				";

		$datos = kernel::db()->consultar_fila($sql);

		if (empty($datos)) {
			throw new error_preinscripcion_login('admin_login_error_clave');
		}

		kernel::proyecto()->set_punto_acceso(preinscripcion::perfil_admin);
		return $datos['id_usuario'];
	}

	protected function cifrar_clave($clave)
	{
		$salt = kernel::proyecto()->get('salt_admin');
		return sha1($salt . $clave);
	}

	//--------------------------------------------------------------------------
	//---- Funciones auxiliares para la consola --------------------------------
	//--------------------------------------------------------------------------
	
	function crear_usuario($usuario, $clave, $id_pto_acceso)
	{
		$db = $this->get_db();
		
		$q_usuario = $db->quote(trim($usuario));
		$q_clave = $db->quote($this->cifrar_clave(trim($clave)));
		$q_id_pto_acceso = $db->quote(trim($id_pto_acceso));

		$sql = "INSERT INTO admin_usuarios (
					usuario,
					clave,
					id_pto_acceso
				) VALUES (
					$q_usuario,
					$q_clave,
					$q_id_pto_acceso
				)";

		$db->ejecutar($sql);
	}
	
	/**
	 * Determina si un nombre de usuario administrador es válido
	 * @param string $usuario
	 * @throws error_preinscripcion_usuario
	 */
	function usuario_valido($usuario)
	{
		if (empty($usuario)) {
			$mensaje = kernel::traductor()->trans('admin_error_user_vacio');
			throw new error_preinscripcion_usuario($mensaje);
		}
		if ($this->existe_usuario($usuario)) {
			$mensaje = kernel::traductor()->trans('admin_error_user_repetido');
			throw new error_preinscripcion_usuario($mensaje);
		}
	}
	
	function clave_valida($usuario, $clave)
	{
		// La clave debe ser distinta al nombre de usuario
		if ($clave == $usuario) {
			$mensaje = kernel::traductor()->trans('error_clave_igual_usuario');
			throw new error_preinscripcion_clave($mensaje);
		}

		// La clave debe tener una longitud determinada.
		$longitud_minima = kernel::proyecto()->get_clave_long_minima();
		if (strlen($clave) < $longitud_minima) {
			$mensaje = kernel::traductor()->trans('error_clave_corta', array('%1%' => $longitud_minima));
			throw new error_preinscripcion_clave($mensaje);
		}
	}
	
	protected function existe_usuario($usuario)
	{
		$db = $this->get_db();
		$q_usuario = $db->quote($usuario);

		$sql = "SELECT 
					1 AS existe
				FROM 
					admin_usuarios
				WHERE
					usuario = $q_usuario";

		$datos = $db->consultar_fila($sql);
		return !empty($datos['existe']);
	}

	/**
	 * 
	 * @return \siu\modelo\DbPdo
	 */
	function get_db()
	{
		if (empty($this->db)) {
			$acceso = current(kernel::proyecto()->get_accesos_bd());
			$this->db = new DbPdo($acceso);
		}
		return $this->db;
	}
}
?>